<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Payment;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\PaymentMethodAdminNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminPaymentListFetched;
use MyVendor\BeMart\Be\Final\PaymentMethodAdminDeleted;
use MyVendor\BeMart\Be\Final\PaymentMethodAdminUpdated;
use MyVendor\BeMart\Be\Input\DeletePaymentMethodAdminInput;
use MyVendor\BeMart\Be\Input\GetAdminPaymentListInput;
use MyVendor\BeMart\Be\Input\UpdatePaymentMethodAdminInput;
use MyVendor\BeMart\Form\AdminPaymentForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function getenv;
use function sprintf;
use function str_contains;
use function urlencode;

/**
 * EC-CUBE doUpdatePayment + doDeletePayment — single-row endpoint
 * (Wave 9θ).
 *
 *   - GET    → goPaymentEdit (safe read, admin AUTHZ, Setting/Shop Tier-2)
 *   - PUT    → doUpdatePayment (admin edits a payment master — idempotent)
 *   - DELETE → doDeletePayment (admin removes a payment master — idempotent)
 */
class Payment extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE 支払方法設定（編集） — Setting/Shop Tier-2.
     *
     * Thin GET renderer for `Setting/Shop/payment_edit.twig`. An empty
     * `$paymentId` renders a blank "new payment" form; a known id
     * pre-fills the editor; an unknown id is 404. The payment-master
     * list doubles as the AUTHZ gate — no admin session → 403.
     *
     * @psalm-taint-source input $paymentId
     */
    #[Alps('doUpdatePayment')]
    #[JsonSchema(schema: 'get-admin-payment-payment.json', params: 'get-admin-payment-payment.param.json')]
    #[Link(rel: 'doUpdatePayment', href: 'page://self/admin/payment/payment', method: 'put')]
    public function onGet(string $paymentId = ''): static
    {
        $final = ($this->becoming)(new GetAdminPaymentListInput());

        assert($final instanceof AdminPaymentListFetched);

        $payment = null;
        foreach ($final->payments as $row) {
            if ($row['paymentId'] === $paymentId) {
                $payment = $row;

                break;
            }
        }

        if ($paymentId !== '' && $payment === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された支払方法は見つかりませんでした。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminPaymentForm::class);
        assert($form instanceof AdminPaymentForm);
        if ($payment !== null) {
            $form->fillValues([
                'paymentMethodName' => $payment['paymentMethodName'],
                'charge' => $payment['charge'],
                'ruleMin' => $payment['ruleMin'],
                'ruleMax' => $payment['ruleMax'],
                'visible' => $payment['visible'] ? '1' : null,
            ]);
        }

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'paymentId' => $paymentId,
            'payment' => $payment,
        ];

        return $this;
    }

    /**
     * ALPS `doUpdatePayment` に対応する PUT 操作。
     * @psalm-taint-source input $paymentId
     * @psalm-taint-source input $paymentMethodName
     * @psalm-taint-source input $charge
     * @psalm-taint-source input $ruleMin
     * @psalm-taint-source input $ruleMax
     * @psalm-taint-source input $visible
     */
    #[Alps('doUpdatePayment')]
    #[JsonSchema(schema: 'put-admin-payment-payment.json', params: 'put-admin-payment-payment.param.json')]
    #[Link(rel: 'goPaymentList', href: 'page://self/admin/payment/payment-list')]
    #[CsrfProtected]
    public function onPut(
        string $paymentId,
        string|null $paymentMethodName = null,
        int|null $charge = null,
        int|null $ruleMin = null,
        int|null $ruleMax = null,
        bool|null $visible = null,
    ): static {
        $final = ($this->becoming)(new UpdatePaymentMethodAdminInput(
            paymentId: $paymentId,
            paymentMethodName: $paymentMethodName,
            charge: $charge,
            ruleMin: $ruleMin,
            ruleMax: $ruleMax,
            visible: $visible,
        ));

        assert($final instanceof PaymentMethodAdminUpdated);

        $this->code = str_contains((string) getenv('APP_CONTEXT'), 'html') ? Code::SEE_OTHER : Code::OK;
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

    /**
     * ALPS `doDeletePayment` に対応する DELETE 操作。
     * @psalm-taint-source input $paymentId
     */
    #[Alps('doDeletePayment')]
    #[JsonSchema(schema: 'delete-admin-payment-payment.json', params: 'delete-admin-payment-payment.param.json')]
    #[Link(rel: 'goPaymentList', href: 'page://self/admin/payment/payment-list')]
    #[Link(rel: 'goDeliveryList', href: 'page://self/admin/delivery/delivery-list')]
    #[CsrfProtected]
    public function onDelete(string $paymentId): static
    {
        $final = ($this->becoming)(new DeletePaymentMethodAdminInput(paymentId: $paymentId));

        assert($final instanceof PaymentMethodAdminDeleted);

        $this->code = str_contains((string) getenv('APP_CONTEXT'), 'html') ? Code::SEE_OTHER : Code::OK;
        $this->headers['Location'] = '/admin/payment/payment-list';
        $this->body = ['paymentId' => $final->paymentId];

        return $this;
    }
}
