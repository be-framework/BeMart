<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\CartEntity;
use MyVendor\BeMart\Be\Reason\Entity\CartItemEntity;
use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductClassQueryInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function array_sum;
use function count;
use function min;
use function sprintf;

/**
 * Cascade Diamond Final — proof that the past order's items have been
 * re-projected into the customer's current cart(s).
 *
 * Multi-side-effect convergence: existence of this object proves that
 * for each saleType group in {@see \MyVendor\BeMart\Be\Being\ReorderResolving}'s
 * `$included`, the existing cart (if any) was loaded, the items were
 * ADDED (mirroring Pilot 2's CartMerged semantics — current adjustment
 * is capped, then any pre-existing same-productCode quantity in the
 * cart is summed in with the same cap reapplied), and the resulting
 * CartEntity was saved.
 *
 * One Save() call per saleType partition. The public surface mirrors
 * what callers need to render the post-reorder state: counts of
 * included vs. skipped, the productCodes that were skipped (so the UI
 * can flag them), and the cartKeys touched.
 *
 * Skip-rather-than-fail: `Reordered` never throws — Stage 1 has already
 * decided what is includable; this Final only persists. An empty
 * `$included` is a valid Final (the order had nothing replayable);
 * `addedCount` is 0 and `cartKeys` is empty.
 */
final readonly class Reordered
{
    public string $customerId;
    public string $orderNo;
    public int $addedCount;
    public int $skippedCount;

    /** @var list<string> */
    public array $skippedProductCodes;

    /** @var list<string> */
    public array $cartKeys;

    /**
     * @param list<array{productCode: string, productName: string, requestedQuantity: int, adjustedQuantity: int, unitPrice: int, saleTypeId: int, saleTypeName: string, deliveryFee: int, stockUnlimited: bool, stock: int|null, saleLimit: int|null}> $included
     * @param list<array{productCode: string, reason: string}> $skipped
     */
    public function __construct(
        #[Input] string $customerId,
        #[Input] string $sessionPrefix,
        #[Input] string $orderNo,
        #[Input] array $included,
        #[Input] array $skipped,
        #[Inject] CartQueryInterface $cartQuery,
        #[Inject] CartCommandInterface $cartCommand,
        #[Inject] ProductClassQueryInterface $productClassQuery,
    ) {
        // Partition includable items by saleTypeId — one cart per partition.
        /** @var array<int, list<array{productCode: string, productName: string, requestedQuantity: int, adjustedQuantity: int, unitPrice: int, saleTypeId: int, saleTypeName: string, deliveryFee: int, stockUnlimited: bool, stock: int|null, saleLimit: int|null}>> $bySaleType */
        $bySaleType = [];
        foreach ($included as $row) {
            $bySaleType[$row['saleTypeId']][] = $row;
        }

        $cartKeys = [];
        $addedCount = 0;
        foreach ($bySaleType as $saleTypeId => $rows) {
            $cartKey = sprintf('%s_%d', $sessionPrefix, $saleTypeId);
            $existing = $cartQuery->item($cartKey)
                ?? new CartEntity(
                    cartKey: $cartKey,
                    saleTypeId: $saleTypeId,
                    saleTypeName: $rows[0]['saleTypeName'],
                    items: [],
                    totalPrice: 0,
                    deliveryFeeTotal: 0,
                    preOrderId: '',
                );

            $merged = $this->mergeRows($existing, $rows, $productClassQuery);
            $cartCommand->save($merged);
            $cartKeys[] = $cartKey;
            foreach ($rows as $row) {
                $addedCount += $row['adjustedQuantity'];
            }
        }

        $this->customerId = $customerId;
        $this->orderNo = $orderNo;
        $this->addedCount = $addedCount;
        $this->skippedCount = count($skipped);
        $this->skippedProductCodes = array_map(
            static fn (array $r): string => $r['productCode'],
            $skipped,
        );
        $this->cartKeys = $cartKeys;
    }

    /**
     * Merge a list of includable rows (same saleTypeId) into an existing
     * cart. Same-productCode rows in the cart are summed-then-capped;
     * new productCodes are appended. After items are resolved,
     * totalPrice and deliveryFeeTotal are recomputed against the current
     * catalog (mirroring CartMerged's recompute strategy).
     *
     * @param list<array{productCode: string, productName: string, requestedQuantity: int, adjustedQuantity: int, unitPrice: int, saleTypeId: int, saleTypeName: string, deliveryFee: int, stockUnlimited: bool, stock: int|null, saleLimit: int|null}> $rows
     */
    private function mergeRows(
        CartEntity $existing,
        array $rows,
        ProductClassQueryInterface $productClassQuery,
    ): CartEntity {
        // Index rows by productCode for O(1) "is this productCode being
        // reordered?" lookups during the existing-items pass.
        /** @var array<string, array{productCode: string, productName: string, requestedQuantity: int, adjustedQuantity: int, unitPrice: int, saleTypeId: int, saleTypeName: string, deliveryFee: int, stockUnlimited: bool, stock: int|null, saleLimit: int|null}> $byCode */
        $byCode = [];
        foreach ($rows as $row) {
            $byCode[$row['productCode']] = $row;
        }

        $items = [];
        $seen = [];
        foreach ($existing->items as $item) {
            $row = $byCode[$item->productCode] ?? null;
            if ($row === null) {
                $items[] = $item;

                continue;
            }

            $newQty = $item->quantity + $row['adjustedQuantity'];
            if (! $row['stockUnlimited'] && $row['stock'] !== null) {
                $newQty = min($newQty, $row['stock']);
            }

            if ($row['saleLimit'] !== null) {
                $newQty = min($newQty, $row['saleLimit']);
            }

            $items[] = new CartItemEntity(
                productCode: $item->productCode,
                quantity: $newQty,
                price: $row['unitPrice'],
            );
            $seen[$item->productCode] = true;
        }

        // Rows whose productCode was not already in the cart: append.
        foreach ($rows as $row) {
            if (isset($seen[$row['productCode']])) {
                continue;
            }

            $items[] = new CartItemEntity(
                productCode: $row['productCode'],
                quantity: $row['adjustedQuantity'],
                price: $row['unitPrice'],
            );
        }

        $totalPrice = (int) array_sum(
            array_map(static fn (CartItemEntity $i): int => $i->price * $i->quantity, $items),
        );

        $deliveryFeeTotal = (int) array_sum(
            array_map(
                static function (CartItemEntity $i) use ($productClassQuery): int {
                    $pc = $productClassQuery->item($i->productCode);

                    return $pc instanceof ProductClassEntity ? $pc->deliveryFee * $i->quantity : 0;
                },
                $items,
            ),
        );

        return new CartEntity(
            cartKey: $existing->cartKey,
            saleTypeId: $existing->saleTypeId,
            saleTypeName: $existing->saleTypeName,
            items: $items,
            totalPrice: $totalPrice,
            deliveryFeeTotal: $deliveryFeeTotal,
            preOrderId: $existing->preOrderId,
        );
    }
}
