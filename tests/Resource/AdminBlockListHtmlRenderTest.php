<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestModule;
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
 * Phase 3 — fidelity check for the admin Block-list HTML port (the
 * Content section's `Content/block.twig` DATA/LIST page).
 *
 * Same residual-diff standard as the admin pilot {@see AdminNewsListHtmlRenderTest}.
 */
final class AdminBlockListHtmlRenderTest extends TestCase
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
        '<title>ブロック管理 コンテンツ管理 - BeMart</title>',
        '<title>ブロック管理 コンテンツ管理 - EC-CUBE</title>',
        // Block list: current BeMart fake rows expose the edit/delete action
        // buttons while the EC-CUBE reference fixture is sparse.
        '<div class="col-6">',
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

    public function testBlockListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/block/block-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('<div class="c-container">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testBlockListPreservesEcCubeAdminMarkupStructure(): void
    {
        $html = $this->resource->get('page://self/admin/block/block-list')->toString();

        foreach ([
            '<header class="c-headerBar">',
            '<div class="c-mainNavArea">',
            '<div class="c-contentsArea__cols">',
            'class="c-primaryCol"',
            'class="list-group list-group-flush"',
            'id="search-block"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "ported markup missing: {$needle}");
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('ec-cube-reference')]
    public function testBlockListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $beMart = $this->resource->get('page://self/admin/block/block-list')->toString();
        $ecCube = $this->renderEcCube();

        $beMartLines = $this->normalize($beMart);
        $ecCubeLines = $this->normalize($ecCube);

        $onlyInEcCube = array_values(array_diff($ecCubeLines, $beMartLines));
        $onlyInBeMart = array_values(array_diff($beMartLines, $ecCubeLines));
        $hasBlockActionColumnResidual = in_array('<div class="col-6">', [...$onlyInEcCube, ...$onlyInBeMart], true);

        $unexplained = array_values(array_filter(
            [...$onlyInEcCube, ...$onlyInBeMart],
            static fn (string $line): bool => ! self::isResidual($line)
                // EC-CUBE's normalized action button can leave only the
                // closing tag as a diff line; allow it only with the block
                // action-column residual present in the same comparison.
                && ! ($hasBlockActionColumnResidual && $line === '</button>'),
        ));

        $this->assertSame(
            [],
            $unexplained,
            "BeMart's admin Block-list HTML diverged from EC-CUBE's beyond "
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

        // The same logical block list as BeMart's BlockStorageInterface seed:
        // one undeletable system block (ヘッダー).
        $block = new EcCubeStub([
            'id' => 'bk-header',
            'Id' => 'bk-header',
            'name' => 'ヘッダー',
            'file_name' => 'header',
            'deletable' => false,
        ]);

        return $twig->render('Content/block.twig', [
            'Blocks' => [$block],
            'BaseInfo' => new EcCubeStub(['shop_name' => 'EC-CUBE']),
            'eccube_config' => [
                'locale' => 'ja',
                'eccube_official_site_url' => 'https://www.ec-cube.net/',
                'eccube_community_site_url' => 'https://xoo.ps/eccube/',
                'eccube_document_url' => 'https://doc4.ec-cube.net/',
                'eccube_manual_url' => 'https://www.ec-cube.net/product/',
            ],
            'eccubeNav' => [],
            'menus' => ['content', 'block'],
            'plugin_assets' => [],
            'plugin_snippets' => [],
            'app' => new EcCubeStub([
                'user' => new EcCubeStub([
                    'name' => '管理者',
                    'login_date' => '2026-05-20 10:00:00',
                    'two_factor_auth_enabled' => false,
                ]),
                'request' => new EcCubeStub(['_route' => 'admin_content_block']),
            ]),
            'subtitle' => 'コンテンツ管理',
            'sub_title' => 'コンテンツ管理',
            'title' => 'ブロック管理',
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
