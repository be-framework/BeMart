<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use Override;
use RuntimeException;

use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Phase 1 (FakeQuery) implementation: reads var/fake/products.json.
 *
 * Production Phase 2 will swap this binding to a Ray.MediaQuery
 * `#[DbQuery('get_product')]` interface backed by var/db/sql/get_product.sql.
 */
final class FakeProductQuery implements ProductQueryInterface
{
    /** @var array<string, ProductEntity>|null */
    private array|null $cache = null;

    #[Override]
    public function item(string $productCode): ProductEntity|null
    {
        return $this->load()[$productCode] ?? null;
    }

    /** @return array<string, ProductEntity> */
    private function load(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $path = dirname(__DIR__, 3) . '/var/fake/products.json';
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException(sprintf('Fake fixture missing: %s', $path));
        }

        /** @var list<array{productCode: string, productName: string, price02: int, stock: int|null}> $rows */
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows)) {
            throw new RuntimeException(sprintf('Fake fixture must be a JSON array: %s', $path));
        }

        $entities = [];
        foreach ($rows as $row) {
            $entities[$row['productCode']] = new ProductEntity(
                productCode: $row['productCode'],
                productName: $row['productName'],
                price02: $row['price02'],
                stock: $row['stock'],
            );
        }

        return $this->cache = $entities;
    }
}
