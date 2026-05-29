<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Form\ShoppingShippingEditForm;
use MyVendor\BeMart\Module\HtmlTestModule;
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
use function str_replace;
use function str_starts_with;
use function trim;

/**
 * Phase 3 — fidelity check for the Shopping shipping-edit
 * (goShoppingShippingEdit) HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * `Shopping/shipping_edit.twig` is a FORM page. This port follows the
 * Ray.WebFormModule form-page recipe (see var/templates/README.md): the
 * ShippingEdit resource exposes a real {@see ShoppingShippingEditForm}
 * (an AbstractForm) as `body.form`. This test renders EC-CUBE's
 * `form_widget(form.name.name01)` calls through the SAME form instance
 * so the inputs diff to ZERO; `form_label` is stubbed to the same
 * `<label class="ec-label">` BeMart authors plainly. `is_granted` is
 * stubbed FALSE — the not-a-member branch (the お届け先の変更 header +
 * back-to-shopping link), which the port renders. The residual is the
 * genuinely EC-CUBE-runtime-only `<head>` frame material + the empty
 * CSRF hidden value.
 */
final class ShoppingShippingEditHtmlRenderTest extends TestCase
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
        '<title>BeMart / 商品購入/お届け先の変更</title>',
        '<title>EC-CUBE / 商品購入/お届け先の変更</title>',
        '<meta name="author" content="">',
        // --- form: CSRF hidden input ------------------------------------
        '<input type="hidden" name="_token" value="">',
        // --- form action: the shippingId route param --------------------
        // EC-CUBE's `url('shopping_shipping_edit', {'id': shippingId})`
        // appends the editing shipping's id (`?id=1`). The ShippingEdit
        // resource is a Wave-future pure renderer with no per-shipping
        // context — `shippingId` is a MISSING BODY FIELD follow-up — so
        // the port posts to the bare route. Same route, the id param
        // absent. Identical for the back-link below.
        '<form method="post" action="/shopping/shipping/edit?id=1" class="h-adr">',
        '<form method="post" action="/shopping/shipping/edit" class="h-adr">',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $injector = new Injector(
            new HtmlTestModule($meta),
            dirname(__DIR__, 2) . '/var/tmp/html',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testShippingEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping-edit');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testShippingEditPreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-edit')->toString();

        foreach ([
            '<h1>お届け先の変更</h1>',
            '<div class="ec-registerRole">',
            '<div class="ec-borderedDefs">',
            'class="ec-halfInput"',
            'class="ec-zipInput"',
            'class="ec-telInput"',
            'class="ec-RegisterRole__actions"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /** The address inputs are rendered by a real form library. */
    public function testShippingEditRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-edit')->toString();

        $this->assertStringContainsString('name="name01"', $html);
        $this->assertStringContainsString('name="postalCode"', $html);
        $this->assertStringContainsString('name="phoneNumber"', $html);
    }

    public function testShippingEditHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/shopping/shipping-edit')->toString();
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
            "BeMart's Shopping shipping-edit HTML diverged from EC-CUBE's "
            . "beyond the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        $this->assertLessThanOrEqual(
            14,
            count($onlyInEcCube) + count($onlyInBeMart),
            'residual diff unexpectedly large — port may have drifted',
        );
    }

    private static function isResidual(string $line): bool
    {
        if (in_array($line, self::RESIDUAL_ALLOWLIST, true)) {
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

        if (str_starts_with($line, '<label class="ec-label">')) {
            return true;
        }

        return false;
    }

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

        return $twig->render('Shopping/shipping_edit.twig', [
            'form' => new EcCubeStub([
                'name' => new EcCubeStub(['name01' => 'name01', 'name02' => 'name02']),
                'kana' => new EcCubeStub(['kana01' => 'kana01', 'kana02' => 'kana02']),
                'company_name' => 'companyName',
                'postal_code' => 'postalCode',
                'address' => new EcCubeStub([
                    'pref' => 'pref', 'addr01' => 'addr01', 'addr02' => 'addr02',
                ]),
                'phone_number' => 'phoneNumber',
                '_token' => '__token__',
            ]),
            'shippingId' => 1,
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
            ]), 'request' => new EcCubeStub(['_route' => 'shopping_shipping_edit'])]),
            'subtitle' => '商品購入/お届け先の変更',
            'title' => '商品購入/お届け先の変更',
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
        $twig->addFilter(new TwigFilter('nl2br', static fn ($s): string => nl2br((string) $s), ['is_safe' => ['html']]));
        $twig->addFilter(new TwigFilter('number_format', static fn ($n): string => number_format((float) $n)));
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));
        $twig->addFilter(new TwigFilter('filter', static fn ($it, $f): array => []));

        $twig->addFunction(new TwigFunction('trans', $trans));
        // not-a-member branch (the お届け先の変更 header) — matches the port.
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));

        $form = (new FormFactory())->newInstance(ShoppingShippingEditForm::class);
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($form): Markup {
            if ($field === '__token__') {
                return new Markup('<input type="hidden" name="_token" value="">', 'UTF-8');
            }

            if ($form instanceof ShoppingShippingEditForm && is_string($field) && $field !== '') {
                return new Markup($form->input($field), 'UTF-8');
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
