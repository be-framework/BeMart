<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_unique;
use function file_get_contents;
use function glob;
use function implode;
use function in_array;
use function preg_match;
use function preg_match_all;
use function sort;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function trim;
use function ucfirst;

use const JSON_THROW_ON_ERROR;

/**
 * SSOT-projection completeness audit, as a permanent regression gate.
 *
 * alps.json is the source of truth. The implementation projects each affordance
 * onto a resource method (#[Alps('op')]) and an HTML <form>. This test walks the
 * three projection edges and demands every edge be clean OR explicitly acknowledged
 * in docs/eccube-spec-coverage/ssot-projection-baseline.json:
 *
 *   Contract A (resource -> alps): every #[Alps('id')] in src/Resource resolves to
 *     an alps.json descriptor, else it is a baseline.resourceAlps entry.
 *   Contract B (template -> alps): every do/go token in a template <form class>
 *     resolves to an alps.json descriptor, else it is a baseline.templateAffordance entry.
 *   Contract C (template -> resource): every no-JS write <form> submits to a resource
 *     that exposes the implied write handler, else it is a baseline.writeFormWithoutHandler entry.
 *
 * The match is EXACT in both directions: a new orphan fails (add it to the baseline
 * or fix it); a baseline entry that now projects cleanly fails (it was fixed — remove it).
 *
 * Pure static analysis (no container, no DB). Sibling op-coverage gate:
 * {@see WorkflowSkipRegistryTest} (every do* op is covered by a flow test or skipped).
 */
final class SsotProjectionAuditTest extends TestCase
{
    private const ROOT = __DIR__ . '/../..';
    private const BASELINE_PATH = self::ROOT . '/docs/eccube-spec-coverage/ssot-projection-baseline.json';

    private const VALID_CATEGORIES = [
        'framework-internal',
        'naming-mismatch',
        'missing-alps-descriptor',
        'missing-write-handler',
        'js-only',
        'intentional-anonymous-affordance',
    ];

    private const WRITE_VERBS = ['onPost', 'onPut', 'onDelete', 'onPatch'];

    /** @return array<string, mixed> */
    private function baseline(): array
    {
        $json = file_get_contents(self::BASELINE_PATH);
        self::assertIsString($json, 'baseline must be readable: ' . self::BASELINE_PATH);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }

    /** @return list<string> sorted alps.json descriptor ids */
    private function alpsIds(): array
    {
        $json = file_get_contents(self::ROOT . '/alps.json');
        self::assertIsString($json);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        $ids = [];
        foreach ($data['alps']['descriptor'] as $descriptor) {
            if (isset($descriptor['id'])) {
                $ids[$descriptor['id']] = true;
            }
        }

        return array_keys($ids);
    }

    /** @return array<string, list<string>> alps op id => resource files declaring it via #[Alps('id')] */
    private function resourceAlpsTags(): array
    {
        $tags = [];
        foreach ($this->phpFilesUnder(self::ROOT . '/src/Resource') as $file) {
            $src = (string) file_get_contents($file);
            preg_match_all("/#\\[Alps\\('([^']+)'\\)\\]/", $src, $m);
            $rel = str_replace(self::ROOT . '/src/Resource/', '', $file);
            foreach ($m[1] as $id) {
                $tags[$id][] = $rel;
            }
        }

        return $tags;
    }

    /** @return list<string> */
    private function phpFilesUnder(string $dir): array
    {
        $out = [];
        foreach ((array) glob($dir . '/*') as $entry) {
            if (is_dir($entry)) {
                $out = [...$out, ...$this->phpFilesUnder($entry)];
            } elseif (str_ends_with((string) $entry, '.php')) {
                $out[] = (string) $entry;
            }
        }

        return $out;
    }

    /** @return list<string> */
    private function twigFilesUnder(string $dir): array
    {
        $out = [];
        foreach ((array) glob($dir . '/*') as $entry) {
            if (is_dir($entry)) {
                $out = [...$out, ...$this->twigFilesUnder($entry)];
            } elseif (str_ends_with((string) $entry, '.twig')) {
                $out[] = (string) $entry;
            }
        }

        return $out;
    }

    /** @return list<array{owner: string, tag: string, action: string, block: string}> POST forms only */
    private function postForms(): array
    {
        $forms = [];
        foreach ($this->twigFilesUnder(self::ROOT . '/var/templates') as $file) {
            $src = (string) file_get_contents($file);
            $owner = str_replace([self::ROOT . '/var/templates/', '.html.twig'], '', $file);
            if (! preg_match_all('~<form\b[^>]*?>~is', $src, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($m[0] as $hit) {
                $tag = $hit[0];
                $start = (int) $hit[1];
                $endPos = strpos($src, '</form>', $start);
                $block = substr($src, $start, $endPos === false ? null : $endPos - $start);
                if (strtolower($this->attr('method', $tag) ?? 'get') !== 'post') {
                    continue;
                }

                $forms[] = [
                    'owner' => $owner,
                    'tag' => $tag,
                    'action' => $this->attr('action', $tag) ?? '',
                    'block' => $block,
                ];
            }
        }

        return $forms;
    }

    private function attr(string $name, string $tag): ?string
    {
        if (preg_match('~\b' . $name . '\s*=\s*"([^"]*)"~is', $tag, $m)) {
            return $m[1];
        }

        return null;
    }

    /** A no-JS submit exists: an explicit type=submit, or a bare <button> with no type (defaults to submit). */
    private function hasNoJsSubmit(string $block): bool
    {
        return (bool) preg_match('~<(?:button|input)\b[^>]*type\s*=\s*"submit"~i', $block)
            || (bool) preg_match('~<button\b(?![^>]*\btype\s*=)[^>]*>~i', $block);
    }

    private function pathToResource(string $path): ?string
    {
        $path = strtok($path, '?#');
        $path = trim((string) $path);
        if (! str_starts_with($path, '/')) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $path), static fn ($s) => $s !== ''));
        if ($segments === []) {
            return null;
        }

        $pascal = [];
        foreach ($segments as $seg) {
            $pascal[] = implode('', array_map(static fn ($w) => ucfirst($w), explode('-', $seg)));
        }

        return 'Page/' . implode('/', $pascal);
    }

    /** @return list<string>|null resource HTTP verbs, or null if the resource file does not exist */
    private function resourceVerbs(?string $rel): ?array
    {
        if ($rel === null) {
            return null;
        }

        $file = self::ROOT . '/src/Resource/' . $rel . '.php';
        if (! is_file($file)) {
            return null;
        }

        preg_match_all('~function\s+(onGet|onPost|onPut|onDelete|onPatch)\b~', (string) file_get_contents($file), $m);

        return array_values(array_unique($m[1]));
    }

    // ---- Contract A -------------------------------------------------------

    /** @return list<string> resource #[Alps] ids that do not resolve to an alps.json descriptor */
    private function unresolvedResourceAlps(): array
    {
        $alps = $this->alpsIds();
        $out = [];
        foreach (array_keys($this->resourceAlpsTags()) as $id) {
            if (! in_array($id, $alps, true)) {
                $out[] = $id;
            }
        }

        sort($out);

        return $out;
    }

    // ---- Contract B -------------------------------------------------------

    /** @return list<string> do/go tokens on template <form class> that do not resolve to alps.json */
    private function unresolvedTemplateAffordances(): array
    {
        $alps = $this->alpsIds();
        $tokens = [];
        foreach ($this->postFormsAndGet() as $form) {
            $class = $this->attr('class', $form['tag']);
            if ($class === null) {
                continue;
            }

            preg_match_all('~\b(?:do|go)[A-Z][A-Za-z0-9]*~', $class, $m);
            foreach ($m[0] as $tok) {
                if (! in_array($tok, $alps, true)) {
                    $tokens[$tok] = true;
                }
            }
        }

        $out = array_keys($tokens);
        sort($out);

        return $out;
    }

    /** @return list<array{owner: string, tag: string, action: string, block: string}> all forms (any method) */
    private function postFormsAndGet(): array
    {
        // Contract B inspects the class of every <form> regardless of method.
        $forms = [];
        foreach ($this->twigFilesUnder(self::ROOT . '/var/templates') as $file) {
            $src = (string) file_get_contents($file);
            $owner = str_replace([self::ROOT . '/var/templates/', '.html.twig'], '', $file);
            if (! preg_match_all('~<form\b[^>]*?>~is', $src, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($m[0] as $hit) {
                $forms[] = ['owner' => $owner, 'tag' => $hit[0], 'action' => '', 'block' => ''];
            }
        }

        return $forms;
    }

    // ---- Contract C -------------------------------------------------------

    /** @return list<string> owner templates whose no-JS write form targets a resource lacking the implied write handler */
    private function writeFormsWithoutHandler(): array
    {
        $owners = [];
        foreach ($this->postForms() as $form) {
            if (! $this->hasNoJsSubmit($form['block'])) {
                continue;
            }

            $action = $form['action'];
            $dynamic = str_contains($action, '{{') || str_contains($action, '{%');

            if ($dynamic) {
                // Each literal /path branch must resolve to a resource with SOME write handler.
                preg_match_all('~/[A-Za-z][A-Za-z0-9/_\-]*~', $action, $m);
                foreach ($m[0] as $path) {
                    $verbs = $this->resourceVerbs($this->pathToResource($path));
                    if ($verbs !== null && array_intersect($verbs, self::WRITE_VERBS) === []) {
                        $owners[$form['owner']] = true;
                    }
                }

                continue;
            }

            // Literal action: empty/#/? posts to self; otherwise resolve the path.
            $trimmed = trim($action);
            $target = in_array($trimmed, ['', '#', '?'], true) ? $form['owner'] : $this->pathToResource($action);
            $expected = 'onPost';
            $lower = strtolower($action);
            if (str_contains($lower, '_method=put')) {
                $expected = 'onPut';
            } elseif (str_contains($lower, '_method=delete')) {
                $expected = 'onDelete';
            }

            $verbs = $this->resourceVerbs($target);
            if ($verbs === null || ! in_array($expected, $verbs, true)) {
                $owners[$form['owner']] = true;
            }
        }

        $out = array_keys($owners);
        sort($out);

        return $out;
    }

    // ---- assertions -------------------------------------------------------

    private function assertExactBaseline(string $section, array $computed): void
    {
        $registered = array_keys((array) ($this->baseline()[$section] ?? []));
        sort($registered);
        sort($computed);

        $newOrphans = array_values(array_diff($computed, $registered));
        $staleEntries = array_values(array_diff($registered, $computed));

        self::assertSame(
            [],
            $newOrphans,
            sprintf(
                "New SSOT-projection orphan(s) in baseline section '%s': %s. "
                . 'Fix the projection, or register each in docs/eccube-spec-coverage/ssot-projection-baseline.json '
                . 'with a category, reason and evidence.',
                $section,
                implode(', ', $newOrphans),
            ),
        );

        self::assertSame(
            [],
            $staleEntries,
            sprintf(
                "Stale baseline entr(ies) in section '%s': %s now project cleanly (fixed). "
                . 'Remove them from docs/eccube-spec-coverage/ssot-projection-baseline.json.',
                $section,
                implode(', ', $staleEntries),
            ),
        );
    }

    public function testBaselineEntriesAreWellFormed(): void
    {
        $baseline = $this->baseline();
        foreach (['resourceAlps', 'templateAffordance', 'writeFormWithoutHandler', 'anonymousAffordanceLeak'] as $section) {
            self::assertArrayHasKey($section, $baseline, "baseline must declare section {$section}.");
            foreach ((array) $baseline[$section] as $key => $entry) {
                self::assertIsArray($entry, sprintf('%s.%s must be an object.', $section, (string) $key));
                self::assertArrayHasKey('category', $entry, sprintf('%s.%s needs a category.', $section, (string) $key));
                self::assertContains($entry['category'], self::VALID_CATEGORIES, sprintf('%s.%s category "%s" is unknown.', $section, (string) $key, (string) $entry['category']));
                self::assertArrayHasKey('reason', $entry, sprintf('%s.%s needs a reason.', $section, (string) $key));
                self::assertNotSame('', trim((string) $entry['reason']), sprintf('%s.%s reason must not be empty.', $section, (string) $key));
                self::assertArrayHasKey('evidence', $entry, sprintf('%s.%s needs evidence.', $section, (string) $key));
                self::assertNotEmpty($entry['evidence'], sprintf('%s.%s needs at least one evidence item.', $section, (string) $key));
            }
        }
    }

    public function testContractAResourceAlpsResolvesOrIsBaselined(): void
    {
        $this->assertExactBaseline('resourceAlps', $this->unresolvedResourceAlps());
    }

    public function testContractBTemplateAffordanceResolvesOrIsBaselined(): void
    {
        $this->assertExactBaseline('templateAffordance', $this->unresolvedTemplateAffordances());
    }

    public function testContractCWriteFormHasHandlerOrIsBaselined(): void
    {
        $this->assertExactBaseline('writeFormWithoutHandler', $this->writeFormsWithoutHandler());
    }

    // ---- Contract D: anonymous-affordance gating --------------------------

    /**
     * Auth-only storefront affordances: their target page firewalls anonymous
     * visitors (EC-CUBE redirects an anonymous `mypage_*` / favorite request
     * to the login form — see Eccube\Controller\Mypage\* + the `secured_area`
     * firewall in config/eccube/packages/security.yaml). Presenting one to a
     * visitor who cannot perform it is the お気に入り→401 class of bug: the
     * affordance must be hidden behind an is_logged_in() guard (or live inside
     * a firewalled page template that anonymous never reaches), NEVER shown
     * bare so that clicking it only 401s.
     *
     * Each token is an alps.json `actor-customer` descriptor whose `rt`/path
     * sits under the firewalled /mypage area (+ the favorite endpoints).
     * Anonymous-allowed customer tokens (goLogin/doLogin/goCart/goContactForm/
     * doAddCartItem/…) are deliberately NOT here — they are public.
     *
     * @var list<string>
     */
    private const AUTH_ONLY_CUSTOMER_TOKENS = [
        'goMypage',
        'goMypageChange',
        'goMypageHistory',
        'goMypageWithdraw',
        'goMypageWithdrawConfirm',
        'goOrderHistory',
        'goFavoriteList',
        'goCustomerAddressList',
        'doAddFavorite',
        'doRemoveFavorite',
        'doReorder',
        'doUpdateCustomer',
        'doCreateCustomerAddress',
        'doUpdateCustomerAddress',
        'doDeleteCustomerAddress',
        'doWithdrawCustomer',
    ];

    /**
     * Firewalled href/action path prefixes: a link/form to one of these is an
     * auth-only affordance even when its class carries no do/go token (e.g. the
     * header's plain <a href="/mypage/favorite-list">). Anonymous visitors are
     * redirected to /login before the target renders.
     *
     * @var list<string>
     */
    private const FIREWALLED_PATH_PREFIXES = ['/mypage'];

    /**
     * A template owner is itself auth-firewalled (its resource redirects
     * anonymous visitors to /login) when it lives under the storefront mypage
     * area or the admin area. Affordances inside such a template are never
     * shown to a visitor who cannot perform them, so they are exempt from the
     * is_logged_in() guard requirement.
     */
    private function isFirewalledOwner(string $owner): bool
    {
        return str_starts_with($owner, 'Page/Mypage')
            || str_starts_with($owner, 'Page/Admin');
    }

    /**
     * Is the byte offset $pos inside the template source covered by an
     * is_logged_in() / is_granted() auth guard? Tracks {% if/elseif/else/endif %}
     * nesting on the comment-stripped source: an affordance is guarded when it
     * sits in the true-branch of an `if (is_logged_in())` (or admin is_granted)
     * that has not yet been closed by endif or flipped by its else.
     */
    private function authGuardedAt(string $strippedSrc, int $pos): bool
    {
        $head = substr($strippedSrc, 0, $pos);
        /** @var list<bool> $stack each open {% if %} -> is it an auth guard true-branch */
        $stack = [];
        if (! preg_match_all('~\{%\s*(if|elseif|else|endif)\b([^%]*)%\}~', $head, $m, PREG_SET_ORDER)) {
            return false;
        }

        foreach ($m as $tag) {
            $kw = $tag[1];
            $cond = $tag[2] ?? '';
            $isAuth = str_contains($cond, 'is_logged_in') || str_contains($cond, 'is_granted');
            if ($kw === 'if') {
                $stack[] = $isAuth;
            } elseif ($kw === 'elseif') {
                // condition continues the same if-block; an auth elseif still guards.
                if ($stack !== []) {
                    $stack[count($stack) - 1] = $isAuth;
                }
            } elseif ($kw === 'else') {
                // leaving the auth true-branch into its negative => no longer guarded.
                if ($stack !== []) {
                    $stack[count($stack) - 1] = false;
                }
            } elseif ($kw === 'endif') {
                array_pop($stack);
            }
        }

        return in_array(true, $stack, true);
    }

    /**
     * Walk every storefront template that anonymous visitors can reach and
     * collect auth-only affordances that are presented WITHOUT an is_logged_in()
     * guard — the leak this contract exists to catch.
     *
     * An affordance is either (a) a do/go token in a <form class>, or (b) a
     * link/form whose href/action targets a firewalled /mypage path. A login-
     * routing link (href targets /login or /entry) is never a leak.
     *
     * @return list<string> "owner::affordance" leak keys, sorted
     */
    private function anonymousAffordanceLeaks(): array
    {
        $leaks = [];
        foreach ($this->twigFilesUnder(self::ROOT . '/var/templates') as $file) {
            $owner = str_replace([self::ROOT . '/var/templates/', '.html.twig'], '', $file);
            if ($this->isFirewalledOwner($owner)) {
                continue;
            }

            $src = (string) file_get_contents($file);
            $stripped = (string) preg_replace('~\{#.*?#\}~s', '', $src);

            // (a) auth-only do/go token in a <form class="...">.
            if (preg_match_all('~<form\b[^>]*\bclass\s*=\s*"([^"]*)"[^>]*>~is', $stripped, $fm, PREG_OFFSET_CAPTURE)) {
                foreach ($fm[0] as $i => $hit) {
                    $class = $fm[1][$i][0];
                    $pos = (int) $hit[1];
                    preg_match_all('~\b(?:do|go)[A-Z][A-Za-z0-9]*~', $class, $tm);
                    foreach ($tm[0] as $tok) {
                        if (in_array($tok, self::AUTH_ONLY_CUSTOMER_TOKENS, true)
                            && ! $this->authGuardedAt($stripped, $pos)) {
                            $leaks[$owner . '::' . $tok] = true;
                        }
                    }
                }
            }

            // (b) link/form whose href/action targets a firewalled /mypage path.
            if (preg_match_all('~\b(?:href|action)\s*=\s*"([^"]*)"~is', $stripped, $hm, PREG_OFFSET_CAPTURE)) {
                foreach ($hm[1] as $hit) {
                    $target = $hit[0];
                    $pos = (int) $hit[1];
                    foreach (self::FIREWALLED_PATH_PREFIXES as $prefix) {
                        if (($target === $prefix || str_starts_with($target, $prefix . '/') || str_starts_with($target, $prefix . '?'))
                            && ! $this->authGuardedAt($stripped, $pos)) {
                            $leaks[$owner . '::' . $target] = true;
                        }
                    }
                }
            }
        }

        $out = array_keys($leaks);
        sort($out);

        return $out;
    }

    public function testContractDAnonymousAffordanceGatedOrIsBaselined(): void
    {
        $this->assertExactBaseline('anonymousAffordanceLeak', $this->anonymousAffordanceLeaks());
    }
}
