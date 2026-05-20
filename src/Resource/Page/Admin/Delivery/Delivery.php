<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Delivery;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\DeliveryNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\DeliveryDeleted;
use MyVendor\BeMart\Be\Final\DeliveryUpdated;
use MyVendor\BeMart\Be\Input\DeleteDeliveryInput;
use MyVendor\BeMart\Be\Input\UpdateDeliveryInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doUpdateDelivery + doDeleteDelivery — single-row endpoint
 * (Wave 9θ).
 *
 *   - PUT    → doUpdateDelivery (admin edits a delivery master — idempotent)
 *   - DELETE → doDeleteDelivery (admin removes a delivery master — idempotent)
 */
class Delivery extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $deliveryId
     * @psalm-taint-source input $deliveryName
     * @psalm-taint-source input $visible
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goDeliveryList', href: 'page://self/admin/delivery/delivery-list')]
    public function onPut(
        string $deliveryId,
        string|null $deliveryName = null,
        bool|null $visible = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

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
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goDeliveryList', href: 'page://self/admin/delivery/delivery-list')]
    public function onDelete(string $deliveryId, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

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
