<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Delivery;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminDeliveryListFetched;
use MyVendor\BeMart\Be\Final\DeliveryCreated;
use MyVendor\BeMart\Be\Input\CreateDeliveryInput;
use MyVendor\BeMart\Be\Input\GetAdminDeliveryListInput;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goDeliveryList + doCreateDelivery — collection endpoint
 * (Wave 9θ).
 *
 *   - GET  → goDeliveryList    (admin lists delivery masters — safe read)
 *   - POST → doCreateDelivery  (admin adds a new delivery master)
 *
 * Single-row affordances live at `page://self/admin/delivery/delivery`.
 */
class DeliveryList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'doCreateDelivery', href: 'page://self/admin/delivery/delivery-list', method: 'post')]
    #[Link(rel: 'goDelivery', href: 'page://self/admin/delivery/delivery', method: 'get')]
    #[Link(rel: 'doUpdateDelivery', href: 'page://self/admin/delivery/delivery', method: 'put')]
    #[Link(rel: 'doDeleteDelivery', href: 'page://self/admin/delivery/delivery', method: 'delete')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetAdminDeliveryListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminDeliveryListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'deliveries' => $final->deliveries,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $deliveryName
     * @psalm-taint-source input $visible
     */
    #[Link(rel: 'goDeliveryList', href: 'page://self/admin/delivery/delivery-list')]
    #[CsrfProtected]
    public function onPost(
        string $deliveryName,
        bool $visible = true,
    ): static {
        try {
            $final = ($this->becoming)(new CreateDeliveryInput(
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
        }

        assert($final instanceof DeliveryCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/admin/delivery/delivery?deliveryId=%s', urlencode($final->deliveryId));
        $this->body = [
            'deliveryId' => $final->deliveryId,
            'deliveryName' => $final->deliveryName,
            'visible' => $final->visible,
        ];

        return $this;
    }
}
