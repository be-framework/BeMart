<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Cart;

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
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
        private readonly CsrfTokenInterface $csrf,
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
     * @psalm-taint-source input $csrfToken
     * @psalm-taint-source input $sessionPrefix
     */
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[Link(rel: 'doRemoveCartItem', href: 'page://self/cart/item', method: 'delete')]
    #[Link(rel: 'doCheckout', href: 'page://self/shopping', method: 'post')]
    public function onPost(
        string $productCode,
        int $quantity,
        string|null $csrfToken = null,
        string $sessionPrefix = self::DEFAULT_SESSION_PREFIX,
    ): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AddCartItemInput(
                $productCode,
                $quantity,
                HtmlCartSession::cartSessionPrefix() ?? $sessionPrefix,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'productCode' => $productCode,
                'quantity' => $quantity,
            ];

            return $this;
        } catch (ProductClassNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Product not found.', 'productCode' => $productCode];

            return $this;
        } catch (OutOfStockException) {
            // BEAR\Resource\Code lacks CONFLICT; use the integer literal.
            $this->code = 409;
            $this->body = ['message' => 'The product is out of stock.', 'productCode' => $productCode];

            return $this;
        }

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
     * @psalm-taint-source input $csrfToken
     * @psalm-taint-source input $sessionPrefix
     */
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    public function onPut(
        string $productCode,
        int $quantity,
        string|null $csrfToken = null,
        string $sessionPrefix = self::DEFAULT_SESSION_PREFIX,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateCartItemQuantityInput(
                productCode: $productCode,
                quantity: $quantity,
                sessionPrefix: HtmlCartSession::cartSessionPrefix() ?? $sessionPrefix,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'productCode' => $productCode,
                'quantity' => $quantity,
            ];

            return $this;
        } catch (ProductClassNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Product not found.', 'productCode' => $productCode];

            return $this;
        } catch (CartItemNotInCartException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'The product is not in the cart.', 'productCode' => $productCode];

            return $this;
        } catch (OutOfStockException) {
            $this->code = 409;
            $this->body = ['message' => 'The product is out of stock.', 'productCode' => $productCode];

            return $this;
        }

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
     * @psalm-taint-source input $csrfToken
     * @psalm-taint-source input $sessionPrefix
     */
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    public function onDelete(
        string $productCode,
        string|null $csrfToken = null,
        string $sessionPrefix = self::DEFAULT_SESSION_PREFIX,
    ): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new RemoveCartItemInput(
                productCode: $productCode,
                sessionPrefix: HtmlCartSession::cartSessionPrefix() ?? $sessionPrefix,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'productCode' => $productCode,
            ];

            return $this;
        } catch (CartItemNotInCartException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'The product is not in the cart.', 'productCode' => $productCode];

            return $this;
        }

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
}
