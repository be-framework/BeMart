<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Cart;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Auth\HtmlCartSession;
use MyVendor\BeMart\Be\Exception\CartItemNotInCartException;
use MyVendor\BeMart\Be\Exception\OutOfStockException;
use MyVendor\BeMart\Be\Exception\ProductClassNotFoundException;
use MyVendor\BeMart\Be\Final\CartItemAdded;
use MyVendor\BeMart\Be\Final\CartItemQuantityUpdated;
use MyVendor\BeMart\Be\Final\CartItemRemoved;
use MyVendor\BeMart\Be\Input\AddCartItemInput;
use MyVendor\BeMart\Be\Input\RemoveCartItemInput;
use MyVendor\BeMart\Be\Input\UpdateCartItemQuantityInput;

use function assert;

/**
 * EC-CUBE doAddCartItem —カートに商品を追加。
 *
 * Resource is the HTTP entry point: it builds AddCartItemInput, hands it
 * to Becoming, and projects the resulting CartItemAdded into the response
 * body. Domain exceptions are mapped to HTTP codes per the integration
 * contract (see application-implement.md §DomainException → Code mapping).
 */
class Item extends ResourceObject
{
    private const DEFAULT_SESSION_PREFIX = 'session-prefix-1';

    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * Phase B Slice 9: all three params arrive from the HTTP request body
     * and are user-controlled. Declared as taint sources so Psalm can
     * trace them through Becoming into any downstream sink (Phase 2 will
     * surface real flows once Fake Reasons are swapped for DB-backed
     * implementations).
     *
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $quantity
     * @psalm-taint-source input $sessionPrefix
     */
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[Link(rel: 'doRemoveCartItem', href: 'page://self/cart/item', method: 'delete')]
    #[Link(rel: 'doCheckout', href: 'page://self/shopping', method: 'post')]
    #[CsrfProtected]
    public function onPost(
        string $productCode,
        int|null $quantity = null,
        string $sessionPrefix = self::DEFAULT_SESSION_PREFIX,
        string|null $operation = null,
    ): static
    {
        if ($operation === 'remove') {
            $this->onDelete($productCode, $sessionPrefix);

            return $this->redirectToCartOnSuccess();
        }

        if ($operation === 'up' || $operation === 'down' || $operation === 'update') {
            if ($quantity === null) {
                return $this->missingQuantity($productCode);
            }

            $this->onPut($productCode, $quantity, $sessionPrefix);

            return $this->redirectToCartOnSuccess();
        }

        if ($operation !== null) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => 'Invalid cart operation.', 'productCode' => $productCode];

            return $this;
        }

        if ($quantity === null) {
            return $this->missingQuantity($productCode);
        }

        $final = ($this->becoming)(new AddCartItemInput(
            $productCode,
            $quantity,
            HtmlCartSession::cartSessionPrefix() ?? $sessionPrefix,
        ));

        assert($final instanceof CartItemAdded);

        $this->code = Code::CREATED;
        $this->headers['Location'] = '/cart';
        $this->body = [
            'cartKey' => $final->cartKey,
            'productCode' => $final->productCode,
            'requestedQuantity' => $final->requestedQuantity,
            'adjustedQuantity' => $final->adjustedQuantity,
            'unitPrice' => $final->unitPrice,
            'totalPrice' => $final->totalPrice,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
            'saleTypeName' => $final->saleTypeName,
        ];

        return $this;
    }

    /**
     * EC-CUBE doUpdateCartItemQuantity — replace an item's quantity
     * (Pilot 10). Idempotent (PUT semantics), CSRF-guarded.
     *
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $quantity
     * @psalm-taint-source input $sessionPrefix
     */
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[CsrfProtected]
    public function onPut(
        string $productCode,
        int $quantity,
        string $sessionPrefix = self::DEFAULT_SESSION_PREFIX,
    ): static {
        $final = ($this->becoming)(new UpdateCartItemQuantityInput(
            productCode: $productCode,
            quantity: $quantity,
            sessionPrefix: HtmlCartSession::cartSessionPrefix() ?? $sessionPrefix,
        ));

        assert($final instanceof CartItemQuantityUpdated);

        $this->code = Code::OK;
        $this->body = [
            'cartKey' => $final->cartKey,
            'productCode' => $final->productCode,
            'requestedQuantity' => $final->requestedQuantity,
            'adjustedQuantity' => $final->adjustedQuantity,
            'unitPrice' => $final->unitPrice,
            'totalPrice' => $final->totalPrice,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
            'saleTypeName' => $final->saleTypeName,
        ];

        return $this;
    }

    /**
     * EC-CUBE doRemoveCartItem — remove an item from the cart (Pilot 11).
     * Idempotent (DELETE), CSRF-guarded.
     *
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $sessionPrefix
     */
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[CsrfProtected]
    public function onDelete(
        string $productCode,
        string $sessionPrefix = self::DEFAULT_SESSION_PREFIX,
    ): static
    {
        $final = ($this->becoming)(new RemoveCartItemInput(
            productCode: $productCode,
            sessionPrefix: HtmlCartSession::cartSessionPrefix() ?? $sessionPrefix,
        ));

        assert($final instanceof CartItemRemoved);

        $this->code = Code::OK;
        $this->body = [
            'cartKey' => $final->cartKey,
            'productCode' => $final->productCode,
            'totalPrice' => $final->totalPrice,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
        ];

        return $this;
    }

    private function missingQuantity(string $productCode): static
    {
        $this->code = Code::BAD_REQUEST;
        $this->body = ['message' => 'Invalid input.', 'productCode' => $productCode, 'quantity' => null];

        return $this;
    }

    private function redirectToCartOnSuccess(): static
    {
        if ($this->code < 400) {
            $this->headers['Location'] = '/cart';
        }

        return $this;
    }

}
