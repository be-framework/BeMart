<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Query;

use MyVendor\BeMart\Be\Reason\Entity\FavoriteEntity;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\Result\FavoritePresence;
use Override;

use function array_values;
use function is_array;
use function is_string;
use function session_status;

use const PHP_SESSION_ACTIVE;

/**
 * Session-backed Fake favorite storage for browser HTML contexts.
 *
 * Ray.FakeQuery fixtures are static, so FavoriteStorageInterface::add() does
 * not mutate the JSONL read fixtures used by the next request. Browser demos
 * need the POST /mypage/favorite → redirect → GET /mypage/favorite-list flow
 * to show the just-added product, just like the session-backed fake cart.
 */
final class SessionFavoriteStorage implements FavoriteStorageInterface
{
    private const SESSION_KEY = 'bemart_fake_favorites';

    /** @var array<string, array<string, array<string, mixed>>> */
    private static array $fallback = [];

    public function __construct(
        private readonly ProductQueryInterface $productQuery,
    ) {
    }

    #[Override]
    public function add(FavoriteEntity $favorite): void
    {
        $rows = $this->rows();
        $customerRows = $rows[$favorite->customerId] ?? [];
        $customerRows[$favorite->productCode] = $this->rowFromFavorite($favorite);
        $rows[$favorite->customerId] = $customerRows;
        $this->writeRows($rows);
    }

    #[Override]
    public function exists(string $customerId, string $productCode): FavoritePresence
    {
        $rows = $this->rows();

        return new FavoritePresence(isset($rows[$customerId][$productCode]));
    }

    /** @return list<FavoriteEntity> */
    #[Override]
    public function listByCustomer(string $customerId): array
    {
        $rows = $this->rows();
        $customerRows = $rows[$customerId] ?? [];

        return array_values(array_map(fn (array $row): FavoriteEntity => $this->favoriteFromRow($row), $customerRows));
    }

    #[Override]
    public function delete(string $customerId, string $productCode): void
    {
        $rows = $this->rows();
        unset($rows[$customerId][$productCode]);
        if (($rows[$customerId] ?? []) === []) {
            unset($rows[$customerId]);
        }

        $this->writeRows($rows);
    }

    /** @return array<string, array<string, array<string, mixed>>> */
    private function rows(): array
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            /** @var mixed $rows */
            $rows = $_SESSION[self::SESSION_KEY] ?? [];

            return is_array($rows) ? $rows : [];
        }

        return self::$fallback;
    }

    /** @param array<string, array<string, array<string, mixed>>> $rows */
    private function writeRows(array $rows): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[self::SESSION_KEY] = $rows;

            return;
        }

        self::$fallback = $rows;
    }

    /** @return array<string, mixed> */
    private function rowFromFavorite(FavoriteEntity $favorite): array
    {
        $product = $this->productQuery->item($favorite->productCode);

        return [
            'customerId' => $favorite->customerId,
            'productCode' => $favorite->productCode,
            'productName' => $favorite->productName,
            'unitPrice' => $favorite->unitPrice,
            'fileName' => $favorite->fileName ?? ($product instanceof ProductEntity ? $product->imagePath : null),
        ];
    }

    /** @param array<string, mixed> $row */
    private function favoriteFromRow(array $row): FavoriteEntity
    {
        $productCode = (string) ($row['productCode'] ?? '');
        $product = $productCode !== '' ? $this->productQuery->item($productCode) : null;

        return new FavoriteEntity(
            customerId: $this->stringValue($row['customerId'] ?? ''),
            productCode: $productCode,
            productName: $this->stringValue($row['productName'] ?? '', $product instanceof ProductEntity ? $product->productName : ''),
            unitPrice: (int) ($row['unitPrice'] ?? ($product instanceof ProductEntity ? $product->price02 : 0)),
            fileName: $this->nullableString($row['fileName'] ?? ($product instanceof ProductEntity ? $product->imagePath : null)),
        );
    }

    private function stringValue(mixed $value, string $default = ''): string
    {
        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function nullableString(mixed $value): string|null
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
