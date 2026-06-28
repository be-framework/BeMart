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
 * HTML render checks for admin 受注編集 / 受注登録 (Admin\Order\Edit).
 *
 * All assertions are functional / semantic — they verify the resource
 * contract (data fields, actions, links) rather than implementation
 * markup details. Frame landmark assertions use the idea-admin vocabulary.
 *
 * L1 — required data / form fields are present in the rendered output.
 * L2 — action/method contract and link href/rel are correct.
 * Frame — idea-admin-shell / idea-admin-content landmarks exist.
 */
final class AdminOrderEditHtmlRenderTest extends TestCase
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

    // ── Smoke ─────────────────────────────────────────────────────────

    /** Page returns HTTP 200 and produces a complete HTML document. */
    public function testOrderEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/order/edit');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ── Frame landmarks (idea-admin vocabulary) ───────────────────────

    /**
     * The idea-admin shell and content regions are rendered.
     *
     * These are the canonical frame landmarks for every admin page.
     * Replaces any former c-container / c-mainNavArea checks.
     */
    public function testOrderEditRendersIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/order/edit')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html);
        $this->assertStringContainsString('class="idea-admin-content"', $html);
    }

    // ── L1 — required field presence ─────────────────────────────────

    /**
     * The three contact fields declared in AdminOrderEditForm::init() are rendered.
     *
     * Field IDs are authoritative — they are set by setAttribs() in the form class
     * and must match what the template renders via form.input().
     */
    public function testOrderEditRendersContactFields(): void
    {
        $html = $this->resource->get('page://self/admin/order/edit')->toString();

        // name01 —姓 (required)
        $this->assertStringContainsString('id="order_name_name01"', $html);
        // name02 — 名
        $this->assertStringContainsString('id="order_name_name02"', $html);
        // email
        $this->assertStringContainsString('id="order_email"', $html);
    }

    /**
     * The billing adjustment fields are rendered.
     *
     * discount / charge / usePoint are the three editable financial knobs
     * served by AdminUpdateOrderInput → doUpdateOrder (PUT /admin/order).
     */
    public function testOrderEditRendersBillingAdjustmentFields(): void
    {
        $html = $this->resource->get('page://self/admin/order/edit')->toString();

        $this->assertStringContainsString('id="order_discount"', $html);
        $this->assertStringContainsString('id="order_charge"', $html);
        $this->assertStringContainsString('id="order_use_point"', $html);
    }

    /**
     * The line-item table landmark is present.
     *
     * id="order_item_table" is the semantic anchor for the product detail
     * table — always rendered even on the blank new-order path.
     */
    public function testOrderEditRendersLineItemTable(): void
    {
        $html = $this->resource->get('page://self/admin/order/edit')->toString();

        $this->assertStringContainsString('id="order_item_table"', $html);
    }

    // ── L2 — action / method contract ────────────────────────────────

    /**
     * On the new-order path the form targets POST /admin/order/create.
     *
     * action and method are derived from the #[Link] declarations on the
     * Edit resource (rel: 'doCreateOrder' → POST /admin/order/create).
     * The blank editor path is exercised by GET /admin/order/edit (no orderNo).
     */
    public function testOrderEditNewOrderFormAction(): void
    {
        $html = $this->resource->get('page://self/admin/order/edit')->toString();

        $this->assertStringContainsString('action="/admin/order/create"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    // ── L2 — navigation links ─────────────────────────────────────────

    /**
     * The back-navigation link targets the order list with the correct rel.
     *
     * href and rel are derived from
     *   #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
     * on the Edit resource.
     */
    public function testOrderEditBackNavLinkHrefAndRel(): void
    {
        $html = $this->resource->get('page://self/admin/order/edit')->toString();

        $this->assertStringContainsString('href="/admin/order-list"', $html);
        $this->assertStringContainsString('rel="goOrderList"', $html);
    }
}
