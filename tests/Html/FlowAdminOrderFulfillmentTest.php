<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function dirname;
use function in_array;
use function is_string;

/**
 * HTML hypermedia walk of the admin order editor — driven entirely by the
 * rendered HTML's ALPS affordances (class/rel) over real HTTP.
 *
 * Uses a real order resolved from eccubedb_test at runtime (a unique
 * order_no in status=1/新規受付), so the walk runs against whatever fixture is
 * loaded (EC-CUBE reference or the dev fixture). The walk covers the
 * HTML-followable core of the order fulfillment flow:
 *
 *   1. Open the order list — assert it loads.
 *   2. Open the order edit page for the seeded order — assert doUpdateOrder.
 *   3. Submit doUpdateOrder (discount/charge/usePoint knobs) — confirm 200.
 *   4. Open the shipping-address editor — assert doUpdateShippingAddress.
 *   5. Submit doUpdateShippingAddress — confirm 200.
 *   6. Open the send-mail editor — assert doSendOrderMail.
 *   7. Submit doSendOrderMail — confirm 200.
 *
 * Steps skipped (no HTML affordance / JS-only / JSON-only):
 *   - doUpdateOrderStatus: rendered as JS-driven in-list button on OrderList
 *     (class="confirmationModal", not a <form class="doUpdateOrderStatus">);
 *     the POST endpoint exists but is not reachable via submit().
 *   - goOrder from OrderList: the list's edit link uses class="action-edit",
 *     not an ALPS rel/class token, so follow() cannot resolve it; we navigate
 *     directly via $this->resource->get().
 *   - doUpdateTrackingNumber: rendered as inline AJAX-only widget on OrderList
 *     (JS $.ajax call, not a <form>); no HTML affordance to follow.
 *   - goOrderMailConfirm / goOrderMail: rendered without an ALPS rel/class
 *     token in the current templates; navigated directly by URL.
 *   - doCreatePayment / storefront checkout steps (add-cart → non-member →
 *     confirm → checkout): storefront and payment-creation flows are covered
 *     by FlowCustomerPurchaseTest and the Hypermedia suite; the admin order
 *     HTML walk scopes to post-order admin operations on a seeded order.
 *   - doImportShippingCsv / goExportOrder / goExportShipping / goExportOrderPdf:
 *     CSV/PDF export is not a rendered HTML form affordance.
 */
final class FlowAdminOrderFulfillmentTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-order-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-order-html-csrf-token';

    /** Real order_no resolved from eccubedb_test (unique, status=1). */
    private static string $orderNo = '';

    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$dbSession = WorkflowDbSession::startForAdmin(self::ADMIN_ID, self::CSRF_TOKEN);

        $db = self::$dbSession->injector()->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);
        $orderNo = $db->fetchValue(
            'SELECT order_no FROM dtb_order WHERE order_status_id = 1'
            . ' GROUP BY order_no HAVING COUNT(*) = 1 ORDER BY order_no LIMIT 1',
        );
        assert(is_string($orderNo) && $orderNo !== '', 'no unique status=1 order in eccubedb_test');
        self::$orderNo = $orderNo;
    }

    public static function tearDownAfterClass(): void
    {
        self::$dbSession?->restore();
        self::$dbSession = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        assert(self::$dbSession instanceof WorkflowDbSession);

        return new HttpResource(
            '127.0.0.1:8129',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    // -------------------------------------------------------------------------
    // Step 1 — Order list
    // -------------------------------------------------------------------------

    /**
     * goOrderList: open the admin order list and confirm it renders.
     *
     * The list's per-row edit links use class="action-edit" (not an ALPS
     * token), so follow() cannot resolve them — subsequent steps navigate
     * directly by URL.
     */
    #[Alps('goOrderList')]
    public function testOpensOrderList(): ResourceObject
    {
        $list = $this->resource->get('page://self/admin/order-list');

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));

        return $list;
    }

    // -------------------------------------------------------------------------
    // Step 2 — Order edit: read the editor for the seeded order
    // -------------------------------------------------------------------------

    /**
     * goOrder: open the order editor shell pre-filled for the seeded order.
     *
     * Navigation is direct (goOrder is not rendered as an ALPS-class anchor
     * in OrderList). The editor carries class="doUpdateOrder" when $orderNo
     * is set (Edit.html.twig line 35: `isNew ? '' : 'doUpdateOrder'`).
     */
    #[Alps('goOrder')]
    #[Depends('testOpensOrderList')]
    public function testOpensOrderEditPage(ResourceObject $list): ResourceObject
    {
        $editor = $this->resource->get(
            'page://self/admin/order/edit',
            ['orderNo' => self::$orderNo],
        );

        $this->assertSame(Code::OK, $editor->code, (string) ($editor->view ?? $editor->code));
        $this->assertAffordance($editor, 'doUpdateOrder');

        return $editor;
    }

    // -------------------------------------------------------------------------
    // Step 3 — doUpdateOrder: submit discount / charge / usePoint knobs
    // -------------------------------------------------------------------------

    /**
     * doUpdateOrder: submit the edit form with adjusted money knobs.
     *
     * The form uses method="post" + hidden _method=put (body param).
     * CanonicalResourceRouter detects _method=put in the POST body and
     * routes to onPut on the Order resource, so we include _method in $fields.
     */
    #[Alps('doUpdateOrder')]
    #[Depends('testOpensOrderEditPage')]
    public function testUpdatesOrder(ResourceObject $editor): ResourceObject
    {
        $updated = $this->submit($editor, 'doUpdateOrder', [
            '_method' => 'put',
            'orderNo' => self::$orderNo,
            'discount' => '0',
            'charge' => '0',
            'usePoint' => '0',
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            'doUpdateOrder did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        return $updated;
    }

    // -------------------------------------------------------------------------
    // Step 4 — Shipping address: open the editor
    // -------------------------------------------------------------------------

    /**
     * goOrderShippingAddress: open the shipping-address editor for the
     * seeded order. Navigated directly — there is no ALPS-class anchor
     * rendered on the order-edit page pointing to this page.
     */
    #[Alps('goOrderShippingAddress')]
    #[Depends('testUpdatesOrder')]
    public function testOpensShippingAddressEditor(ResourceObject $updated): ResourceObject
    {
        $shippingEditor = $this->resource->get(
            'page://self/admin/order/shipping-address',
            ['orderNo' => self::$orderNo],
        );

        $this->assertSame(Code::OK, $shippingEditor->code, (string) ($shippingEditor->view ?? $shippingEditor->code));
        $this->assertAffordance($shippingEditor, 'doUpdateShippingAddress');

        return $shippingEditor;
    }

    // -------------------------------------------------------------------------
    // Step 5 — doUpdateShippingAddress: submit the shipping form
    // -------------------------------------------------------------------------

    /**
     * doUpdateShippingAddress: submit the shipping-address form.
     *
     * Like doUpdateOrder, the form uses method="post" + hidden _method=put,
     * so _method must be passed in $fields.
     */
    #[Alps('doUpdateShippingAddress')]
    #[Depends('testOpensShippingAddressEditor')]
    public function testUpdatesShippingAddress(ResourceObject $shippingEditor): ResourceObject
    {
        $updated = $this->submit($shippingEditor, 'doUpdateShippingAddress', [
            '_method' => 'put',
            'orderNo' => self::$orderNo,
            'name01' => '配送',
            'name02' => '太郎',
            'postalCode' => '1500001',
            'pref' => '13',
            'addr01' => '渋谷区',
            'addr02' => 'ワークフロー1-1-1',
            'phoneNumber' => '0312345678',
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            'doUpdateShippingAddress did not succeed: ' . (string) ($updated->view ?? $updated->code),
        );

        return $updated;
    }

    // -------------------------------------------------------------------------
    // Step 6 — Send mail: open the composition screen
    // -------------------------------------------------------------------------

    /**
     * doSendOrderMail (GET): open the mail-composition screen for the
     * seeded order. Navigated directly — there is no ALPS-class anchor
     * in the current templates pointing to /admin/order/send-mail.
     */
    #[Alps('doSendOrderMail')]
    #[Depends('testUpdatesShippingAddress')]
    public function testOpensSendMailScreen(ResourceObject $updated): ResourceObject
    {
        $mailScreen = $this->resource->get(
            'page://self/admin/order/send-mail',
            ['orderNo' => self::$orderNo],
        );

        $this->assertSame(Code::OK, $mailScreen->code, (string) ($mailScreen->view ?? $mailScreen->code));
        $this->assertAffordance($mailScreen, 'doSendOrderMail');

        return $mailScreen;
    }

    // -------------------------------------------------------------------------
    // Step 7 — doSendOrderMail: submit the send-mail form
    // -------------------------------------------------------------------------

    /**
     * doSendOrderMail: submit the mail form (POST — no _method override).
     *
     * The SendMail resource returns 200 + JSON body when the mail is sent.
     */
    #[Alps('doSendOrderMail')]
    #[Depends('testOpensSendMailScreen')]
    public function testSendsOrderMail(ResourceObject $mailScreen): ResourceObject
    {
        $sent = $this->submit($mailScreen, 'doSendOrderMail', [
            'orderNo' => self::$orderNo,
        ]);

        $this->assertTrue(
            in_array($sent->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            'doSendOrderMail did not succeed: ' . (string) ($sent->view ?? $sent->code),
        );

        return $sent;
    }

    // -------------------------------------------------------------------------
    // Step 8 — Shipping notify mail: open the confirmation screen
    // -------------------------------------------------------------------------

    /**
     * doSendShippingNotifyMail (GET): open the shipping-notification
     * confirmation screen for the seeded order. Navigated directly — the
     * order-edit page links here as a plain anchor (no ALPS-class token),
     * so follow() cannot resolve it. The confirmation page renders the
     * POST form carrying class="doSendShippingNotifyMail".
     */
    #[Alps('doSendShippingNotifyMail')]
    #[Depends('testSendsOrderMail')]
    public function testOpensShippingNotifyMailScreen(ResourceObject $sent): ResourceObject
    {
        $screen = $this->resource->get(
            'page://self/admin/order/shipping-notify-mail',
            ['orderNo' => self::$orderNo],
        );

        $this->assertSame(Code::OK, $screen->code, (string) ($screen->view ?? $screen->code));
        $this->assertAffordance($screen, 'doSendShippingNotifyMail');

        return $screen;
    }

    // -------------------------------------------------------------------------
    // Step 9 — doSendShippingNotifyMail: submit the confirmation form
    // -------------------------------------------------------------------------

    /**
     * doSendShippingNotifyMail (POST): submit the confirmation form
     * (POST — no _method override). The ShippingNotifyMail resource
     * returns 200 + JSON body when the notification mail is sent.
     */
    #[Alps('doSendShippingNotifyMail')]
    #[Depends('testOpensShippingNotifyMailScreen')]
    public function testSendsShippingNotifyMail(ResourceObject $screen): void
    {
        $sent = $this->submit($screen, 'doSendShippingNotifyMail', [
            'orderNo' => self::$orderNo,
        ]);

        $this->assertTrue(
            in_array($sent->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            'doSendShippingNotifyMail did not succeed: ' . (string) ($sent->view ?? $sent->code),
        );
    }
}
