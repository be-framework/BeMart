<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Exception\UnauthorizedOrderAccessException;
use MyVendor\BeMart\Be\Final\Reordered;
use MyVendor\BeMart\Be\Input\ReorderInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doReorder — 再注文 (Mypage/Reorder, Pilot 12).
 *
 * Repopulates the current customer's cart(s) from a past order.
 * ALPS: "在庫切れ商品はスキップ、現在価格を適用" — out-of-stock /
 * discontinued products are skipped, current prices apply.
 *
 * Failure mapping:
 *   - SemanticVariableException           → 400 (orderNo malformed)
 *   - UnauthenticatedException            → 401 (no logged-in customer)
 *   - UnauthorizedOrderAccessException    → 403 (not the order owner)
 *   - OrderNotFoundException              → 404 (no such order)
 *   - CSRF                                → 403 (checked before AUTHN)
 */
class Reorder extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * ALPS `doReorder` に対応する POST 操作。
     * @psalm-taint-source input $orderNo
     */
    #[Alps('doReorder')]
    #[JsonSchema(schema: 'post-mypage-reorder.json', params: 'post-mypage-reorder.param.json')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[CsrfToken]
    public function onPost(string $orderNo): static
    {
        $final = ($this->becoming)(new ReorderInput(orderNo: $orderNo));

        assert($final instanceof Reordered);

        ($this->mutationResponse)($this, Code::CREATED, '/cart');
        $this->body = [
            'customerId' => $final->customerId,
            'orderNo' => $final->orderNo,
            'addedCount' => $final->addedCount,
            'skippedCount' => $final->skippedCount,
            'skippedProductCodes' => $final->skippedProductCodes,
            'cartKeys' => $final->cartKeys,
        ];

        return $this;
    }
}
