<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\ContentJaMessages;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
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
 * Phase 3 — fidelity check for the admin News-list HTML port (the admin
 * pilot's DATA/LIST page).
 *
 * Same residual-diff standard as the storefront {@see CartHtmlRenderTest}:
 * BeMart's templates are PORTS of EC-CUBE 4.3's Twig. The difference is
 * the LAYOUT — admin pages extend `admin-base.html.twig` (a port of
 * EC-CUBE's admin-theme `default_frame.twig`), not the storefront
 * `base.html.twig`. EC-CUBE's admin templates + frame are served by
 * {@see EcCubeAdminStubLoader} (which serves `@admin/default_frame.twig`
 * and `@admin/nav.twig` for real — they ARE the layout under test).
 *
 * The News-list page (`admin/Content/news.twig`) is a pure data list.
 * Its resource (NewsList) requires an authenticated admin, so the html
 * context's `AdminSession` is rebound to a seeded admin id.
 *
 *   1. renders EC-CUBE's real `Content/news.twig` + admin frame;
 *   2. renders BeMart's ported `NewsList.html.twig` via the html context;
 *   3. line-diffs the two (whitespace-collapsed);
 *   4. asserts every differing line is in {@see RESIDUAL_ALLOWLIST}.
 */
final class AdminNewsListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /**
     * EC-CUBE lines with no BeMart counterpart and vice versa. Each entry
     * is a whitespace-collapsed line; the comment states WHY it is
     * acceptable.
     *
     * The admin frame carries a slightly larger baseline than the
     * storefront frame: EC-CUBE's admin `default_frame.twig` has a
     * logged-in-operator header menu (`app.user.*`) and a DYNAMIC sidebar
     * nav (`eccubeNav` tree + `active_menus()` state). BeMart's html
     * context has no operator entity and no nav tree, so the operator
     * menu shows a fixed label and the sidebar renders only the static
     * ホーム / 情報 bookend items. Those are the admin-specific residual
     * families; the rest is the same EC-CUBE-runtime <head> baseline as
     * every storefront render-diff test.
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
        // <title>: EC-CUBE composes "<title> <sub_title> - <shop_name>";
        // only the shop name differs (BeMart vs the stub's EC-CUBE).
        '<title>新着情報管理 コンテンツ管理 - BeMart</title>',
        '<title>新着情報管理 コンテンツ管理 - EC-CUBE</title>',
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

    public function testNewsListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/news/news-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testNewsListPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/news/news-list')->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea">',
            '<div class="c-pageTitle">',
            '<div class="c-contentsArea__cols">',
            'class="c-primaryCol"',
            'class="list-group list-group-flush mb-4 sortable-container"',
            'class="list-group-item sortable-item"',
            'class="btn btn-ec-actionIcon"',
            'class="modal fade"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered admin News list against
     * EC-CUBE's own rendering. Every difference must be in the residual
     * allowlist.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testNewsListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/news/news-list')->toString();
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
            "BeMart's admin News-list HTML diverged from EC-CUBE's beyond "
            . "the residual allowlist. Unexplained diff lines:\n  "
            . implode("\n  ", $unexplained)
            . "\n\n(only-in-EC-CUBE: " . count($onlyInEcCube)
            . ', only-in-BeMart: ' . count($onlyInBeMart) . ')',
        );

        // The admin frame's residual is the EC-CUBE-runtime <head>
        // baseline + the admin-specific operator-menu / dynamic-nav /
        // display-status / CSRF-anchor families. If this balloons, the
        // port has drifted.
        $this->assertLessThanOrEqual(
            40,
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
            // Admin frame: the logged-in-operator header user-menu. EC-CUBE
            // renders `app.user.*` (login date, name, change-password /
            // 2FA / logout links inside a Bootstrap popover data-attr);
            // BeMart's html context has no operator entity, so the menu
            // shows a fixed `管理者 様` label. Same `c-headerBar__userMenu`
            // anchor, different (runtime) content.
            'c-headerBar__userMenu',
            'data-bs-content',
            'last_login',
            // Admin frame: the DYNAMIC sidebar nav. EC-CUBE's nav.twig
            // loops `eccubeNav` (the menu tree) with `active_menus()`
            // state; BeMart renders only the static ホーム / 情報 bookend
            // items. The eccubeNav-driven section <li>s have no port
            // counterpart — admin menu runtime.
            'nav-',
            'data-bs-toggle="collapse"',
            'fa-fw',
            // News list: EC-CUBE's `News.visible` display-status column
            // (header <strong> + per-row cell). The AdminNewsListFetched
            // projection does not carry dtb_news.visible (out of the
            // Wave 9 CMS slice), so the column is omitted.
            'display_status',
            '公開状態',
            'col-1 d-flex align-items-center',
            // News list: EC-CUBE adds `csrfcsrfToken_for_anchor()` to the
            // delete <a>. BeMart's html context has no CSRF widget.
            'csrfcsrfToken',
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

        // The same logical news list as BeMart's NewsStorageInterface seed:
        // a single welcome post.
        $news = new EcCubeStub([
            'id' => 'nw-welcome',
            'title' => 'ようこそ',
            'publishDate' => '2026-01-01T00:00:00+09:00',
            'visible' => true,
        ]);

        return $twig->render('Content/news.twig', [
            'pagination' => new EcCubeStub([
                'paginationData' => new EcCubeStub(['pageCount' => 1]),
            ], [$news]),
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['content', 'news'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_content_news']),
            ]),
            'subtitle' => 'コンテンツ管理',
            'sub_title' => 'コンテンツ管理',
            'title' => '新着情報管理',
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
        $twig->addFunction(new TwigFunction('csrfcsrfToken', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('csrfcsrfToken_for_anchor', static fn (): string => ''));
        $twig->addFunction(new TwigFunction('constant', static fn (string $n): string => $n));
        $twig->addFunction(new TwigFunction('active_menus', static fn (): array => ['', '', '']));
        $twig->addFunction(new TwigFunction('class_categories_as_json', static fn (): string => '{}'));
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
