<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\ShopJaMessages;
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
use function in_array;
use function is_dir;
use function number_format;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * HTML render verification for the admin Payment-list page (goPaymentList).
 *
 * The template was rebuilt clean-room in the idea-admin design language.
 * Tests are grouped into:
 *
 *   L0 — HTML document shell + idea-admin landmark presence
 *   L1 — required field / list data output (functional markup parity)
 *   L2 — form action/method and link href/rel semantics
 *
 * The EC-CUBE verbatim-diff suite is archived under the
 * ec-cube-parity-archived group and skipped unconditionally.
 */
final class AdminPaymentListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** @var list<string> kept only for the archived parity test */
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
        '<title>支払方法一覧 店舗設定 - BeMart</title>',
        '<title>店舗設定 支払方法一覧 - EC-CUBE</title>',
        '￥0',
        '〜 無制限',
        '<div class="col-3 text-end">',
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

    // ── L0: HTML document shell ──────────────────────────────────────────

    public function testPaymentListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/payment/payment-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * L0 — idea-admin shell landmarks replace legacy c-* frame elements.
     */
    public function testPaymentListRendersIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment-list')->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
            'class="idea-admin-content"',
            'class="idea-admin-toolbar"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "idea-admin landmark missing: {$needle}");
        }
    }

    // ── L1: required field / list data output ────────────────────────────

    /**
     * L1 — payment list table is rendered with the correct column headers.
     */
    public function testPaymentListTableHeadersArePresent(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment-list')->toString();

        $this->assertStringContainsString('class="idea-admin-table"', $html);
        $this->assertStringContainsString('id="payment-list-table"', $html);
        // Required header columns.
        $this->assertStringContainsString('支払方法名', $html);
        $this->assertStringContainsString('手数料', $html);
        $this->assertStringContainsString('利用金額範囲', $html);
        $this->assertStringContainsString('表示', $html);
    }

    /**
     * L1 — per-row data fields are rendered for each payment method.
     * Fake storage seeds at least one payment, so this verifies field output.
     */
    public function testPaymentListRowsExposeRequiredFields(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment-list')->toString();

        // Each row has an id anchored on paymentId.
        $this->assertMatchesRegularExpression('/id="ex-payment-[^"]+"/', $html);
        // Row carries data-id for sort interaction.
        $this->assertMatchesRegularExpression('/data-id="[^"]+"/', $html);
        // Each row links to the edit page via goPayment.
        $this->assertMatchesRegularExpression(
            '#href="/admin/payment/payment\?paymentId=[^"]*"#',
            $html,
            'edit link href missing or malformed',
        );
    }

    /**
     * L1 — visibility badge is rendered for each payment row.
     */
    public function testPaymentListVisibilityBadgeIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment-list')->toString();

        $this->assertMatchesRegularExpression(
            '/idea-admin-badge--(public|private)/',
            $html,
            'visibility badge class missing',
        );
    }

    // ── L2: link href / rel semantics ────────────────────────────────────

    /**
     * L2 — the "new registration" action links to doCreatePayment.
     */
    public function testPaymentListNewRegistrationActionLinkIsCorrect(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment-list')->toString();

        $this->assertStringContainsString('rel="doCreatePayment"', $html);
    }

    /**
     * L2 — each row edit link carries rel="goPayment".
     */
    public function testPaymentListEditLinkRelIsGoPayment(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment-list')->toString();

        $this->assertStringContainsString('rel="goPayment"', $html);
    }

    /**
     * L2 — delete affordance: per-row button carries the correct action URL
     * (doDeletePayment) with HTTP method override, plus post-action marker.
     */
    public function testPaymentListExposesDeleteAffordance(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment-list')->toString();

        $this->assertMatchesRegularExpression(
            '#data-delete-url="/admin/payment/payment\?paymentId=[^&"]*&amp;_method=delete"#',
            $html,
            'delete action URL missing or malformed',
        );
        $this->assertStringContainsString('data-post-action="delete"', $html);
        $this->assertStringContainsString('data-method="delete"', $html);
    }

    /**
     * L2 — visibility toggle carries rel="doUpdatePayment" and PUT method override.
     */
    public function testPaymentListVisibilityToggleLinkIsCorrect(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment-list')->toString();

        $this->assertStringContainsString('rel="doUpdatePayment"', $html);
        $this->assertMatchesRegularExpression(
            '#href="/admin/payment/payment\?paymentId=[^"]*&amp;_method=put&amp;visible=#',
            $html,
            'visibility toggle href missing or malformed',
        );
    }

    // ── ec-cube-parity-archived ──────────────────────────────────────────

    /**
     * EC-CUBE verbatim-diff comparison.
     *
     * Archived: the template was rebuilt clean-room in the idea-admin design
     * language; EC-CUBE frame markup (c-* / ec-*) is intentionally absent.
     *
     * @group ec-cube-parity-archived
     */
    public function testPaymentListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity archived: PaymentList template rebuilt clean-room '
            . 'in idea-admin design language. Legacy c-*/ec-* classes are '
            . 'intentionally removed. Functional/semantic coverage is provided '
            . 'by the L1/L2 tests in this class.',
        );

        // The code below is preserved for historical reference only.
        // @phpstan-ignore-next-line
        $beMart = $this->resource->get('page://self/admin/payment/payment-list')->toString();
        // @phpstan-ignore-next-line
        $ecCube = $this->renderEcCube();

        $beMartLines = $this->normalize($beMart);
        $ecCubeLines = $this->normalize($ecCube);

        $onlyInEcCube = array_values(array_diff($ecCubeLines, $beMartLines));
        $onlyInBeMart = array_values(array_diff($beMartLines, $ecCubeLines));
        $hasPaymentLimitResidual = in_array('￥0', [...$onlyInEcCube, ...$onlyInBeMart], true)
            && in_array('〜 無制限', [...$onlyInEcCube, ...$onlyInBeMart], true);

        $unexplained = array_values(array_filter(
            [...$onlyInEcCube, ...$onlyInBeMart],
            static fn (string $line): bool => ! self::isResidual($line)
                && ! ($hasPaymentLimitResidual && in_array($line, ['<span>', '</span>'], true)),
        ));

        $this->assertSame([], $unexplained);
        $this->assertLessThanOrEqual(65, count($onlyInEcCube) + count($onlyInBeMart));
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
            'eccube-csrf-token',
            '<title>',
            'c-headerBar__shopTitle',
            'c-headerBar__userMenu',
            'data-bs-content',
            'last_login',
            'nav-',
            'data-bs-toggle="collapse"',
            'fa-fw',
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

        return $twig->render('Setting/Shop/payment.twig', [
            'Payments' => [],
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['setting', 'shop', 'shop_payment'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'id' => self::TEST_ADMIN_ID,
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_setting_shop_payment']),
            ]),
            'subtitle' => '店舗設定',
            'sub_title' => '店舗設定',
            'title' => '支払方法設定',
        ]);
    }

    private function registerEcCubeStubs(Environment $twig): void
    {
        $messages = AdminJaMessages::forSection(ShopJaMessages::keys());
        $trans = static function (string $key, array $params = []) use ($messages): string {
            $text = $messages[$key] ?? $key;
            foreach ($params as $name => $value) {
                $text = str_replace($name, (string) $value, $text);
            }

            return $text;
        };
        $twig->addFilter(new TwigFilter('trans', $trans));
        $twig->addFilter(new TwigFilter('price', static fn ($v): string => '￥' . number_format((float) $v)));
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
