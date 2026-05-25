<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\AdminOrdersBulkDeleted;

/**
 * Input for doBulkDeleteOrder — admin "deletes" several orders at once
 * by flipping each row's `orderStatus` to CANCEL(3).
 *
 *   AdminBulkDeleteOrderInput → AdminOrdersBulkDeleted  (Direct, unsafe)
 *
 * ALPS `doBulkDeleteOrder.type=unsafe`. The ALPS doc says "物理削除" but
 * EC-CUBE's actual deletion semantics keep the row for downstream
 * reporting (the row's order_status_id flips to 3=注文取消 and the
 * row stays put — same convention Wave 6 took for customer-delete and
 * Wave 7 already encodes in the {@see \MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity::STATUS_CANCEL}
 * constant). Phase 2 will revisit the cascade (order-item / shipping
 * row cleanup) when those tables exist; the present Wave only flips
 * the header row.
 *
 * Format validation: the {@see \MyVendor\BeMart\Be\Semantic\OrderNos}
 * Semantic enforces the 1–100 list-size cap + per-element non-empty
 * string rule. Unknown orderNos in the list are silently skipped at
 * the Final (`requestedCount` vs `changedCount` lets the admin UI
 * surface the discrepancy — mirrors the Wave 8 product-bulk pattern).
 *
 * @link https://schema.org/DeleteAction
 */
#[Be(AdminOrdersBulkDeleted::class)]
final readonly class AdminBulkDeleteOrderInput
{
    /**
     * @param list<string> $orderNos
     *
     * @psalm-taint-source input $orderNos
     */
    public function __construct(
        public array $orderNos,
    ) {
    }
}
