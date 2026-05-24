<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Module\HtmlTestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Twig\Environment;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

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
use function preg_replace;
use function str_contains;
use function str_replace;
use function trim;

/**
 * Phase 3 — fidelity check for the Shopping shipping (goShoppingShipping)
 * HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * `Shopping/shipping.twig` is a DATA page — a radio list of the
 * customer's registered shipping addresses. The Shipping resource
 * (src/Resource/Page/Shopping/Shipping.php) is a Wave 3H pure renderer
 * with an empty `addresses` list (the address-book lookup is a
 * Wave-future TODO). This test feeds EC-CUBE's shipping.twig an empty
 * choice list + an empty `Customer.CustomerAddresses`, so both sides
 * render the add-new-address branch + the empty `ec-addressList`
 * skeleton. The residual is the genuinely EC-CUBE-runtime-only `<head>`
 * frame material + the empty CSRF hidden + the `shippingId` route param.
 */
final class ShoppingShippingHtmlRenderTest extends TestCase
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
        '<title>BeMart / お届け先の指定</title>',
        '<title>EC-CUBE / お届け先の指定</title>',
        '<meta name="author" content="">',
        // --- form: CSRF hidden input ------------------------------------
        '<input type="hidden" name="_token" value="">',
        // --- form action / add-new link: the shippingId route param ----
        // EC-CUBE's `url('shopping_shipping', {'id': shippingId})` and
        // `url('shopping_shipping_edit', {'id': shippingId})` append the
        // shipping id. The Shipping resource is a Wave-future pure
        // renderer with no per-shipping context — `shippingId` is a
        // MISSING BODY FIELD follow-up — so the port posts to / links
        // the bare routes. Same routes, the id param absent.
        '<div class="ec-addressRole__actions"><a class="ec-inlineBtn" href="/shopping_shipping_edit?id=1">新規お届け先を追加する</a></div>',
        '<div class="ec-addressRole__actions"><a class="ec-inlineBtn" href="/shopping_shipping_edit">新規お届け先を追加する</a></div>',
        '<form method="post" action="/shopping_shipping?id=1">',
        '<form method="post" action="/shopping_shipping">',
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

    public function testShippingRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testShippingPreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping')->toString();

        foreach ([
            '<h1>お届け先の指定</h1>',
            '<div class="ec-registerRole">',
            '<div class="ec-addressRole">',
            'class="ec-addressRole__actions"',
            '<div class="ec-addressList">',
            'class="ec-registerRole__actions"',
            'class="ec-blockBtn--action"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    public function testShippingHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/shopping/shipping')->toString();
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
            "BeMart's Shopping shipping HTML diverged from EC-CUBE's "
            . "beyond the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // 15 residual lines: the shared <head> frame material + the
        // empty CSRF hidden + the 4-line `shippingId` route-param family
        // (form action + add-new link, EC-CUBE vs BeMart). All
        // allowlisted; if this balloons the port has drifted.
        $this->assertLessThanOrEqual(
            16,
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

        return $twig->render('Shopping/shipping.twig', [
            // Empty address book — the add-new branch + empty
            // ec-addressList skeleton, exactly the pure-renderer port.
            'Customer' => new EcCubeStub(['CustomerAddresses' => []]),
            'form' => new EcCubeStub([
                '_token' => '__token__',
                'addresses' => new EcCubeStub([
                    'vars' => new EcCubeStub(['choices' => [], 'value' => null, 'full_name' => 'addresses']),
                ]),
            ]),
            'shippingId' => 1,
            'eccube_config' => ['locale' => 'ja', 'eccube_deliv_addr_max' => 20],
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
            ]), 'request' => new EcCubeStub(['_route' => 'shopping_shipping'])]),
            'subtitle' => 'お届け先の指定',
            'title' => 'お届け先の指定',
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

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []): Markup {
            if ($field === '__token__') {
                return new Markup('<input type="hidden" name="_token" value="">', 'UTF-8');
            }

            return new Markup('', 'UTF-8');
        }));
        $twig->addFunction(new TwigFunction('form_label', static fn ($f = '', $l = '', $o = []): string => ''));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('has_errors', static fn (...$f): bool => false));
        // `selectedchoice` is the symfony/twig-bridge form test (not
        // installed). shipping.twig uses it inside the per-address radio
        // loop; with an empty choice list the loop body never runs, so
        // the test is a no-op — registered only so the template parses.
        $twig->addTest(new TwigTest('selectedchoice', static fn ($choice, $value = null): bool => false));
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
