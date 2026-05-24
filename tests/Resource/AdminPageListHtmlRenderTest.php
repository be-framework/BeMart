<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\HtmlTestModule;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\ContentJaMessages;
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
 * Phase 3 — fidelity check for the admin Page-list HTML port (the
 * Content section's `Content/page.twig` DATA/LIST page).
 *
 * Same residual-diff standard as the admin pilot {@see AdminNewsListHtmlRenderTest}:
 * BeMart's templates are PORTS of EC-CUBE 4.3's admin Twig. The page
 * extends `admin-base.html.twig` (the port of EC-CUBE's admin-theme
 * `default_frame.twig`), served via {@see EcCubeAdminStubLoader}.
 */
final class AdminPageListHtmlRenderTest extends TestCase
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
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        });
        $injector = new Injector($module, dirname(__DIR__, 2) . '/var/tmp/html');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testPageListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/page/page-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testPageListPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/page/page-list')->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea">',
            '<div class="c-contentsArea__cols">',
            'class="c-primaryCol"',
            'class="table table-sm"',
            'id="search-page"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    public function testPageListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/page/page-list')->toString();
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
            "BeMart's admin Page-list HTML diverged from EC-CUBE's beyond "
            . "the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

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
            // Page list: EC-CUBE's ルーティング名 cell uses the Symfony
            // router (`router.routecollection.get(...)`); BeMart has no
            // router, so the cell shows the bare pageUrl (EC-CUBE's
            // `{% else %}` branch). The router lookup line has no port
            // counterpart.
            'router.routecollection',
            // Page list: EC-CUBE's レイアウト名 column iterates
            // `Page.layouts` (the page->layout join). The
            // AdminPageListFetched projection carries no layout join (out
            // of the Wave 9 CMS slice); the column renders empty —
            // MISSING-BODY-FIELD: pages[].layouts.
            'fa-desktop',
            'fa-mobile',
            // Page list: EC-CUBE adds `csrf_token_for_anchor()` to the
            // delete <a>. BeMart's html context has no CSRF widget.
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

        // The same logical page list as BeMart's PageStorageInterface seed:
        // the system homepage (edit_type 2 = EDIT_TYPE_DEFAULT).
        $page = new EcCubeStub([
            'id' => 'pg-homepage',
            'name' => 'ホームページ',
            'url' => 'homepage',
            'file_name' => 'index',
            'edit_type' => 2,
            'layouts' => [],
        ]);

        return $twig->render('Content/page.twig', [
            'Pages' => [$page],
            'router' => new EcCubeStub(['routecollection' => new EcCubeStub([])]),
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
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
                'request' => new EcCubeStub(['_route' => 'admin_content_page']),
            ]),
            'subtitle' => 'コンテンツ管理',
            'sub_title' => 'コンテンツ管理',
            'title' => 'ページ管理',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig): void
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
        $twig->addFunction(new TwigFunction('csrf_token', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrf_token_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('active_menus', static fn (): array => ['', '', '']));
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
