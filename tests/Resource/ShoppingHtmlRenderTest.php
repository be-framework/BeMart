<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Form\ShoppingOrderForm;
use MyVendor\BeMart\Module\HtmlTestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
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
use function trim;

/**
 * Phase 3 — fidelity check for the Shopping checkout (goShopping) HTML
 * port.
 *
 * Same standard as {@see CartHtmlRenderTest}: BeMart's storefront
 * templates are PORTS of EC-CUBE 4.3's default-theme Twig.
 *
 * `Shopping/index.twig` is the most complex storefront FORM page — the
 * checkout screen: shipping-address review, delivery-method / -date /
 * -time selects, the payment-method radio group, the order-message
 * textarea. This port follows the Ray.WebFormModule form-page recipe:
 * the Shopping resource exposes a real {@see ShoppingOrderForm} (an
 * AbstractForm) as `body.form`; this test renders EC-CUBE's
 * `form_widget(form.message)` / `form_widget(form.Shippings[0].Delivery)`
 * calls through the SAME ShoppingOrderForm so the message textarea + the
 * delivery selects diff to ZERO.
 *
 * 厳密移植 SCOPE — the Shopping body is the checkout REVIEW projection
 * (customer name / email / default shipping address / carts / payment
 * methods / totals); EC-CUBE's index.twig reads off a fully-aggregated
 * `Order` entity. This test feeds EC-CUBE the SAME logical data the body
 * carries, with `is_granted` stubbed TRUE (member checkout — matches the
 * member-path port). The residual is therefore:
 *
 *  - the shared EC-CUBE-runtime-only `<head>` frame material;
 *  - the index.twig `{% block javascript %}` — the redirect-to /
 *    customer-edit AJAX. EC-CUBE client-side behaviour depending on
 *    EC-CUBE's bundled JS / per-request CSRF; EC-CUBE-runtime only;
 *  - MISSING BODY FIELD families: the per-shipping order-item rows
 *    (`shipping.productOrderItems`), the shipping address's name / kana
 *    (`defaultShippingAddress` carries postal / pref / addr / phone
 *    only), and the `shippingId` route param on the change button. A
 *    `doConfirmOrder`-grade Order aggregation is the fix — tracked in
 *    the enrichment backlog;
 *  - the payment-method radios: the port renders them from
 *    `body.paymentMethods`; EC-CUBE renders the Symfony `form.Payment`
 *    EntityType. Same data, different rendering mechanism — the port's
 *    radio lines are an enumerated residual.
 */
final class ShoppingHtmlRenderTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    /** @var list<string> */
    private const RESIDUAL_ALLOWLIST = [
        // --- frame: EC-CUBE-runtime-only <head> nodes (shared) ----------
        '<meta name="eccube-csrf-token" content="">',
        '<title>BeMart / ご注文手続き</title>',
        '<meta name="author" content="">',

        // --- delivery: the change button / title (MISSING BODY FIELD) ---
        // EC-CUBE's `お届け先` title + a `data-id`/`data-trigger`/
        // `data-path` change button keyed by the shipping id; the body
        // carries no `shippingId`, so the port renders a plain button
        // and the title without the inline-collapsed wrapper.
        '<div class="ec-orderDelivery__title">お届け先 <div class="ec-orderDelivery__change">',
        '<div class="ec-orderDelivery__title">お届け先',
        '<div class="ec-orderDelivery__change">',
        '<button class="ec-inlineBtn" data-id="1" data-trigger="click" data-path="/shopping_shipping?id=1">変更</button>',
        '<button class="ec-inlineBtn" type="button">変更</button>',

        // --- delivery: the per-shipping order-item rows (MISSING BODY) --
        // EC-CUBE iterates `shipping.productOrderItems`; the body carries
        // no order-item breakdown, so the `ec-orderDelivery__item` /
        // `ec-borderedList` wrappers are omitted.
        '<div class="ec-orderDelivery__item">',
        '<ul class="ec-borderedList">',
        '<p></p>',

        // --- delivery: the shipping address name / kana (MISSING BODY) --
        // EC-CUBE renders `name01 name02 (kana01 kana02) 様`;
        // `defaultShippingAddress` carries no name / kana, so the port
        // renders the customer's top-level name without the kana paren.
        '<p>山田 アリス ( ) 様</p>',
        '<p>山田 アリス 様</p>',

        // --- payment: radios rendered from body.paymentMethods ----------
        // EC-CUBE renders the Symfony `form.Payment` EntityType radios;
        // the port renders them from `body.paymentMethods`. Same payment
        // methods, different rendering mechanism.
        '<div style="display: block;">',
        '<label>',
        '<input type="radio" name="payment" value="1">',
        '代金引換',
        '</label>',
        '<input type="radio" name="payment" value="2">',
        'クレジットカード',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlTestModule($meta);
        $session = new FakeSession(self::ALICE_ID);
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(SessionInterface::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testShoppingRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/shopping');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="ec-layoutRole">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testShoppingPreservesEcCubeMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/shopping')->toString();

        foreach ([
            '<h1>ご注文手続き</h1>',
            '<ul class="ec-progress">',
            // Slice 9: url('shopping_confirm') now resolves through RouteTable.
            '<form id="shopping-form" method="post" action="/shopping/confirm">',
            '<div class="ec-orderRole">',
            '<div class="ec-orderAccount">',
            '<div class="ec-orderDelivery">',
            '<div class="ec-orderPayment">',
            '<div class="ec-radio">',
            '<div class="ec-orderConfirm">',
            '<div class="ec-totalBox">',
            'class="ec-blockBtn--action"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /** The checkout form inputs are rendered by a real form library. */
    public function testShoppingRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/shopping')->toString();

        $this->assertStringContainsString('name="message"', $html);
        $this->assertStringContainsString('name="delivery"', $html);
        $this->assertStringContainsString('name="payment"', $html);
    }

    public function testShoppingHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/shopping')->toString();
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
            "BeMart's Shopping checkout HTML diverged from EC-CUBE's "
            . "beyond the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );
    }

    /**
     * A diff line is acceptable if it is an exact allowlist entry, a
     * frame residual family, or part of the contiguous EC-CUBE-runtime
     * `{% block javascript %}` of index.twig.
     */
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

        return self::isJsBlockLine($line);
    }

    /**
     * Lines belonging to index.twig's EC-CUBE-runtime `{% block
     * javascript %}` — the redirect-to wiring + the anonymous
     * customer-edit AJAX + the frame's `$.ajaxSetup`. One contiguous
     * client-side region with no data-structure content.
     */
    private static function isJsBlockLine(string $line): bool
    {
        if (in_array($line, ['<script>', '</script>', '}', '};', '}, 2000);'], true)) {
            return true;
        }

        foreach ([
            '$(', '$.', '});', '})', 'function', 'var ', 'if (',
            '} else {', 'return', 'setTimeout', 'loadingOverlay',
            'redirect_to', '.val(', '.attr(', '.submit()', '.on(',
            '.each(', '.click(', '.children(', '.empty()', '.append(',
            '.text(', '.hide()', '.show()', '.length', '.ajax(',
            'postData', 'originalPrefValue', 'ECCUBE-CSRF-TOKEN',
            "'headers'", 'pageshow', 'event', '//', 'data.status',
        ] as $fragment) {
            if (str_contains($line, $fragment)) {
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

        // Same logical data as the Shopping body (alice, default
        // shipping, ￥0 carts). The shipping carries postal / pref / addr
        // / phone (pref the raw id 13, exactly the body) but no name /
        // kana — the order-item rows + shipping name are MISSING BODY
        // FIELD residuals.
        $shipping = new EcCubeStub([
            'id' => 1,
            'name01' => '山田', 'name02' => 'アリス', 'kana01' => '', 'kana02' => '',
            'postal_code' => '1500001', 'pref' => 13,
            'addr01' => '渋谷区', 'addr02' => '神宮前1-1-1', 'phone_number' => '0312345678',
            'productOrderItems' => [], 'order_items' => [],
        ]);
        $order = new EcCubeStub([
            'name01' => '山田', 'name02' => 'アリス', 'kana01' => '', 'kana02' => '',
            'companyName' => '', 'postal_code' => '1500001', 'pref' => 13,
            'addr01' => '渋谷区', 'addr02' => '神宮前1-1-1', 'phone_number' => '0312345678',
            'email' => 'alice@example.com',
            'shippings' => [$shipping], 'multiple' => false, 'order_items' => [],
            'subtotal' => 0, 'charge' => 0, 'deliveryFeeTotal' => 0,
            'taxable_discount' => 0, 'taxable_total' => 0,
            'tax_free_discount_items' => [], 'payment_total' => 0,
            'total_by_tax_rate' => [], 'Customer' => null,
            'Shippings' => [new EcCubeStub([])],
        ]);

        return $twig->render('Shopping/index.twig', [
            'Order' => $order,
            'form' => new EcCubeStub([
                '_token' => '__token__',
                'redirect_to' => '__redirect__',
                'Shippings' => [new EcCubeStub([
                    'Delivery' => 'delivery',
                    'shipping_delivery_date' => 'shipping_delivery_date',
                    'DeliveryTime' => 'delivery_time',
                ])],
                // form.Payment iterated empty — the port renders the
                // radios from body.paymentMethods instead (allowlisted).
                'Payment' => new EcCubeStub(['vars' => new EcCubeStub(['choices' => []])], []),
                'use_point' => 'use_point',
                'message' => 'message',
            ]),
            'activeTradeLaws' => [],
            'BaseInfo' => new EcCubeStub(['isOptionPoint' => false]),
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
            ]), 'request' => new EcCubeStub(['_route' => 'shopping'])]),
            'subtitle' => 'ご注文手続き',
            'title' => 'ご注文手続き',
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
        $twig->addFilter(new TwigFilter('purify', static fn (string $s): string => $s));
        $twig->addFilter(new TwigFilter('no_image_product', static fn ($s): string => $s ? (string) $s : 'assets/img/common/no_image_product.png'));

        $twig->addFunction(new TwigFunction('trans', $trans));
        // Member checkout — matches the member-path port.
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => true));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('template_from_string', static fn (string $s): string => $s));
        $twig->addFunction(new TwigFunction('is_reduced_tax_rate', static fn ($x = null): bool => false));

        // FORM-PAGE recipe: `form_widget(form.message)` /
        // `form_widget(form.Shippings[0].Delivery)` delegate to BeMart's
        // real ShoppingOrderForm so the message textarea + delivery
        // selects diff to ZERO.
        $form = (new FormFactory())->newInstance(ShoppingOrderForm::class);
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($form): Markup {
            if ($field === '__token__') {
                return new Markup('<input type="hidden" name="_token" value="">', 'UTF-8');
            }

            if ($field === '__redirect__') {
                return new Markup('<input type="hidden" id="shopping_order_redirect_to" name="redirect_to" value="">', 'UTF-8');
            }

            if ($form instanceof ShoppingOrderForm && is_string($field) && $field !== '') {
                return new Markup($form->input($field), 'UTF-8');
            }

            return new Markup('', 'UTF-8');
        }));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_label', static fn ($f = '', $l = '', $o = []): string => ''));
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
