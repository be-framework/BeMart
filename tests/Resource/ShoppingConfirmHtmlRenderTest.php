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
use function is_dir;
use function preg_replace;
use function str_contains;
use function str_replace;
use function trim;

/**
 * Phase 3 — fidelity check for the Shopping confirm (goShoppingConfirm)
 * HTML port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * `Shopping/confirm.twig` is a DATA page (+ a checkout submit form /
 * button) — the order-review screen the customer confirms before
 * `doCheckout`. The Confirm resource
 * (src/Resource/Page/Shopping/Confirm.php) is a thin pure renderer (a
 * NEW resource — EC-CUBE's `doConfirmOrder` → `ShoppingConfirm` flow had
 * no BEAR resource backing it; Pilot 5 collapsed the flow). The confirm
 * screen reads off a fully-aggregated `Order` entity; the Confirm
 * resource's body carries NONE of it (a `doConfirmOrder` Be Becoming
 * chain producing an `OrderConfirmed` Final is an enrichment-backlog
 * item — see the resource docblock). This test feeds EC-CUBE's
 * confirm.twig an EMPTY `Order` so both sides render the empty
 * order-detail loops + empty-scalar cells, and `is_granted` is stubbed
 * TRUE (member checkout) to match the member-path port. The residual is
 * the genuinely EC-CUBE-runtime-only `<head>` frame material.
 *
 * MISSING BODY FIELD follow-up: the aggregated `Order` projection
 * (shippings / orderItems / payment / the tax-rate-broken-down totals).
 * With both sides fed an empty Order the order-detail loops emit
 * nothing and the totals show ￥0 — the difference contributes nothing
 * to the diff; it is recorded here as the page's known data gap.
 */
final class ShoppingConfirmHtmlRenderTest extends TestCase
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
        '<title>BeMart / ご注文内容のご確認</title>',
        '<title>EC-CUBE / ご注文内容のご確認</title>',
        '<meta name="author" content="">',
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

    public function testShoppingConfirmRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/shopping/confirm');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testShoppingConfirmPreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/shopping/confirm')->toString();

        foreach ([
            '<h1>ご注文内容のご確認</h1>',
            '<ul class="ec-progress">',
            '<form id="shopping-form" method="post" action="/shopping_checkout">',
            '<div class="ec-orderRole">',
            '<div class="ec-orderAccount">',
            '<div class="ec-orderDelivery">',
            '<div class="ec-orderPayment">',
            '<div class="ec-orderConfirm">',
            '<div class="ec-totalBox">',
            'class="ec-blockBtn--action"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    public function testShoppingConfirmHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/shopping/confirm')->toString();
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
            "BeMart's Shopping confirm HTML diverged from EC-CUBE's "
            . "beyond the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        $this->assertLessThan(
            14,
            count($onlyInEcCube) + count($onlyInBeMart),
            'residual diff unexpectedly large — port may have drifted',
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

        return $twig->render('Shopping/confirm.twig', [
            // Empty Order — the confirm screen reads a fully-aggregated
            // Order; the thin-renderer port carries none of it, so both
            // sides render the empty order-detail loops + ￥0 totals.
            'Order' => new EcCubeStub([
                'shippings' => [],
                'order_items' => [],
                'tax_free_discount_items' => [],
                'total_by_tax_rate' => [],
            ]),
            'form' => new EcCubeStub(['_token' => '__token__']),
            'activeTradeLaws' => [],
            'BaseInfo' => new EcCubeStub(['isOptionPoint' => false]),
            'eccube_config' => ['locale' => 'ja'],
            'Page' => new EcCubeStub([
                'meta_tags' => '', 'description' => '', 'author' => '',
                'keyword' => '', 'meta_robots' => '',
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
            ]), 'request' => new EcCubeStub(['_route' => 'shopping_confirm'])]),
            'subtitle' => 'ご注文内容のご確認',
            'title' => 'ご注文内容のご確認',
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
        $twig->addFilter(new TwigFilter(
            'nl2br',
            static fn ($s): string => nl2br((string) $s),
            ['is_safe' => ['html']],
        ));
        $twig->addFilter(new TwigFilter('number_format', static fn ($n): string => number_format((float) $n)));
        $twig->addFilter(new TwigFilter('price', static function ($n): string {
            $f = new \NumberFormatter('ja_JP', \NumberFormatter::CURRENCY);

            return (string) $f->formatCurrency((float) ($n ?? 0), 'JPY');
        }));
        $twig->addFilter(new TwigFilter('purify', static fn (string $s): string => $s));
        $twig->addFilter(new TwigFilter('no_image_product', static fn ($s): string => $s ? (string) $s : 'assets/img/common/no_image_product.png'));
        $twig->addFilter(new TwigFilter('date_day_with_weekday', static fn ($s): string => (string) $s));

        $twig->addFunction(new TwigFunction('trans', $trans));
        // Member checkout — matches the member-path port.
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => true));
        $twig->addFunction(new TwigFunction('asset', static fn (string $p, $x = null): string => '/' . $p));
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
        $twig->addFunction(new TwigFunction('is_reduced_tax_rate', static fn ($x = null): bool => false));
        // EC-CUBE's `form_widget(form._token)` — the CSRF hidden. Stubbed
        // to the same empty-value `_token` hidden BeMart's port emits.
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []): Markup {
            if ($field === '__token__') {
                return new Markup('<input type="hidden" name="_token" value="">', 'UTF-8');
            }

            return new Markup('', 'UTF-8');
        }));
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
