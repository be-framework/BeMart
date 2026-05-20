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
 * Phase 3 — fidelity check for the Contact (goContactForm) HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * EC-CUBE's `Contact/index.twig` is form-bearing (Symfony FormView),
 * so the label / widget lines are an enumerated residual FAMILY the
 * same way as the Login and Entry ports. This test proves the `ec-*`
 * skeleton — the `ec-contactRole` / `ec-borderedDefs` <dl>/<dt>/<dd>
 * list, the `ec-halfInput` / `ec-input` wrappers, the inquiry-notice
 * paragraph, the action button — is ported verbatim.
 *
 * NOTE — missing-body-field residual. EC-CUBE's contact form collects
 * MORE fields than BeMart's Contact resource carries. EC-CUBE's form has
 * name / kana / address (postal_code / pref / addr01 / addr02) /
 * phone_number / email / contents; BeMart's SubmitContactInput (and the
 * ALPS `ContactForm` descriptor) model only contactName01 /
 * contactName02 / contactEmail / contactContents. The kana / address /
 * phone <dl> rows therefore exist in EC-CUBE's output with no BeMart
 * body field behind them. Per the Phase 3 recipe these missing-field
 * rows are NOT fixed here (no Entity/SQL enrichment in a template wave);
 * they are recorded as an explained residual family. The Contact page
 * is flagged for a follow-up vertical-slice enrichment.
 */
final class ContactHtmlRenderTest extends TestCase
{
    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. Each entry
     * is a whitespace-collapsed line; the comment states WHY it is
     * acceptable. Form-widget lines + the missing-field <dl> rows are
     * matched by FAMILY in {@see isResidual()}.
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
        '<title>BeMart / お問い合わせ</title>',
        '<title>EC-CUBE / お問い合わせ</title>',
        '<meta name="author" content="">',

        // --- missing-body-field: kana / address / phone rows -----------
        // EC-CUBE's contact form has kana / postal_code / pref / addr01 /
        // addr02 / phone_number <dl> rows. BeMart's Contact resource body
        // models only name01/02 + email + contents (ALPS `ContactForm`
        // scope), so these rows have no BeMart body field. They are kept
        // on EC-CUBE's side, absent on BeMart's. NOT fixed in this
        // template wave — flagged for a vertical-slice enrichment.
        // (These EC-CUBE-only wrapper lines are matched by family below.)
    ];

    /**
     * BeMart-side line prefixes with no EC-CUBE counterpart: the plainly
     * authored form widgets (the FormView the resource body does not
     * carry).
     *
     * @var list<string>
     */
    private const BEMART_FORM_WIDGET_PREFIXES = [
        '<input type="text" name=',
        '<input type="hidden" name="_token"',
        '<textarea name=',
        '<label class="ec-label">',
    ];

    /**
     * EC-CUBE-side wrapper lines for the kana / address / phone rows that
     * BeMart's Contact body does not model (missing-body-field residual).
     *
     * @var list<string>
     */
    private const ECCUBE_MISSING_FIELD_LINES = [
        '<div class="ec-zipInput">',
        '<span>〒</span>',
        '<div class="ec-zipInputHelp">',
        '<div class="ec-zipInputHelp__icon">',
        '<div class="ec-icon"><img',
        'src="/assets/icon/question-white.svg" alt="">',
        '</div><a href="https://www.post.japanpost.jp/zipcode/" target="_blank"><span>郵便番号検索</span></a>',
        '<div class="ec-select">',
        '<div class="ec-telInput">',
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

    public function testContactPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/contact');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testContactPagePreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/contact')->toString();

        foreach ([
            '<div class="ec-contactRole">',
            '<div class="ec-pageHeader">',
            '<div class="ec-off1Grid">',
            'class="ec-off1Grid__cell"',
            '<form method="post" action="/contact" class="h-adr" novalidate>',
            '<span class="p-country-name"',
            '<p class="ec-para-normal">',
            '<div class="ec-borderedDefs">',
            '<div class="ec-halfInput">',
            '<div class="ec-RegisterRole__actions">',
            '<div class="ec-off4Grid">',
            'class="ec-blockBtn--action"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered contact page against
     * EC-CUBE's own rendering. Every difference must be in the residual
     * allowlist or an explained residual family.
     */
    public function testContactHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/contact')->toString();
        $ecCube = $this->renderEcCubeContact();

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
            "BeMart's contact HTML diverged from EC-CUBE's beyond the "
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

        foreach (self::ECCUBE_MISSING_FIELD_LINES as $missing) {
            if ($line === $missing) {
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

        foreach (self::BEMART_FORM_WIDGET_PREFIXES as $prefix) {
            if (str_starts_with($line, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render EC-CUBE 4.3's real Contact/index.twig + default_frame.twig
     * from the gitignored clone, with EC-CUBE's Twig API stubbed.
     */
    private function renderEcCubeContact(): string
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

        return $twig->render('Contact/index.twig', [
            'form' => new EcCubeStub([
                'name' => 'form.name',
                'kana' => 'form.kana',
                'postal_code' => 'form.postal_code',
                'address' => 'form.address',
                'phone_number' => 'form.phone_number',
                'email' => 'form.email',
                'contents' => 'form.contents',
                '_token' => 'form._token',
            ]),
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => ['locale' => 'ja'],
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
            ]), 'request' => new EcCubeStub(['_route' => 'contact'])]),
            'subtitle' => 'お問い合わせ',
            'title' => 'お問い合わせ',
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
        // EC-CUBE's real `nl2br` filter returns safe Markup (it emits
        // <br /> tags). Mark the stub safe so Twig does not re-escape the
        // <br /> the way it would for a plain string.
        $twig->addFilter(new TwigFilter('nl2br', static fn (string $s): string => nl2br($s), ['is_safe' => ['html']]));
        $twig->addFilter(new TwigFilter('number_format', static fn ($n): string => number_format((float) $n)));
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));
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
