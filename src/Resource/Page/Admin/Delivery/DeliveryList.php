<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Delivery;

use BEAR\ApiDoc\Annotation\Alps;
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
use BEAR\Resource\Annotation\JsonSchema;

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

    /** ALPS `goDeliveryList` に対応する GET 操作。 */
    #[Alps('goDeliveryList')]
    #[JsonSchema(schema: 'get-admin-delivery-delivery-list.json')]
    #[Link(rel: 'doCreateDelivery', href: 'page://self/admin/delivery/delivery-list', method: 'post')]
    #[Link(rel: 'goDelivery', href: 'page://self/admin/delivery/delivery', method: 'get')]
    #[Link(rel: 'doUpdateDelivery', href: 'page://self/admin/delivery/delivery', method: 'put')]
    #[Link(rel: 'doDeleteDelivery', href: 'page://self/admin/delivery/delivery', method: 'delete')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new GetAdminDeliveryListInput());

        assert($final instanceof AdminDeliveryListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'deliveries' => $final->deliveries,
        ];

        return $this;
    }

    /**
     * ALPS `doCreateDelivery` に対応する POST 操作。
     * @psalm-taint-source input $deliveryName
     * @psalm-taint-source input $visible
     */
    #[Alps('doCreateDelivery')]
    #[JsonSchema(schema: 'post-admin-delivery-delivery-list.json', params: 'post-admin-delivery-delivery-list.param.json')]
    #[Link(rel: 'goDeliveryList', href: 'page://self/admin/delivery/delivery-list')]
    #[CsrfProtected]
    public function onPost(
        string $deliveryName,
        bool $visible = true,
    ): static {
        $final = ($this->becoming)(new CreateDeliveryInput(
            deliveryName: $deliveryName,
            visible: $visible,
        ));

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
