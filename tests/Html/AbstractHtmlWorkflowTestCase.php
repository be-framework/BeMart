<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\Dev\Http\AbstractWorkflowTest;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

use function html_entity_decode;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function sprintf;
use function str_starts_with;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * Base for HTML hypermedia workflow tests.
 *
 * The in-process {@see AbstractWorkflowTest} resolves rels from #[Link]/HAL; the
 * HTTP one resolves them from the Link header. An HTML workflow resolves them
 * from what a browser actually sees: the `data-alps="<transition>"` microformat
 * the rendered <a> and <form> elements carry (see var/templates +
 * AffordanceContractTest). So follow() and linkHref() are overridden to read the
 * ALPS id off the HTML body — never the Link header.
 *
 *   follow()   — `go*`  : the <a data-alps="…" href> a browser would click (GET)
 *   linkHref() — target : the href/action of the data-alps element, unresolved
 *   submit()   — `do*`  : the <form data-alps="…"> a browser would submit (POST),
 *                         carrying the form's own action (its ?_method=… override
 *                         drives PUT/DELETE) and rendered hidden CSRF token
 *
 * A concrete test returns {@see HttpResource} from newResource(), so the walk
 * runs over a real HTTP round-trip.
 */
abstract class AbstractHtmlWorkflowTestCase extends AbstractWorkflowTest
{
    /** Follow a safe `go*` affordance: the data-alps anchor a browser would click. */
    protected function follow(ResourceObject $response, string $rel, array $query = []): ResourceObject
    {
        $next = $this->resource->get($this->linkHref($response, $rel), $query);
        $this->assertSame(Code::OK, $next->code, (string) ($next->view ?? $next->code));

        return $next;
    }

    /** Resolve a rel to its rendered href/action by the data-alps id — no request. */
    protected function linkHref(ResourceObject $response, string $rel): string
    {
        $view = (string) ($response->view ?? '');
        $found = preg_match(
            '/<(?:a|area|form)\b[^>]*\bdata-alps="' . preg_quote($rel, '/') . '"[^>]*>/i',
            $view,
            $element,
        );
        $this->assertSame(1, $found, sprintf('affordance data-alps="%s" is not rendered', $rel));

        $href = $this->attribute($element[0], 'href');
        $action = $href === '' ? $this->attribute($element[0], 'action') : $href;
        $this->assertNotSame('', $action, sprintf('affordance data-alps="%s" has no href/action', $rel));

        return $this->resourceUri($action);
    }

    /**
     * Submit the `do*` affordance: the data-alps <form> a browser would submit.
     *
     * @param array<string, mixed> $fields
     */
    protected function submit(ResourceObject $response, string $rel, array $fields = []): ResourceObject
    {
        $view = (string) ($response->view ?? '');
        $found = preg_match(
            '/<form\b[^>]*\bdata-alps="' . preg_quote($rel, '/') . '"[^>]*>(.*?)<\/form>/is',
            $view,
            $form,
        );
        $this->assertSame(1, $found, sprintf('form affordance data-alps="%s" is not rendered', $rel));

        $action = $this->attribute((string) preg_replace('/>.*$/s', '>', $form[0]), 'action');
        $this->assertNotSame('', $action, sprintf('form affordance data-alps="%s" has no action', $rel));

        $token = $this->hiddenField($form[1], 'csrfToken');
        if ($token !== null) {
            $fields += ['csrfToken' => $token];
        }

        return $this->resource->post($this->resourceUri($action), $fields);
    }

    /** Assert the page renders an affordance (anchor or form) for the ALPS transition. */
    protected function assertAffordance(ResourceObject $response, string $rel): void
    {
        $this->assertMatchesRegularExpression(
            '/\bdata-alps="' . preg_quote($rel, '/') . '"/i',
            (string) ($response->view ?? ''),
            sprintf('affordance data-alps="%s" is not rendered', $rel),
        );
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
        if (preg_match('/name="' . preg_quote($name, '/') . '"[^>]*value="([^"]*)"/i', $body, $match) !== 1) {
            return null;
        }

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
    }

    private function resourceUri(string $action): string
    {
        return str_starts_with($action, '/') ? 'page://self' . $action : $action;
    }
}
