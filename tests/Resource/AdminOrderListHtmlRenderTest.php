<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminOrderSearchForm;
use MyVendor\BeMart\Module\HtmlTestModule;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\OrderJaMessages;
use NumberFormatter;
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
use function trim;

/**
 * Phase 3 — fidelity check for the admin Order-list HTML port (the
 * Order section's DATA/LIST + search-form page).
 *
 * Same residual-diff standard as the admin pilot {@see AdminNewsListHtmlRenderTest}
 * and the Product wave {@see AdminProductListHtmlRenderTest}: BeMart's
 * templates are PORTS of EC-CUBE 4.3's admin Twig. The page extends
 * `admin-base.html.twig` (the port of EC-CUBE's admin-theme
 * `default_frame.twig`), served via {@see EcCubeAdminStubLoader}.
 *
 *   1. renders EC-CUBE's real `Order/index.twig` + admin frame +
 *      `Order/confirmationModal_js.twig`;
 *   2. renders BeMart's ported `OrderList.html.twig` via the html
 *      context with a seeded admin session;
 *   3. line-diffs the two (whitespace-collapsed);
 *   4. asserts every differing line is in {@see RESIDUAL_ALLOWLIST} or a
 *      residual family.
 *
 * The `multi` keyword box is rendered by a real {@see AdminOrderSearchForm}
 * on BOTH sides, so it diffs to ZERO.
 */
final class AdminOrderListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

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
        "'ECCUBE-CSRF-TOKEN': $('meta[name=\"eccube-csrf-token\"]').attr('content')",
        '}',
        '});',
        '});',
        '</script>',
        '<title>受注一覧 受注管理 - BeMart</title>',
        '<title>受注管理 受注一覧 - EC-CUBE</title>',
        // EC-CUBE's `searchDetail` collapse panel adds `{{ has_errors ?
        // ' show' }}` — BeMart's port has no `has_errors` body field (no
        // server-side search-error replay in the Wave 7 slice), so the
        // class list differs by the trailing ` show` toggle.
        '<div class="c-subContents ec-collapse collapse" id="searchDetail">',
        '<div class="c-subContents ec-collapse collapse " id="searchDetail">',
        // EC-CUBE's bulk form hidden CSRF input: `name` is the
        // `Constant::TOKEN_NAME` PHP constant (the `constant()` Twig fn
        // returns the constant NAME in the stub), the value is
        // `csrfcsrfToken(...)`. BeMart's port names it `csrfToken` literally
        // with an empty value — no per-request CSRF widget.
        '<input type="hidden" name="csrfToken" value="">',
        '<input type="hidden" name="Eccube\\Common\\Constant::TOKEN_NAME" value="">',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlTestModule($meta);
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOrderListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/order-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testOrderListPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/order-list')->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea">',
            '<div class="c-pageTitle">',
            'class="c-outsideBlock"',
            'id="searchDetail"',
            'class="c-contentsArea__cols"',
            'class="c-primaryCol"',
            'id="search_result"',
            'id="form_bulk"',
            'id="sentUpdateModal"',
            'id="bulkDeleteModal"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    public function testOrderListRendersSeededOrderRows(): void
    {
        $html = $this->resource->get('page://self/admin/order-list')->toString();

        $this->assertStringContainsString('id="admin_search_order_multi"', $html);
        $this->assertStringContainsString('検索結果：', $html);
        $this->assertStringContainsString('class="action-edit"', $html);
    }

    /**
     * The honesty test: diff BeMart's rendered admin Order list against
     * EC-CUBE's own rendering. Every difference must be in the residual
     * allowlist or a residual family.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testOrderListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/order-list')->toString();
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
            "BeMart's admin Order-list HTML diverged from EC-CUBE's "
            . "beyond the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        $this->assertLessThanOrEqual(
            160,
            count($onlyInEcCube) + count($onlyInBeMart),
            'residual diff unexpectedly large — port may have drifted',
        );
    }

    private static function isResidual(string $line): bool
    {
        if (RenderDiffResiduals::isAdminListEnrichment($line)) {
            return true;
        }

        if (in_array($line, self::RESIDUAL_ALLOWLIST, true)) {
            return true;
        }

        foreach ([
            // EC-CUBE-runtime <head> furniture.
            'eccube-csrf-token',
            '<title>',
            'c-headerBar__shopTitle',
            'c-headerBar__userMenu',
            'data-bs-content',
            'last_login',
            // Admin frame: the DYNAMIC sidebar nav (eccubeNav tree).
            'nav-',
            'data-bs-toggle="collapse"',
            'fa-fw',
            // Bulk form: EC-CUBE's hidden CSRF input + `csrfcsrfToken_for_anchor`.
            'name="csrfToken"',
            'csrfcsrfToken',
            'Constant::TOKEN_NAME',
            // Order list: EC-CUBE keys order rows by the per-shipment
            // `Shipping.id`; BeMart's AdminOrderListFetched projection is
            // a flat order projection keyed by `orderNo` with NO nested
            // Shippings (Wave 7 shallow admin grid — see the port
            // header). The row `id` / checkbox value / data-* urls / the
            // tracking-number input id / the per-shipment action anchors
            // all carry the orderNo in place of the shipment id — same
            // elements, different id token. FLAGGED for enrichment.
            'check_',
            'name="ids[]"',
            'tracking_number_',
            'admin_shipping_preview_notify_mail',
            'admin_shipping_notify_mail',
            'admin_shipping_update_order_status',
            'admin_shipping_update_tracking_number',
            'admin_order_edit?id=',
            'admin_order_export_pdf',
            'data-shipping_id',
            'data-update-status-id',
            // Order list: EC-CUBE renders the orderer name
            // (`name01 ~ name02`), payment method, status color, payment
            // date, message/note tooltips, shipping date / shipment
            // address — none carried by the flat projection (Wave 7
            // slice). The cells render the projection value or empty.
            // FLAGGED for enrichment follow-up.
            'OrderStatusColor',
            'fa-commenting',
            'order_message',
            'order_note',
            'fa-envelope',
            'tabindex=',
            // Order list: EC-CUBE's `pageMaxis` per-page-count <select>
            // dropdown + the `@admin/pager.twig` wrapper + the per-order
            // status-count anchors. No server-side paging / status
            // master in the Wave 7 slice — OMITTED.
            'd-inline-block align-bottom',
            'form-select" onchange',
            '</select>',
            'page_count',
            'admin_order_page',
            'admin_order?order_status_id',
            // Order list: EC-CUBE's CSV-setting links pass the
            // `CsvType::CSV_TYPE_ORDER` / `CSV_TYPE_SHIPPING` constant as
            // the `id` query param; BeMart's port links to the bare
            // route. Same anchor + label, different query string.
            'admin_setting_shop_csv',
            // Order list: the search-status checkbox loop iterates
            // `searchForm.status.children` — AdminOrderSearchForm
            // declares only `multi`, so the loop is empty on both sides
            // (defensive family).
            'admin_search_order_status',
            // Order list: the empty-result branch. EC-CUBE has a
            // three-way `pagination / has_errors / else`; BeMart's port
            // has a two-way `count / else`. With seeded rows the
            // populated branch is taken on both sides — defensive.
            'search_invalid_condition',
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        return false;
    }

    private function renderEcCube(): string
    {
        $adminTemplates = dirname(__DIR__, 2)
            . '/tools/ec-cube-source/src/Eccube/Resource/template/admin';
        if (! is_dir($adminTemplates)) {
            $this->markTestSkipped('EC-CUBE 4.3 reference clone not present.');
        }

        $twig = new Environment(new EcCubeAdminStubLoader($adminTemplates), [
            'autoescape' => 'html',
            'strict_variables' => false,
        ]);

        $searchForm = (new FormFactory())->newInstance(AdminOrderSearchForm::class);

        $this->registerEcCubeStubs($twig, $searchForm instanceof AdminOrderSearchForm ? $searchForm : null);

        // The same logical order list as BeMart's JSON order corpus seed,
        // projected onto the EC-CUBE row shape. EC-CUBE iterates one row
        // per shipment (`Order.Shippings`); the AdminOrderListFetched
        // projection has no nested shipments, so the EC-CUBE stub gives
        // each order a single Shipping keyed by the orderNo to keep the
        // action hrefs aligned.
        $beMartList = $this->resource->get('page://self/admin/order-list');
        $rows = [];
        foreach ($beMartList->body['orders'] as $order) {
            $rows[] = new EcCubeStub([
                'id' => $order['orderNo'],
                'order_no' => $order['orderNo'],
                'name01' => $order['customerId'],
                'name02' => '',
                'order_date' => $order['orderDate'],
                'payment_method' => '',
                'OrderStatus' => (string) $order['orderStatus'],
                'OrderStatusColor' => '',
                'payment_total' => $order['paymentTotal'],
                'payment_date' => null,
                'message' => '',
                'note' => '',
                'is_multiple' => false,
                'Shippings' => [
                    new EcCubeStub([
                        'id' => $order['orderNo'],
                        'name01' => '',
                        'name02' => '',
                        'shipping_date' => null,
                        'mail_send_date' => null,
                        'trackingNumber' => '',
                        'Pref' => new EcCubeStub(['name' => '']),
                    ]),
                ],
            ]);
        }

        return $twig->render('Order/index.twig', [
            'searchForm' => new EcCubeStub([
                'csrfToken' => 'csrfToken',
                'multi' => 'multi',
                'name' => 'name',
                'kana' => 'kana',
                'company_name' => 'company_name',
                'email' => 'email',
                'phone_number' => 'phone_number',
                'order_no' => 'order_no',
                'payment' => 'payment',
                'status' => new EcCubeStub(['children' => [], 'order_count' => []], []),
                'order_datetime_start' => 'order_datetime_start',
                'order_datetime_end' => 'order_datetime_end',
                'payment_datetime_start' => 'payment_datetime_start',
                'payment_datetime_end' => 'payment_datetime_end',
                'update_datetime_start' => 'update_datetime_start',
                'update_datetime_end' => 'update_datetime_end',
                'shipping_delivery_datetime_start' => 'shipping_delivery_datetime_start',
                'shipping_delivery_datetime_end' => 'shipping_delivery_datetime_end',
                'payment_total_start' => 'payment_total_start',
                'payment_total_end' => 'payment_total_end',
                'tracking_number' => 'tracking_number',
                'buy_product_name' => 'buy_product_name',
                'shipping_mail' => 'shipping_mail',
                'sortkey' => 'sortkey',
                'sorttype' => 'sorttype',
            ], []),
            'pagination' => new EcCubeStub([
                'totalItemCount' => count($rows),
                'paginationData' => new EcCubeStub(['pageCount' => 1]),
            ], $rows),
            'pageMaxis' => [],
            'page_count' => 50,
            'has_errors' => false,
            'OrderStatuses' => [],
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['order', 'order_master'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_order']),
            ]),
            'subtitle' => '受注管理',
            'sub_title' => '受注管理',
            'title' => '受注一覧',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminOrderSearchForm|null $searchForm): void
    {
        $messages = AdminJaMessages::forSection(OrderJaMessages::keys());
        $trans = static function (string $key, array $params = []) use ($messages): string {
            $text = $messages[$key] ?? $key;
            foreach ($params as $name => $value) {
                $text = str_replace($name, (string) $value, $text);
            }

            return $text;
        };
        $jpy = new NumberFormatter('ja_JP', NumberFormatter::CURRENCY);
        $price = static fn ($value): string => (string) $jpy->formatCurrency((float) $value, 'JPY');

        $twig->addFilter(new TwigFilter('trans', $trans));
        $twig->addFilter(new TwigFilter('date_sec', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('date_min', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('date_day', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('number_format', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('price', $price));
        $twig->addFilter(new TwigFilter('nl2br', static fn ($d): string => (string) $d));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrfcsrfToken_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('active_menus', static fn (): array => ['', '', '']));

        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($searchForm): Markup {
            if ($searchForm instanceof AdminOrderSearchForm && is_string($field) && $field === 'multi') {
                return new Markup($searchForm->input('multi'), 'UTF-8');
            }

            return new Markup('', 'UTF-8');
        }));
        $twig->addFunction(new TwigFunction('form_errors', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('form_label', static fn ($f = '', $l = '', $o = []): string => ''));
        $twig->addFunction(new TwigFunction('form_row', static fn ($f = '', $o = []): string => ''));
        $twig->addFunction(new TwigFunction('form_rest', static fn ($f = ''): string => ''));
        $twig->addFunction(new TwigFunction('has_errors', static fn (...$f): bool => false));
    }

    /**
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
