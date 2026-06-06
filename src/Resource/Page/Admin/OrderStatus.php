<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderStatusUpdated;
use MyVendor\BeMart\Be\Input\AdminUpdateOrderStatusInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminOrderStatusForm;
use Ray\WebFormModule\FormFactory;

use function assert;
use function count;

/**
 * EC-CUBE doUpdateOrderStatus — 受注ステータス変更 (Wave 7).
 *
 *   POST → flip the persisted orderStatus column on one order.
 *
 * Status-flip is a sub-resource of the order rather than a method on
 * {@see Order} because its semantics are workflow-significant (the
 * change has cascade effects in EC-CUBE — cancel reverses stock /
 * points, ship awards points, etc. — which the Phase 2 PurchaseFlow
 * adapter will wire up). Surfacing a distinct URL (`/admin/order-status`)
 * keeps the audit story explicit and matches the ALPS-level separation
 * of `doUpdateOrder` vs `doUpdateOrderStatus`.
 *
 * Choice of POST (not PATCH): BEAR.Sunday's natural verb set is GET /
 * POST / PUT / DELETE — PATCH is not first-class. POST against this
 * sub-resource carries the same shape as Wave 6's DeleteCustomer
 * (POST + CSRF + target id in body).
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (orderStatus format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - OrderNotFoundException                → 404 (unknown orderNo)
 *
 * Idempotency: when the supplied `orderStatus` matches the persisted
 * value, the projection carries `changed=false` and the storage is
 * untouched. A replay returns 200 with the same body shape — mirrors
 * AdminCustomerDeleted's `alreadyDeleted` discipline (Wave 6).
 *
 * Mass-assignment safety: only `orderNo` (target) and `orderStatus`
 * (new value) are accepted; no path here reaches the other dtb_order
 * columns.
 */
class OrderStatus extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE 受注対応状況設定 — Setting/Shop Tier-2.
     *
     * Thin GET renderer for `Setting/Shop/order_status.twig`. BeMart
     * has a per-order status-change transition on POST, but not yet a
     * master-data transition for editing status labels/colors.
     */
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminOrderStatusForm::class);
        assert($form instanceof AdminOrderStatusForm);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'orderStatuses' => AdminOrderStatusForm::rows(),
        ];

        return $this;
    }

    /**
     * EC-CUBE doUpdateOrderStatusList — settings-side status list update.
     *
     * This is intentionally separate from {@see onPost()}, which
     * updates one order's workflow status. The settings screen submits
     * the master-list shape; this wave exposes a concrete CSRF/AUTHZ
     * surface and returns the accepted payload count without claiming
     * full EC-CUBE master-data persistence yet.
     *
     * @param array<array-key, mixed> $orderStatuses
     *
     * @psalm-taint-source input $orderStatuses
     * @psalm-taint-source input $orderStatusRows
     */
    #[CsrfProtected]
    public function onPut(
        array $orderStatuses = [],
        string|null $orderStatusRows = null,
    ): static {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'doUpdateOrderStatusList',
            'count' => count($orderStatuses),
            'orderStatusRows' => $orderStatusRows,
            'message' => '対応状況一覧更新Resourceへ到達しました。',
        ];

        return $this;
    }

    /**
     * Wave 7: both `orderNo` and `orderStatus` are admin-form input
     * (orderNo selected from the order-list row, orderStatus picked
     * from a dropdown of dtb_order_status values).
     *
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $orderStatus
     */
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    #[Link(rel: 'goOrderShippingAddress', href: 'page://self/admin/order/shipping-address', method: 'get')]
    #[CsrfProtected]
    public function onPost(
        string $orderNo,
        int $orderStatus,
    ): static {
        $final = ($this->becoming)(new AdminUpdateOrderStatusInput(
            orderNo: $orderNo,
            orderStatus: $orderStatus,
        ));

        assert($final instanceof AdminOrderStatusUpdated);

        $this->code = Code::OK;
        $this->body = [
            'orderNo' => $final->orderNo,
            'previousStatus' => $final->previousStatus,
            'orderStatus' => $final->orderStatus,
            'changed' => $final->changed,
        ];

        return $this;
    }
}
