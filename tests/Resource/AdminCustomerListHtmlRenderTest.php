<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminCustomerSearchForm;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\CustomerJaMessages;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
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
 * Phase 3 — fidelity check for the admin Customer-list HTML port (the
 * Customer section's DATA/LIST + search-form page).
 *
 * Same residual-diff standard as the admin pilot {@see AdminNewsListHtmlRenderTest}:
 * BeMart's templates are PORTS of EC-CUBE 4.3's admin Twig. The page
 * extends `admin-base.html.twig` (the port of EC-CUBE's admin-theme
 * `default_frame.twig`), served via {@see EcCubeAdminStubLoader}.
 *
 *   1. renders EC-CUBE's real `Customer/index.twig` + admin frame;
 *   2. renders BeMart's ported `CustomerList.html.twig` via the html
 *      context with a seeded admin session;
 *   3. line-diffs the two (whitespace-collapsed);
 *   4. asserts every differing line is in {@see RESIDUAL_ALLOWLIST} or a
 *      residual family.
 *
 * The `multi` keyword box is rendered by a real {@see AdminCustomerSearchForm}
 * on BOTH sides, so it diffs to ZERO. The detail-search panel's other
 * `form_widget` calls render EMPTY on both sides (the form declares only
 * `multi` — see the form's class doc), so the panel structure matches;
 * the empty cells are an enumerated residual family.
 */
final class AdminCustomerListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. Each entry
     * is a whitespace-collapsed line; the comment states WHY it is
     * acceptable. The residual is the same admin-frame baseline as
     * {@see AdminNewsListHtmlRenderTest} plus the Customer-list-specific
     * page-count dropdown / pager / CSV-setting families.
     *
     * @var list<string>
     */
    private const RESIDUAL_ALLOWLIST = [
        // --- frame: EC-CUBE-runtime-only <head> nodes (shared) ----------
        // EC-CUBE's admin default_frame.twig emits a live CSRF token and
        // wires it into jQuery's $.ajaxSetup; BeMart's html context has no
        // per-request CSRF widget, so admin-base.html.twig omits the
        // script and the meta is empty. EC-CUBE-runtime only.
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
        // <title>: EC-CUBE's admin frame composes
        // "<sub_title> <title> - <shop_name>"; BeMart's admin-base orders
        // it "<title> <sub_title> - <shop_name>". Only the order + the
        // brand label differ — caught by the `<title>` family below.
        '<title>会員一覧 会員管理 - BeMart</title>',
        '<title>会員管理 会員一覧 - EC-CUBE</title>',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testCustomerListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/customer-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testCustomerListPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/customer-list')->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea">',
            '<div class="c-pageTitle">',
            'class="c-outsideBlock"',
            'id="searchDetail"',
            'class="c-contentsArea__cols"',
            'class="c-primaryCol"',
            '<table class="table">',
            'class="btn btn-ec-actionIcon"',
            'class="modal fade"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The customer rows are rebound to the CustomerList resource body:
     * the seeded customers appear with their id / name / email.
     */
    public function testCustomerListRendersSeededCustomerRows(): void
    {
        $html = $this->resource->get('page://self/admin/customer-list')->toString();

        $this->assertStringContainsString('alice@example.com', $html);
        $this->assertStringContainsString('id="admin_search_customer_multi"', $html);
        $this->assertStringContainsString('検索結果：', $html);
    }

    /**
     * The honesty test: diff BeMart's rendered admin Customer list against
     * EC-CUBE's own rendering. Every difference must be in the residual
     * allowlist or a residual family.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testCustomerListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/customer-list')->toString();
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
            "BeMart's admin Customer-list HTML diverged from EC-CUBE's "
            . "beyond the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // The residual is the EC-CUBE-runtime <head> baseline + the
        // admin-frame operator-menu / shop-title families + the
        // Customer-list page-count-dropdown / pager / empty-detail-panel
        // families. If this balloons, the port has drifted.
        $this->assertLessThanOrEqual(
            50,
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
            // Admin frame: the header's shop-title link shows the shop
            // name (BaseInfo.shop_name) — a brand label, BeMart vs the
            // stub's EC-CUBE. Same `c-headerBar__shopTitle` anchor.
            'c-headerBar__shopTitle',
            // Admin frame: the logged-in-operator header user-menu.
            // EC-CUBE renders `app.user.*` (login date, change-password /
            // logout links inside a Bootstrap popover data-attr); BeMart's
            // html context has no operator entity, so the menu shows a
            // fixed label. Same `c-headerBar__userMenu` anchor.
            'c-headerBar__userMenu',
            'data-bs-content',
            'last_login',
            // Admin frame: the DYNAMIC sidebar nav (eccubeNav tree).
            'nav-',
            'data-bs-toggle="collapse"',
            'fa-fw',
            // Customer list: EC-CUBE's `pageMaxis` per-page-count <select>
            // dropdown (会員一覧 has server-side paging). BeMart's
            // CustomerList endpoint returns the full collection (no
            // server-side paging in the Wave 5 slice — see the resource
            // docblock), so the dropdown + its wrapping
            // `d-inline-block me-2 align-bottom` / `col-5` cell are
            // OMITTED.
            'd-inline-block me-2 align-bottom',
            'form-select',
            '</select>',
            'col-5',
            // Customer list: EC-CUBE's `@admin/pager.twig` wrapper row.
            // No server-side paging => no pager (the stub loader serves
            // pager.twig empty; the wrapping `<div>` is EC-CUBE-only).
            'justify-content-md-center',
            // Customer list: EC-CUBE's CSV-setting link passes the
            // `CsvType::CSV_TYPE_CUSTOMER` constant as the `id` query
            // param (`admin_setting_shop_csv?id=...`); BeMart's port links
            // to the bare route. Same anchor + label, different query
            // string — list-runtime master-data constant.
            'admin_setting_shop_csv',
            // Customer list: the detail-search panel's empty cells. The
            // panel STRUCTURE (rows / labels / collapse) is kept verbatim,
            // but BeMart's AdminCustomerSearchForm declares only `multi`
            // (Wave 5 first iteration — see the form's class doc), so the
            // other widgets render empty and the cells collapse to bare
            // `<div class="col"></div>`. EC-CUBE's real template renders
            // the same widgets empty too, but its cells wrap a `form_widget`
            // call so the `<div>` is not alone on one line — the diff is a
            // line-grouping artefact of identical empty panels.
            '<div class="col"></div>',
            '<div class="col-7"></div>',
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

        // The detail-search panel's `multi` keyword box is rendered through
        // BeMart's real AdminCustomerSearchForm so it is byte-identical to
        // BeMart's port (which renders the same form). The remaining
        // detail-panel widgets are NOT declared by the form, so they
        // render empty on both sides.
        $searchForm = (new FormFactory())->newInstance(AdminCustomerSearchForm::class);
        if ($searchForm instanceof AdminCustomerSearchForm) {
            $searchForm->fillFilters([]);
        }

        $this->registerEcCubeStubs($twig, $searchForm);

        // The same logical customer list as BeMart's CustomerQueryInterface
        // seed, projected onto the EC-CUBE row shape.
        $beMartList = $this->resource->get('page://self/admin/customer-list');
        $rows = [];
        foreach ($beMartList->body['customers'] as $customer) {
            $rows[] = new EcCubeStub([
                'id' => $customer['customerId'],
                'name01' => $customer['name01'],
                'name02' => $customer['name02'],
                // The CustomerList projection does not carry the phone
                // number (Wave 5 shallow-list slice); both sides render an
                // empty phone cell.
                'phone_number' => '',
                'email' => $customer['email'],
                // Non-provisional status — the resend modal branch is not
                // taken (BeMart has no resend affordance).
                'Status' => new EcCubeStub(['id' => 99]),
            ]);
        }

        return $twig->render('Customer/index.twig', [
            // The `searchForm` variable's children are the field NAMES;
            // the stubbed form_widget renders `multi` through the real
            // form and the rest empty. `searchForm` itself is iterated by
            // the entity-extension `{% for %}` — an empty iteration set.
            'searchForm' => new EcCubeStub([
                'csrfToken' => 'csrfToken',
                'multi' => 'multi',
                'customer_status' => 'customer_status',
                'sex' => 'sex',
                'birth_month' => 'birth_month',
                'pref' => 'pref',
                'phone_number' => 'phone_number',
                'buy_product_name' => 'buy_product_name',
                'buy_total_start' => 'buy_total_start',
                'buy_total_end' => 'buy_total_end',
                'buy_times_start' => 'buy_times_start',
                'buy_times_end' => 'buy_times_end',
                'birth_start' => 'birth_start',
                'birth_end' => 'birth_end',
                'create_datetime_start' => 'create_datetime_start',
                'create_datetime_end' => 'create_datetime_end',
                'update_datetime_start' => 'update_datetime_start',
                'update_datetime_end' => 'update_datetime_end',
                'last_buy_start' => 'last_buy_start',
                'last_buy_end' => 'last_buy_end',
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
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['customer', 'customer_master'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_customer']),
            ]),
            'subtitle' => '会員管理',
            'sub_title' => '会員管理',
            'title' => '会員一覧',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminCustomerSearchForm|null $searchForm): void
    {
        $messages = AdminJaMessages::forSection(CustomerJaMessages::keys());
        $trans = static function (string $key, array $params = []) use ($messages): string {
            $text = $messages[$key] ?? $key;
            foreach ($params as $name => $value) {
                $text = str_replace($name, (string) $value, $text);
            }

            return $text;
        };
        $twig->addFilter(new TwigFilter('trans', $trans));
        $twig->addFilter(new TwigFilter('date_sec', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('date_min', static fn ($d): string => (string) $d));
        $twig->addFilter(new TwigFilter('number_format', static fn ($d): string => (string) $d));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrfcsrfToken_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('active_menus', static fn (): array => ['', '', '']));

        // EC-CUBE's `form_widget(searchForm.multi)` renders through
        // BeMart's real AdminCustomerSearchForm so the keyword box is
        // byte-identical to BeMart's port. Every other detail-panel field
        // is NOT declared by the form, so it renders empty here — exactly
        // as BeMart's port renders it empty. Returns Twig\Markup so the
        // markup is not double-escaped.
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($searchForm): Markup {
            if ($searchForm instanceof AdminCustomerSearchForm && is_string($field) && $field === 'multi') {
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
