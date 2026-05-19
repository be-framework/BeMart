<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TaxRuleEntity;
use Override;

use function array_values;
use function ksort;

/**
 * In-memory TaxRule store. Starts empty — tests seed via POST.
 * Singleton so reads see same-request writes.
 */
final class FakeTaxRuleStorage implements TaxRuleStorageInterface
{
    /** @var array<string, TaxRuleEntity> keyed by taxRuleId */
    private array $byId = [];

    /** @return list<TaxRuleEntity> */
    #[Override]
    public function list(): array
    {
        $rows = $this->byId;
        ksort($rows);

        return array_values($rows);
    }

    #[Override]
    public function getById(string $taxRuleId): TaxRuleEntity|null
    {
        return $this->byId[$taxRuleId] ?? null;
    }

    #[Override]
    public function put(TaxRuleEntity $taxRule): void
    {
        $this->byId[$taxRule->taxRuleId] = $taxRule;
    }

    #[Override]
    public function remove(string $taxRuleId): void
    {
        unset($this->byId[$taxRuleId]);
    }
}
