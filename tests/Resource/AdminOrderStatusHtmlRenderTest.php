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
 * Phase 3 — HTML render verification for the admin 受注対応状況設定 page.
 *
 * Verification levels:
 *   L1 — required data present (status rows, CSRF token, form fields)
 *   L2 — action surface (form action, method override, PUT semantics)
 *   Frame — idea-admin shell / content landmarks
 *
 * The old EC-CUBE-parity markup assertions (c-container, c-headerBar,
 * id="order_status_row_1" in Bootstrap context, etc.) are archived below
 * as @group ec-cube-parity-archived. They are skipped — the template has
 * been rewritten clean-room in idea-admin vocabulary.
 */
final class AdminOrderStatusHtmlRenderTest extends TestCase
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

    // ──────────────────────────────────────────────────────────────────
    // Frame landmark checks
    // ──────────────────────────────────────────────────────────────────

    public function testRendersFullHtmlDocument(): void
    {
        $ro   = $this->resource->get('page://self/admin/order-status');
        $html = $ro->toString();

        $this->assertSame(Code::OK, $ro->code);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testIdeaAdminShellLandmarkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order-status')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html, 'idea-admin-shell wrapper required');
        $this->assertStringContainsString('class="idea-admin-content"', $html, 'idea-admin-content landmark required');
    }

    // ──────────────────────────────────────────────────────────────────
    // L1 — required data present
    // ──────────────────────────────────────────────────────────────────

    public function testCsrfTokenHiddenInputPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order-status')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html, 'CSRF hidden input required');
    }

    public function testAllFiveStatusRowsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/order-status')->toString();

        // Each row is anchored by data-status-id matching the DEFAULT_ROWS ids: 1,3,4,5,6
        foreach ([1, 3, 4, 5, 6] as $id) {
            $this->assertStringContainsString(
                'data-status-id="' . $id . '"',
                $html,
                "Status row id={$id} must be rendered",
            );
        }
    }

    public function testAdminNameFieldsPresentForAllRows(): void
    {
        $html = $this->resource->get('page://self/admin/order-status')->toString();

        // nameKey fields: order_status_{id}_name
        foreach ([1, 3, 4, 5, 6] as $id) {
            $this->assertStringContainsString(
                'order_status_' . $id . '_name',
                $html,
                "Admin name field for status id={$id} required",
            );
        }
    }

    public function testCustomerNameFieldsPresentForAllRows(): void
    {
        $html = $this->resource->get('page://self/admin/order-status')->toString();

        // customerNameKey fields: order_status_{id}_customer_order_status_name
        foreach ([1, 3, 4, 5, 6] as $id) {
            $this->assertStringContainsString(
                'order_status_' . $id . '_customer_order_status_name',
                $html,
                "Customer name field for status id={$id} required",
            );
        }
    }

    public function testColorFieldsPresentForAllRows(): void
    {
        $html = $this->resource->get('page://self/admin/order-status')->toString();

        // colorKey fields: order_status_{id}_color
        foreach ([1, 3, 4, 5, 6] as $id) {
            $this->assertStringContainsString(
                'order_status_' . $id . '_color',
                $html,
                "Color field for status id={$id} required",
            );
        }
    }

    public function testDisplayOrderCountFieldsPresentForAllRows(): void
    {
        $html = $this->resource->get('page://self/admin/order-status')->toString();

        // displayOrderCountKey fields: order_status_{id}_display_order_count
        foreach ([1, 3, 4, 5, 6] as $id) {
            $this->assertStringContainsString(
                'order_status_' . $id . '_display_order_count',
                $html,
                "displayOrderCount field for status id={$id} required",
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // L2 — action / method surface
    // ──────────────────────────────────────────────────────────────────

    public function testFormActionPointsToOrderStatusEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/order-status')->toString();

        $this->assertStringContainsString('action="/admin/order-status"', $html, 'Form must target /admin/order-status');
    }

    public function testFormUsesMethodOverridePut(): void
    {
        $html = $this->resource->get('page://self/admin/order-status')->toString();

        // PUT tunnel via _method hidden (BEAR.Sunday convention)
        $this->assertStringContainsString('name="_method"', $html, '_method hidden required for PUT tunnel');
        $this->assertStringContainsString('value="PUT"', $html, '_method value must be PUT');
    }

    public function testFormIdPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order-status')->toString();

        $this->assertStringContainsString('id="order_status_form"', $html, 'Form id required');
    }

    public function testSubmitButtonPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order-status')->toString();

        $this->assertStringContainsString('type="submit"', $html, 'Submit button required');
    }

    // ──────────────────────────────────────────────────────────────────
    // EC-CUBE parity archived — old markup assertions
    // The template has been rewritten; these are kept for audit trail.
    // ──────────────────────────────────────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testEcCubeMarkupArchived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity markup check archived. '
            . 'Template rewritten in idea-admin vocabulary; '
            . 'functional checks above replace this assertion.',
        );

        // The following assertions belonged to the old EC-CUBE-derived template
        // and are preserved here for reference only:
        //   assertStringContainsString('<header class="c-headerBar">', $html)
        //   assertStringContainsString('<div class="c-contentsArea">', $html)
        //   assertStringContainsString('id="order_status_row_1"', $html)
        //   assertStringContainsString('<div class="c-container">', $html)
        //   assertStringContainsString('受注対応状況', $html)
    }
}
