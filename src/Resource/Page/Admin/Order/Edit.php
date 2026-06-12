<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderFetched;
use MyVendor\BeMart\Be\Input\GetAdminOrderInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminOrderEditForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE 受注登録 / 受注編集 — Order Tier-2 (`admin/Order/edit.twig`,
 * the ~1057-line multi-panel order editor).
 *
 *   GET /admin/order/edit            → blank "new order" editor
 *   GET /admin/order/edit?orderNo=…  → editor pre-filled for one order
 *
 * Thin GET renderer. The sibling JSON resource {@see \MyVendor\BeMart\Resource\Page\Admin\Order}
 * carries the `goOrder` read + `doUpdateOrder` write; this resource is
 * the HTML editor shell only. An empty `$orderNo` renders the blank
 * editor (EC-CUBE's "受注登録" path — the render-smoke test exercises
 * this with empty JSON-backed fake storage); a known orderNo pre-fills; an unknown
 * orderNo is 404.
 *
 * AUTHZ: the blank-editor path checks the admin session directly
 * (Pattern B — no Be transition is invoked when there is no order to
 * read); the pre-fill path delegates to {@see AdminOrderFetched}, which
 * raises {@see UnauthorizedAdminAccessException} for a non-admin
 * firewall. Both surface 403.
 */
class Edit extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly AdminSession $adminSession,
        private readonly CsrfToken $csrf,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * ALPS `goOrder` に対応する GET 操作。
     * @psalm-taint-source input $orderNo
     */
    #[Alps('goOrder')]
    #[JsonSchema(schema: 'get-admin-order-edit.json', params: 'get-admin-order-edit.param.json')]
    #[Link(rel: 'doUpdateOrder', href: 'page://self/admin/order', method: 'put')]
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    public function onGet(string $orderNo = ''): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminOrderEditForm::class);
        assert($form instanceof AdminOrderEditForm);

        if ($orderNo === '') {
            $form->fillValues([
                'name01' => '', 'name02' => '', 'email' => '',
                'discount' => '0', 'charge' => '0', 'usePoint' => '0', 'message' => '',
            ]);

            $this->code = Code::OK;
            $this->body = [
                'form' => $form,
                'orderNo' => '',
                'order' => null,
                'items' => [],
                'csrfToken' => $this->csrf->token,
            ];

            return $this;
        }

        $final = ($this->becoming)(new GetAdminOrderInput(orderNo: $orderNo));

        assert($final instanceof AdminOrderFetched);

        $form->fillValues([
            'name01' => $final->customer['name01'] ?? '',
            'name02' => $final->customer['name02'] ?? '',
            'email' => $final->customer['email'] ?? '',
            'discount' => (string) $final->discount,
            'charge' => (string) $final->charge,
            'usePoint' => (string) $final->usePoint,
            'message' => '',
        ]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'orderNo' => $final->orderNo,
            'order' => [
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
                'addPoint' => $final->addPoint,
                'usePoint' => $final->usePoint,
                'orderStatus' => $final->orderStatus,
                'orderDate' => $final->orderDate,
                'paymentDate' => $final->paymentDate,
            ],
            'items' => $final->items,
            'csrfToken' => $this->csrf->token,
        ];

        return $this;
    }
}
