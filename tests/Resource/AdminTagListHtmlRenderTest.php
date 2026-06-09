<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminTagForm;
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
 * Phase 3 — fidelity check for the admin Tag-management HTML port (the
 * Product section's LIST + inline-create-FORM page).
 *
 * Same residual-diff standard as the admin pilot {@see AdminNewsListHtmlRenderTest}:
 * BeMart's templates are PORTS of EC-CUBE 4.3's admin Twig. The page
 * extends `admin-base.html.twig` (the port of EC-CUBE's admin-theme
 * `default_frame.twig`), served via {@see EcCubeAdminStubLoader}.
 *
 * The inline-create `name` box is rendered by a real {@see AdminTagForm}
 * on BOTH sides, so it diffs to ZERO.
 */
final class AdminTagListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var list<string> */
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
        '<title>タグ管理 商品管理 - BeMart</title>',
        '<title>タグ管理 商品管理 - EC-CUBE</title>',
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

    public function testTagListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/tag/tag-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testTagListPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/tag/tag-list')->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea">',
            'class="c-primaryCol"',
            'class="list-group list-group-flush sortable-container"',
            'class="modal fade"',
            'id="DeleteModal"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    public function testTagListRendersSeededTagRows(): void
    {
        $html = $this->resource->get('page://self/admin/tag/tag-list')->toString();

        $this->assertStringContainsString('id="admin_tag_name"', $html);
        $this->assertStringContainsString('新商品', $html);
        $this->assertStringContainsString('セール', $html);
    }

    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testTagListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/tag/tag-list')->toString();
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
            "BeMart's admin Tag HTML diverged from EC-CUBE's beyond the "
            . "residual allowlist. Unexplained diff lines:\n  "
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
            // Tag list: EC-CUBE's hidden `csrfToken` CSRF input. BeMart keeps
            // the hidden input (structure) with an empty value.
            'name="csrfToken"',
            // Tag list: `Tag.sortNo` — dtb_tag has the column but
            // TagEntity does not project it; the `data-sort-no` attr
            // renders empty on BeMart's side. EC-CUBE renders a value.
            'data-sort-no=',
            // Tag list: the per-row inline-EDIT form. ALPS exposes no
            // `doUpdateTag`, so BeMart renders a plain `<input>` instead
            // of a `form_widget`. The `mode-edit` panel STRUCTURE is
            // kept; the input markup differs (id / form-object attrs).
            'data-origin-value',
            'mode-edit',
            // Tag list: the entity-extension `auto_render` loop output.
            // EC-CUBE-runtime only — dropped.
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

        $createForm = (new FormFactory())->newInstance(AdminTagForm::class);

        $this->registerEcCubeStubs($twig, $createForm instanceof AdminTagForm ? $createForm : null);

        $beMartList = $this->resource->get('page://self/admin/tag/tag-list');
        $tags = [];
        $forms = [];
        foreach ($beMartList->body['tags'] as $tag) {
            $tags[] = new EcCubeStub([
                'id' => $tag['tagId'],
                'name' => $tag['tagName'],
                'sortNo' => '',
            ]);
            // EC-CUBE renders a per-row inline-edit form. BeMart has no
            // `doUpdateTag`; the row-form widgets render empty here so
            // they fall into the `mode-edit` residual family.
            $forms[$tag['tagId']] = new EcCubeStub([
                'csrfToken' => 'csrfToken',
                'name' => 'name',
            ]);
        }

        return $twig->render('Product/tag.twig', [
            'form' => new EcCubeStub(['csrfToken' => 'csrfToken', 'name' => 'name']),
            'forms' => $forms,
            'Tags' => $tags,
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['product', 'product_tag'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_product_tag']),
            ]),
            'subtitle' => '商品管理',
            'sub_title' => '商品管理',
            'title' => 'タグ管理',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminTagForm|null $createForm): void
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
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrfcsrfToken_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('active_menus', static fn (): array => ['', '', '']));

        // EC-CUBE's `form_widget(form.name)` renders through BeMart's
        // real AdminTagForm so the inline-create input is byte-identical
        // to BeMart's port. The per-row `forms[id].*` widgets are NOT the
        // BeMart form (no `doUpdateTag`); they render empty here and fall
        // into the `mode-edit` residual family.
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($createForm): Markup {
            if ($createForm instanceof AdminTagForm && is_string($field) && $field === 'name') {
                return new Markup($createForm->input('name'), 'UTF-8');
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
