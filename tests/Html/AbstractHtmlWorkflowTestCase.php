<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\Dev\Http\AbstractWorkflowTest;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

use function html_entity_decode;
use function in_array;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_split;
use function sprintf;
use function str_starts_with;
use function trim;

use const ENT_HTML5;
use const ENT_QUOTES;
use const PREG_SET_ORDER;

/**
 * Base for HTML hypermedia workflow tests.
 *
 * The in-process {@see AbstractWorkflowTest} resolves rels from #[Link]/HAL; the
 * HTTP one from the Link header. An HTML workflow resolves them the way the ALPS
 * spec renders them in HTML (RFC draft §"A Simple ALPS Example"): the descriptor
 * id is the `class` token of a <form> (and semantic elements) and the `rel` of a
 * hypermedia <a> link. So follow() and linkHref() match the ALPS id off the
 * rendered HTML's class/rel — never the Link header.
 *
 *   follow()   — `go*` : the <a rel="…"> a browser would click (GET)
 *   linkHref() — target: the href/action of the class/rel-tagged element
 *   submit()   — `do*` : the <form class="…"> a browser would submit (POST),
 *                        carrying the form's own action (its ?_method=… override
 *                        drives PUT/DELETE) and rendered hidden CSRF token
 *
 * A concrete test returns {@see HttpResource} from newResource(), so the walk
 * runs over a real HTTP round-trip.
 */
abstract class AbstractHtmlWorkflowTestCase extends AbstractWorkflowTest
{
    /** Follow a safe `go*` affordance: the rel/class-tagged anchor a browser would click. */
    protected function follow(ResourceObject $response, string $rel, array $query = []): ResourceObject
    {
        $next = $this->resource->get($this->linkHref($response, $rel), $query);
        $this->assertSame(Code::OK, $next->code, (string) ($next->view ?? $next->code));

        return $next;
    }

    /** Resolve a rel to its rendered href/action by the ALPS class/rel token — no request. */
    protected function linkHref(ResourceObject $response, string $rel): string
    {
        $affordance = $this->affordance((string) ($response->view ?? ''), $rel);
        $this->assertNotNull($affordance, sprintf('affordance "%s" (class/rel) is not rendered', $rel));

        $href = $this->attribute($affordance, 'href');
        $action = $href === '' ? $this->attribute($affordance, 'action') : $href;
        $this->assertNotSame('', $action, sprintf('affordance "%s" has no href/action', $rel));

        return $this->resourceUri($action);
    }

    /**
     * Submit the `do*` affordance: the <form class="…"> a browser would submit.
     *
     * @param array<string, mixed> $fields
     */
    protected function submit(ResourceObject $response, string $rel, array $fields = []): ResourceObject
    {
        $view = (string) ($response->view ?? '');
        preg_match_all('/<form\b([^>]*)>(.*?)<\/form>/is', $view, $forms, PREG_SET_ORDER);
        foreach ($forms as $form) {
            if (! $this->hasToken($form[1], $rel)) {
                continue;
            }

            $action = $this->attribute($form[1], 'action');
            $this->assertNotSame('', $action, sprintf('form affordance "%s" has no action', $rel));

            $token = $this->hiddenField($form[2], 'csrfToken');
            if ($token !== null) {
                $fields += ['csrfToken' => $token];
            }

            return $this->resource->post($this->resourceUri($action), $fields);
        }

        $this->fail(sprintf('form affordance "%s" (class token) is not rendered', $rel));
    }

    /** Assert the page renders an affordance (form or anchor) carrying the ALPS id. */
    protected function assertAffordance(ResourceObject $response, string $rel): void
    {
        $this->assertNotNull(
            $this->affordance((string) ($response->view ?? ''), $rel),
            sprintf('affordance "%s" (class/rel) is not rendered', $rel),
        );
    }

    /**
     * Assert the rendered page conveys a descriptor's value — the HTML counterpart
     * of the JSON workflow's bodyValue(), so an E2E walk can verify state, not just
     * links. It checks what the page actually shows: a control value
     * (`<input name="…" value>`) or a class-tagged display (`<… class="…">…<`),
     * the ALPS HTML representation of a descriptor.
     */
    protected function assertState(ResourceObject $response, string $descriptor, string $expected): void
    {
        $value = $this->renderedValue((string) ($response->view ?? ''), $descriptor);
        $this->assertNotNull($value, sprintf('state "%s" is not rendered', $descriptor));
        $this->assertSame($expected, $value, sprintf('rendered state "%s" drifted', $descriptor));
    }

    private function renderedValue(string $view, string $descriptor): string|null
    {
        $name = preg_quote($descriptor, '/');
        if (preg_match('/<(?:input|select|textarea)\b[^>]*\bname="' . $name . '"[^>]*\bvalue="([^"]*)"/i', $view, $control) === 1) {
            return html_entity_decode($control[1], ENT_QUOTES | ENT_HTML5);
        }

        if (preg_match('/<[^>]*\bclass="[^"]*\b' . $name . '\b[^"]*"[^>]*>([^<]*)</i', $view, $display) === 1) {
            return trim(html_entity_decode($display[1], ENT_QUOTES | ENT_HTML5));
        }

        return null;
    }

    /** First <a|area|form|button> open tag whose class or rel carries the ALPS token. */
    private function affordance(string $view, string $rel): string|null
    {
        preg_match_all('/<(?:a|area|form|button)\b[^>]*>/i', $view, $tags);
        foreach ($tags[0] as $tag) {
            if ($this->hasToken($tag, $rel)) {
                return $tag;
            }
        }

        return null;
    }

    private function hasToken(string $tag, string $token): bool
    {
        foreach (['class', 'rel'] as $name) {
            $value = $this->attribute($tag, $name);
            $tokens = preg_split('/\s+/', trim($value)) ?: [];
            if (in_array($token, $tokens, true)) {
                return true;
            }
        }

        return false;
    }

    private function attribute(string $tag, string $name): string
    {
        if (preg_match('/\b' . $name . '="([^"]*)"/i', $tag, $match) !== 1) {
            return '';
        }

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
    }

    private function hiddenField(string $body, string $name): string|null
    {
        if (preg_match('/name="' . $name . '"[^>]*value="([^"]*)"/i', $body, $match) !== 1) {
            return null;
        }

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
    }

    private function resourceUri(string $action): string
    {
        return str_starts_with($action, '/') ? 'page://self' . $action : $action;
    }
}
