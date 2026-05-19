<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderCreated;
use MyVendor\BeMart\Be\Input\AdminCreateOrderInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doCreateOrder — 受注を手動作成する (Wave 9η,
 * **Phase 2 simplification**).
 *
 *   POST /admin/order/create
 *
 * Admin-created orders bypass Cart, PaymentMethod::verify(), and the
 * customer-side checkout entirely (EC-CUBE supports this for phone /
 * FAX orders entered by back-office staff). Wave 9η wires the AUTHZ +
 * URL surface; the PurchaseFlow recompute (tax / delivery / stock) is
 * Phase 2.
 *
 * The Final allocates the orderNo server-side via the existing
 * {@see \MyVendor\BeMart\Be\Reason\Service\OrderNumberGeneratorInterface}
 * — admins cannot inject a chosen orderNo.
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (field formats)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 */
class Create extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $customerId
     * @psalm-taint-source input $paymentMethodId
     * @psalm-taint-source input $subtotal
     * @psalm-taint-source input $deliveryFeeTotal
     * @psalm-taint-source input $charge
     * @psalm-taint-source input $discount
     * @psalm-taint-source input $tax
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    public function onPost(
        string $customerId,
        int $paymentMethodId,
        int $subtotal,
        int $deliveryFeeTotal = 0,
        int $charge = 0,
        int $discount = 0,
        int $tax = 0,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminCreateOrderInput(
                customerId: $customerId,
                paymentMethodId: $paymentMethodId,
                subtotal: $subtotal,
                deliveryFeeTotal: $deliveryFeeTotal,
                charge: $charge,
                discount: $discount,
                tax: $tax,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminOrderCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = '/admin/order?orderNo=' . $final->orderNo;
        $this->body = [
            'orderNo' => $final->orderNo,
            'customerId' => $final->customerId,
            'paymentMethodId' => $final->paymentMethodId,
            'subtotal' => $final->subtotal,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
            'charge' => $final->charge,
            'discount' => $final->discount,
            'tax' => $final->tax,
            'total' => $final->total,
            'paymentTotal' => $final->paymentTotal,
            'orderStatus' => $final->orderStatus,
            'orderDate' => $final->orderDate,
        ];

        return $this;
    }
}
