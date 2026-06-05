<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Auth\HtmlCartSession;
use MyVendor\BeMart\Be\Final\CartsFetched;
use MyVendor\BeMart\Be\Input\GetCartsInput;
use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;

use function array_map;
use function assert;

/**
 * EC-CUBE goCart — current shopping session の cart 一覧 (Pilot 9).
 *
 * Safe read. No CSRF, no AUTHZ — the cart is bound to the
 * sessionPrefix cookie, ownership is implicit. Returns 200 with a
 * (possibly empty) list of carts plus per-session totals.
 */
class Cart extends ResourceObject
{
    private const DEFAULT_SESSION_PREFIX = 'session-prefix-1';

    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfToken $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $sessionPrefix
     */
    #[Link(rel: 'doAddCartItem', href: 'page://self/cart/item', method: 'post')]
    #[Link(rel: 'goShoppingNonMember', href: 'page://self/shopping/non-member')]
    #[Link(rel: 'goShopping', href: 'page://self/shopping')]
    public function onGet(string $sessionPrefix = self::DEFAULT_SESSION_PREFIX): static
    {
        $final = ($this->becoming)(new GetCartsInput(
            sessionPrefix: HtmlCartSession::cartSessionPrefix() ?? $sessionPrefix,
        ));
        assert($final instanceof CartsFetched);

        $this->code = Code::OK;
        $this->body = [
            'cartCount' => $final->cartCount,
            'totalPrice' => $final->totalPrice,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
            'csrfToken' => $this->csrf->token,
            'carts' => array_map(
                static fn (CartEntity $cart): array => [
                    'cartKey' => $cart->cartKey,
                    'saleTypeId' => $cart->saleTypeId,
                    'saleTypeName' => $cart->saleTypeName,
                    'totalPrice' => $cart->totalPrice,
                    'deliveryFeeTotal' => $cart->deliveryFeeTotal,
                    'items' => array_map(
                        static fn (CartItemEntity $item): array => [
                            'productClassId' => $item->productClassId,
                            'productId' => $item->productId,
                            'productCode' => $item->productCode,
                            'productName' => $item->productName,
                            'mainImage' => $item->mainImage,
                            'classCategoryName1' => $item->classCategoryName1,
                            'className1' => $item->className1,
                            'classCategoryName2' => $item->classCategoryName2,
                            'className2' => $item->className2,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                        ],
                        $cart->items,
                    ),
                ],
                $final->carts,
            ),
        ];

        return $this;
    }
}
