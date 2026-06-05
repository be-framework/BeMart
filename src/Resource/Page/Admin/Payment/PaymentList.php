<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Payment;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminPaymentListFetched;
use MyVendor\BeMart\Be\Final\PaymentMethodAdminCreated;
use MyVendor\BeMart\Be\Input\CreatePaymentMethodAdminInput;
use MyVendor\BeMart\Be\Input\GetAdminPaymentListInput;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goPaymentList + doCreatePayment — collection endpoint
 * (Wave 9θ).
 *
 *   - GET  → goPaymentList   (admin lists payment masters — safe read)
 *   - POST → doCreatePayment (admin adds a new payment master)
 *
 * Single-row affordances (`doUpdatePayment`, `doDeletePayment`) live
 * at `page://self/admin/payment/payment`.
 */
class PaymentList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'doCreatePayment', href: 'page://self/admin/payment/payment-list', method: 'post')]
    #[Link(rel: 'goPayment', href: 'page://self/admin/payment/payment', method: 'get')]
    #[Link(rel: 'doUpdatePayment', href: 'page://self/admin/payment/payment', method: 'put')]
    #[Link(rel: 'doDeletePayment', href: 'page://self/admin/payment/payment', method: 'delete')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetAdminPaymentListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminPaymentListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'payments' => $final->payments,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $paymentMethodName
     * @psalm-taint-source input $charge
     * @psalm-taint-source input $ruleMin
     * @psalm-taint-source input $ruleMax
     * @psalm-taint-source input $visible
     */
    #[Link(rel: 'goPaymentList', href: 'page://self/admin/payment/payment-list')]
    #[CsrfProtected]
    public function onPost(
        string $paymentMethodName,
        int $charge = 0,
        int|null $ruleMin = null,
        int|null $ruleMax = null,
        bool $visible = true,
    ): static {
        try {
            $final = ($this->becoming)(new CreatePaymentMethodAdminInput(
                paymentMethodName: $paymentMethodName,
                charge: $charge,
                ruleMin: $ruleMin,
                ruleMax: $ruleMax,
                visible: $visible,
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

        assert($final instanceof PaymentMethodAdminCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/admin/payment/payment?paymentId=%s', urlencode($final->paymentId));
        $this->body = [
            'paymentId' => $final->paymentId,
            'paymentMethodName' => $final->paymentMethodName,
            'charge' => $final->charge,
            'ruleMin' => $final->ruleMin,
            'ruleMax' => $final->ruleMax,
            'visible' => $final->visible,
        ];

        return $this;
    }
}
