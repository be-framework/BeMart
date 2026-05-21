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
 * `doCheckout`.
 *
 * Phase 3 ENRICHMENT — the Confirm resource
 * (src/Resource/Page/Shopping/Confirm.php) now drives the doConfirmOrder
 * Be Becoming chain (ConfirmOrderInput → … → OrderConfirmed) rather than
 * being a thin pure renderer. The body carries the full confirm-screen
 * projection: the customer-info block, the order's line items, the
 * payment method and the tax-inclusive totals. This test feeds EC-CUBE's
 * confirm.twig the SAME logical order (the alice confirm-screen pre-order
 * fixture `aceface…a11ce` — sample-001 ×2 @￥1,200 + 2026 春予約バッグ ×1
 * @￥13,500, クレジットカード) so the previously-empty order-detail loops
 * now diff to zero. `is_granted` is stubbed TRUE (member checkout) to
 * match the member-path port. The residual is the genuinely
 * EC-CUBE-runtime-only `<head>` frame material.
 *
 * Known data gap — the per-tax-rate breakdown rows: BeMart's PurchaseFlow
 * folds tax into one `tax` total, so the reduced-tax-rate mark and the
 * per-rate sub-rows are omitted; EC-CUBE is fed empty `total_by_tax_rate`
 * so neither side renders them and the diff stays zero.
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

        // The alice confirm-screen pre-order (orders.json `aceface…a11ce`):
        // sample-001 ×2 @￥1,200 + 2026 春予約バッグ ×1 @￥13,500. The
        // FakePurchaseFlow folds these to subtotal 15900 / tax 1590 /
        // delivery 800 / total 18290 / paymentTotal 18290 — fed identically
        // to BeMart's body so the order-detail loops + totals diff to zero.
        $items = [
            new EcCubeStub([
                'productName' => 'サンプル商品 A',
                'priceIncTax' => 1200,
                'quantity' => 2,
                'totalPrice' => 2400,
            ]),
            new EcCubeStub([
                'productName' => '2026 春予約バッグ',
                'priceIncTax' => 13500,
                'quantity' => 1,
                'totalPrice' => 13500,
            ]),
        ];
        // The confirm screen renders a single Shipping carrying the order's
        // items + the recipient address (alice's default address — the
        // BeMart confirm projection is single-shipping, EC-CUBE's
        // default-theme checkout norm).
        $shipping = new EcCubeStub([
            'productOrderItems' => $items,
            'name01' => '山田', 'name02' => 'アリス',
            'kana01' => 'ヤマダ', 'kana02' => 'アリス',
            'postal_code' => '1500001', 'pref' => 13,
            'addr01' => '渋谷区', 'addr02' => '神宮前1-1-1',
            'phone_number' => '0312345678',
        ]);

        return $twig->render('Shopping/confirm.twig', [
            'Order' => new EcCubeStub([
                'shippings' => [$shipping],
                'order_items' => $items,
                'tax_free_discount_items' => [],
                'total_by_tax_rate' => [],
                // customer-info block — alice's profile.
                'name01' => '山田', 'name02' => 'アリス',
                'kana01' => 'ヤマダ', 'kana02' => 'アリス',
                'companyName' => '', 'email' => 'alice@example.com',
                'phone_number' => '0312345678',
                'postal_code' => '1500001', 'pref' => 13,
                'addr01' => '渋谷区', 'addr02' => '神宮前1-1-1',
                // payment + tax-inclusive totals.
                'Payment' => 'クレジットカード',
                'subtotal' => 15900, 'charge' => 0, 'deliveryFeeTotal' => 800,
                'taxable_total' => 18290, 'payment_total' => 18290,
                'message' => '',
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
