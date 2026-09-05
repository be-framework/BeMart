<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use DateTimeImmutable;
use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\PaymentMethodFactoryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function bin2hex;
use function ctype_digit;
use function random_bytes;

/**
 * Non-member checkout entry — Final, proof the guest fields validated.
 *
 *   SubmitNonMemberInput → NonMemberSubmitted
 *
 * EC-CUBE allows anonymous customers to submit shipping info and
 * subsequently confirm a checkout without registering an account. This
 * Final validates every guest field, materialises the current anonymous
 * cart as a PROCESSING order, and stores the guest customer snapshot on
 * that order so the confirm/checkout screens do not need a customer row.
 *
 * It still mints the 40-hex handle locally because the current scope has
 * only this one non-member pre-order creation point.
 *
 * The Final's public surface mirrors the doSubmitNonMember ALPS
 * descriptor (#name01, #name02, #email) plus the synthesised
 * preOrderId the caller can use as the doCheckout handle.
 */
final readonly class NonMemberSubmitted
{
    public string $preOrderId;
    public int $paymentMethodId;
    public string $name01;
    public string $name02;
    public string $email;

    public function __construct(
        #[Input] string $name01,
        #[Input] string $name02,
        #[Input] string $kana01,
        #[Input] string $kana02,
        #[Input] string $email,
        #[Input] string $phoneNumber,
        #[Input] string $postalCode,
        #[Input] int $pref,
        #[Input] string $addr01,
        #[Input] string $addr02,
        #[Input] string $sessionPrefix,
        #[Inject] CartQueryInterface $cartQuery,
        #[Inject] CartCommandInterface $cartCommand,
        #[Inject] OrderCommandInterface $orderCommand,
        #[Inject] PaymentMethodAdminStorageInterface $paymentMethods,
        #[Inject] PaymentMethodFactoryInterface $paymentMethodFactory,
    ) {
        $this->preOrderId = bin2hex(random_bytes(20));
        $this->paymentMethodId = $this->selectPaymentMethodId($paymentMethods, $paymentMethodFactory);
        $this->name01 = $name01;
        $this->name02 = $name02;
        $this->email = $email;
        $customerSnapshot = [
            'name01' => $name01,
            'name02' => $name02,
            'kana01' => $kana01,
            'kana02' => $kana02,
            'companyName' => null,
            'email' => $email,
            'phoneNumber' => $phoneNumber,
            'postalCode' => $postalCode,
            'pref' => $pref,
            'addr01' => $addr01,
            'addr02' => $addr02,
        ];

        $subtotal = 0;
        $deliveryFeeTotal = 0;
        foreach ($cartQuery->listBySessionPrefix($sessionPrefix) as $cart) {
            $deliveryFeeTotal += $cart->deliveryFeeTotal;
            foreach ($cart->items as $item) {
                $subtotal += $item->price * $item->quantity;
            }

            $cartCommand->save(new CartEntity(
                cartKey: $cart->cartKey,
                saleTypeId: $cart->saleTypeId,
                saleTypeName: $cart->saleTypeName,
                items: $cart->items,
                totalPrice: $cart->totalPrice,
                deliveryFeeTotal: $cart->deliveryFeeTotal,
                preOrderId: $this->preOrderId,
            ));
        }

        $total = $subtotal + $deliveryFeeTotal;
        $orderCommand->register(new FinalizedOrderEntity(
            orderNo: $this->preOrderId,
            preOrderId: $this->preOrderId,
            customerId: '',
            paymentMethodId: $this->paymentMethodId,
            subtotal: $subtotal,
            deliveryFeeTotal: $deliveryFeeTotal,
            charge: 0,
            discount: 0,
            tax: 0,
            total: $total,
            paymentTotal: $total,
            addPoint: 0,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_PROCESSING,
            orderDate: (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            paymentDate: '',
            customerSnapshot: $customerSnapshot,
        ));
    }

    private function selectPaymentMethodId(
        PaymentMethodAdminStorageInterface $paymentMethods,
        PaymentMethodFactoryInterface $paymentMethodFactory,
    ): int {
        foreach ($paymentMethods->list() as $paymentMethod) {
            if (! $paymentMethod->visible || ! ctype_digit($paymentMethod->paymentId)) {
                continue;
            }

            return (int) $paymentMethod->paymentId;
        }

        $available = $paymentMethodFactory->available();
        $first = $available[0]['paymentMethodId'] ?? 1;

        return (int) $first;
    }
}
