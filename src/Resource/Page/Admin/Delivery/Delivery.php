<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Delivery;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\DeliveryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminDeliveryListFetched;
use MyVendor\BeMart\Be\Final\DeliveryDeleted;
use MyVendor\BeMart\Be\Final\DeliveryUpdated;
use MyVendor\BeMart\Be\Input\DeleteDeliveryInput;
use MyVendor\BeMart\Be\Input\GetAdminDeliveryListInput;
use MyVendor\BeMart\Be\Input\UpdateDeliveryInput;
use MyVendor\BeMart\Form\AdminDeliveryForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE doUpdateDelivery + doDeleteDelivery — single-row endpoint
 * (Wave 9θ).
 *
 *   - GET    → goDeliveryEdit (safe read, admin AUTHZ, Setting/Shop Tier-2)
 *   - PUT    → doUpdateDelivery (admin edits a delivery master — idempotent)
 *   - DELETE → doDeleteDelivery (admin removes a delivery master — idempotent)
 */
class Delivery extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * EC-CUBE 配送方法設定（編集） — Setting/Shop Tier-2.
     *
     * Thin GET renderer for `Setting/Shop/delivery_edit.twig`. An empty
     * `$deliveryId` renders a blank "new delivery" form; a known id
     * pre-fills the editor; an unknown id is 404. The delivery-master
     * list doubles as the AUTHZ gate — no admin session → 403.
     *
     * @psalm-taint-source input $deliveryId
     */
    #[Alps('doUpdateDelivery')]
    #[JsonSchema(schema: 'get-admin-delivery-delivery.json', params: 'get-admin-delivery-delivery.param.json')]
    #[Link(rel: 'doUpdateDelivery', href: 'page://self/admin/delivery/delivery', method: 'put')]
    public function onGet(string $deliveryId = ''): static
    {
        $final = ($this->becoming)(new GetAdminDeliveryListInput());

        assert($final instanceof AdminDeliveryListFetched);

        $delivery = null;
        foreach ($final->deliveries as $row) {
            if ($row['deliveryId'] === $deliveryId) {
                $delivery = $row;

                break;
            }
        }

        if ($deliveryId !== '' && $delivery === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された配送方法は見つかりませんでした。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminDeliveryForm::class);
        assert($form instanceof AdminDeliveryForm);
        if ($delivery !== null) {
            $form->fillValues([
                'deliveryName' => $delivery['deliveryName'],
                'visible' => $delivery['visible'] ? '1' : null,
            ]);
        }

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'deliveryId' => $deliveryId,
            'delivery' => $delivery,
        ];

        return $this;
    }

    /**
     * ALPS `doUpdateDelivery` に対応する PUT 操作。
     * @psalm-taint-source input $deliveryId
     * @psalm-taint-source input $deliveryName
     * @psalm-taint-source input $visible
     */
    #[Alps('doUpdateDelivery')]
    #[JsonSchema(schema: 'put-admin-delivery-delivery.json', params: 'put-admin-delivery-delivery.param.json')]
    #[Link(rel: 'goDeliveryList', href: 'page://self/admin/delivery/delivery-list')]
    #[CsrfProtected]
    public function onPut(
        string $deliveryId,
        string|null $deliveryName = null,
        bool|null $visible = null,
    ): static {
        $final = ($this->becoming)(new UpdateDeliveryInput(
            deliveryId: $deliveryId,
            deliveryName: $deliveryName,
            visible: $visible,
        ));

        assert($final instanceof DeliveryUpdated);

        ($this->mutationResponse)($this, Code::OK);
        $this->headers['Location'] = sprintf('/admin/delivery/delivery?deliveryId=%s', urlencode($final->deliveryId));
        $this->body = [
            'deliveryId' => $final->deliveryId,
            'deliveryName' => $final->deliveryName,
            'visible' => $final->visible,
        ];

        return $this;
    }

    /**
     * ALPS `doDeleteDelivery` に対応する DELETE 操作。
     * @psalm-taint-source input $deliveryId
     */
    #[Alps('doDeleteDelivery')]
    #[JsonSchema(schema: 'delete-admin-delivery-delivery.json', params: 'delete-admin-delivery-delivery.param.json')]
    #[Link(rel: 'goDeliveryList', href: 'page://self/admin/delivery/delivery-list')]
    #[Link(rel: 'goTaxRuleList', href: 'page://self/admin/tax-rule/tax-rule-list')]
    #[CsrfProtected]
    public function onDelete(string $deliveryId): static
    {
        $final = ($this->becoming)(new DeleteDeliveryInput(deliveryId: $deliveryId));

        assert($final instanceof DeliveryDeleted);

        ($this->mutationResponse)($this, Code::OK);
        $this->headers['Location'] = '/admin/delivery/delivery-list';
        $this->body = ['deliveryId' => $final->deliveryId];

        return $this;
    }
}
