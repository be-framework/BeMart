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

/**
 * Phase 3 — HTML render verification for the admin 出荷CSV取込 page.
 *
 * Verifies:
 *   L1  Required data fields / form fields are present in output.
 *   L2  Resource contract: action URL, method, CSRF field, link hrefs with rel.
 *   Frame  idea-admin-shell / idea-admin-content landmarks present.
 */
final class AdminOrderCsvShippingHtmlRenderTest extends TestCase
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

    /** L0 — document envelope */
    public function testRendersCompleteHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/order/import-shipping');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    /** Frame — idea-admin-shell + idea-admin-content landmarks */
    public function testRendersIdeaAdminFrameLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/order/import-shipping')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html, 'idea-admin-shell landmark missing');
        $this->assertStringContainsString('class="idea-admin-content"', $html, 'idea-admin-content landmark missing');
    }

    /** L1 — form field: csv textarea present and named correctly */
    public function testCsvTextareaFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/import-shipping')->toString();

        $this->assertStringContainsString('name="csv"', $html, 'csv textarea field missing');
        $this->assertStringContainsString('<textarea', $html, 'textarea element missing');
    }

    /** L2 — action: POST /admin/order/import-shipping (doImportShippingCsv) */
    public function testFormPostsToCorrectEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/order/import-shipping')->toString();

        $this->assertStringContainsString('method="post"', $html, 'form POST method missing');
        $this->assertStringContainsString('action="/admin/order/import-shipping"', $html, 'form action missing');
    }

    /** L2 — CSRF token hidden field */
    public function testCsrfTokenHiddenFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/import-shipping')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html, 'csrfToken hidden field missing');
        $this->assertStringContainsString('type="hidden"', $html, 'hidden input missing');
    }

    /** L2 — goExportShipping link present with rel attribute */
    public function testExportShippingLinkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/import-shipping')->toString();

        $this->assertStringContainsString('/admin/order/export-shipping', $html, 'goExportShipping link missing');
        $this->assertStringContainsString('rel="goExportShipping"', $html, 'goExportShipping rel missing');
    }

    /** L2 — goOrderList link present with rel attribute */
    public function testOrderListLinkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/import-shipping')->toString();

        $this->assertStringContainsString('/admin/order-list', $html, 'goOrderList link missing');
        $this->assertStringContainsString('rel="goOrderList"', $html, 'goOrderList rel missing');
    }

    /**
     * EC-CUBE parity check — archived (old markup removed in idea-admin rebuild).
     *
     * @group ec-cube-parity-archived
     */
    public function testCsvShippingPreservesEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity check archived: idea-admin clean-room rebuild removed ' .
            'c-headerBar / c-contentsArea / csv_shipping_form / csv_shipping_import_file markup. ' .
            'Functional verification is covered by the L1/L2 tests above.'
        );
    }
}
