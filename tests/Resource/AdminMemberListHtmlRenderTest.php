<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\SystemJaMessages;
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
 * Phase 3 — fidelity check for the admin Member-list HTML port (the
 * Setting/System section's DATA/LIST page).
 *
 * Same residual-diff standard as the admin pilot {@see AdminNewsListHtmlRenderTest}:
 * BeMart's templates are PORTS of EC-CUBE 4.3's Twig. The page extends
 * `admin-base.html.twig` (a port of EC-CUBE's admin-theme
 * `default_frame.twig`), served via {@see EcCubeAdminStubLoader}. The
 * MemberList resource requires an authenticated admin, so the html
 * context's `AdminSession` is rebound to a seeded admin id.
 *
 *   1. renders EC-CUBE's real `Setting/System/member.twig` + admin frame;
 *   2. renders BeMart's ported `MemberList.html.twig` via the html context;
 *   3. line-diffs the two (whitespace-collapsed);
 *   4. asserts every differing line is in {@see RESIDUAL_ALLOWLIST}.
 */
final class AdminMemberListHtmlRenderTest extends TestCase
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
        // <title>: EC-CUBE's admin frame composes "<sub_title> <title> -
        // <shop_name>"; BeMart's admin-base orders it "<title> <sub_title>
        // - <shop_name>". Also the shop name differs.
        '<title>メンバー管理 システム設定 - BeMart</title>',
        '<title>システム設定 メンバー管理 - EC-CUBE</title>',
        // member list: EC-CUBE renders `common.disabled` (無効) in the
        // 2段階認証 cell when `two_factor_auth_enabled` is falsy; the
        // MemberListFetched projection does not carry the 2FA columns, so
        // BeMart's cell is empty. FLAGGED missing-body-field.
        '無効',
        // Member list fake corpus includes a deleted operator row label that
        // the sparse EC-CUBE reference fixture does not carry.
        '削除済み管理者',
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

    public function testMemberListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/member-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testMemberListPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/member-list')->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea">',
            '<div class="c-pageTitle">',
            'class="c-primaryCol"',
            'id="ex-member-new"',
            'class="table table-sm"',
            'class="btn btn-ec-actionIcon action-edit"',
            'class="modal fade"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    /**
     * The honesty test: diff BeMart's rendered admin Member list against
     * EC-CUBE's own rendering. Every difference must be in the residual
     * allowlist or a residual family.
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testMemberListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/member-list')->toString();
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
            "BeMart's admin Member-list HTML diverged from EC-CUBE's beyond "
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
            // Admin frame: the header's shop-title link / operator menu.
            'c-headerBar__shopTitle',
            'c-headerBar__userMenu',
            'data-bs-content',
            'last_login',
            // Admin frame: the DYNAMIC sidebar nav (eccubeNav tree).
            'nav-',
            'data-bs-toggle="collapse"',
            'fa-fw',
            // member list: EC-CUBE's `csrfcsrfToken_for_anchor()` on the
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

        // The same logical member list as BeMart's AdminQueryInterface seed.
        // EC-CUBE keys the row + actions by `Member.id`; BeMart keys by
        // `loginId`, so the EC-CUBE stub's `id` is fed the loginId to keep
        // the action hrefs aligned. `Authority` / `Work` / department /
        // 2FA fields are left undefined — the MemberListFetched projection
        // does not carry them; the cells render empty on both sides
        // (EC-CUBE's falsy `two_factor_auth_enabled` -> `common.disabled`
        // is the one residual).
        $members = [
            new EcCubeStub(['id' => 'test-admin', 'name' => 'テスト管理者']),
            new EcCubeStub(['id' => 'shop-owner', 'name' => '店舗オーナー']),
            new EcCubeStub(['id' => 'deputy', 'name' => '副管理者']),
        ];

        return $twig->render('Setting/System/member.twig', [
            'Members' => $members,
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['setting', 'system', 'member'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'id' => self::TEST_ADMIN_ID,
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_setting_system_member']),
            ]),
            'subtitle' => 'システム設定',
            'sub_title' => 'システム設定',
            'title' => 'メンバー管理',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig): void
    {
        $messages = AdminJaMessages::forSection(SystemJaMessages::keys());
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
