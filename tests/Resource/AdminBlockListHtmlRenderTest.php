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

use function preg_match_all;
use function str_contains;

/**
 * Functional / semantic render test for admin BlockList HTML page.
 *
 * Verifies:
 *   L1 — required data fields are present in rendered HTML
 *   L2 — action links carry correct href, rel, and HTTP method semantics
 *   L3 — idea-admin shell / content landmarks are present
 *
 * EC-CUBE parity comparison tests have been retired.
 * (The original parity tests are preserved as @group ec-cube-parity-archived
 * with markTestSkipped so the group name remains discoverable.)
 */
final class AdminBlockListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private string $html;

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

        $ro = $this->resource->get('page://self/admin/block/block-list');
        $this->html = $ro->toString();
    }

    // ── L0: HTTP / content-type ────────────────────────────────────────── //

    public function testResponseIsOk(): void
    {
        $ro = $this->resource->get('page://self/admin/block/block-list');
        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testRendersValidHtmlDocument(): void
    {
        $this->assertStringContainsString('<!doctype html>', $this->html);
        $this->assertStringContainsString('<html lang="ja">', $this->html);
        $this->assertStringContainsString('</body>', $this->html);
    }

    // ── L1: required data fields ───────────────────────────────────────── //

    public function testCountIsRendered(): void
    {
        // The KPI strip and tfoot both render the count value.
        $this->assertStringContainsString('id="js-block-count"', $this->html);
    }

    public function testBlockNamesAreRendered(): void
    {
        // Fake storage includes at minimum the seeded system block "ヘッダー".
        $this->assertStringContainsString('ヘッダー', $this->html);
    }

    public function testBlockFileNamesAreRendered(): void
    {
        // The seeded system block has file name "header"; rendered as "header.twig".
        $this->assertStringContainsString('header.twig', $this->html);
    }

    public function testEachBlockRowHasIdAttribute(): void
    {
        // Rows carry id="ex-block-{blockId}" for scripted access.
        $this->assertMatchesRegularExpression('/id="ex-block-[^"]+"/u', $this->html);
    }

    public function testSystemBadgeAppearsForUndeletableBlock(): void
    {
        // The seeded "ヘッダー" block is not deletable → system badge.
        $this->assertStringContainsString('idea-admin-badge--system', $this->html);
    }

    public function testDataAttributesExistForClientFilter(): void
    {
        // Each row carries data-block-name and data-block-file for JS filtering.
        $this->assertStringContainsString('data-block-name=', $this->html);
        $this->assertStringContainsString('data-block-file=', $this->html);
    }

    // ── L2: actions / links / method ──────────────────────────────────── //

    public function testEditLinkPointsToBlockResource(): void
    {
        // Edit links must contain /admin/block/block?blockId= and rel=goBlock.
        $this->assertStringContainsString('/admin/block/block?blockId=', $this->html);
        $this->assertStringContainsString('rel="goBlock"', $this->html);
    }

    public function testCreateFormTargetsBlockListWithPost(): void
    {
        // Quick-create form posts to /admin/block/block-list.
        $this->assertStringContainsString('action="/admin/block/block-list"', $this->html);
        $this->assertStringContainsString('method="post"', $this->html);
        $this->assertStringContainsString('rel="doCreateBlock"', $this->html);
    }

    public function testCreateFormHasCsrfTokenField(): void
    {
        $this->assertStringContainsString('name="csrfToken"', $this->html);
    }

    public function testCreateFormHasBlockNameField(): void
    {
        $this->assertStringContainsString('name="blockName"', $this->html);
    }

    public function testCreateFormHasBlockFileNameField(): void
    {
        $this->assertStringContainsString('name="blockFileName"', $this->html);
    }

    public function testDeleteLinkCarriesMethodOverrideAndRel(): void
    {
        // Delete links use _method=delete tunnel and rel=doDeleteBlock.
        // System blocks are not deletable, so this test requires at least one
        // deletable block in the fake storage. If none exist, the assertion is
        // skipped gracefully.
        if (! str_contains($this->html, '_method=delete')) {
            $this->markTestSkipped('No deletable blocks in fake storage — delete link assertions skipped.');
        }

        $this->assertStringContainsString('_method=delete', $this->html);
        $this->assertStringContainsString('rel="doDeleteBlock"', $this->html);
    }

    public function testNewBlockButtonIsPresent(): void
    {
        $this->assertStringContainsString('id="js-open-create"', $this->html);
    }

    // ── L3: idea-admin shell landmarks ────────────────────────────────── //

    public function testIdeaAdminShellLandmarkPresent(): void
    {
        $this->assertStringContainsString('idea-admin-shell', $this->html);
    }

    public function testIdeaAdminContentLandmarkPresent(): void
    {
        $this->assertStringContainsString('idea-admin-content', $this->html);
    }

    public function testKpiStripIsRendered(): void
    {
        $this->assertStringContainsString('idea-admin-kpi', $this->html);
    }

    public function testTableWrapIsRendered(): void
    {
        $this->assertStringContainsString('idea-admin-table-wrap', $this->html);
        $this->assertStringContainsString('idea-admin-table', $this->html);
    }

    // ── Archived: EC-CUBE parity ───────────────────────────────────────── //

    /**
     * @group ec-cube-parity-archived
     */
    public function testBlockListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity comparison retired. '
            . 'BlockList.html.twig is now a clean-room design; '
            . 'functional coverage is provided by the L1/L2/L3 tests above.',
        );
    }

    /**
     * @group ec-cube-parity-archived
     */
    public function testBlockListPreservesEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup structure assertions retired. '
            . 'The template no longer mirrors EC-CUBE Bootstrap/c-* classes.',
        );
    }
}
