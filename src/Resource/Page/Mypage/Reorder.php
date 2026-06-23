<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Auth\CartSessionPrefixInterface;
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
 * The reordered items are written into the SAME cart partition the
 * browser's current shopping session reads — the cartKey is
 * `{sessionPrefix}_{saleTypeId}`, so the live session's prefix (the
 * `CartSessionPrefixInterface`, identical to Cart / Cart\Item /
 * Shopping) MUST flow into the ReorderInput. Otherwise the Final
 * persists the cart under the fallback `session-prefix-1` key while
 * /cart reads the real session prefix, so the user is redirected to an
 * EMPTY cart — a silent reorder that observably did nothing.
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
    private const DEFAULT_SESSION_PREFIX = 'session-prefix-1';

    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CartSessionPrefixInterface $cartSessionPrefix,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * ALPS `doReorder` に対応する POST 操作。
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $sessionPrefix
     */
    #[Alps('doReorder')]
    #[JsonSchema(schema: 'post-mypage-reorder.json', params: 'post-mypage-reorder.param.json')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[CsrfToken]
    public function onPost(string $orderNo, string $sessionPrefix = self::DEFAULT_SESSION_PREFIX): static
    {
        $final = ($this->becoming)(new ReorderInput(
            orderNo: $orderNo,
            sessionPrefix: $this->cartSessionPrefix->prefix() ?? $sessionPrefix,
        ));

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
