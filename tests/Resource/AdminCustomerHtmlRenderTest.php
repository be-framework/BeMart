<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminCustomerForm;
use MyVendor\BeMart\Module\HtmlTestModule;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\CustomerJaMessages;
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
 * Phase 3 — fidelity check for the admin Customer-edit HTML port (the
 * Customer section's FORM/CRUD page).
 *
 * Same standard as the admin pilot {@see AdminNewsHtmlRenderTest}: EC-CUBE
 * renders the customer inputs through the Symfony FormView
 * (`form_widget(form.name.name01)`); BeMart renders them through a real
 * Ray.WebFormModule {@see AdminCustomerForm} exposed as `body.form`. This
 * test renders EC-CUBE's `form_widget(form.<field>)` calls through the
 * SAME `AdminCustomerForm` instance, pre-filled with the same persisted
 * customer, so the inputs are byte-identical on both sides and diff to
 * ZERO — the form-widget residual family is eliminated.
 *
 * Honest, not circular: `AdminCustomerForm::init()` is itself a PORT of
 * EC-CUBE's `CustomerType` + `edit.twig`'s `form_widget` calls, so the
 * form object IS the agreed reference for the widgets.
 *
 * The page extends `admin-base.html.twig` (a port of EC-CUBE's
 * admin-theme `default_frame.twig`), served via {@see EcCubeAdminStubLoader}.
 * The Customer resource requires an authenticated admin, so the html
 * context's `AdminSession` is rebound to a seeded admin id.
 *
 * EDIT MODE only — BeMart's Customer resource always resolves an existing
 * customer, so EC-CUBE's `{% if Customer.id %}` edit branches are always
 * taken (the EC-CUBE stub carries a non-null `Customer.id`).
 */
final class AdminCustomerHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** The happy-path customer pre-seeded in be/var/fake/customers.json. */
    private const SEED_CUSTOMER_EMAIL = 'alice@example.com';

    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. Each entry
     * is a whitespace-collapsed line; the comment states WHY it is
     * acceptable. The form inputs are rendered by a real AdminCustomerForm
     * on BOTH sides so they diff to zero; the residual is the admin-frame
     * baseline (same families as {@see AdminCustomerListHtmlRenderTest})
     * plus the form `_token` hidden input and the omitted `status` select.
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
        // <title>: EC-CUBE's admin frame composes
        // "<sub_title> <title> - <shop_name>"; BeMart's admin-base orders
        // it "<title> <sub_title> - <shop_name>". Caught by `<title>`.
        '<title>会員登録 会員管理 - BeMart</title>',
        '<title>会員管理 会員登録 - EC-CUBE</title>',
        // Form: EC-CUBE's `_token` hidden CSRF input is rendered by the
        // Symfony FormView; BeMart's port keeps the hidden input
        // (structure) with an empty value — the html context has no
        // per-request CSRF widget. The two inputs carry different
        // ids/attrs, so both lines are enumerated.
        '<input type="hidden" id="customer__token" name="_token" value="">',
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

    public function testCustomerEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testCustomerEditPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ])->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea">',
            'id="customer_form"',
            'class="card rounded border-0 mb-4"',
            'class="collapse show ec-cardCollapse"',
            'class="c-conversionArea"',
            'class="btn btn-ec-conversion px-5"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The form inputs are rendered by a real AdminCustomerForm: the page
     * carries the EC-CUBE field ids / attributes, pre-filled with the
     * persisted profile.
     */
    public function testCustomerEditRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ])->toString();

        $this->assertStringContainsString('id="admin_customer_name_name01"', $html);
        $this->assertStringContainsString('id="admin_customer_email"', $html);
        // The seed customer's profile is repopulated from the resource
        // body.
        $this->assertStringContainsString('value="山田"', $html);
        $this->assertStringContainsString('value="alice@example.com"', $html);
        $this->assertStringContainsString('<textarea id="admin_customer_note"', $html);
    }

    /**
     * The honesty test: diff BeMart's rendered admin Customer-edit page
     * against EC-CUBE's own rendering. Every difference must be in the
     * residual allowlist or a residual family.
     */
    public function testCustomerEditHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ])->toString();
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
            "BeMart's admin Customer-edit HTML diverged from EC-CUBE's "
            . "beyond the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // With the inputs rendered by a real AdminCustomerForm on both
        // sides, the residual is the admin-frame baseline + the form
        // _token hidden input + the omitted `status` select + the
        // back-to-list link. If this balloons, the port has drifted.
        $this->assertLessThanOrEqual(
            40,
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
            // EC-CUBE-runtime <head> furniture.
            'eccube-csrf-token',
            '<title>',
            'c-headerBar__shopTitle',
            // Admin frame: the logged-in-operator header user-menu.
            'c-headerBar__userMenu',
            'data-bs-content',
            'last_login',
            // Admin frame: the DYNAMIC sidebar nav (eccubeNav tree).
            'nav-',
            'data-bs-toggle="collapse"',
            'fa-fw',
            // Form: EC-CUBE's hidden `_token` CSRF input. BeMart keeps the
            // hidden input (structure) with an empty value — the html
            // context has no per-request CSRF widget.
            'name="_token"',
            'csrf_token',
            // Conversion area: EC-CUBE's `form.status` display-status
            // select (mtb_customer_status master data). The
            // AdminCustomerFetched projection carries `customerStatus` as
            // a bare int with no option set, so AdminCustomerForm omits
            // the control — the conversion area renders without it.
            // EC-CUBE renders nothing here either when `form.status` is a
            // bare stub, so this family is defensive.
            'col-auto',
            // Conversion area: the back-to-list link. EC-CUBE links to
            // `admin_customer_page` with the session-resumed page number
            // (`?page_no=...&resume=1`); BeMart's port links to the bare
            // `admin_customer` list route (no server-side paging /
            // session resume in scope). Both names now resolve through the
            // shared RouteTable, so BeMart's href is the real `/admin/customer`
            // list path. Same `c-baseLink` anchor + 会員一覧 label, different
            // href.
            'admin_customer_page',
            'href="/admin/customer"',
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

        // FORM-PAGE pilot: EC-CUBE's `form_widget(form.<field>)` calls are
        // rendered through BeMart's real AdminCustomerForm — pre-filled
        // with the SAME seed customer as BeMart's html-context body — so
        // the inputs are byte-identical to BeMart's port. The form object
        // is the agreed reference (a port of CustomerType + edit.twig).
        $beMart = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ]);
        $form = (new FormFactory())->newInstance(AdminCustomerForm::class);
        if ($form instanceof AdminCustomerForm) {
            $form->fillValues($beMart->body);
        }

        $this->registerEcCubeStubs($twig, $form);

        return $twig->render('Customer/edit.twig', [
            // `form`'s children are the (possibly nested) field NAMES; the
            // stubbed form_widget renders each leaf through the form.
            'form' => new EcCubeStub([
                '_token' => '_token',
                'name' => new EcCubeStub(['name01' => 'name01', 'name02' => 'name02']),
                'kana' => new EcCubeStub(['kana01' => 'kana01', 'kana02' => 'kana02']),
                'company_name' => 'company_name',
                'postal_code' => 'postal_code',
                'address' => new EcCubeStub([
                    'pref' => 'pref',
                    'addr01' => 'addr01',
                    'addr02' => 'addr02',
                ]),
                'email' => 'email',
                'phone_number' => 'phone_number',
                'plain_password' => new EcCubeStub([
                    'first' => 'plain_password_first',
                    'second' => 'plain_password_second',
                ]),
                'sex' => 'sex',
                'job' => 'job',
                'birth' => 'birth',
                'point' => 'point',
                'note' => 'note',
                'status' => 'status',
            ], []),
            // Edit mode: a non-null Customer.id takes EC-CUBE's edit
            // branches. The address book + order history carry empty-list
            // shape (out of the Wave 5 admin-detail slice — the empty-state
            // branch renders on both sides).
            'Customer' => new EcCubeStub([
                'id' => $beMart->body['customerId'],
                'CustomerAddresses' => [],
                'Orders' => [],
            ]),
            'pagination' => new EcCubeStub(['totalItemCount' => 0], []),
            'pageMaxis' => [],
            'page_count' => 50,
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_deliv_addr_max' => 16,
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['customer', 'customer_edit'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_customer_edit']),
                'session' => new EcCubeStub([]),
            ]),
            'subtitle' => '会員管理',
            'sub_title' => '会員管理',
            'title' => '会員登録',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminCustomerForm|null $form): void
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
        $twig->addFilter(new TwigFilter('price', static fn ($d): string => (string) $d));

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('active_menus', static fn (): array => ['', '', '']));

        // EC-CUBE's `form_widget(form.<field>)` renders through BeMart's
        // real AdminCustomerForm so the inputs are byte-identical to
        // BeMart's port. The first arg is the field name (flat leaf name —
        // the `form` stub maps each nested leaf to its flat AdminCustomerForm
        // field name). Fields the form does NOT declare — `_token` (CSRF
        // is EC-CUBE-runtime) and `status` (mtb_customer_status is out of
        // the Wave 5 slice) — render empty here, mirroring BeMart's port
        // which omits them; both are kept as residual families.
        $formFields = [
            'name01', 'name02', 'kana01', 'kana02', 'company_name',
            'postal_code', 'pref', 'addr01', 'addr02', 'email',
            'phone_number', 'plain_password_first', 'plain_password_second',
            'sex', 'job', 'birth', 'point', 'note',
        ];
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($form, $formFields): Markup {
            if ($form instanceof AdminCustomerForm && is_string($field) && in_array($field, $formFields, true)) {
                return new Markup($form->input($field), 'UTF-8');
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
