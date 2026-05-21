<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Form\ContactForm;
use MyVendor\BeMart\Module\HtmlModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Ray\WebFormModule\FormFactory;
use Twig\Environment;
use Twig\Markup;
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
use function in_array;
use function is_dir;
use function is_string;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Phase 3 — fidelity check for the Contact (goContactForm) HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * WAVE-1 was authored BEFORE the form-page recipe existed: static
 * `<input>`s with no value/error binding, render-diff 46 lines.
 *
 * This rework adopts the Ray.WebFormModule form-page recipe (see
 * var/templates/README.md). BeMart's Contact resource now exposes a real
 * {@see ContactForm} (an AbstractForm) as `body.form`, and the port
 * renders the inputs via `{{ form.input('contactEmail') }}`. This test
 * renders EC-CUBE's `form_widget` / `form_label` calls through the SAME
 * `ContactForm` instance — so the widgets diff to ZERO.
 *
 * MISSING-BODY-FIELD residual. EC-CUBE's `ContactType` collects MORE
 * fields than BeMart's Contact resource carries. EC-CUBE's form has
 * name / kana / address (postal_code / pref / addr01 / addr02) /
 * phone_number / email / contents; BeMart's `SubmitContactInput` (and
 * the ALPS `ContactForm` descriptor) model ONLY contactName01 /
 * contactName02 / contactEmail / contactContents. The kana / address /
 * phone <dl> rows therefore exist in EC-CUBE's output with no BeMart
 * body field behind them — they are OMITTED from BeMart's port (never
 * invented) and recorded here as an explained EC-CUBE-only residual.
 *
 * Per the Phase-3 recipe these missing-field rows are NOT fixed in a
 * template wave (no Entity/SQL/ALPS enrichment); the Contact page is
 * FLAGGED for a follow-up vertical-slice that would model kana / address
 * / phone on `SubmitContactInput` + the ALPS `ContactForm` descriptor.
 */
final class ContactHtmlRenderTest extends TestCase
{
    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. Each entry
     * is a whitespace-collapsed line; the comment states WHY it is
     * acceptable.
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

        // --- contact form: CSRF hidden input ----------------------------
        // EC-CUBE's hidden _token carries a live form CSRF token; BeMart's
        // html context has no CSRF widget, so the value is empty.
        '<input type="hidden" name="_token" value="">',
    ];

    /**
     * EC-CUBE-side lines for the kana / address / phone <dl> rows that
     * BeMart's Contact body does NOT model (missing-body-field residual).
     * EC-CUBE renders these rows; BeMart's port omits them entirely. Each
     * line is whitespace-collapsed exactly as the diff sees it. NOT fixed
     * in this template wave — flagged for a vertical-slice enrichment.
     *
     * @var list<string>
     */
    private const ECCUBE_MISSING_FIELD_LINES = [
        // kana row — `<dt>` label + `<dd>` half-input wrapper.
        '<label class="ec-label">お名前(カナ)</label>',
        // address row — postal / zip-help / pref-select / addr lines.
        '<label class="ec-label">住所</label>',
        '<div class="ec-zipInput">',
        '<span>〒</span>',
        '<div class="ec-zipInputHelp">',
        '<div class="ec-zipInputHelp__icon">',
        '<div class="ec-icon"><img',
        'src="/assets/icon/question-white.svg" alt="">',
        '</div><a href="https://www.post.japanpost.jp/zipcode/" target="_blank"><span>郵便番号検索</span></a>',
        '<div class="ec-select">',
        // phone row — `<dt>` label + `<dd>` tel-input wrapper.
        '<label class="ec-label">電話番号</label>',
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
     * The form inputs are rendered by a real form library: the page
     * carries `<input>` / `<textarea>` with the EC-CUBE field names.
     */
    public function testContactPageRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/contact')->toString();

        $this->assertStringContainsString('name="contactName01"', $html);
        $this->assertStringContainsString('placeholder="姓"', $html);
        $this->assertStringContainsString('name="contactName02"', $html);
        $this->assertStringContainsString('name="contactEmail"', $html);
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="contactContents"', $html);
    }

    /**
     * The honesty test: diff BeMart's rendered contact page against
     * EC-CUBE's own rendering. Every difference must be in the residual
     * allowlist or the explained missing-body-field family.
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

        // With the modelled form inputs rendered by a real ContactForm on
        // both sides, wave-1's 46-line residual collapses to 23 — ALL
        // explained: 13 shared <head>/<title>/CSRF frame lines + 10
        // distinct EC-CUBE-only kana/address/phone missing-body-field
        // lines (some collapse-equal across the three rows). The
        // form-widget residual family is eliminated; what remains is the
        // frame + the genuinely-missing fields, which are flagged for a
        // follow-up vertical slice (not fixed in a template wave).
        $this->assertLessThanOrEqual(
            24,
            count($onlyInEcCube) + count($onlyInBeMart),
            'residual diff unexpectedly large — port may have drifted',
        );
    }

    private static function isResidual(string $line): bool
    {
        if (in_array($line, self::RESIDUAL_ALLOWLIST, true)) {
            return true;
        }

        if (in_array($line, self::ECCUBE_MISSING_FIELD_LINES, true)) {
            return true;
        }

        foreach ([
            'eccube-csrf-token',
            '<title>',
            'meta name="author"',
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render EC-CUBE 4.3's real Contact/index.twig + default_frame.twig
     * from the gitignored clone, with EC-CUBE's Twig API stubbed.
     *
     * `form_widget` / `form_label` for the four MODELLED fields delegate
     * to the real {@see ContactForm} so they diff to ZERO. The kana /
     * address / phone compound children resolve to `null` field names;
     * the stubbed form_widget renders nothing for them — those rows are
     * the missing-body-field residual.
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
                // MODELLED fields — resolve to ContactForm field names.
                'name' => new EcCubeStub([
                    'name01' => 'contactName01', 'name02' => 'contactName02',
                ]),
                'email' => 'contactEmail',
                'contents' => 'contactContents',
                // MISSING fields — kana / address / phone have no BeMart
                // body field. Their compound leaves resolve to null; the
                // stubbed form_widget emits nothing for them.
                'kana' => new EcCubeStub([]),
                'postal_code' => null,
                'address' => new EcCubeStub([]),
                'phone_number' => null,
                '_token' => '__token__',
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
                'Head' => null, 'BodyAfter' => null, 'Header' => [new EcCubeStub(['file_name' => 'logo'])],
                'ContentsTop' => null, 'SideLeft' => null, 'SideRight' => null,
                'MainTop' => null, 'MainBottom' => null, 'ContentsBottom' => null,
                'Footer' => [new EcCubeStub(['file_name' => 'footer'])], 'Drawer' => [0 => 'x'], 'CloseBodyBefore' => null,
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
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));

        // FORM-PAGE recipe: `form_widget(form.<field>)` for the four
        // MODELLED fields delegates to BeMart's real ContactForm so the
        // inputs are byte-identical. The missing kana / address / phone
        // compound children resolve to null — the stub emits nothing for
        // them (those rows are the missing-body-field residual).
        $contactForm = (new FormFactory())->newInstance(ContactForm::class);
        $modelled = ['contactName01', 'contactName02', 'contactEmail', 'contactContents'];
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($contactForm, $modelled): Markup {
            if ($field === '__token__') {
                return new Markup('<input type="hidden" name="_token" value="">', 'UTF-8');
            }

            if ($contactForm instanceof ContactForm && is_string($field) && in_array($field, $modelled, true)) {
                return new Markup($contactForm->input($field), 'UTF-8');
            }

            return new Markup('', 'UTF-8');
        }));
        // EC-CUBE's `form_label` renders a Symfony FormView <label>; for
        // the MODELLED fields BeMart authors the same `<label
        // class="ec-label">ja</label>`, so the stub renders the real
        // <label> and the two diff to zero. For the MISSING kana /
        // address / phone rows the <label> still renders on EC-CUBE's
        // side — those label lines are listed in the missing-field
        // residual family.
        $twig->addFunction(new TwigFunction('form_label', static function ($f = '', $l = '', $o = []) use ($trans): Markup {
            $text = is_string($l) ? $trans($l) : '';

            return new Markup('<label class="ec-label">' . $text . '</label>', 'UTF-8');
        }));
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
