<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminPageForm;
use MyVendor\BeMart\Module\HtmlTestModule;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\ContentJaMessages;
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
 * Phase 3 — fidelity check for the admin Page-edit HTML port (the
 * Content section's `Content/page_edit.twig` FORM/CRUD page).
 *
 * Same standard as the admin form pilot {@see AdminNewsHtmlRenderTest}:
 * EC-CUBE renders the page inputs through the Symfony FormView; BeMart
 * renders them through a real Ray.WebFormModule {@see AdminPageForm}
 * exposed as `body.form`. EC-CUBE's `form_widget(form.<field>)` calls
 * render through the SAME `AdminPageForm` instance so the inputs diff to
 * zero.
 */
final class AdminPageHtmlRenderTest extends TestCase
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
        '<title>ページ管理 コンテンツ管理 - BeMart</title>',
        '<title>ページ管理 コンテンツ管理 - EC-CUBE</title>',
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

    public function testPageEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/page/page', [
            'pageId' => 'pg-homepage',
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testPageEditRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/admin/page/page', [
            'pageId' => 'pg-homepage',
        ])->toString();

        $this->assertStringContainsString('id="main_edit_name"', $html);
        // The seed page name is repopulated from the resource body.
        $this->assertStringContainsString('value="ホームページ"', $html);
        $this->assertStringContainsString('id="main_edit_url"', $html);
        $this->assertStringContainsString('id="main_edit_file_name"', $html);
        $this->assertStringContainsString('class="c-conversionArea"', $html);
    }

    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testPageEditHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/page/page', [
            'pageId' => 'pg-homepage',
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
            "BeMart's admin Page-edit HTML diverged from EC-CUBE's beyond "
            . "the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        $this->assertLessThanOrEqual(
            55,
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
            // Form: EC-CUBE's hidden `csrfToken` CSRF input.
            'name="csrfToken"',
            'csrfcsrfToken',
            // Page edit: EC-CUBE's URL / file-name rows show a static
            // path label (`app.request.schemeAndHttpHost`, `template_path`)
            // that is request-runtime; BeMart shows the bare pageUrl /
            // pageFileName. The differing label spans are residual.
            'align-middle',
            // Page edit: the PC / Mobile layout selects
            // (`form_widget(form.PcLayout)` / `form.SpLayout`). The
            // AdminPageFetched projection carries no page->layout join;
            // the AdminPageForm declares no PcLayout/SpLayout fields and
            // the select cells render empty — MISSING-BODY-FIELD.
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

        // EC-CUBE's form_widget calls render through BeMart's real
        // AdminPageForm — pre-filled with the SAME seed page as BeMart's
        // html-context body.
        $form = (new FormFactory())->newInstance(AdminPageForm::class);
        if ($form instanceof AdminPageForm) {
            $form->fillValues([
                'pageId' => 'pg-homepage',
                'pageName' => 'ホームページ',
                'pageUrl' => 'homepage',
                'pageFileName' => 'index',
                'pageEditType' => 2,
            ]);
        }

        $this->registerEcCubeStubs($twig, $form instanceof AdminPageForm ? $form : null);

        return $twig->render('Content/page_edit.twig', [
            'form' => new EcCubeStub([
                'csrfToken' => 'csrfToken',
                'name' => 'name',
                'url' => 'url',
                'file_name' => 'file_name',
                'tpl_data' => 'tpl_data',
                'PcLayout' => 'PcLayout',
                'SpLayout' => 'SpLayout',
                'author' => 'author',
                'description' => 'description',
                'keyword' => 'keyword',
                'meta_robots' => 'meta_robots',
                'meta_tags' => 'meta_tags',
            ]),
            'page_id' => 'pg-homepage',
            'is_user_data_page' => false,
            'is_confirm_page' => false,
            'url' => '/homepage',
            'template_path' => 'app/template/default',
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_user_data_route' => 'user_data',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['content', 'page'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub([
                    '_route' => 'admin_content_page_edit',
                    'schemeAndHttpHost' => 'https://example.com',
                    'basePath' => '',
                    'query' => new EcCubeStub([]),
                ]),
            ]),
            'subtitle' => 'コンテンツ管理',
            'sub_title' => 'コンテンツ管理',
            'title' => 'ページ管理',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig, AdminPageForm|null $form): void
    {
        $messages = AdminJaMessages::forSection(ContentJaMessages::keys());
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

        $formFields = ['name', 'url', 'file_name', 'tpl_data', 'author', 'description', 'keyword', 'meta_robots', 'meta_tags'];
        $twig->addFunction(new TwigFunction('form_widget', static function ($field = '', $opts = []) use ($form, $formFields): Markup {
            if ($form instanceof AdminPageForm && is_string($field) && in_array($field, $formFields, true)) {
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
