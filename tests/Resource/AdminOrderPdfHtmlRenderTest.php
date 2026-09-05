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
 * Phase 3 — HTML render test for the admin 帳票出力 page.
 *
 * Verification layers:
 *   L1  Required data fields are present in the rendered output.
 *   L2  Action endpoints, HTTP methods, and link relations are correct.
 *   L3  Structural landmarks conform to the idea-admin shell contract.
 *
 * EC-CUBE parity checks have been retired: any test that asserted
 * EC-CUBE-specific markup (c-headerBar, c-contentsArea, btn-ec-*,
 * form id="order_pdf_form", etc.) is archived below with
 * @group ec-cube-parity-archived. The BeMart template is a clean-room
 * idea-admin design and does not reproduce EC-CUBE DOM structure.
 */
final class AdminOrderPdfHtmlRenderTest extends TestCase
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

    // ─────────────────────────────────────────────────────────────────
    // L1 — Required data / field output
    // ─────────────────────────────────────────────────────────────────

    public function testRendersSuccessfully(): void
    {
        $ro = $this->resource->get('page://self/admin/order/order-pdf');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testOutputIsFullHtmlDocument(): void
    {
        $html = $this->resource->get('page://self/admin/order/order-pdf')->toString();

        $this->assertStringContainsString('<!doctype html>', strtolower($html));
        $this->assertStringContainsString('</body>', $html);
        $this->assertStringContainsString('</html>', $html);
    }

    public function testAllFormFieldsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/order/order-pdf')->toString();

        foreach ([
            'order_pdf_title',
            'order_pdf_message1',
            'order_pdf_message2',
            'order_pdf_message3',
            'order_pdf_note1',
            'order_pdf_note2',
            'order_pdf_note3',
            'order_pdf_issue_date',
        ] as $fieldId) {
            $this->assertStringContainsString($fieldId, $html, "Form field missing: {$fieldId}");
        }
    }

    public function testOrderNoPassedThroughToHiddenInput(): void
    {
        $html = $this->resource
            ->get('page://self/admin/order/order-pdf', ['orderNo' => 'past0000000000000000000000000001'])
            ->toString();

        $this->assertStringContainsString('past0000000000000000000000000001', $html);
    }

    // ─────────────────────────────────────────────────────────────────
    // L2 — Action endpoints, HTTP methods, and link relations
    // ─────────────────────────────────────────────────────────────────

    public function testFormPostsToExportOrderPdfEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/order/order-pdf')->toString();

        $this->assertStringContainsString('/admin/order/export-order-pdf', $html);
    }

    public function testBackLinkTargetsOrderList(): void
    {
        $html = $this->resource->get('page://self/admin/order/order-pdf')->toString();

        $this->assertStringContainsString('/admin/order-list', $html);
        $this->assertStringContainsString('goOrderList', $html);
    }

    // ─────────────────────────────────────────────────────────────────
    // L3 — Structural landmarks (idea-admin shell contract)
    // ─────────────────────────────────────────────────────────────────

    public function testIdeaAdminShellLandmarkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/order-pdf')->toString();

        $this->assertStringContainsString('idea-admin-shell', $html);
        $this->assertStringContainsString('idea-admin-content', $html);
    }

    public function testIdeaAdminToolbarPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/order-pdf')->toString();

        $this->assertStringContainsString('idea-admin-toolbar', $html);
    }

    public function testFormIdIsOrderPdfForm(): void
    {
        $html = $this->resource->get('page://self/admin/order/order-pdf')->toString();

        $this->assertStringContainsString('id="order-pdf-form"', $html);
    }

    // ─────────────────────────────────────────────────────────────────
    // EC-CUBE parity — archived (clean-room design does not reproduce
    // EC-CUBE DOM structure; retained as documentation only)
    // ─────────────────────────────────────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testEcCubeMarkupStructure_archived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity check retired. BeMart uses a clean-room idea-admin design; '
            . 'EC-CUBE-specific classes (c-headerBar, c-contentsArea, btn-ec-*) '
            . 'and form id="order_pdf_form" are not reproduced.',
        );
    }
}
