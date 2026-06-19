<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Annotation\Link;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

use function array_filter;
use function array_keys;
use function array_unique;
use function array_values;
use function class_exists;
use function file_get_contents;
use function implode;
use function in_array;
use function preg_match;
use function preg_match_all;
use function sprintf;
use function str_contains;
use function str_replace;
use function strlen;
use function strtolower;
use function substr;

/**
 * ALPS-affordance contract check across every tagged storefront/admin form.
 *
 * Each write <form> carries a `data-alps="<transition>"` microformat (see e.g.
 * var/templates/Page/Admin/Product/Edit.html.twig). For every such form this
 * test confirms, statically (no DB / no render):
 *
 *   1. the transition id is a write affordance the rendering resource ADVERTISES
 *      via #[Link(rel: …)] on onGet — catching a typo / invented / stale id, and
 *      catching a rendered affordance the resource never declared, and
 *   2. the form's action targets that #[Link]'s href path with its method — the
 *      drift that 405s the submit button in a browser.
 *
 * The template is mapped to its mirror Page resource; the contract is read from
 * the resource's own #[Link] declarations. Forms whose action is a runtime-only
 * value (post-to-self, a `?` placeholder) are consistency-checked only.
 */
final class AffordanceContractTest extends TestCase
{
    private const TEMPLATE_ROOT = __DIR__ . '/../../var/templates/Page';
    private const RESOURCE_NS = 'MyVendor\\BeMart\\Resource\\Page\\';

    public function testEveryTaggedFormAffordanceMatchesItsResourceLinkContract(): void
    {
        $problems = [];
        $checkedPaths = 0;

        foreach ($this->taggedForms() as $form) {
            [$template, $resourceClass, $alpsExpr, $actionExpr, $body] = $form;

            if (! class_exists($resourceClass)) {
                $problems[] = sprintf('%s: no mirror resource %s', $template, $resourceClass);
                continue;
            }

            $contract = $this->advertisedWriteLinks($resourceClass);
            $actionPaths = $this->paths($actionExpr);
            $methods = $this->methodsAvailable($actionExpr, $body);

            foreach ($this->literals($alpsExpr) as $transition) {
                if (! isset($contract[$transition])) {
                    $problems[] = sprintf(
                        '%s: data-alps="%s" is not advertised by %s::onGet #[Link] (has: %s)',
                        $template,
                        $transition,
                        $resourceClass,
                        implode(', ', array_keys($contract)) ?: '(no write links)',
                    );
                    continue;
                }

                $expected = $contract[$transition];

                if ($actionPaths !== [] && ! in_array($expected['path'], $actionPaths, true)) {
                    $problems[] = sprintf(
                        '%s: %s action %s does not target #[Link] href "%s"',
                        $template,
                        $transition,
                        implode('|', $actionPaths),
                        $expected['path'],
                    );
                }

                if (! in_array($expected['method'], $methods, true)) {
                    $problems[] = sprintf(
                        '%s: %s form cannot issue #[Link] method "%s" (available: %s)',
                        $template,
                        $transition,
                        $expected['method'],
                        implode('|', $methods),
                    );
                }

                if ($actionPaths !== []) {
                    $checkedPaths++;
                }
            }
        }

        $this->assertSame([], $problems, "Affordance contract drift:\n" . implode("\n", $problems));
        $this->assertGreaterThan(20, $checkedPaths, 'expected the rollout to action-check many forms');
    }

    /**
     * @return list<array{string, string, string, string, string}>
     *     [templateRel, resourceClass, dataAlpsExpr, actionExpr, formBody]
     */
    private function taggedForms(): array
    {
        $forms = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::TEMPLATE_ROOT));
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'twig') {
                continue;
            }

            $html = (string) file_get_contents($file->getPathname());
            if (! str_contains($html, 'data-alps')) {
                continue;
            }

            $relative = str_replace(self::TEMPLATE_ROOT . '/', '', str_replace('\\', '/', $file->getPathname()));
            $resourceClass = self::RESOURCE_NS . str_replace('/', '\\', substr($relative, 0, -strlen('.html.twig')));

            preg_match_all('/<form\b[^>]*\bdata-alps="([^"]*)"[^>]*>(.*?)<\/form>/is', $html, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                $openTag = (string) preg_replace('/>.*$/s', '>', $tag[0]);
                $action = preg_match('/\baction="([^"]*)"/i', $openTag, $a) === 1 ? $a[1] : '';
                $forms[] = [$relative, $resourceClass, $tag[1], $action, $tag[2]];
            }
        }

        return $forms;
    }

    /**
     * Write affordances the page advertises (onGet #[Link] with a method): rel => {path, method}.
     *
     * @return array<string, array{path: string, method: string}>
     */
    private function advertisedWriteLinks(string $resourceClass): array
    {
        $reflection = new ReflectionClass($resourceClass);
        if (! $reflection->hasMethod('onGet')) {
            return [];
        }

        $links = [];
        foreach ($reflection->getMethod('onGet')->getAttributes(Link::class) as $attribute) {
            $link = $attribute->newInstance();
            if ($link->method === null) {
                continue;
            }

            $links[$link->rel] = [
                'path' => substr($link->href, strlen('page://self')),
                'method' => strtolower($link->method),
            ];
        }

        return $links;
    }

    /**
     * Path literals inside an action attribute — handles a literal value, a
     * `{{ c ? '/a' : '/b' }}` ternary, and a `{% if %}/a{% else %}/b{% endif %}`
     * block alike (every `/…` token up to a `?` or quote).
     *
     * @return list<string>
     */
    private function paths(string $action): array
    {
        preg_match_all('#/[A-Za-z0-9._/-]*#', $action, $m);

        return array_values(array_unique($m[0]));
    }

    /**
     * Methods the form can issue: POST always, plus any `_method=` override in
     * the action query or a hidden `<input name="_method">`.
     *
     * @return list<string>
     */
    private function methodsAvailable(string $action, string $body): array
    {
        $methods = ['post'];
        preg_match_all('/_method=([a-zA-Z]+)/', $action, $inAction);
        foreach ($inAction[1] as $method) {
            $methods[] = strtolower($method);
        }

        if (preg_match('/name="_method"[^>]*value="([a-zA-Z]+)"/', $body, $hidden) === 1) {
            $methods[] = strtolower($hidden[1]);
        }

        return array_values(array_unique($methods));
    }

    /**
     * Transition ids in a data-alps value: the quoted branches of a
     * `{{ cond ? 'a' : 'b' }}` expression, or the literal value.
     *
     * @return list<string>
     */
    private function literals(string $expr): array
    {
        if (str_contains($expr, '{{')) {
            preg_match_all("/'([^']*)'/", $expr, $m);

            return array_values(array_filter($m[1], static fn (string $s): bool => $s !== ''));
        }

        return $expr === '' ? [] : [$expr];
    }
}
