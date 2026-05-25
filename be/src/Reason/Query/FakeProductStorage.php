<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use RuntimeException;

use function array_slice;
use function array_values;
use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;
use function str_contains;

use const JSON_THROW_ON_ERROR;

/**
 * In-memory Product store shared by FakeProductQuery + FakeProductCommand.
 *
 * Singleton-scoped in AppModule so a Wave 8 create / update / delete /
 * copy / bulk-update is visible to subsequent reads within the same
 * request (and within a single test). Mirrors FakeCustomerStorage's
 * convention (Wave 5+).
 *
 * The seed fixture (`var/fake/products.json`) holds the Pilot 1 happy-
 * path products (`sample-001`, `sample-002`) plus three admin-side rows
 * (`admin-active-001`, `admin-hidden-001`, `admin-withdrawn-001`) that
 * exercise each productStatus branch.
 *
 * Pilot 1 backward-compatibility: the fixture JSON may omit any of the
 * Wave 8 fields (productStatus, description, …). The loader fills in
 * the ProductEntity defaults so existing fixtures keep working without
 * a fixture migration.
 */
final class FakeProductStorage
{
    /** @var array<string, ProductEntity>|null indexed by productCode */
    private array|null $byCode = null;

    public function getByCode(string $productCode): ProductEntity|null
    {
        return $this->load()[$productCode] ?? null;
    }

    /**
     * @return list<ProductEntity>
     */
    public function listAll(int $limit, int $offset = 0): array
    {
        $rows = array_values($this->load());

        return array_slice($rows, $offset, $limit);
    }

    /**
     * Substring filter on productName. Admin grid scope (sees all
     * statuses); pass null/empty to disable the keyword filter.
     *
     * @return list<ProductEntity>
     */
    public function search(?string $nameKeyword, int $limit = 50): array
    {
        $rows = array_values($this->load());
        if ($nameKeyword === null || $nameKeyword === '') {
            return array_slice($rows, 0, $limit);
        }

        $matches = [];
        foreach ($rows as $product) {
            if (str_contains($product->productName, $nameKeyword)) {
                $matches[] = $product;
            }
        }

        return array_slice($matches, 0, $limit);
    }

    /**
     * Full unpaged dump — CSV export.
     *
     * @return list<ProductEntity>
     */
    public function listForExport(): array
    {
        return array_values($this->load());
    }

    public function put(ProductEntity $product): void
    {
        $this->load();
        $this->byCode[$product->productCode] = $product;
    }

    public function exists(string $productCode): bool
    {
        return isset($this->load()[$productCode]);
    }

    /**
     * Remove a row entirely. Wave 8 only uses this as a Phase 2 hook
     * — the public delete path is a soft delete (status=3 via replace).
     */
    public function remove(string $productCode): void
    {
        $rows = $this->load();
        unset($rows[$productCode]);
        $this->byCode = $rows;
    }

    /** @return array<string, ProductEntity> */
    private function load(): array
    {
        if ($this->byCode !== null) {
            return $this->byCode;
        }

        $path = dirname(__DIR__, 3) . '/var/fake/products.json';
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException(sprintf('Fake fixture missing: %s', $path));
        }

        /** @var list<array{productCode: string, productName: string, price02: int, stock: int|null, productStatus?: int, description?: string|null, searchWord?: string|null, note?: string|null}> $rows */
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows)) {
            throw new RuntimeException(sprintf('Fake fixture must be a JSON array: %s', $path));
        }

        $byCode = [];
        foreach ($rows as $row) {
            $byCode[$row['productCode']] = new ProductEntity(
                productCode: $row['productCode'],
                productName: $row['productName'],
                price02: $row['price02'],
                stock: $row['stock'],
                productStatus: $row['productStatus'] ?? ProductEntity::STATUS_VISIBLE,
                description: $row['description'] ?? null,
                searchWord: $row['searchWord'] ?? null,
                note: $row['note'] ?? null,
            );
        }

        return $this->byCode = $byCode;
    }
}
