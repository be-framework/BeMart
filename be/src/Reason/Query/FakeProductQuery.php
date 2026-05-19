<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use Override;

/**
 * Phase 1 (FakeQuery) implementation delegating to the shared
 * FakeProductStorage singleton.
 *
 * Production Phase 2 will swap this binding to a Ray.MediaQuery
 * `#[DbQuery('get_product')]` interface backed by var/db/sql/get_product.sql.
 *
 * Wave 8 split: list / search / export projections all delegate to
 * the Storage so the FakeProductCommand (Wave 8) and FakeProductQuery
 * share one corpus. Mirrors FakeCustomerQuery's CQRS layout.
 */
final class FakeProductQuery implements ProductQueryInterface
{
    public function __construct(
        private readonly FakeProductStorage $storage,
    ) {
    }

    #[Override]
    public function item(string $productCode): ProductEntity|null
    {
        return $this->storage->getByCode($productCode);
    }

    /**
     * @return list<ProductEntity>
     */
    #[Override]
    public function listAll(int $limit, int $offset = 0): array
    {
        return $this->storage->listAll($limit, $offset);
    }

    /**
     * @return list<ProductEntity>
     */
    #[Override]
    public function search(?string $nameKeyword, int $limit = 50): array
    {
        return $this->storage->search($nameKeyword, $limit);
    }

    /**
     * @return list<ProductEntity>
     */
    #[Override]
    public function listForExport(): array
    {
        return $this->storage->listForExport();
    }
}
