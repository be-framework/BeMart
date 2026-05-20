<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Module\HtmlModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function array_diff;
use function array_filter;
use function array_values;
use function count;
use function dirname;
use function explode;
use function http_build_query;
use function implode;
use function is_dir;
use function is_string;
use function preg_replace;
use function str_contains;
use function str_starts_with;
use function trim;

/**
 * Phase 3 — fidelity check for the Entry (goCustomerRegistration) HTML
 * port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * EC-CUBE's `Entry/index.twig` is the most form-heavy port of the wave:
 * every one of the 16 customer-registration fields is rendered through
 * the Symfony FormView (`form_label` / `form_widget` / `form_errors`).
 * BeMart's Entry resource body carries the field NAMES (`fields`), not a
 * FormView, so the labels and `<input>`/`<select>` widgets are authored
 * plainly in the port. The EC-CUBE side stubs the form_* helpers to
 * deterministic markers; the label and widget lines therefore differ on
 * each side and are an enumerated residual FAMILY ("Symfony FormView
 * runtime"). What this test proves is that the `ec-*` skeleton — the
 * `ec-registerRole` / `ec-borderedDefs` <dl>/<dt>/<dd> definition list,
 * every `ec-halfInput` / `ec-zipInput` / `ec-select` / `ec-input` /
 * `ec-birth` / `ec-radio` / `ec-telInput` wrapper, the policy checkbox
 * row, the action buttons — is ported verbatim.
 */
final class EntryHtmlRenderTest extends TestCase
{
    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. Each entry
     * is a whitespace-collapsed line; the comment states WHY it is
     * acceptable. The form-widget lines are matched by FAMILY in
     * {@see isResidual()} rather than enumerated one by one.
     *
     * @var list<string>
     */
    private const RESIDUAL_ALLOWLIST = [
        // --- frame: EC-CUBE-runtime-only <head> nodes (shared) ----------
        '<meta name="eccube-csrf-token" content="">',
        '<script>',
        '$(function() {',
        '$.ajaxSetup({',
        "'headers': {",
        '}',
        '});',
        '});',
        '</script>',
        '<title>BeMart / 新規会員登録</title>',
        '<title>EC-CUBE / 新規会員登録</title>',
        '<meta name="author" content="">',
    ];

    /**
     * BeMart-side line prefixes with no EC-CUBE counterpart: the plainly
     * authored form widgets. EC-CUBE renders these through the Symfony
     * FormView (stubbed to `[form_widget:...]` / `[form_label:...]`
     * markers here); BeMart's resource body carries field names, not a
     * FormView, so the port authors them as bare HTML. Same fields,
     * FormView-runtime markup only.
     *
     * @var list<string>
     */
    private const BEMART_FORM_WIDGET_PREFIXES = [
        '<input type="text" name=',
        '<input type="password" name=',
        '<input type="radio" name=',
        '<input type="checkbox" name=',
        '<input type="hidden" name="_token"',
        '<select name=',
        '<label class="ec-label">',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $injector = new Injector(
            new HtmlModule($meta),
            dirname(__DIR__, 2) . '/var/tmp/html',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testEntryPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/entry');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testEntryPagePreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/entry')->toString();

        foreach ([
            '<div class="ec-registerRole">',
            '<div class="ec-pageHeader">',
            '<div class="ec-off1Grid">',
            'class="ec-off1Grid__cell"',
            '<form method="post" action="/entry" novalidate class="h-adr">',
            '<span class="p-country-name"',
            '<div class="ec-borderedDefs">',
            '<div class="ec-halfInput">',
            '<div class="ec-zipInput">',
            '<div class="ec-zipInputHelp">',
            '<div class="ec-telInput">',
            '<div class="ec-birth">',
            '<div class="ec-radio">',
            '<div class="ec-registerRole__actions">',
            '<div class="ec-checkbox">',
            '<div class="ec-off4Grid">',
            'class="ec-blockBtn--action"',
            'class="ec-blockBtn--cancel"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered registration page against
     * EC-CUBE's own rendering. Every difference must be in the residual
     * allowlist or an explained residual family.
     */
    public function testEntryHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/entry')->toString();
        $ecCube = $this->renderEcCubeEntry();

        $beMartLines = $this->normalize($beMart);
        $ecCubeLines = $this->normalize($ecCube);

        $onlyInEcCube = array_values(array_diff($ecCubeLines, $beMartLines));
        $onlyInBeMart = array_values(array_diff($beMartLines, $ecCubeLines));

        $unexplained = array_values(array_filter(
            [...$onlyInEcCube, ...$onlyInBeMart],
            static fn (string $line): bool => ! self::isResidual($line),
        ));

        $this->assertSame(
            [],
            $unexplained,
            "BeMart's entry HTML diverged from EC-CUBE's beyond the "
            . "residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );
    }

    private static function isResidual(string $line): bool
    {
        foreach (self::RESIDUAL_ALLOWLIST as $allowed) {
            if ($line === $allowed) {
                return true;
            }
        }

        foreach ([
            'eccube-csrf-token',
            '<title>',
            'meta name="author"',
            'form_widget:',          // Symfony FormView widget marker
            'form_label:',           // Symfony FormView label marker
            'form_row',              // Symfony FormView entity-extension row
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        // BeMart-side plainly-authored form widgets (the FormView the
        // resource body does not carry — see BEMART_FORM_WIDGET_PREFIXES).
        foreach (self::BEMART_FORM_WIDGET_PREFIXES as $prefix) {
            if (str_starts_with($line, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render EC-CUBE 4.3's real Entry/index.twig + default_frame.twig
     * from the gitignored clone, with EC-CUBE's Twig API stubbed.
     */
    private function renderEcCubeEntry(): string
    {
        $ecCubeTemplates = dirname(__DIR__, 2)
            . '/tools/ec-cube-source/src/Eccube/Resource/template/default';
        if (! is_dir($ecCubeTemplates)) {
            $this->markTestSkipped('EC-CUBE 4.3 reference clone not present.');
        }

        $twig = new Environment(new EcCubeStubLoader($ecCubeTemplates), [
            'autoescape' => 'html',
            'strict_variables' => false,
        ]);
        $this->registerEcCubeStubs($twig);

        // `form` is iterated by the entity-extension auto-render loop; an
        // empty bag means the loop emits nothing (a core install has no
        // entity extensions). The named children resolve via __get to a
        // marker string for the stubbed form_widget / form_label.
        return $twig->render('Entry/index.twig', [
            'form' => new EcCubeStub([
                'name' => 'form.name',
                'kana' => 'form.kana',
                'company_name' => 'form.company_name',
                'postal_code' => 'form.postal_code',
                'address' => 'form.address',
                'phone_number' => 'form.phone_number',
                'email' => 'form.email',
                'plain_password' => 'form.plain_password',
                'birth' => 'form.birth',
                'sex' => 'form.sex',
                'job' => 'form.job',
                'user_policy_check' => 'form.user_policy_check',
                '_token' => 'form._token',
            ]),
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_password_min_len' => 8,
                'eccube_password_max_len' => 32,
            ],
            'Page' => new EcCubeStub([
                'meta_tags' => '',
                'description' => '',
                'author' => '',
                'keyword' => '',
                'meta_robots' => '',
            ]),
            'Layout' => new EcCubeStub([
                'Head' => null, 'BodyAfter' => null, 'Header' => [0 => 'x'],
                'ContentsTop' => null, 'SideLeft' => null, 'SideRight' => null,
                'MainTop' => null, 'MainBottom' => null, 'ContentsBottom' => null,
                'Footer' => [0 => 'x'], 'Drawer' => [0 => 'x'], 'CloseBodyBefore' => null,
                'ColumnNum' => 1,
            ]),
            'app' => new EcCubeStub(['session' => new EcCubeStub([
                'flashbag' => new EcCubeFlashBag(),
            ]), 'request' => new EcCubeStub(['_route' => 'entry'])]),
            'subtitle' => '新規会員登録',
            'title' => '新規会員登録',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig): void
    {
        $trans = static function (string $key, array $params = []): string {
            $messages = EcCubeStub::jaMessages();
            $text = $messages[$key] ?? $key;
            foreach ($params as $name => $value) {
                $text = str_replace($name, (string) $value, $text);
            }

            return $text;
        };
        $twig->addFilter(new TwigFilter('trans', $trans));
        $twig->addFilter(new TwigFilter('nl2br', static fn (string $s): string => nl2br($s)));
        $twig->addFilter(new TwigFilter('number_format', static fn ($n): string => number_format((float) $n)));
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));
        // Entry/index.twig's auto-render loop uses `form|filter(...)`.
        $twig->addFilter(new TwigFilter('filter', static fn ($it, $f): array => []));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        $twig->addFunction(new TwigFunction('asset', static fn (string $p): string => '/' . $p));
        $twig->addFunction(new TwigFunction('url', static function (string $r, array $p = []): string {
            return '/' . $r . ($p ? '?' . http_build_query($p) : '');
        }));
        $twig->addFunction(new TwigFunction('path', static function (string $r, array $p = []): string {
            return '/' . $r . ($p ? '?' . http_build_query($p) : '');
        }));
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));

        // Symfony FormView helpers — stubbed to deterministic markers.
        $twig->addFunction(new TwigFunction('form_widget', static fn ($f = '', $o = []): string => '[form_widget:' . (is_string($f) ? $f : 'field') . ']'));
        $twig->addFunction(new TwigFunction('form_label', static fn ($f = '', $l = '', $o = []): string => '[form_label:' . (is_string($l) ? $l : 'label') . ']'));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_rest', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_row', static fn ($f = '', $o = []): string => '[form_row]'));
        $twig->addFunction(new TwigFunction('has_errors', static fn (...$f): bool => false));
    }

    /**
     * Collapse a rendered HTML document to a list of non-empty,
     * whitespace-trimmed lines for structural line-diffing.
     *
     * @return list<string>
     */
    private function normalize(string $html): array
    {
        $collapsed = (string) preg_replace('/[ \t]+/', ' ', $html);
        $lines = [];
        foreach (explode("\n", $collapsed) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }
}
