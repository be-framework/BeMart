<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use RuntimeException;

use function array_slice;
use function array_values;
use function file_put_contents;
use function getenv;
use function dirname;
use function file_get_contents;
use function is_array;
use function json_decode;
use function json_encode;
use function preg_replace_callback;
use function sprintf;
use function str_contains;
use function str_repeat;
use function strlen;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

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
    /**
     * Opt-in fixture persistence for browser demos.
     *
     * Unit tests use the default in-memory behavior so they do not dirty
     * `be/var/fake/products.json`. The malt/nginx browser stack runs
     * `APP_CONTEXT=html`, so admin UI create/update/delete survives the
     * redirect/new request cycle.
     */
    private const PERSIST_ENV = 'BEMART_FAKE_PRODUCT_PERSIST';

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
        $this->persistIfEnabled();
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
        $this->persistIfEnabled();
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

        /** @var list<array{productCode: string, productName: string, price02: int, stock: int|null, productStatus?: int, description?: string|null, searchWord?: string|null, note?: string|null, imagePath?: string|null, categoryNames?: list<string>, tagNames?: list<string>, classNames?: list<string>}> $rows */
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
                imagePath: $row['imagePath'] ?? null,
                categoryNames: $row['categoryNames'] ?? [],
                tagNames: $row['tagNames'] ?? [],
                classNames: $row['classNames'] ?? [],
            );
        }

        return $this->byCode = $byCode;
    }

    private function persistIfEnabled(): void
    {
        $context = getenv('APP_CONTEXT');
        $force = getenv(self::PERSIST_ENV);
        if ($context !== 'html' && $force !== '1') {
            return;
        }

        $rows = [];
        foreach (array_values($this->load()) as $product) {
            $rows[] = [
                'productCode' => $product->productCode,
                'productName' => $product->productName,
                'price02' => $product->price02,
                'stock' => $product->stock,
                'productStatus' => $product->productStatus,
                'description' => $product->description,
                'searchWord' => $product->searchWord,
                'note' => $product->note,
                'imagePath' => $product->imagePath,
                'categoryNames' => $product->categoryNames,
                'tagNames' => $product->tagNames,
                'classNames' => $product->classNames,
            ];
        }

        $path = dirname(__DIR__, 3) . '/var/fake/products.json';
        file_put_contents(
            $path,
            $this->encodeJson($rows),
        );

        $this->persistProductClassesForCart($rows);
    }

    /**
     * Keep the fake ProductClass lookup in sync for browser-created
     * products, otherwise `/cart/item` cannot find the newly-created
     * product even though product list/detail can.
     *
     * @param list<array{productCode: string, productName: string, price02: int, stock: int|null, productStatus: int, description: string|null, searchWord: string|null, note: string|null, imagePath?: string|null, categoryNames?: list<string>, tagNames?: list<string>, classNames?: list<string>}> $products
     */
    private function persistProductClassesForCart(array $products): void
    {
        $path = dirname(__DIR__, 3) . '/var/fake/product_classes.json';
        $json = file_get_contents($path);
        if ($json === false) {
            return;
        }

        /** @var mixed $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            return;
        }

        /** @var array<string, mixed> $classes */
        $classes = $decoded;
        foreach ($products as $product) {
            $code = $product['productCode'];
            /** @var mixed $existing */
            $existing = $classes[$code] ?? null;
            $existingRow = is_array($existing) ? $existing : [];
            $classes[$code] = [
                'productCode' => $code,
                'productName' => $product['productName'],
                'stock' => $product['stock'],
                'stockUnlimited' => $product['stock'] === null,
                'saleLimit' => $existingRow['saleLimit'] ?? null,
                'price01' => $existingRow['price01'] ?? $product['price02'],
                'price02' => $product['price02'],
                'deliveryFee' => $existingRow['deliveryFee'] ?? 0,
                'saleTypeName' => $existingRow['saleTypeName'] ?? '通常販売',
                'saleTypeId' => $existingRow['saleTypeId'] ?? 1,
            ];
        }

        file_put_contents(
            $path,
            $this->encodeJson($classes),
        );
    }

    /**
     * Repository JSON style is 2-space indentation; PHP's JSON_PRETTY_PRINT
     * emits 4 spaces, so shrink leading indentation after encoding.
     *
     * @param array<array-key, mixed> $value
     */
    private function encodeJson(array $value): string
    {
        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return (string) preg_replace_callback(
            '/^( +)/m',
            static fn (array $matches): string => str_repeat(' ', (int) (strlen($matches[1]) / 2)),
            $json,
        ) . "\n";
    }
}
