<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function array_sum;
use function count;

/**
 * Shopping fetched — Final, the checkout review page projection.
 *
 *   GetShoppingInput → ShoppingFetched  (Direct, safe read)
 *
 * Aggregates three independent reads so the BEAR layer renders the
 * pre-doCheckout review screen in one pass:
 *
 *   - `CustomerSession::$customerId`        → AUTHN (null → 401)
 *   - `CustomerQuery::findById`             → default shipping address
 *   - `CartQuery::bySessionPrefix`          → current carts + totals
 *   - `PaymentMethodFactory::available`     → user-selectable methods
 *
 * AUTHN: customerId is resolved from the session. A null session — or a
 * session pointing to a deleted customer — raises UnauthenticatedException;
 * the BEAR layer maps both to 401. EC-CUBE permits goShopping for guest
 * sessions, but our Pilot 5 doCheckout currently requires an authenticated
 * session for AUTHZ. To keep the pair consistent we require AUTHN here
 * too. Guest-checkout support is Phase 2 and would extend the session
 * model (Pilot 8 lesson — never leak existence signal across the AAA
 * boundary, treat unknown-customer the same as not-logged-in).
 *
 * Empty-cart handling: if the session has no cart entries we return the
 * usual projection with `canCheckout = false`. The Resource still emits
 * 200; the frontend renders "カートが空です" when canCheckout is false
 * rather than relying on a 404. This mirrors goCart's empty-list
 * semantics and avoids a special-case error path.
 *
 * The projection is intentionally shallow — only the fields the review
 * UI needs. Payment methods are returned as `{id, name}` pairs so the
 * frontend never reaches into the plugin classes.
 */
final readonly class ShoppingFetched
{
    public string $customerId;
    public string $email;
    public string $name01;
    public string $name02;

    /**
     * @var array{
     *     postalCode: string|null,
     *     pref: int|null,
     *     addr01: string|null,
     *     addr02: string|null,
     *     phoneNumber: string|null
     * }
     */
    public array $defaultShippingAddress;

    /**
     * @var list<array{
     *     cartKey: string,
     *     saleTypeName: string,
     *     totalPrice: int,
     *     deliveryFeeTotal: int,
     *     items: list<array{productCode: string, quantity: int, price: int}>
     * }>
     */
    public array $carts;

    public int $cartCount;
    public int $totalPrice;
    public int $deliveryFeeTotal;

    /** @var list<array{paymentMethodId: int, paymentMethodName: string}> */
    public array $paymentMethods;

    public bool $canCheckout;

    public function __construct(
        #[Input] string $sessionPrefix,
        #[Inject] CustomerSession $session,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] CartQueryInterface $cartQuery,
        #[Inject] PaymentMethodFactoryInterface $paymentMethodFactory,
    ) {
        $sessionCustomerId = $session->customerId;
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $customer = $customerQuery->item($sessionCustomerId);
        if ($customer === null) {
            // Session points to a non-existent customer (deleted /
            // expired). Treat same as not-logged-in to avoid leaking
            // existence signal across the AAA boundary.
            throw new UnauthenticatedException();
        }

        $carts = $cartQuery->listBySessionPrefix($sessionPrefix);

        $this->customerId = $customer->customerId;
        $this->email = $customer->email;
        $this->name01 = $customer->name01;
        $this->name02 = $customer->name02;
        $this->defaultShippingAddress = [
            'postalCode' => $customer->postalCode,
            'pref' => $customer->pref,
            'addr01' => $customer->addr01,
            'addr02' => $customer->addr02,
            'phoneNumber' => $customer->phoneNumber,
        ];

        $this->carts = array_map(
            static fn (CartEntity $cart): array => [
                'cartKey' => $cart->cartKey,
                'saleTypeName' => $cart->saleTypeName,
                'totalPrice' => $cart->totalPrice,
                'deliveryFeeTotal' => $cart->deliveryFeeTotal,
                'items' => array_map(
                    static fn (CartItemEntity $item): array => [
                        'productCode' => $item->productCode,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ],
                    $cart->items,
                ),
            ],
            $carts,
        );
        $this->cartCount = count($carts);
        $this->totalPrice = array_sum(array_map(static fn (CartEntity $c) => $c->totalPrice, $carts));
        $this->deliveryFeeTotal = array_sum(array_map(static fn (CartEntity $c) => $c->deliveryFeeTotal, $carts));
        $this->paymentMethods = $paymentMethodFactory->available();
        $this->canCheckout = $this->cartCount > 0;
    }
}
