<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Form\ContactConfirmForm;
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
use function nl2br;
use function preg_replace;
use function str_contains;
use function str_replace;
use function trim;

/**
 * Phase 3 — fidelity check for the Contact confirm (goContactConfirm)
 * HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * `Contact/confirm.twig` is the inquiry-CONFIRM screen — it re-shows the
 * submitted inquiry as plain text AND carries it forward as HIDDEN
 * inputs (`form_widget(form.email, { type : 'hidden' })`) so the final
 * "送信する" submit re-posts the inquiry to `doSubmitContact`.
 *
 * FORM page — the Ray.WebFormModule form-page recipe (see
 * var/templates/README.md). The Confirm resource
 * (src/Resource/Page/Contact/Confirm.php) is a thin pure renderer (a NEW
 * resource — EC-CUBE keeps the confirm step on the same controller
 * action via the `mode` POST param; BeMart's Pilot 15 collapsed the
 * flow). It exposes a {@see ContactConfirmForm} — every inquiry field
 * declared `hidden` — as `body.form`. This test renders EC-CUBE's
 * `form_widget(form.<field>, { type : 'hidden' })` calls through the
 * SAME ContactConfirmForm, so the hidden carriers diff to ZERO.
 *
 * MISSING-BODY-FIELD residual — as with {@see ContactHtmlRenderTest},
 * EC-CUBE's contact-confirm screen re-shows MORE fields than BeMart's
 * Contact resource carries: EC-CUBE has name / kana / address /
 * phone_number / email / contents; BeMart's `SubmitContactInput` (and
 * the ALPS `ContactForm` descriptor) model ONLY contactName01 /
 * contactName02 / contactEmail / contactContents. The kana / address /
 * phone <dl> rows are OMITTED from BeMart's port (never invented) and
 * recorded here as an EC-CUBE-only residual family — flagged for a
 * follow-up vertical slice.
 *
 * The plain-text value cells render empty: a pure `onGet` renderer has
 * no submitted payload, so this test feeds EC-CUBE's confirm.twig empty
 * `vars.data` so both sides render empty value cells.
 */
final class ContactConfirmHtmlRenderTest extends TestCase
{
    /** @var list<string> */
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

        // --- confirm form: CSRF hidden input ----------------------------
        // EC-CUBE's hidden _token carries a live form CSRF token; BeMart's
        // html context has no CSRF widget, so the value is empty.
        '<input type="hidden" name="_token" value="">',
    ];

    /**
     * EC-CUBE-side lines for the kana / address / phone <dl> rows that
     * BeMart's Contact body does NOT model (missing-body-field residual).
     * EC-CUBE re-shows these rows on its confirm screen; BeMart's port
     * omits them entirely. NOT fixed in this template wave — flagged for
     * a vertical-slice enrichment.
     *
     * @var list<string>
     */
    private const ECCUBE_MISSING_FIELD_LINES = [
        // kana / address / phone <dt> labels (ported `<label>` markup).
        '<label class="ec-label">お名前(カナ)</label>',
        '<label class="ec-label">住所</label>',
        '<label class="ec-label">電話番号</label>',
        // address row — postal-symbol span.
        '<span><span>〒</span></span>',
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

    public function testContactConfirmPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/contact/confirm');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testContactConfirmPagePreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/contact/confirm')->toString();

        foreach ([
            '<div class="ec-contactConfirmRole">',
            '<div class="ec-pageHeader">',
            '<h1>お問い合わせ</h1>',
            '<div class="ec-off1Grid">',
            'class="ec-off1Grid__cell"',
            '<form method="post" action="/contact" class="h-adr">',
            '<div class="ec-borderedDefs">',
            '<div class="ec-RegisterRole__actions">',
            '<div class="ec-off4Grid">',
            'class="ec-blockBtn--action"',
            'class="ec-blockBtn--cancel"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The inquiry payload is carried forward as real hidden inputs
     * rendered by a form library, not static markup.
     */
    public function testContactConfirmPageRendersHiddenFormCarriers(): void
    {
        $html = $this->resource->get('page://self/contact/confirm')->toString();

        $this->assertStringContainsString('<input type="hidden" name="contactName01"', $html);
        $this->assertStringContainsString('<input type="hidden" name="contactName02"', $html);
        $this->assertStringContainsString('<input type="hidden" name="contactEmail"', $html);
        $this->assertStringContainsString('<input type="hidden" name="contactContents"', $html);
    }

    public function testContactConfirmHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/contact/confirm')->toString();
        $ecCube = $this->renderEcCube();

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
            "BeMart's contact-confirm HTML diverged from EC-CUBE's beyond "
            . "the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // With the 4 hidden carriers + the 3 modelled-field labels
        // rendered by a real ContactConfirmForm / ported `form_label` on
        // both sides, the residual is the shared <head>/<title>/CSRF frame
        // material + the kana/address/phone missing-body-field rows.
        $this->assertLessThanOrEqual(
            18,
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
            // EC-CUBE renders each field label through the Symfony
            // FormView `form_label` helper; BeMart authors the plain
            // `<label class="ec-label">`. The MODELLED-field labels diff
            // to zero; the kana/address/phone labels are missing-field.
            'form_label:',
            'form_row',
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render EC-CUBE 4.3's real Contact/confirm.twig + default_frame.twig
     * from the gitignored clone, with EC-CUBE's Twig API stubbed.
     *
     * `form_widget(form.<field>, { type : 'hidden' })` for the four
     * MODELLED fields delegates to the real {@see ContactConfirmForm}; the
     * kana / address / phone compound children resolve to leaves with no
     * `__fieldName`, and the stubbed form_widget renders nothing for them.
     */
    private function renderEcCube(): string
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

        return $twig->render('Contact/confirm.twig', [
            'form' => new EcCubeStub([
                // MODELLED fields — leaves carry the ContactConfirmForm
                // field name + an empty `vars.data` value cell.
                'name' => new EcCubeStub([
                    'name01' => self::leaf('contactName01'),
                    'name02' => self::leaf('contactName02'),
                ]),
                'email' => self::leaf('contactEmail'),
                'contents' => self::leaf('contactContents'),
                // MISSING fields — kana / address / phone have no BeMart
                // body field. Leaves carry no `__fieldName`; the stubbed
                // form_widget emits nothing for them.
                'kana' => new EcCubeStub([
                    'kana01' => self::emptyLeaf(),
                    'kana02' => self::emptyLeaf(),
                ]),
                'postal_code' => self::emptyLeaf(),
                'address' => new EcCubeStub([
                    'pref' => self::emptyLeaf(),
                    'addr01' => self::emptyLeaf(),
                    'addr02' => self::emptyLeaf(),
                ]),
                'phone_number' => self::emptyLeaf(),
                '_token' => '__token__',
            ]),
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => ['locale' => 'ja'],
            'Page' => new EcCubeStub([
                'meta_tags' => '', 'description' => '', 'author' => '',
                'keyword' => '', 'meta_robots' => '',
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
            ]), 'request' => new EcCubeStub(['_route' => 'contact_confirm'])]),
            'subtitle' => 'お問い合わせ',
            'title' => 'お問い合わせ',
        ]);
    }

    /** A modelled-field leaf: carries the field name + empty `vars.data`. */
    private static function leaf(string $fieldName): EcCubeStub
    {
        return new EcCubeStub([
            '__fieldName' => $fieldName,
            'vars' => new EcCubeStub(['data' => '']),
        ]);
    }

    /** A missing-field leaf: no `__fieldName`, empty `vars.data`. */
    private static function emptyLeaf(): EcCubeStub
    {
        return new EcCubeStub(['vars' => new EcCubeStub(['data' => ''])]);
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
        $twig->addFilter(new TwigFilter('nl2br', static fn (string $s): string => nl2br($s), ['is_safe' => ['html']]));
        $twig->addFilter(new TwigFilter('number_format', static fn ($n): string => number_format((float) $n)));
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));
        $twig->addFilter(new TwigFilter('filter', static fn ($it, $f): array => []));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));

        // FORM-PAGE recipe: EC-CUBE's `form_widget(form.<field>,
        // { type : 'hidden' })` for the four MODELLED fields delegates to
        // BeMart's real ContactConfirmForm so the hidden carriers are
        // byte-identical. The missing kana / address / phone leaves carry
        // no `__fieldName` — the stub emits nothing for them.
        $confirmForm = (new FormFactory())->newInstance(ContactConfirmForm::class);
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($confirmForm): Markup {
            if ($field === '__token__') {
                return new Markup('<input type="hidden" name="_token" value="">', 'UTF-8');
            }

            if ($field instanceof EcCubeStub) {
                $name = $field['__fieldName'];
                if ($confirmForm instanceof ContactConfirmForm && $name !== null) {
                    return new Markup($confirmForm->input((string) $name), 'UTF-8');
                }
            }

            return new Markup('', 'UTF-8');
        }));
        $twig->addFunction(new TwigFunction('form_label', static function ($f = '', $l = '', $o = []) use ($trans): Markup {
            $text = is_string($l) ? $trans($l) : '';

            return new Markup('<label class="ec-label">' . $text . '</label>', 'UTF-8');
        }));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_rest', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_row', static fn ($f = '', $o = []): string => '[form_row]'));
        $twig->addFunction(new TwigFunction('has_errors', static fn (...$f): bool => false));
    }

    /** @return list<string> */
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
