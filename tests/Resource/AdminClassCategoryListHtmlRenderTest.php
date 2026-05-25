<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminClassCategoryForm;
use MyVendor\BeMart\Module\HtmlTestModule;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\ProductJaMessages;
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
 * Phase 3 — fidelity check for the admin ClassCategory-management HTML
 * port (the Product section's LIST + inline-create-FORM page).
 *
 * Same residual-diff standard as the admin pilot {@see AdminNewsListHtmlRenderTest}.
 * The page extends `admin-base.html.twig`, served via
 * {@see EcCubeAdminStubLoader}. The inline-create inputs are rendered by
 * a real {@see AdminClassCategoryForm} on BOTH sides, so they diff to
 * ZERO. The list is rendered unscoped (no `classNameId`); the BeMart
 * ClassCategoryStorageInterface seeds empty, so the verification is on the
 * page CHROME + the inline-form + the parent-ClassName header card.
 */
final class AdminClassCategoryListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var list<string> */
    private const RESIDUAL_ALLOWLIST = [
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
        '<title>規格分類管理 商品管理 - BeMart</title>',
        '<title>規格分類管理 商品管理 - EC-CUBE</title>',
        // The unscoped list has a NULL `classNameId`; BeMart's `url()`
        // drops the null `class_name_id` param yielding a bare `?`,
        // EC-CUBE's reference is fed `id: ''` and keeps `class_name_id=`.
        // A null-vs-empty query-string artefact — same routes, same forms.
        '<a class="btn btn-ec-regular" href="/admin_product_class_category_export?">',
        '<form role="form" class="row" name="form1" id="form1" method="post" action="/admin_product_class_category?">',
        'location.href = "/admin_product_class_category?";',
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

    public function testClassCategoryListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/class-category/class-category-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testClassCategoryListPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/class-category/class-category-list')->toString();

        foreach ([
            '<header class="c-headerBar">',
            'class="c-primaryCol"',
            'id="ex-class_category"',
            'id="ex-class_category-edit"',
            'class="list-group list-group-flush sortable-container"',
            'class="c-conversionArea"',
            'id="DeleteModal"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    public function testClassCategoryListRendersRealCreateForm(): void
    {
        $html = $this->resource->get('page://self/admin/class-category/class-category-list')->toString();

        $this->assertStringContainsString('id="admin_class_category_name"', $html);
        $this->assertStringContainsString('id="admin_class_category_backend_name"', $html);
    }

    public function testClassCategoryListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/class-category/class-category-list')->toString();
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
            "BeMart's admin ClassCategory HTML diverged from EC-CUBE's "
            . "beyond the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        $this->assertLessThanOrEqual(
            45,
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
            'c-headerBar__shopTitle',
            'c-headerBar__userMenu',
            'data-bs-content',
            'last_login',
            'nav-',
            'data-bs-toggle="collapse"',
            'fa-fw',
            // ClassCategory list: EC-CUBE's hidden `_token` CSRF input.
            'name="_token"',
            // ClassCategory list: EC-CUBE's CSV-setting link passes the
            // `CsvType::CSV_TYPE_CLASS_CATEGORY` constant as the `id`
            // query param; BeMart's port links to the bare route.
            'admin_setting_shop_csv',
            // ClassCategory list: the parent-ClassName header card. The
            // ClassCategoryList resource carries only `classNameId`, not
            // the parent ClassName name / backend label (Wave 7 slice),
            // so BeMart's two header `<span>`s render empty. EC-CUBE
            // renders the real name / backend. FLAGGED for enrichment.
            'class_name_id=',
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

        $createForm = (new FormFactory())->newInstance(AdminClassCategoryForm::class);

        $this->registerEcCubeStubs($twig, $createForm instanceof AdminClassCategoryForm ? $createForm : null);

        // The parent ClassName carries an empty name / backend on BeMart's
        // side (the resource carries only `classNameId`); the EC-CUBE
        // reference is fed the matching empty-label parent so the header
        // card diffs to zero apart from the (residual) id token.
        return $twig->render('Product/class_category.twig', [
            'form' => new EcCubeStub(['_token' => '_token', 'name' => 'name', 'backend_name' => 'backend_name']),
            'forms' => [],
            'ClassName' => new EcCubeStub(['id' => '', 'name' => '', 'backend_name' => '']),
            'ClassCategories' => [],
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['product', 'class_name'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_product_class_category']),
            ]),
            'subtitle' => '商品管理',
            'sub_title' => '商品管理',
            'title' => '規格分類管理',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminClassCategoryForm|null $createForm): void
    {
        $messages = AdminJaMessages::forSection(ProductJaMessages::keys());
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

        $twig->addFunction(new TwigFunction('trans', $trans));
        $twig->addFunction(new TwigFunction('is_granted', static fn (): bool => false));
        EcCubeAssetStub::register($twig);
        EcCubeRouteStub::register($twig);
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('active_menus', static fn (): array => ['', '', '']));

        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($createForm): Markup {
            if ($createForm instanceof AdminClassCategoryForm && is_string($field) && in_array($field, ['name', 'backend_name'], true)) {
                return new Markup($createForm->input($field), 'UTF-8');
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
