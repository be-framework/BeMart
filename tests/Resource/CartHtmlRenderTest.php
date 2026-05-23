<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\HtmlModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function array_diff;
use function array_filter;
use function array_values;
use function dirname;
use function explode;
use function http_build_query;
use function implode;
use function is_dir;
use function preg_replace;
use function trim;

/**
 * Phase 3 — fidelity check for the Cart HTML port.
 *
 * The corrected Phase 3 standard: BeMart's storefront templates are PORTS
 * of EC-CUBE 4.3's default-theme Twig templates. ALPS deliberately carries
 * no presentation, so grading the HTML against ALPS would grade markup
 * against a spec that is silent on markup. The honest reference is
 * EC-CUBE's own templates.
 *
 * Phase 3 Step 2 (this revision): the cart ROW is now a faithful
 * reproduction. The earlier port could only render the bare productCode
 * because the ALPS `CartItem` descriptor had been back-formed from a
 * deliberately-thin Entity. `CartItem` has since been re-derived from
 * EC-CUBE's cart screen — it composes productName / mainImage /
 * classCategoryName / productId / productClassId — and CartItemEntity,
 * SqlCartQuery's JOIN and FakeCartQuery's read-side enrichment carry the
 * fields through. The render-diff residual fell from ~16 lines to 11,
 * all of which are now genuinely EC-CUBE-runtime-only.
 *
 * This test therefore does NOT just assert "body data appears". It:
 *
 *   1. renders EC-CUBE's real `Cart/index.twig` + `default_frame.twig`
 *      (from the gitignored 4.3 clone) through a standalone Twig env with
 *      EC-CUBE's functions/filters stubbed (trans -> ja literal,
 *      is_granted -> false, asset/url/path -> deterministic, price ->
 *      JPY NumberFormatter, frame block-includes -> empty);
 *   2. feeds it the SAME logical cart as BeMart's Fake fixture;
 *   3. renders BeMart's ported `Cart.html.twig` via the `html` context;
 *   4. line-diffs the two (whitespace-collapsed);
 *   5. asserts every differing line is in {@see RESIDUAL_ALLOWLIST} — an
 *      enumerated, justified set of differences that genuinely cannot be
 *      matched because BeMart's resource body does not carry the data, or
 *      the difference is an EC-CUBE-runtime-only artefact.
 *
 * If BeMart's markup structurally diverges from EC-CUBE's beyond that
 * allowlist, the diff contains an unexplained line and the test FAILS.
 * The allowlist is the honesty metric: it is the exhaustive, reviewed
 * list of what could not be matched and why.
 */
final class CartHtmlRenderTest extends TestCase
{
    /**
     * EC-CUBE lines that legitimately have no BeMart counterpart, plus
     * BeMart lines with no EC-CUBE counterpart. Each entry is a
     * whitespace-collapsed line; the comment states WHY it is acceptable.
     *
     * @var list<string>
     */
    private const RESIDUAL_ALLOWLIST = [
        // --- frame: EC-CUBE-runtime-only <head> nodes -------------------
        // EC-CUBE emits a live CSRF token; BeMart has no per-request CSRF
        // widget in the html context, so the meta is rendered empty.
        '<meta name="eccube-csrf-token" content="">',
        // EC-CUBE's default_frame.twig has an inline <script> that wires
        // the CSRF token into jQuery's $.ajaxSetup default headers. BeMart
        // has no CSRF token and no jQuery-coupled AJAX layer, so the
        // ported base.html.twig omits the script. EC-CUBE-runtime only.
        '<script>',
        '$(function() {',
        '$.ajaxSetup({',
        "'headers': {",
        '}',
        '});',
        '});',
        '</script>',
        // EC-CUBE's <title> is "<shop_name> / <page title>" with the
        // shop name from BaseInfo; BeMart's html context has no BaseInfo,
        // so the shop name differs ("BeMart" vs the stub's "EC-CUBE").
        // Same composition, different shop-name source.
        '<title>BeMart / ショッピングカート</title>',
        '<title>EC-CUBE / ショッピングカート</title>',
        // EC-CUBE injects meta.twig (description/keywords/OGP). BeMart has
        // no Page entity; the include renders nothing.
        '<meta name="author" content="">',
        // viewport/charset/favicon/css/js lines are identical and match.

        // --- frame: configurable layout blocks --------------------------
        // EC-CUBE's <header>/<footer> wrap include('block.twig', ...) for
        // operator-configured blocks (logo, nav, search, cart icon...).
        // BeMart has no block system; the wrappers are kept but empty.
        // The block.twig include is stubbed empty here too, so on the
        // EC-CUBE side these are also empty — no residual from blocks.

        // --- cart page: delivery-fee-free progress ----------------------
        // EC-CUBE's ec-cartRole__progress shows a "あと N 円で送料無料"
        // message computed from BaseInfo thresholds. BeMart's body has no
        // BaseInfo, so the block is kept (structure) but empty. No diff
        // line: both sides emit the empty block (BaseInfo thresholds are
        // null in the EC-CUBE stub too).

        // NOTE — Phase 3 Step 2: the cart ROW now matches structurally.
        // The corrected ALPS `CartItem` descriptor (re-derived from
        // EC-CUBE's Cart/index.twig, no longer back-formed from the thin
        // Entity) composes productName / mainImage / classCategoryName /
        // productId / productClassId; CartItemEntity, SqlCartQuery's JOIN
        // and FakeCartQuery's read-side enrichment carry them through, so
        // the product thumbnail, the product-detail link, the linked
        // product name and the variation axes are all reproduced. The
        // former image / product_detail / ClassCategory / ec-cartRow__name
        // / cart_handle_item-keying residual entries are therefore GONE.
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

    public function testCartPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        // A real HTML document ported from default_frame.twig.
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testCartPagePreservesEcCubeMarkupStructure(): void
    {
        $post = $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 3,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $post->code);

        $html = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ])->toString();

        // The verbatim EC-CUBE markup skeleton survived the port.
        foreach ([
            '<div class="ec-cartRole">',
            '<ul class="ec-progress">',
            'class="ec-progress__item is-complete"',
            '<div class="ec-cartRole__totalText">',
            '<form name="form" id="form_cart" class="ec-cartRole" method="post"',
            '<div class="ec-cartTable">',
            '<ol class="ec-cartHeader">',
            '<ul class="ec-cartRow">',
            'class="ec-cartRow__delColumn"',
            'class="ec-cartRow__contentColumn"',
            'class="ec-cartRow__amountColumn"',
            'class="ec-cartRow__subtotalColumn"',
            'class="ec-blockBtn--action"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered cart against EC-CUBE's own
     * rendering of the same logical cart. Every difference must be in the
     * enumerated residual allowlist.
     */
    public function testCartHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        // Same logical cart on both sides: one normal-sale cart with one
        // item (sample-001 x3 @ ￥1,200) plus one empty reservation cart.
        $this->resource->post('page://self/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => 3,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $beMart = $this->resource->get('page://self/cart', [
            'sessionPrefix' => 'session-prefix-1',
        ])->toString();

        $ecCube = $this->renderEcCubeCart();

        $beMartLines = $this->normalize($beMart);
        $ecCubeLines = $this->normalize($ecCube);

        // Lines in EC-CUBE's output absent from BeMart's, and vice versa.
        $onlyInEcCube = array_values(array_diff($ecCubeLines, $beMartLines));
        $onlyInBeMart = array_values(array_diff($beMartLines, $ecCubeLines));

        $unexplained = array_values(array_filter(
            [...$onlyInEcCube, ...$onlyInBeMart],
            static fn (string $line): bool => ! self::isResidual($line),
        ));

        $this->assertSame(
            [],
            $unexplained,
            "BeMart's cart HTML diverged from EC-CUBE's beyond the residual "
            . "allowlist. Unexplained diff lines:\n  " . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ", only-in-BeMart: " . count($onlyInBeMart) . ')',
        );

        // Sanity: the diff is genuinely small — the bulk of the markup
        // matches. Phase 3 Step 2 cut it to 11 lines (a 1-line title
        // shop-name difference + the 9-line EC-CUBE jQuery-CSRF
        // <script>; was ~16 when the cart row could not render
        // name/image/link/variation). If this balloons, the port has
        // drifted.
        $this->assertLessThan(
            16,
            count($onlyInEcCube) + count($onlyInBeMart),
            'residual diff unexpectedly large — port may have drifted',
        );
    }

    /**
     * A line is "residual" if it is an exact allowlist entry, or it
     * belongs to one of the few structurally-explained families that
     * genuinely cannot match.
     *
     * Phase 3 Step 2 shrank this set sharply: the cart ROW is now a
     * faithful reproduction (image, product-detail link, linked name,
     * variation axes — all carried by the re-derived ALPS CartItem).
     * What remains is EC-CUBE-runtime-only <head> material plus the
     * CSRF anchor token, none of which BeMart's html context models.
     */
    private static function isResidual(string $line): bool
    {
        foreach (self::RESIDUAL_ALLOWLIST as $allowed) {
            if ($line === $allowed) {
                return true;
            }
        }

        // Genuinely-unmatched families (EC-CUBE runtime artefacts only).
        foreach ([
            // EC-CUBE adds csrf_token_for_anchor() to the up/down/remove
            // <a> tags. BeMart's html context has no per-request CSRF
            // widget, so the attribute is absent. EC-CUBE-runtime only.
            'csrf_token',                            // CSRF anchor token
            'eccube-csrf-token',                     // <head> CSRF meta
            '<title>',                               // shop title composition
            'meta name="author"',                    // meta.twig include
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render EC-CUBE 4.3's real Cart/index.twig + default_frame.twig from
     * the gitignored clone, with EC-CUBE's Twig API stubbed.
     */
    private function renderEcCubeCart(): string
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

        // The same logical cart as BeMart's html-context fixture: the
        // sample-001 item, name resolved through the product-class Fake
        // (サンプル商品 A). The Fake write path (CartMerged) does not
        // carry the surrogate ids, so productClassId / productId surface
        // as 0 on both sides; the product has no image (→ no_image
        // fallback) and no variation. The corrected ALPS CartItem
        // descriptor means BeMart's cart row now reproduces EC-CUBE's
        // thumbnail, product-detail link and (absent here) variation.
        $normalItem = new EcCubeStub([
            'price' => 1200,
            'quantity' => 3,
            'total_price' => 3600,
            'ProductClass' => new EcCubeStub([
                'id' => 0,
                'Product' => new EcCubeStub([
                    'id' => 0,
                    'name' => 'サンプル商品 A',
                    'MainListImage' => null,
                ]),
                'ClassCategory1' => null,
                'ClassCategory2' => null,
            ]),
        ]);
        $normalCart = new EcCubeStub([
            'cart_key' => 'session-prefix-1_1',
            'CartItems' => [$normalItem],
            'totalPrice' => 3600,
        ]);
        $reservationCart = new EcCubeStub([
            'cart_key' => 'session-prefix-1_2',
            'CartItems' => [],
            'totalPrice' => 0,
        ]);

        return $twig->render('Cart/index.twig', [
            'totalPrice' => 3600,
            'totalQuantity' => 3,
            'Carts' => [$normalCart, $reservationCart],
            'least' => [],
            'quantity' => [],
            'is_delivery_free' => [],
            'BaseInfo' => new EcCubeStub([
                'shop_name' => 'EC-CUBE',
                'delivery_free_amount' => null,
                'delivery_free_quantity' => null,
            ]),
            'eccube_config' => ['locale' => 'ja'],
            'Page' => new EcCubeStub([
                'meta_tags' => '',
                'description' => '',
                'author' => '',
                'keyword' => '',
                'meta_robots' => '',
            ]),
            // A default EC-CUBE install configures Header/Footer/Drawer
            // layout regions (logo, nav, footer links...). The regions
            // are set truthy so EC-CUBE emits the <header>/<footer>/drawer
            // WRAPPERS (which BeMart's base.html.twig keeps); their block
            // CONTENT is rendered via the empty-stubbed block.twig, so
            // both sides emit the wrappers empty. Side/top/bottom regions
            // stay null — BeMart's base.html.twig has no such regions.
            'Layout' => new EcCubeStub([
                'Head' => null, 'BodyAfter' => null, 'Header' => [new EcCubeStub(['file_name' => 'logo'])],
                'ContentsTop' => null, 'SideLeft' => null, 'SideRight' => null,
                'MainTop' => null, 'MainBottom' => null, 'ContentsBottom' => null,
                'Footer' => [new EcCubeStub(['file_name' => 'footer'])], 'Drawer' => [0 => 'x'], 'CloseBodyBefore' => null,
                'ColumnNum' => 1,
            ]),
            'app' => new EcCubeStub(['session' => new EcCubeStub([
                'flashbag' => new EcCubeFlashBag(),
            ]), 'request' => new EcCubeStub(['_route' => 'cart'])]),
            'subtitle' => 'ショッピングカート',
            'title' => 'ショッピングカート',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig): void
    {
        // trans -> the ja literal (the lower-noise rebinding choice, see
        // base.html.twig header). With %placeholders% substituted.
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
        // EC-CUBE's no_image_product filter substitutes the shared
        // placeholder image when the product has no MainListImage. The
        // ported Cart.html.twig uses `|default('assets/img/common/
        // no_image_product.png')` for the same effect, so this stub
        // returns the same path for a falsy value.
        $twig->addFilter(new TwigFilter(
            'no_image_product',
            static fn ($s): string => $s ? (string) $s : 'assets/img/common/no_image_product.png',
        ));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        // Symfony's twig/string-extra helper, used by default_frame.twig
        // for Page.meta_tags. Page has no meta tags here so it never runs,
        // but the function must exist for the template to parse. Twig's
        // built-in `include` is left intact — EcCubeStubLoader resolves
        // every frame include (meta.twig/block.twig/...) to an empty
        // template.
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));
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
