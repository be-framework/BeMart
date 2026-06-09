<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use DateTimeImmutable;
use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderCommandInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function bin2hex;
use function random_bytes;

/**
 * Non-member checkout entry — Final, proof the guest fields validated.
 *
 *   SubmitNonMemberInput → NonMemberSubmitted
 *
 * Wave 7W SCOPE — FORM ENTRY ONLY.
 *
 *   EC-CUBE allows anonymous customers to submit shipping info and
 *   subsequently confirm a checkout without registering an account.
 *   This Final proves the form transition exists: every guest field
 *   passes its Semantic validation (Email, Name01, Name02, Kana01,
 *   Kana02, PhoneNumber, PostalCode, Pref, Addr01, Addr02) and the
 *   server synthesises a preOrderId from CustomerIdProvider.
 *
 * PHASE 2 GAP — what this Final intentionally does NOT do:
 *
 *   - It does not persist a CartEntity / PreOrder under the guest's
 *     identity. Pilot 9 wired `bySessionPrefix` for member carts; the
 *     equivalent "anonymous PreOrder" entity is out of Wave 7W's scope.
 *   - It does not relax Pilot 5's AUTHZ on doCheckout. Today's
 *     CheckoutPrepared still raises UnauthorizedPreOrderAccessException
 *     when the session has no customerId; a downstream `doCheckout`
 *     POST using the preOrderId returned here will therefore 403.
 *     Closing that gap is Phase 2's job (a dedicated GuestProfile and
 *     a non-member PreOrder branch in CheckoutPrepared).
 *   - It reuses CustomerIdProvider to mint the preOrderId;
 *     Phase 2 should introduce a dedicated PreOrderIdProvider (and
 *     align with PreOrderId Semantic's 40-hex format, which the
 *     reused provider does NOT satisfy — it produces 32 hex chars).
 *
 * The Final's public surface mirrors the doSubmitNonMember ALPS
 * descriptor (#name01, #name02, #email) plus the synthesised
 * preOrderId the caller can use as the doCheckout handle.
 */
final readonly class NonMemberSubmitted
{
    public string $preOrderId;
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
    ) {
        $this->preOrderId = bin2hex(random_bytes(20));
        $this->name01 = $name01;
        $this->name02 = $name02;
        $this->email = $email;

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
            paymentMethodId: 1,
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
        ));
    }
}
