<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Auth\CartSessionPrefixInterface;
use MyVendor\BeMart\Be\Final\ShoppingFetched;
use MyVendor\BeMart\Be\Input\GetShoppingInput;
use Ray\Csrf\CsrfTokenInterface;
use MyVendor\BeMart\Form\ShoppingOrderForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE checkout entry / goShopping — 注文情報入力画面.
 *
 * Safe read. No CSRF (read-only). This resource is also the HTML
 * checkout gateway reached from the cart CTA (`goCheckoutEntry`).
 * Anonymous / stale sessions are redirected to the checkout login page
 * instead of exposing a raw JSON 401 in the browser. Authenticated
 * sessions resolve the member checkout projection (`goShopping`):
 * customer shipping fields, the current carts under the active
 * sessionPrefix, and selectable payment methods.
 *
 * Empty-cart handling: 200 with `canCheckout = false` rather than
 * 404. The frontend renders the "カートが空です" panel in that case;
 * the customer can navigate back to `goCart` to add items.
 *
 * Failure mapping:
 *   - SemanticVariableException → 400 (sessionPrefix malformed)
 *   - UnauthenticatedException  → 303 /shopping/login (checkout entry)
 *
 * Coexists with `Resource\Page\Shopping\` directory (which holds
 * Checkout.php from Pilot 5) — the same file-plus-sibling-directory
 * pattern as Mypage.
 *
 * Phase 3 — HTML FORM page. `Shopping/index.twig` is form-heavy: the
 * checkout page carries the order message textarea + the delivery /
 * payment selection controls. The resource builds a {@see
 * ShoppingOrderForm} (Ray.WebFormModule AbstractForm) and exposes it as
 * `body['form']` so the HTML port renders real `<input>` / `<select>`
 * markup via `{{ form.input(...) }}`. The form is a field-definition +
 * renderer only — VALIDATION AUTHORITY STAYS WITH the Be Becoming chain
 * (doCheckout / CheckoutInput). The JSON contexts ignore `body['form']`;
 * the JSON-context tests assert key-wise on `body` and are unaffected.
 */
class Shopping extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
        private readonly CsrfTokenInterface $csrf,
        private readonly CartSessionPrefixInterface $cartSessionPrefix,
    ) {
    }

    /**
     * ALPS `goCheckoutEntry` / `goShopping` に対応する GET 操作。
     * @psalm-taint-source input $sessionPrefix
     */
    #[Alps('goCheckoutEntry')]
    #[Alps('goShopping')]
    #[JsonSchema(schema: 'get-shopping.json', params: 'get-shopping.param.json')]
    #[Link(rel: 'doConfirmOrder', href: 'page://self/shopping/confirm', method: 'post')]
    #[Link(rel: 'goShoppingShipping', href: 'page://self/shopping/shipping')]
    #[Link(rel: 'goShoppingShippingEdit', href: 'page://self/shopping/shipping-edit')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    public function onGet(string $sessionPrefix = 'session-prefix-1'): static
    {
        try {
            $final = ($this->becoming)(new GetShoppingInput(
                sessionPrefix: $this->cartSessionPrefix->prefix() ?? $sessionPrefix,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthenticatedException) {
            return $this->redirectToShoppingLogin();
        }

        assert($final instanceof ShoppingFetched);

        $firstCart = $final->carts[0] ?? null;

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'defaultShippingAddress' => $final->defaultShippingAddress,
            'preOrderId' => is_array($firstCart) ? ($firstCart['preOrderId'] ?? null) : null,
            'carts' => $final->carts,
            'cartCount' => $final->cartCount,
            'totalPrice' => $final->totalPrice,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
            'paymentMethods' => $final->paymentMethods,
            'canCheckout' => $final->canCheckout,
            'csrfToken' => $this->csrf->issue(),
            // Phase 3: an empty ShoppingOrderForm for the HTML port to
            // render the message textarea + delivery / payment controls.
            // JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(ShoppingOrderForm::class),
        ];

        return $this;
    }

    private function redirectToShoppingLogin(): static
    {
        $this->code = Code::SEE_OTHER;
        $this->headers['Location'] = '/shopping/login';
        $this->body = [
            'transitionId' => 'goCheckoutEntry',
            'message' => '購入手続きに進むにはログインまたはゲスト購入を選択してください。',
            '_links' => [
                // Suppress method-level doConfirmOrder for anonymous 303 HAL; entries without href are not rendered.
                'doConfirmOrder' => [],
                'goShoppingLogin' => ['href' => 'page://self/shopping/login'],
                'goShoppingNonMember' => ['href' => 'page://self/shopping/non-member'],
                'goCart' => ['href' => 'page://self/cart'],
            ],
        ];

        return $this;
    }
}
