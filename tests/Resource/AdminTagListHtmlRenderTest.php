<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

use function str_contains;

/**
 * HTML render tests for the admin Tag-management page (TagList).
 *
 * The template was rebuilt as a clean-room idea-admin design using
 * `idea-admin-*` vocabulary (public/assets/idea-store/css/idea-admin.css).
 * EC-CUBE-derived landmarks (c-container, c-mainNavArea, list-group
 * sortable-container, #DeleteModal, etc.) are no longer present.
 *
 * Test levels:
 *   L1 — required fields / list data rendered in output
 *   L2 — form action/method and link href/rel correctness
 *
 * EC-CUBE rendering parity tests are retired to the
 * @group ec-cube-parity-archived group and skipped unconditionally.
 */
final class AdminTagListHtmlRenderTest extends TestCase
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

    // ── L0: Document structure ─────────────────────────────────────────────

    public function testTagListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/tag/tag-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('idea-admin-shell', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ── L1: Frame landmarks ────────────────────────────────────────────────

    public function testTagListRendersIdeaAdminFrameLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/tag/tag-list')->toString();

        foreach ([
            'idea-admin-shell',
            'idea-admin-topbar',
            'idea-admin-sidebar',
            'idea-admin-content',
            'idea-admin-page-title',
        ] as $landmark) {
            $this->assertStringContainsString(
                $landmark,
                $html,
                "idea-admin frame landmark missing: {$landmark}",
            );
        }
    }

    // ── L1: Required field present ─────────────────────────────────────────

    /**
     * The tagName input must be rendered by AdminTagForm via
     * form.input('tagName')|raw. The form field carries id="admin_tag_name"
     * (set in AdminTagForm::init()).
     */
    public function testTagListRendersTagNameInput(): void
    {
        $html = $this->resource->get('page://self/admin/tag/tag-list')->toString();

        $this->assertStringContainsString(
            'id="admin_tag_name"',
            $html,
            'tagName input (id=admin_tag_name) not rendered by AdminTagForm',
        );
    }

    // ── L1: List data output ───────────────────────────────────────────────

    public function testTagListRendersSeededTagRows(): void
    {
        $html = $this->resource->get('page://self/admin/tag/tag-list')->toString();

        $this->assertStringContainsString('新商品', $html, 'seeded tag 新商品 missing from output');
        $this->assertStringContainsString('セール', $html, 'seeded tag セール missing from output');
    }

    public function testTagListRendersCountChip(): void
    {
        $ro   = $this->resource->get('page://self/admin/tag/tag-list');
        $html = $ro->toString();

        /** @var int $count */
        $count = $ro->body['count'];
        $this->assertStringContainsString(
            (string) $count,
            $html,
            'count value from resource body not rendered in page',
        );
    }

    // ── L2: Form action / method ───────────────────────────────────────────

    /**
     * The inline-create form must POST to /admin/tag/tag-list
     * (doCreateTag as declared by #[Link] on TagList::onGet).
     */
    public function testTagListCreateFormPostsToTagListEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/tag/tag-list')->toString();

        $this->assertStringContainsString(
            'action="/admin/tag/tag-list"',
            $html,
            'create form action must be /admin/tag/tag-list',
        );
        $this->assertStringContainsString(
            'method="post"',
            $html,
            'create form method must be post',
        );
    }

    /**
     * Each tag row must carry a delete link whose href targets
     * /admin/tag/tag?tagId={id} (doDeleteTag as declared by
     * #[Link(rel:'doDeleteTag', href:'page://self/admin/tag/tag', method:'delete')]).
     */
    public function testTagListDeleteLinkHrefsTargetTagEndpoint(): void
    {
        $ro   = $this->resource->get('page://self/admin/tag/tag-list');
        $html = $ro->toString();

        /** @var list<array{tagId:string, tagName:string}> $tags */
        $tags = $ro->body['tags'];
        $this->assertNotEmpty($tags, 'no tags in Fake seed — cannot verify delete links');

        foreach ($tags as $tag) {
            $expected = '/admin/tag/tag?tagId=' . $tag['tagId'];
            $this->assertTrue(
                str_contains($html, $expected),
                "delete link href for tagId={$tag['tagId']} not found; expected: {$expected}",
            );
        }
    }

    /**
     * The CSRF hidden input must be present in the create form.
     * Field name "csrfToken" matches CsrfToken service convention.
     */
    public function testTagListCreateFormHasCsrfHiddenInput(): void
    {
        $html = $this->resource->get('page://self/admin/tag/tag-list')->toString();

        $this->assertStringContainsString(
            'name="csrfToken"',
            $html,
            'CSRF hidden input (name=csrfToken) missing from create form',
        );
    }

    // ── Archived: EC-CUBE rendering parity ────────────────────────────────

    /**
     * EC-CUBE rendering parity comparison is no longer applicable after the
     * clean-room rebuild. The template now uses idea-admin-* design language
     * and does not share DOM structure with EC-CUBE's admin Twig.
     *
     * @group ec-cube-parity-archived
     */
    public function testTagListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE rendering parity retired: TagList.html.twig was rebuilt '
            . 'as a clean-room idea-admin template and no longer shares DOM '
            . 'structure with EC-CUBE\'s Product/tag.twig. '
            . 'Functional/semantic coverage is provided by the L1/L2 tests above.',
        );
    }
}
