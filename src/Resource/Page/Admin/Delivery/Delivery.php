<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Delivery;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
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

use function assert;

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
    #[Link(rel: 'doUpdateDelivery', href: 'page://self/admin/delivery/delivery', method: 'put')]
    public function onGet(string $deliveryId = ''): static
    {
        try {
            $final = ($this->becoming)(new GetAdminDeliveryListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

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
                'name' => $delivery['deliveryName'],
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
     * @psalm-taint-source input $deliveryId
     * @psalm-taint-source input $deliveryName
     * @psalm-taint-source input $visible
     */
    #[Link(rel: 'goDeliveryList', href: 'page://self/admin/delivery/delivery-list')]
    #[CsrfProtected]
    public function onPut(
        string $deliveryId,
        string|null $deliveryName = null,
        bool|null $visible = null,
    ): static {
        try {
            $final = ($this->becoming)(new UpdateDeliveryInput(
                deliveryId: $deliveryId,
                deliveryName: $deliveryName,
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
        } catch (DeliveryNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された配送方法は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof DeliveryUpdated);

        $this->code = Code::OK;
        $this->body = [
            'deliveryId' => $final->deliveryId,
            'deliveryName' => $final->deliveryName,
            'visible' => $final->visible,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $deliveryId
     */
    #[Link(rel: 'goDeliveryList', href: 'page://self/admin/delivery/delivery-list')]
    #[CsrfProtected]
    public function onDelete(string $deliveryId): static
    {
        try {
            $final = ($this->becoming)(new DeleteDeliveryInput(deliveryId: $deliveryId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (DeliveryNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された配送方法は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof DeliveryDeleted);

        $this->code = Code::OK;
        $this->body = ['deliveryId' => $final->deliveryId];

        return $this;
    }
}
