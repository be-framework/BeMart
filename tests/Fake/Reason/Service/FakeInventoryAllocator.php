<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\InventoryAllocatorInterface;
use MyVendor\BeMart\Be\Exception\InsufficientStockException;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use Override;
use RuntimeException;

use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

/**
 * Phase 1 fake: reads var/fake/inventory.json keyed by productCode and
 * decrements an in-memory copy of stock. Throws InsufficientStockException
 * the moment any line item exceeds the available count — the entire
 * allocation is rolled back (no partial commits, matching EC-CUBE's
 * transactional StockReducePostProcessor).
 */
final class FakeInventoryAllocator implements InventoryAllocatorInterface
{
    /** @var array<string, int>|null */
    private array|null $stock = null;

    #[Override]
    public function allocate(OrderEntity $preOrder): void
    {
        $stock = $this->load();

        $next = $stock;
        foreach ($preOrder->items as $item) {
            $code = $item->productCode;
            $available = $next[$code] ?? 0;
            if ($available < $item->quantity) {
                throw new InsufficientStockException(sprintf(
                    'Product %s has %d in stock, requested %d.',
                    $code,
                    $available,
                    $item->quantity,
                ));
            }

            $next[$code] = $available - $item->quantity;
        }

        $this->stock = $next;
    }

    public function remaining(string $productCode): int
    {
        return $this->load()[$productCode] ?? 0;
    }

    /** @return array<string, int> */
    private function load(): array
    {
        if ($this->stock !== null) {
            return $this->stock;
        }

        $path = dirname(__DIR__, 4) . '/be/var/fake/inventory.json';
        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException(sprintf('Fake fixture missing: %s', $path));
        }

        /** @var list<array{productCode: string, stock: int}> $rows */
        $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($rows)) {
            throw new RuntimeException(sprintf('Fake fixture must be a JSON array: %s', $path));
        }

        $stock = [];
        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['productCode'], $row['stock'])) {
                continue;
            }

            $stock[(string) $row['productCode']] = (int) $row['stock'];
        }

        return $this->stock = $stock;
    }

}
