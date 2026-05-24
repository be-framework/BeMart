<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\PaymentSuccessCase;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;

/**
 * Final — proof that confirm() succeeded and the ShoppingConfirm state is
 * ready for the customer.
 *
 * Selected by the Be Framework when OrderConfirming's $being discriminator
 * is a PaymentSuccessCase. All ShoppingConfirm scalars are read off that
 * Case — the Final delegates to it (medical-triage Case-class pattern).
 *
 * The public surface mirrors ALPS ShoppingConfirm descriptors.
 *
 * Phase 3 enrichment — the confirm screen (EC-CUBE `Shopping/confirm.twig`)
 * renders far more than the totals box: an お客様情報 customer-info block,
 * a 配送情報 per-shipping block with the order's line items, and the
 * payment-method line. The earlier Final carried only the totals, so the
 * Confirm resource was a thin pure renderer with empty order-detail loops.
 *
 * This Final now composes the screen's order-detail projection:
 *  - `customer` — the buyer's name / address / contact, read for
 *    `order.customerId` via {@see CustomerQueryInterface} (the same
 *    aggregate read {@see ShoppingFetched} does for the review page);
 *  - `items`    — the pre-order's line items, each with the product name
 *    resolved from {@see ProductClassQueryInterface} (the Fake mirror of
 *    SqlCartQuery's product-class JOIN — the pre-order's `OrderEntity`
 *    carries bare `CartItemEntity` rows with productCode only);
 *  - `paymentMethodName` — the display label for `paymentMethodId`,
 *    resolved from {@see PaymentMethodFactoryInterface::available}.
 *
 * The resolved pre-order `OrderEntity` is forwarded through the Becoming
 * chain (PreOrderResolved → PurchaseFlowApplied → PaymentVerified →
 * OrderConfirming) as a plain `#[Input]` — no widely-shared entity is
 * mutated. The single-shipping projection mirrors EC-CUBE's default-theme
 * checkout (one Shipping per Order); multi-shipping is a later slice.
 */
final readonly class OrderConfirmed
{
    public string $preOrderId;
    public int $paymentMethodId;
    public string $paymentMethodName;
    public int $subtotal;
    public int $deliveryFeeTotal;
    public int $charge;
    public int $discount;
    public int $tax;
    public int $total;
    public int $paymentTotal;
    public int $addPoint;
    public int $usePoint;

    /**
     * @var array{
     *   name01: string, name02: string, kana01: string|null, kana02: string|null,
     *   companyName: string|null, email: string, phoneNumber: string|null,
     *   postalCode: string|null, pref: int|null, addr01: string|null, addr02: string|null
     * }
     */
    public array $customer;

    /**
     * @var list<array{
     *   productCode: string, productName: string, quantity: int,
     *   unitPrice: int, totalPrice: int
     * }>
     */
    public array $items;

    public function __construct(
        #[Input] public PaymentSuccessCase $being,
        #[Input] string $preOrderId,
        #[Input] int $paymentMethodId,
        #[Input] OrderEntity $order,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] ProductClassQueryInterface $productClasses,
        #[Inject] PaymentMethodFactoryInterface $paymentMethodFactory,
    ) {
        $totals = $being->totals;

        $this->preOrderId = $preOrderId;
        $this->paymentMethodId = $paymentMethodId;
        $this->subtotal = $totals->subtotal;
        $this->deliveryFeeTotal = $totals->deliveryFeeTotal;
        $this->charge = $totals->charge;
        $this->discount = $totals->discount;
        $this->tax = $totals->tax;
        $this->total = $totals->total;
        $this->paymentTotal = $totals->paymentTotal;
        $this->addPoint = $totals->addPoint;
        $this->usePoint = $totals->usePoint;

        $this->paymentMethodName = $this->resolvePaymentMethodName(
            $paymentMethodFactory,
            $paymentMethodId,
        );

        $customer = $customerQuery->item($order->customerId);
        $this->customer = [
            'name01' => $customer?->name01 ?? '',
            'name02' => $customer?->name02 ?? '',
            'kana01' => $customer?->kana01,
            'kana02' => $customer?->kana02,
            'companyName' => $customer?->companyName,
            'email' => $customer?->email ?? '',
            'phoneNumber' => $customer?->phoneNumber,
            'postalCode' => $customer?->postalCode,
            'pref' => $customer?->pref,
            'addr01' => $customer?->addr01,
            'addr02' => $customer?->addr02,
        ];

        $this->items = array_map(
            function (CartItemEntity $item) use ($productClasses): array {
                $productClass = $productClasses->item($item->productCode);

                return [
                    'productCode' => $item->productCode,
                    'productName' => $productClass?->productName ?? $item->productName,
                    'quantity' => $item->quantity,
                    'unitPrice' => $item->price,
                    'totalPrice' => $item->price * $item->quantity,
                ];
            },
            $order->items,
        );
    }

    /**
     * Resolve the payment-method display label from the factory's
     * user-selectable list. Falls back to an empty string for an id
     * absent from that list (e.g. the test-only fault-injection method).
     */
    private function resolvePaymentMethodName(
        PaymentMethodFactoryInterface $factory,
        int $paymentMethodId,
    ): string {
        foreach ($factory->available() as $method) {
            if ($method['paymentMethodId'] === $paymentMethodId) {
                return $method['paymentMethodName'];
            }
        }

        return '';
    }
}
