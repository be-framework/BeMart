<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminClassNameForm;
use MyVendor\BeMart\Tests\Resource\Admin\AdminJaMessages;
use MyVendor\BeMart\Tests\Resource\Admin\ProductJaMessages;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
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
 * Phase 3 HTML render tests for the admin ClassName list (規格管理).
 *
 * The page extends `admin-base.html.twig` via the idea-admin design language.
 *
 * L1 — required field / list data output (functional parity).
 * L2 — form action/method and link href/rel (hypermedia contract parity).
 *
 * The EC-CUBE rendering comparison is archived to @group ec-cube-parity-archived.
 */
final class AdminClassNameListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

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

    // ── Shell landmarks (idea-admin frame) ──────────────────────────────────

    public function testRendersCompleteHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/class-name/class-name-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testRendersIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/class-name/class-name-list')->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
            'class="idea-admin-content"',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "idea-admin shell landmark missing: {$landmark}");
        }
    }

    // ── L1: required field rendering ────────────────────────────────────────

    public function testRendersClassNameLabelInputWithCorrectId(): void
    {
        $html = $this->resource->get('page://self/admin/class-name/class-name-list')->toString();

        // AdminClassNameForm renders classNameLabel with id="admin_class_name_name"
        $this->assertStringContainsString('id="admin_class_name_name"', $html);
    }

    public function testRendersClassNameLabelInputWithCorrectName(): void
    {
        $html = $this->resource->get('page://self/admin/class-name/class-name-list')->toString();

        $this->assertStringContainsString('name="classNameLabel"', $html);
    }

    public function testDoesNotRenderBackendNameAsSubmittableField(): void
    {
        $html = $this->resource->get('page://self/admin/class-name/class-name-list')->toString();

        // backend_name is not projected by AdminClassNameListFetched (Wave 7 shallow slice)
        // and is not accepted by the Resource POST endpoint. It must not appear as a named input.
        $this->assertStringNotContainsString('name="backend_name"', $html);
    }

    // ── L1: list data output ────────────────────────────────────────────────

    public function testRendersSeededClassNameRows(): void
    {
        $ro = $this->resource->get('page://self/admin/class-name/class-name-list');
        $classNames = $ro->body['classNames'] ?? [];

        $this->assertNotEmpty($classNames, 'Fake seed must supply at least one className row');

        $html = $ro->toString();

        foreach ($classNames as $row) {
            $this->assertStringContainsString(
                (string) $row['classNameId'],
                $html,
                "classNameId '{$row['classNameId']}' not rendered in list",
            );
            $this->assertStringContainsString(
                (string) $row['name'],
                $html,
                "name '{$row['name']}' not rendered in list",
            );
        }
    }

    public function testRendersCountInOutput(): void
    {
        $ro = $this->resource->get('page://self/admin/class-name/class-name-list');

        $html = $ro->toString();
        $count = (int) ($ro->body['count'] ?? 0);

        $this->assertStringContainsString((string) $count, $html, 'count value not rendered');
    }

    // ── L2: form action / method (hypermedia contract) ──────────────────────

    public function testCreateFormPostsToClassNameListEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/class-name/class-name-list')->toString();

        $this->assertStringContainsString('action="/admin/class-name/class-name-list"', $html);
        $this->assertMatchesRegularExpression(
            '#<form[^>]+method=["\']post["\'][^>]*action="/admin/class-name/class-name-list"#i',
            $html,
            'Create form must POST to /admin/class-name/class-name-list',
        );
    }

    public function testPerRowEditFormPostsToClassNameEndpointWithPutTunnel(): void
    {
        $ro = $this->resource->get('page://self/admin/class-name/class-name-list');
        $classNames = $ro->body['classNames'] ?? [];

        if ($classNames === []) {
            $this->markTestSkipped('No className rows in Fake seed — cannot verify per-row edit form.');
        }

        $html = $ro->toString();

        // PUT tunnel: action contains _method=put
        $this->assertStringContainsString('_method=put', $html, 'Per-row edit form must carry _method=put tunnel');
        // The action targets the single-row endpoint
        $this->assertStringContainsString('/admin/class-name/class-name?classNameId=', $html);
    }

    public function testDeleteActionTargetsClassNameEndpointWithDeleteTunnel(): void
    {
        $ro = $this->resource->get('page://self/admin/class-name/class-name-list');
        $classNames = $ro->body['classNames'] ?? [];

        if ($classNames === []) {
            $this->markTestSkipped('No className rows in Fake seed — cannot verify delete action.');
        }

        $html = $ro->toString();

        $this->assertStringContainsString('_method=delete', $html, 'Delete action must carry _method=delete tunnel');
    }

    // ── L2: navigation links (href/rel contract) ─────────────────────────────

    public function testRendersLinkToClassCategoryListWithClassNameId(): void
    {
        $ro = $this->resource->get('page://self/admin/class-name/class-name-list');
        $classNames = $ro->body['classNames'] ?? [];

        if ($classNames === []) {
            $this->markTestSkipped('No className rows — cannot verify goClassCategoryList link.');
        }

        $html = $ro->toString();

        foreach ($classNames as $row) {
            $href = '/admin/class-category/class-category-list?classNameId=' . $row['classNameId'];
            $this->assertStringContainsString(
                $href,
                $html,
                "goClassCategoryList link missing for classNameId={$row['classNameId']}",
            );
        }
    }

    public function testRendersLinkToClassNameExport(): void
    {
        $html = $this->resource->get('page://self/admin/class-name/class-name-list')->toString();

        $this->assertStringContainsString('/admin/class-name/class-name-export', $html);
    }

    // ── EC-CUBE parity (archived) ────────────────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testClassNameListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup-parity test archived: template rebuilt in idea-admin-* design language. '
            . 'Functional/semantic coverage is provided by L1/L2 tests above.',
        );
    }

    // ── Helpers (preserved for archived test; kept for future reference) ─────

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
        '<title>規格管理 商品管理 - BeMart</title>',
        '<title>規格管理 商品管理 - EC-CUBE</title>',
    ];

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
            'name="csrfToken"',
            'admin_setting_shop_csv',
            'data-sort-no=',
            'admin_product_class_category?class_name_id=',
            'data-origin-value',
            'mode-edit',
        ] as $family) {
            if (str_contains($line, $family)) {
                return true;
            }
        }

        return false;
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
