<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\HtmlModule;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\StoreJaMessages;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Twig\Environment;
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
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Phase 3 — fidelity check for the admin Template-list HTML port (the
 * Store section's テンプレート一覧 page).
 *
 * Same residual-diff standard as the admin pilot {@see AdminNewsListHtmlRenderTest}:
 * BeMart's templates are PORTS of EC-CUBE 4.3's Twig. The page extends
 * `admin-base.html.twig` (a port of EC-CUBE's admin-theme
 * `default_frame.twig`), served via {@see EcCubeAdminStubLoader}. The
 * TemplateList resource requires an authenticated admin, so the html
 * context's `AdminSessionInterface` is rebound to a seeded admin id.
 *
 * `template.twig` is nominally a Symfony form page (it wraps the table
 * in a `<form>` with `form_widget(form._token)` + `form_widget(form.
 * selected)`), but the TemplateList resource is a list-only endpoint
 * with no `body['form']`, so the page is ported as a DATA page — the two
 * `form_widget` calls are enumerated residuals (stubbed empty here,
 * EC-CUBE-runtime CSRF on the BeMart side).
 */
final class AdminTemplateListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa.
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
        // <title>: EC-CUBE's admin frame composes "<sub_title> <title> -
        // <shop_name>"; BeMart's admin-base orders it "<title> <sub_title>
        // - <shop_name>". Also the shop name differs.
        '<title>テンプレート一覧 オーナーズストア - BeMart</title>',
        '<title>オーナーズストア テンプレート一覧 - EC-CUBE</title>',
    ];

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $module = new HtmlModule($meta);
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $module->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testTemplateListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/template/template-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testTemplateListPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-list')->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea">',
            '<div class="c-pageTitle">',
            'class="c-primaryCol"',
            'name="form1" id="form1"',
            'class="table"',
            'class="btn btn-ec-actionIcon action-download"',
            'class="btn btn-ec-actionIcon action-delete"',
            'class="modal fade"',
            'class="c-conversionArea"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered admin Template list against
     * EC-CUBE's own rendering. Every difference must be in the residual
     * allowlist or a residual family.
     */
    public function testTemplateListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/template/template-list')->toString();
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
            "BeMart's admin Template-list HTML diverged from EC-CUBE's beyond "
            . "the residual allowlist. Unexplained diff lines:\n  "
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
            // EC-CUBE-runtime <head> furniture.
            'eccube-csrf-token',
            '<title>',
            // Admin frame: the header's shop-title link / operator menu.
            'c-headerBar__shopTitle',
            'c-headerBar__userMenu',
            'data-bs-content',
            'last_login',
            // Admin frame: the DYNAMIC sidebar nav (eccubeNav tree).
            'nav-',
            'data-bs-toggle="collapse"',
            'fa-fw',
            // template.twig: `form_widget(form._token)` (CSRF hidden
            // input) + `form_widget(form.selected)` (hidden chosen-radio
            // field). The TemplateList resource carries no `body['form']`
            // — the page is a list-only endpoint. EC-CUBE-runtime form.
            'form_selected',
            // template.twig: the radio `value` differs — EC-CUBE keys it
            // by `Template.id` (numeric), BeMart by `templateId`; the
            // delete/download hrefs likewise carry the id token. Same
            // markup, only the id param differs.
            'admin_store_template_download',
            'admin_store_template_delete',
            'value="tp-default',
            // template.twig: the save-path <li>s render `Template.code`
            // (EC-CUBE) vs `templateId` (BeMart) — the projection carries
            // no separate template `code`. FLAGGED missing-body-field.
            'app/template/',
            'html/template/',
            // template.twig: `eccube_config.eccube_theme_code` drives the
            // `checked` radio + the `default_template` delete-disable
            // guard. BeMart's html context exposes no `eccube_config`, the
            // projection no `default_template` — the radio is never
            // pre-checked and the delete <a> never `disabled`.
            'checked="checked"',
            'disabled"',
            // template.twig: the delete <a>'s class line — EC-CUBE emits
            // `action-delete ` (trailing space before the now-empty
            // `{% if default_template or active %}disabled{% endif %}`);
            // BeMart's port drops the guard entirely so the class ends
            // `action-delete"`. Same anchor, same family as the guard
            // residual above.
            'btn btn-ec-actionIcon action-delete',
            // template.twig: `csrf_token_for_anchor()` on the delete <a>.
            'csrf_token',
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
        $this->registerEcCubeStubs($twig);

        // The same logical template list as BeMart's FakeTemplateStorage
        // seed: the two stock default templates per device type. EC-CUBE
        // keys the row + actions by `Template.id` and reads `Template.code`
        // for the save path; the AdminTemplateListFetched projection
        // surfaces only `templateId` (used as both), so the EC-CUBE stub's
        // `id` / `code` are fed the templateId to keep hrefs aligned.
        $templates = [
            new EcCubeStub([
                'id' => 'tp-default-pc',
                'code' => 'tp-default-pc',
                'name' => 'デフォルト (PC)',
                'default_template' => false,
            ]),
            new EcCubeStub([
                'id' => 'tp-default-sp',
                'code' => 'tp-default-sp',
                'name' => 'デフォルト (スマホ)',
                'default_template' => false,
            ]),
        ];

        return $twig->render('Store/template.twig', [
            'Templates' => $templates,
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_theme_code' => 'default',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['store', 'template', 'template_list'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_store_template']),
            ]),
            'subtitle' => 'オーナーズストア',
            'sub_title' => 'オーナーズストア',
            'title' => 'テンプレート一覧',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig): void
    {
        $messages = AdminJaMessages::forSection(StoreJaMessages::keys());
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
        $twig->addFunction(new TwigFunction('class_categories_as_json', static fn (): string => '{}'));
        // template.twig is nominally a form page; its two `form_widget`
        // calls (`_token`, `selected`) are enumerated residuals — stubbed
        // empty (EC-CUBE-runtime form, no `body['form']` on BeMart side).
        $twig->addFunction(new TwigFunction('form_widget', static fn (): string => ''));
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
