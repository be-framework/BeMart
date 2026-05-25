<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Being;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Exception\UnauthorizedOrderAccessException;
use MyVendor\BeMart\Be\Final\Reordered;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function min;

/**
 * Stage 1 Being — past order resolved, ownership verified, items
 * re-projected onto the current catalog.
 *
 * Cascade Diamond Stage 1 (loan-application demo). Collapses three
 * Reasons into one existence:
 *
 *   1. `SessionInterface` — AUTHN: requires a logged-in customer.
 *      Anonymous (null customerId) throws `UnauthenticatedException`.
 *   2. `OrderQueryInterface` — loads the finalized-order header via
 *      `byOrderNo`, then performs the AUTHZ check (`$order->customerId`
 *      vs session customerId); mismatch throws
 *      `UnauthorizedOrderAccessException`. Unknown orderNo throws
 *      `OrderNotFoundException`. Past items are loaded via
 *      `itemsByOrderNo`.
 *   3. `ProductClassQueryInterface` — per past-item, looks up the
 *      *current* ProductClass row to apply the ALPS contract
 *      "在庫切れ商品はスキップ、現在価格を適用" (skip out-of-stock
 *      products, apply current prices) and re-cap the quantity against
 *      the current `stock` / `saleLimit`.
 *
 * Existence of this object proves: the order exists, the requester owns
 * it, and every past line has been classified as either includable
 * (with current prices/quantities) or skipped (with a reason).
 *
 * Ordering note: AUTHN (no session at all) precedes header lookup
 * because an anonymous request has no business probing existence.
 * Then existence precedes AUTHZ (consistent with Pilot 5) so the
 * 404 vs 403 distinction is preserved for legitimate but-unauthorized
 * callers.
 *
 * Skip-rather-than-fail policy:
 *   - product no longer in catalog (ProductClass missing) → skip
 *   - stock=0 and !stockUnlimited                          → skip
 *   - quantity over current cap                            → adjust
 *     (min of past, current stock, current saleLimit) — recorded as
 *     `adjustedQuantity` on the included item, never raised.
 */
#[Be(Reordered::class)]
final readonly class ReorderResolving
{
    public string $customerId;
    public string $sessionPrefix;
    public string $orderNo;

    /**
     * Items that survive the current-catalog re-projection. Each row
     * carries the productCode, the current ProductClass-derived display
     * values (productName / unitPrice / saleType / deliveryFee), and the
     * capped quantity. Final stage groups these by saleTypeId and
     * persists per cartKey.
     *
     * @var list<array{productCode: string, productName: string, requestedQuantity: int, adjustedQuantity: int, unitPrice: int, saleTypeId: int, saleTypeName: string, deliveryFee: int, stockUnlimited: bool, stock: int|null, saleLimit: int|null}>
     */
    public array $included;

    /**
     * Items that the current catalog cannot replay. `reason` is a short
     * machine-readable tag for client-side messaging:
     *   - `discontinued` — ProductClass row missing from current catalog
     *   - `out-of-stock` — stock=0 (and !stockUnlimited)
     *
     * @var list<array{productCode: string, reason: string}>
     */
    public array $skipped;

    public function __construct(
        #[Input] string $orderNo,
        #[Input] string $sessionPrefix,
        #[Inject] SessionInterface $session,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] ProductClassQueryInterface $productClassQuery,
    ) {
        $sessionCustomerId = $session->customerId();
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $order = $orderQuery->byOrderNo($orderNo);
        if (! $order instanceof FinalizedOrderEntity) {
            throw new OrderNotFoundException();
        }

        if ($order->customerId !== $sessionCustomerId) {
            throw new UnauthorizedOrderAccessException();
        }

        $included = [];
        $skipped = [];
        foreach ($orderQuery->itemsByOrderNo($orderNo) as $pastItem) {
            $this->classify($pastItem, $productClassQuery, $included, $skipped);
        }

        $this->customerId = $sessionCustomerId;
        $this->sessionPrefix = $sessionPrefix;
        $this->orderNo = $orderNo;
        $this->included = $included;
        $this->skipped = $skipped;
    }

    /**
     * Side-effecting (by reference) classifier — kept private so the
     * constructor reads as a single pass over the past items.
     *
     * @param list<array{productCode: string, productName: string, requestedQuantity: int, adjustedQuantity: int, unitPrice: int, saleTypeId: int, saleTypeName: string, deliveryFee: int, stockUnlimited: bool, stock: int|null, saleLimit: int|null}> $included
     * @param list<array{productCode: string, reason: string}> $skipped
     *
     * @param-out list<array{productCode: string, productName: string, requestedQuantity: int, adjustedQuantity: int, unitPrice: int, saleTypeId: int, saleTypeName: string, deliveryFee: int, stockUnlimited: bool, stock: int|null, saleLimit: int|null}> $included
     * @param-out list<array{productCode: string, reason: string}> $skipped
     */
    private function classify(
        OrderItemEntity $pastItem,
        ProductClassQueryInterface $productClassQuery,
        array &$included,
        array &$skipped,
    ): void {
        $productClass = $productClassQuery->item($pastItem->productCode);
        if (! $productClass instanceof ProductClassEntity) {
            $skipped[] = ['productCode' => $pastItem->productCode, 'reason' => 'discontinued'];

            return;
        }

        if (! $productClass->stockUnlimited && $productClass->stock === 0) {
            $skipped[] = ['productCode' => $pastItem->productCode, 'reason' => 'out-of-stock'];

            return;
        }

        $adjusted = $pastItem->quantity;
        if (! $productClass->stockUnlimited && $productClass->stock !== null) {
            $adjusted = min($adjusted, $productClass->stock);
        }

        if ($productClass->saleLimit !== null) {
            $adjusted = min($adjusted, $productClass->saleLimit);
        }

        $included[] = [
            'productCode' => $productClass->productCode,
            'productName' => $productClass->productName,
            'requestedQuantity' => $pastItem->quantity,
            'adjustedQuantity' => $adjusted,
            'unitPrice' => $productClass->price02,
            'saleTypeId' => $productClass->saleTypeId,
            'saleTypeName' => $productClass->saleTypeName,
            'deliveryFee' => $productClass->deliveryFee,
            'stockUnlimited' => $productClass->stockUnlimited,
            'stock' => $productClass->stock,
            'saleLimit' => $productClass->saleLimit,
        ];
    }
}
