<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderFetched;
use MyVendor\BeMart\Be\Final\AdminOrderUpdated;
use MyVendor\BeMart\Be\Input\AdminUpdateOrderInput;
use MyVendor\BeMart\Be\Input\GetAdminOrderInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE goOrder / doUpdateOrder — 受注詳細 (Wave 7).
 *
 *   - GET → goOrder        (read header + items + customer summary)
 *   - PUT → doUpdateOrder  (partial-update: discount / charge / usePoint)
 *
 * The status-flip flow (doUpdateOrderStatus) lives at a sibling resource
 * `/admin/order-status` ({@see OrderStatus}) — it is a sub-resource of
 * the order with workflow-significant semantics, so we keep its URL
 * distinct rather than overloading PUT here. Choice (B) from the Wave 7
 * design note.
 *
 * Admin-only — both methods raise {@see UnauthorizedAdminAccessException}
 * via the Be Final when the admin firewall is unset. CSRF is enforced
 * on PUT only (read-only GET does not need a token).
 *
 * Failure mapping (cross-firewall AUTHZ → existence ladder):
 *   - Invalid CSRF (PUT)                    → 403
 *   - SemanticVariableException             → 400 (input format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - OrderNotFoundException                → 404 (unknown orderNo)
 *
 * The 403-before-404 ordering matches the Be Final's check sequence —
 * an admin-anonymous client learns NOTHING about which orderNos resolve.
 *
 * Mass-assignment safety (PUT): see {@see AdminUpdateOrderInput} — only
 * discount / charge / usePoint are editable. `orderNo` IS in the body
 * because it is the target selector (admin needs to pick which order),
 * but `customerId` / `total` / `orderStatus` are NOT writable from
 * here.
 */
class Order extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * Wave 7: orderNo comes from the admin UI (click on an order-list
     * row, or pasted into the URL).
     *
     * @psalm-taint-source input $orderNo
     */
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    #[Link(rel: 'doUpdateOrder', href: 'page://self/admin/order', method: 'put')]
    #[Link(rel: 'doUpdateOrderStatus', href: 'page://self/admin/order-status', method: 'post')]
    public function onGet(string $orderNo): static
    {
        try {
            $final = ($this->becoming)(new GetAdminOrderInput(orderNo: $orderNo));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (OrderNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された注文は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof AdminOrderFetched);

        $this->code = Code::OK;
        $this->body = [
            'orderNo' => $final->orderNo,
            'preOrderId' => $final->preOrderId,
            'customerId' => $final->customerId,
            'paymentMethodId' => $final->paymentMethodId,
            'subtotal' => $final->subtotal,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
            'charge' => $final->charge,
            'discount' => $final->discount,
            'tax' => $final->tax,
            'total' => $final->total,
            'paymentTotal' => $final->paymentTotal,
            'addPoint' => $final->addPoint,
            'usePoint' => $final->usePoint,
            'orderStatus' => $final->orderStatus,
            'orderDate' => $final->orderDate,
            'paymentDate' => $final->paymentDate,
            'items' => $final->items,
            'itemCount' => $final->itemCount,
            'customer' => $final->customer,
        ];

        return $this;
    }

    /**
     * Wave 7: every editable field is admin-form input. The orderNo
     * selector is also admin-controlled. Same taint discipline as the
     * Wave 5 / Wave 6 admin resources.
     *
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $discount
     * @psalm-taint-source input $charge
     * @psalm-taint-source input $usePoint
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    public function onPut(
        string $orderNo,
        int|null $discount = null,
        int|null $charge = null,
        int|null $usePoint = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminUpdateOrderInput(
                orderNo: $orderNo,
                discount: $discount,
                charge: $charge,
                usePoint: $usePoint,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'orderNo' => $orderNo,
            ];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (OrderNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された注文は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof AdminOrderUpdated);

        $this->code = Code::OK;
        $this->body = [
            'orderNo' => $final->orderNo,
            'customerId' => $final->customerId,
            'discount' => $final->discount,
            'charge' => $final->charge,
            'usePoint' => $final->usePoint,
            'subtotal' => $final->subtotal,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
            'tax' => $final->tax,
            'total' => $final->total,
            'paymentTotal' => $final->paymentTotal,
            'orderStatus' => $final->orderStatus,
        ];

        return $this;
    }
}
