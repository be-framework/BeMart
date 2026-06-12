<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Be\Final\OrderConfirmed;
use MyVendor\BeMart\Be\Final\OrderConfirmFailed;
use MyVendor\BeMart\Be\Input\ConfirmOrderInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goShoppingConfirm — 注文内容のご確認.
 *
 * The order-review screen the customer confirms before `doCheckout`.
 * EC-CUBE's checkout flow runs `doConfirmOrder` → `ShoppingConfirm`
 * (ALPS `#ShoppingConfirm`) between `goShopping` and `doCheckout`.
 *
 * Phase 3 enrichment — this resource now drives the `doConfirmOrder` Be
 * Becoming chain ({@see ConfirmOrderInput} → … → {@see OrderConfirmed})
 * rather than being a thin pure renderer. The chain resolves the
 * processing pre-order, runs the PurchaseFlow totals, verifies payment
 * and branches; on success the body carries the full confirm-screen
 * projection EC-CUBE's `Shopping/confirm.twig` renders — the customer
 * info, the order's line items, the payment method and the
 * tax-inclusive totals.
 *
 * On a verify failure the chain produces an {@see OrderConfirmFailed}
 * Final; the resource forwards the customer to the ShoppingError state
 * (`goShoppingError`), mirroring EC-CUBE's controller behaviour.
 *
 * Maps to `page://self/shopping/confirm`. The submit target is
 * doCheckout (`page://self/shopping/checkout`).
 */
class Confirm extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `goShopping` に対応する GET 操作。
     * @psalm-taint-source input $preOrderId
     * @psalm-taint-source input $paymentMethodId
     */
    #[Alps('goShopping')]
    #[JsonSchema(schema: 'get-shopping-confirm.json', params: 'get-shopping-confirm.param.json')]
    #[Link(rel: 'doCheckout', href: 'page://self/shopping/checkout', method: 'post')]
    #[Link(rel: 'goShoppingError', href: 'page://self/shopping/error')]
    public function onGet(
        string $preOrderId = 'aceface0000000000000000000000000000a11ce',
        int $paymentMethodId = 2,
    ): static {
        return $this->confirmOrder($preOrderId, $paymentMethodId);
    }

    /**
     * HTML checkout form posts the selected payment field as `payment`.
     * Keep GET query compatibility while accepting the real browser form.
     *
     * @psalm-taint-source input $preOrderId
     * @psalm-taint-source input $payment
     */
    #[Alps('goShopping')]
    #[JsonSchema(schema: 'get-shopping-confirm.json', params: 'post-shopping-confirm.param.json')]
    #[Link(rel: 'doCheckout', href: 'page://self/shopping/checkout', method: 'post')]
    #[Link(rel: 'goShoppingError', href: 'page://self/shopping/error')]
    #[CsrfProtected]
    public function onPost(
        string $preOrderId,
        int $payment = 2,
    ): static {
        return $this->confirmOrder($preOrderId, $payment);
    }

    private function confirmOrder(string $preOrderId, int $paymentMethodId): static
    {
        try {
            $final = ($this->becoming)(new ConfirmOrderInput(
                preOrderId: $preOrderId,
                paymentMethodId: $paymentMethodId,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (PreOrderNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Pre-order not found.', 'preOrderId' => $preOrderId];

            return $this;
        }


        if ($final instanceof OrderConfirmFailed) {
            // verify() rejected the pre-order — bounce to ShoppingError.
            $this->code = Code::SEE_OTHER;
            $this->headers['Location'] = '/shopping/error';
            $this->body = [
                'message' => '決済の確認に失敗しました。',
                'errors' => $final->errors,
            ];

            return $this;
        }

        assert($final instanceof OrderConfirmed);

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingConfirm',
            'preOrderId' => $final->preOrderId,
            'paymentMethodId' => $final->paymentMethodId,
            'paymentMethodName' => $final->paymentMethodName,
            'customer' => $final->customer,
            'items' => $final->items,
            'subtotal' => $final->subtotal,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
            'charge' => $final->charge,
            'discount' => $final->discount,
            'tax' => $final->tax,
            'total' => $final->total,
            'paymentTotal' => $final->paymentTotal,
            'addPoint' => $final->addPoint,
            'usePoint' => $final->usePoint,
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/shopping/checkout',
            ],
            'csrfToken' => null,
        ];

        return $this;
    }
}
