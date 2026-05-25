<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\ShoppingFetched;
use MyVendor\BeMart\Be\Input\GetShoppingInput;

use function assert;

/**
 * EC-CUBE goShopping — 注文情報入力画面 (Pilot — checkout review).
 *
 * Safe read. No CSRF (read-only). AUTHN required — Be Final raises
 * UnauthenticatedException when the session has no customerId, which
 * we map to 401. Aggregates the customer's default shipping fields,
 * the current carts under the active sessionPrefix, and the list of
 * user-selectable payment methods into a single review projection.
 *
 * Empty-cart handling: 200 with `canCheckout = false` rather than
 * 404. The frontend renders the "カートが空です" panel in that case;
 * the customer can navigate back to `goCart` to add items.
 *
 * Failure mapping:
 *   - SemanticVariableException → 400 (sessionPrefix malformed)
 *   - UnauthenticatedException  → 401 (no / stale session)
 *
 * Coexists with `Resource\Page\Shopping\` directory (which holds
 * Checkout.php from Pilot 5) — the same file-plus-sibling-directory
 * pattern as Mypage.
 */
class Shopping extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * @psalm-taint-source input $sessionPrefix
     */
    #[Link(rel: 'doCheckout', href: 'page://self/shopping/checkout', method: 'post')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    public function onGet(string $sessionPrefix = 'session-prefix-1'): static
    {
        try {
            $final = ($this->becoming)(new GetShoppingInput(sessionPrefix: $sessionPrefix));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthenticatedException) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        }

        assert($final instanceof ShoppingFetched);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'defaultShippingAddress' => $final->defaultShippingAddress,
            'carts' => $final->carts,
            'cartCount' => $final->cartCount,
            'totalPrice' => $final->totalPrice,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
            'paymentMethods' => $final->paymentMethods,
            'canCheckout' => $final->canCheckout,
        ];

        return $this;
    }
}
