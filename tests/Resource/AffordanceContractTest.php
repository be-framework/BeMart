<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Annotation\Link;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

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
 * The ALPS HTML representation (RFC draft §"A Simple ALPS Example") renders a
 * transition's descriptor id as a `class` token of the <form>. For every form
 * carrying such an ALPS id (a `do*`/`go*` class token) this test confirms,
 * statically (no DB / no render):
 *
 *   1. the id is a transition the rendering resource declares via #[Link] on
 *      onGet — catching a typo / invented / stale id, and
 *   2. for a write transition, the form's action targets that #[Link]'s href
 *      with its method — the drift that 405s the submit button in a browser.
 *
 * The template maps to its mirror Page resource; the contract is read from the
 * resource's own #[Link] declarations. ALPS ids are told apart from styling
 * classes by the `^(do|go)[A-Z]` shape and intersection with the declared
 * vocabulary, so Bootstrap/ec- classes are ignored.
 */
final class AffordanceContractTest extends TestCase
{
    private const TEMPLATE_ROOT = __DIR__ . '/../../var/templates/Page';
    private const RESOURCE_NS = 'MyVendor\\BeMart\\Resource\\Page\\';

    public function testEveryTaggedFormAffordanceMatchesItsResourceLinkContract(): void
    {
        $problems = [];
        $checked = 0;

        foreach ($this->forms() as $form) {
            [$template, $resourceClass, $classAttr, $actionExpr, $body] = $form;

            $ids = $this->alpsTokens($classAttr);
            if ($ids === []) {
                continue;
            }

            if (! class_exists($resourceClass)) {
                $problems[] = sprintf('%s: no mirror resource %s for ALPS form', $template, $resourceClass);
                continue;
            }

            $links = $this->declaredLinks($resourceClass);
            $actionPaths = $this->paths($actionExpr);
            $methods = $this->methodsAvailable($actionExpr, $body);

            foreach ($ids as $id) {
                if (! isset($links[$id])) {
                    $problems[] = sprintf(
                        '%s: class token "%s" is not declared by %s::onGet #[Link] (has: %s)',
                        $template,
                        $id,
                        $resourceClass,
                        implode(', ', array_keys($links)) ?: '(none)',
                    );
                    continue;
                }

                $method = $links[$id]['method'];
                if ($method === null) {
                    continue; // safe `go*` declared on a form — no action contract to assert
                }

                if ($actionPaths !== [] && ! in_array($links[$id]['path'], $actionPaths, true)) {
                    $problems[] = sprintf(
                        '%s: %s action %s does not target #[Link] href "%s"',
                        $template,
                        $id,
                        implode('|', $actionPaths),
                        $links[$id]['path'],
                    );
                }

                if (! in_array($method, $methods, true)) {
                    $problems[] = sprintf(
                        '%s: %s form cannot issue #[Link] method "%s" (available: %s)',
                        $template,
                        $id,
                        $method,
                        implode('|', $methods),
                    );
                }

                $checked++;
            }
        }

        $this->assertSame([], $problems, "Affordance contract drift:\n" . implode("\n", $problems));
        $this->assertGreaterThan(20, $checked, 'expected the rollout to contract-check many forms');
    }

    /**
     * @return list<array{string, string, string, string, string}>
     *     [templateRel, resourceClass, classAttr, actionExpr, formBody]
     */
    private function forms(): array
    {
        $forms = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::TEMPLATE_ROOT));
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'twig') {
                continue;
            }

            $html = (string) file_get_contents($file->getPathname());
            if (! str_contains($html, '<form')) {
                continue;
            }

            $relative = str_replace(self::TEMPLATE_ROOT . '/', '', str_replace('\\', '/', $file->getPathname()));
            $resourceClass = self::RESOURCE_NS . str_replace('/', '\\', substr($relative, 0, -strlen('.html.twig')));

            preg_match_all('/<form\b([^>]*)>(.*?)<\/form>/is', $html, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                $class = preg_match('/\bclass="([^"]*)"/i', $tag[1], $c) === 1 ? $c[1] : '';
                $action = preg_match('/\baction="([^"]*)"/i', $tag[1], $a) === 1 ? $a[1] : '';
                $forms[] = [$relative, $resourceClass, $class, $action, $tag[2]];
            }
        }

        return $forms;
    }

    /**
     * ALPS transition ids inside a class attribute — the `do*`/`go*` tokens,
     * including the quoted branches of a `{{ cond ? 'a' : 'b' }}` class.
     *
     * @return list<string>
     */
    private function alpsTokens(string $classAttr): array
    {
        preg_match_all('/(?:do|go)[A-Z][A-Za-z]*/', $classAttr, $matches);

        return array_values(array_unique($matches[0]));
    }

    /**
     * Transitions the page declares on onGet: rel => {path, method|null}.
     *
     * @return array<string, array{path: string, method: string|null}>
     */
    private function declaredLinks(string $resourceClass): array
    {
        $reflection = new ReflectionClass($resourceClass);
        if (! $reflection->hasMethod('onGet')) {
            return [];
        }

        $links = [];
        foreach ($reflection->getMethod('onGet')->getAttributes(Link::class) as $attribute) {
            $link = $attribute->newInstance();
            $links[$link->rel] = [
                'path' => substr($link->href, strlen('page://self')),
                'method' => $link->method === null ? null : strtolower($link->method),
            ];
        }

        return $links;
    }

    /** @return list<string> */
    private function paths(string $action): array
    {
        preg_match_all('#/[A-Za-z0-9._/-]*#', $action, $m);

        return array_values(array_unique($m[0]));
    }

    /** @return list<string> */
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
}
