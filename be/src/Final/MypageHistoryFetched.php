<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Exception\UnauthorizedOrderAccessException;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryMailEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderHistoryShippingEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderHistoryQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;

/**
 * Mypage history fetched — Final, the detail view of one past order
 * for the logged-in customer.
 *
 *   GetMypageHistoryInput → MypageHistoryFetched  (Direct, safe read)
 *
 * AUTHN + AUTHZ — order check sequencing (Pilot 12 lesson):
 *
 *   1. No session                  → UnauthenticatedException  (401)
 *   2. orderNo unknown             → OrderNotFoundException    (404)
 *   3. order owned by someone else → UnauthorizedOrderAccessException
 *                                                              (403)
 *
 * Anonymous requests are rejected before existence is probed (an
 * anonymous client has no business learning whether a given orderNo
 * resolves). Existence precedes AUTHZ so that the 404/403 distinction
 * is preserved for legitimate but-unauthorized callers — consistent
 * with how ReorderResolving stages the same three checks.
 *
 * Phase 3 enrichment — the projection composes the FULL order-history
 * detail screen (EC-CUBE `Mypage/history.twig`): the order header +
 * totals, the customer's `message`, the `paymentMethod` name, the
 * per-shipping address blocks (each with its line items) and the
 * mail-delivery history. The earlier thin projection (totals + a flat
 * `items` list) carried none of the shipping / payment / message / mail
 * data the screen renders.
 *
 * The composite sub-objects (`shippings`, `mailHistories`) are exposed
 * as projection arrays — not the entities themselves — so the HTTP body
 * shape stays flat and the entity's internal field layout does not leak
 * across the AAA boundary, the same convention the Mypage dashboard's
 * `recentOrders` follows.
 */
final readonly class MypageHistoryFetched
{
    public string $orderNo;
    public string $customerId;
    public string $message;
    public string $paymentMethod;
    public int $subtotal;
    public int $deliveryFeeTotal;
    public int $charge;
    public int $discount;
    public int $tax;
    public int $total;
    public int $paymentTotal;
    public int $addPoint;
    public int $usePoint;
    public int $orderStatus;
    public string $orderDate;
    public string $paymentDate;

    /**
     * Per-shipping address blocks. Each carries the recipient address,
     * the delivery method / date / time and the block's line items.
     *
     * @var list<array{
     *   name01: string, name02: string, kana01: string, kana02: string,
     *   postalCode: string, prefName: string, addr01: string,
     *   addr02: string, phoneNumber: string, deliveryName: string,
     *   deliveryDate: string, deliveryTime: string,
     *   items: list<array{productCode: string, productName: string, quantity: int, unitPrice: int}>
     * }>
     */
    public array $shippings;

    /** @var list<array{sendDate: string, mailSubject: string, mailBody: string}> */
    public array $mailHistories;

    public function __construct(
        #[Input] string $orderNo,
        #[Inject] SessionInterface $session,
        #[Inject] OrderHistoryQueryInterface $orderQuery,
    ) {
        $sessionCustomerId = $session->customerId();
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $order = $orderQuery->item($orderNo);
        if (! $order instanceof OrderHistoryEntity) {
            throw new OrderNotFoundException();
        }

        if ($order->customerId !== $sessionCustomerId) {
            throw new UnauthorizedOrderAccessException();
        }

        $this->orderNo = $order->orderNo;
        $this->customerId = $order->customerId;
        $this->message = $order->message;
        $this->paymentMethod = $order->paymentMethod;
        $this->subtotal = $order->subtotal;
        $this->deliveryFeeTotal = $order->deliveryFeeTotal;
        $this->charge = $order->charge;
        $this->discount = $order->discount;
        $this->tax = $order->tax;
        $this->total = $order->total;
        $this->paymentTotal = $order->paymentTotal;
        $this->addPoint = $order->addPoint;
        $this->usePoint = $order->usePoint;
        $this->orderStatus = $order->orderStatus;
        $this->orderDate = $order->orderDate;
        $this->paymentDate = $order->paymentDate;

        $this->shippings = array_map(
            static fn (OrderHistoryShippingEntity $s): array => [
                'name01' => $s->name01,
                'name02' => $s->name02,
                'kana01' => $s->kana01,
                'kana02' => $s->kana02,
                'postalCode' => $s->postalCode,
                'prefName' => $s->prefName,
                'addr01' => $s->addr01,
                'addr02' => $s->addr02,
                'phoneNumber' => $s->phoneNumber,
                'deliveryName' => $s->deliveryName,
                'deliveryDate' => $s->deliveryDate,
                'deliveryTime' => $s->deliveryTime,
                'items' => array_map(
                    static fn (OrderHistoryItemEntity $i): array => [
                        'productCode' => $i->productCode,
                        'productName' => $i->productName,
                        'quantity' => $i->quantity,
                        'unitPrice' => $i->unitPrice,
                    ],
                    $s->items,
                ),
            ],
            $order->shippings,
        );

        $this->mailHistories = array_map(
            static fn (OrderHistoryMailEntity $m): array => [
                'sendDate' => $m->sendDate,
                'mailSubject' => $m->mailSubject,
                'mailBody' => $m->mailBody,
            ],
            $order->mailHistories,
        );
    }
}
