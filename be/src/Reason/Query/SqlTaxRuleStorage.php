<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TaxRuleEntity;
use Override;

use function ctype_digit;
use function str_replace;
use function strlen;

final class SqlTaxRuleStorage implements TaxRuleStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    /** @return list<TaxRuleEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->ttax_rule_list());
    }

    #[Override]
    public function getById(string $taxRuleId): TaxRuleEntity|null
    {
        if (! ctype_digit($taxRuleId)) {
            return null;
        }
        $row = $this->db->ttax_rule_get(id: (int) $taxRuleId);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(TaxRuleEntity $taxRule): void
    {
        if (! ctype_digit($taxRule->taxRuleId)) {
            return;
        }
        $id = (int) $taxRule->taxRuleId;
        if ($this->db->ttax_rule_exists(id: $id) === null) {
            $this->db->ttax_rule_insert(id: $id, taxRate: $taxRule->taxRate, applyDate: $this->toMysqlDatetime($taxRule->applyDate));

            return;
        }

        $this->db->ttax_rule_update(id: $id, taxRate: $taxRule->taxRate, applyDate: $this->toMysqlDatetime($taxRule->applyDate));
    }

    #[Override]
    public function remove(string $taxRuleId): void
    {
        if (ctype_digit($taxRuleId)) {
            $this->db->ttax_rule_delete(id: (int) $taxRuleId);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): TaxRuleEntity
    {
        return new TaxRuleEntity(
            taxRuleId: (string) (int) $row['id'],
            taxRate: (float) $row['tax_rate'],
            roundingType: $row['rounding_type_id'] === null ? 1 : (int) $row['rounding_type_id'],
            applyDate: (string) ($row['apply_date'] ?? ''),
        );
    }

    private function toMysqlDatetime(string $value): string
    {
        $normalized = str_replace('T', ' ', $value);
        return strlen($normalized) === 10 ? $normalized . ' 00:00:00' : $normalized;
    }
}
