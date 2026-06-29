<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\BlockEntity;
use MyVendor\BeMart\Be\Reason\Query\BlockStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * Phase 3 — semantic/functional parity check for the admin Block-edit page.
 *
 * The template has been clean-room rebuilt using the idea-admin design
 * vocabulary. These tests verify:
 *  L1 — required fields render with correct name/id attributes
 *  L2 — form action, method, and CSRF hidden input are correct
 *  Frame — the page extends admin-base.html.twig (idea-admin-shell landmark)
 *
 * The EC-CUBE rendering comparison was retired when the template was
 * rebuilt clean-room (@group ec-cube-parity-archived).
 */
final class AdminBlockHtmlRenderTest extends TestCase
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

    // ── Frame ──────────────────────────────────────────────────────────────

    public function testBlockEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/block/block');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testBlockEditUsesIdeaAdminShellLandmark(): void
    {
        $html = $this->resource->get('page://self/admin/block/block')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html);
        $this->assertStringContainsString('class="idea-admin-content"', $html);
    }

    // ── L1: required fields ────────────────────────────────────────────────

    public function testBlockEditRendersBlockNameField(): void
    {
        $html = $this->resource->get('page://self/admin/block/block')->toString();

        $this->assertStringContainsString('id="block_name"', $html);
        $this->assertStringContainsString('name="blockName"', $html);
    }

    public function testBlockEditRendersBlockFileNameField(): void
    {
        $html = $this->resource->get('page://self/admin/block/block')->toString();

        $this->assertStringContainsString('id="block_file_name"', $html);
        $this->assertStringContainsString('name="blockFileName"', $html);
    }

    public function testBlockEditRendersBlockHtmlField(): void
    {
        $html = $this->resource->get('page://self/admin/block/block')->toString();

        $this->assertStringContainsString('id="block_block_html"', $html);
        $this->assertStringContainsString('disabled="disabled"', $html);
    }

    // ── L2: form action / method / CSRF ───────────────────────────────────

    public function testNewBlockFormPostsToBlockList(): void
    {
        $html = $this->resource->get('page://self/admin/block/block')->toString();

        // New-block form: POST to block-list (doCreateBlock)
        $this->assertStringContainsString('action="/admin/block/block-list"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    public function testBlockFormIncludesCsrfHiddenInput(): void
    {
        $html = $this->resource->get('page://self/admin/block/block')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    public function testEditBlockFormPostsToPutEndpoint(): void
    {
        // Provide a fake BlockStorageInterface that seeds a block with id 'bk-test'
        $fakeBlock = new BlockEntity('bk-test', 'テストブロック', 'test_block', true);
        $fakeStorage = new class ($fakeBlock) implements BlockStorageInterface {
            public function __construct(private readonly BlockEntity $block)
            {
            }

            /** @return list<BlockEntity> */
            public function list(): array
            {
                return [$this->block];
            }

            public function item(string $blockId): BlockEntity|null
            {
                return $this->block->blockId === $blockId ? $this->block : null;
            }

            public function put(BlockEntity $block): void
            {
            }

            public function delete(string $blockId): void
            {
            }
        };
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session, $fakeStorage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly BlockStorageInterface $blockStorage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
                $this->bind(BlockStorageInterface::class)->toInstance($this->blockStorage);
            }
        });
        $resource = $injector->getInstance(ResourceInterface::class);

        $html = $resource->get('page://self/admin/block/block', ['blockId' => 'bk-test'])->toString();

        // Edit-block form: PUT tunnel via _method=put
        $this->assertStringContainsString('bk-test', $html);
        $this->assertStringContainsString('_method=put', $html);
    }

    // ── L2: navigation link ────────────────────────────────────────────────

    public function testBlockEditContainsBackLinkToBlockList(): void
    {
        $html = $this->resource->get('page://self/admin/block/block')->toString();

        $this->assertStringContainsString('href="/admin/block/block-list"', $html);
    }

    // ── EC-CUBE parity (retired) ───────────────────────────────────────────

    /**
     * EC-CUBE rendering comparison retired — template rebuilt clean-room.
     *
     * @group ec-cube-parity-archived
     */
    public function testBlockEditHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE rendering comparison retired. '
            . 'Template rebuilt clean-room using idea-admin design language. '
            . 'Functional parity verified by L1/L2 assertions above.'
        );
    }
}
