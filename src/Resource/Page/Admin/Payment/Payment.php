<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Payment;

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

use function assert;

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
    #[Link(rel: 'doUpdatePayment', href: 'page://self/admin/payment/payment', method: 'put')]
    public function onGet(string $paymentId = ''): static
    {
        try {
            $final = ($this->becoming)(new GetAdminPaymentListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

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
                'method' => $payment['paymentMethodName'],
                'charge' => $payment['charge'],
                'rule_min' => $payment['ruleMin'],
                'rule_max' => $payment['ruleMax'],
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
     * @psalm-taint-source input $paymentId
     * @psalm-taint-source input $paymentMethodName
     * @psalm-taint-source input $charge
     * @psalm-taint-source input $ruleMin
     * @psalm-taint-source input $ruleMax
     * @psalm-taint-source input $visible
     */
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
        try {
            $final = ($this->becoming)(new UpdatePaymentMethodAdminInput(
                paymentId: $paymentId,
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
        } catch (PaymentMethodAdminNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された支払方法は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof PaymentMethodAdminUpdated);

        $this->code = Code::OK;
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
     * @psalm-taint-source input $paymentId
     */
    #[Link(rel: 'goPaymentList', href: 'page://self/admin/payment/payment-list')]
    #[Link(rel: 'goDeliveryList', href: 'page://self/admin/delivery/delivery-list')]
    #[CsrfProtected]
    public function onDelete(string $paymentId): static
    {
        try {
            $final = ($this->becoming)(new DeletePaymentMethodAdminInput(paymentId: $paymentId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (PaymentMethodAdminNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された支払方法は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof PaymentMethodAdminDeleted);

        $this->code = Code::OK;
        $this->body = ['paymentId' => $final->paymentId];

        return $this;
    }
}
