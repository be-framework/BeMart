<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\Dev\Http\AbstractWorkflowTest;
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
 * The in-process {@see AbstractWorkflowTest} follows #[Link]/HAL rels; an HTTP
 * workflow follows the same rels over the wire. An HTML workflow follows what a
 * browser actually sees: the `data-alps="<transition>"` microformat the rendered
 * <form>s carry (see var/templates + AffordanceContractTest).
 *
 * It reuses the HTTP transport (a concrete test returns {@see HttpResource} from
 * newResource()), so navigation (`go*`) keeps the inherited follow() /
 * followLocation() — anchor rel / Link header / Location. {@see submit()} adds
 * the unsafe leg: locate the affordance by its ALPS id, read the action and
 * hidden CSRF token AS RENDERED, and POST that exact form over HTTP — the leg a
 * resource- or header-level test never exercises.
 */
abstract class AbstractHtmlWorkflowTestCase extends AbstractWorkflowTest
{
    /**
     * Submit the rendered affordance carrying data-alps="$alpsId".
     *
     * Posts to the form's own action (its `?_method=…` override drives PUT/DELETE
     * exactly as the browser would) with the form's hidden CSRF token merged in.
     *
     * @param array<string, mixed> $fields
     */
    protected function submit(ResourceObject $page, string $alpsId, array $fields = []): ResourceObject
    {
        $view = (string) ($page->view ?? '');
        $found = preg_match(
            '/<form\b[^>]*\bdata-alps="' . preg_quote($alpsId, '/') . '"[^>]*>(.*?)<\/form>/is',
            $view,
            $form,
        );
        $this->assertSame(1, $found, sprintf('affordance data-alps="%s" is not rendered', $alpsId));

        $openTag = (string) preg_replace('/>.*$/s', '>', $form[0]);
        $action = $this->attribute($openTag, 'action');
        $this->assertNotSame('', $action, sprintf('affordance "%s" has no action', $alpsId));

        $token = $this->hiddenField($form[1], 'csrfToken');
        if ($token !== null) {
            $fields += ['csrfToken' => $token];
        }

        return $this->resource->post($this->resourceUri($action), $fields);
    }

    /** Assert the page renders an affordance (form or anchor) for the ALPS transition. */
    protected function assertAffordance(ResourceObject $page, string $alpsId): void
    {
        $this->assertMatchesRegularExpression(
            '/\bdata-alps="' . preg_quote($alpsId, '/') . '"/i',
            (string) ($page->view ?? ''),
            sprintf('affordance data-alps="%s" is not rendered', $alpsId),
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
