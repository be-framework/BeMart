<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * Admin-side tax-rule master row — projection of EC-CUBE dtb_tax_rule
 * (Wave 9θ shop settings slice).
 *
 *   - taxRuleId     : opaque server-generated identifier
 *   - taxRate       : percentage applied at this rule's apply-date
 *                     (e.g. 10 for 10%, 8 for 軽減税率)
 *   - roundingType  : 1 = 四捨五入, 2 = 切り捨て, 3 = 切り上げ
 *                     (matches the EC-CUBE master enum verbatim)
 *   - applyDate     : ISO-8601 timestamp after which this rule starts
 *                     to apply. Multiple rules are evaluated in
 *                     applyDate order.
 *
 * NOTE: the alps.json profile only exposes create / delete / list for
 * tax rules — there is no `doUpdateTaxRule` transition. In real
 * EC-CUBE, mutating an existing tax rule has cascade effects on
 * historical order snapshots, which is why edits flow as
 * delete-then-create. Phase 2 may add per-row updates once a
 * dependency-aware audit trail exists.
 */
final readonly class TaxRuleEntity
{
    public function __construct(
        public string $taxRuleId,
        public float $taxRate,
        public int $roundingType,
        public string $applyDate,
    ) {
    }
}
