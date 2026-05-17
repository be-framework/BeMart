<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductClassEntity;
use Override;
use RuntimeException;

use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Phase 1 (FakeQuery): reads var/fake/product_classes.json keyed by productCode.
 *
 * Phase 2 will swap this to a Ray.MediaQuery interface backed by
 * SELECT … FROM dtb_product_class JOIN dtb_sale_type ….
 */
final class FakeProductClassQuery implements ProductClassQueryInterface
{
    /** @var array<string, ProductClassEntity>|null */
    private array|null $cache = null;

    #[Override]
    public function item(string $productCode): ProductClassEntity|null
    {
        return $this->load()[$productCode] ?? null;
    }

    /** @return array<string, ProductClassEntity> */
    private function load(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $path = dirname(__DIR__, 3) . '/var/fake/product_classes.json';
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException(sprintf('Fake fixture missing: %s', $path));
        }

        /** @var array<string, array{productCode: string, productName: string, stock: int|null, stockUnlimited: bool, saleLimit: int|null, price01: int, price02: int, deliveryFee: int, saleTypeName: string, saleTypeId: int}|string> $rows */
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows)) {
            throw new RuntimeException(sprintf('Fake fixture must be a JSON object: %s', $path));
        }

        $entities = [];
        foreach ($rows as $key => $row) {
            if ($key === '$comment' || ! is_array($row)) {
                continue;
            }

            $entities[$row['productCode']] = new ProductClassEntity(
                productCode: $row['productCode'],
                productName: $row['productName'],
                stock: $row['stock'],
                stockUnlimited: $row['stockUnlimited'],
                saleLimit: $row['saleLimit'],
                price02: $row['price02'],
                deliveryFee: $row['deliveryFee'],
                saleTypeName: $row['saleTypeName'],
                saleTypeId: $row['saleTypeId'],
            );
        }

        return $this->cache = $entities;
    }
}
