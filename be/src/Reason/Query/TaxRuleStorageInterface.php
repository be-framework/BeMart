<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TaxRuleEntity;

/**
 * Admin tax-rule master — unified Query + Command (Wave 9θ).
 *
 * Same convention as {@see ClassNameStorageInterface}. Note that the
 * alps.json profile has NO update transition for tax rules — edits
 * happen as delete-then-create so that an explicit history of applied
 * rules is preserved. `put` here is therefore only used by the create
 * Final.
 */
interface TaxRuleStorageInterface
{
    /** @return list<TaxRuleEntity> */
    public function list(): array;

    public function getById(string $taxRuleId): TaxRuleEntity|null;

    public function put(TaxRuleEntity $taxRule): void;

    public function remove(string $taxRuleId): void;
}
