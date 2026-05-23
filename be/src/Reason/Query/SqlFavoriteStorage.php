<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FavoriteEntity;
use MyVendor\BeMart\Be\Reason\Query\Result\FavoritePresence;
use Override;

use function ctype_digit;

final class SqlFavoriteStorage implements FavoriteStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    #[Override]
    public function add(FavoriteEntity $favorite): void
    {
        $productId = $this->resolveProductId($favorite->productCode);
        if ($productId === null || ! ctype_digit($favorite->customerId)) {
            return;
        }
        $this->db->favorite_add(customerId: (int) $favorite->customerId, productId: $productId);
    }

    #[Override]
    public function has(string $customerId, string $productCode): FavoritePresence
    {
        if (! ctype_digit($customerId)) {
            return new FavoritePresence(false);
        }

        return new FavoritePresence($this->db->favorite_has(customerId: (int) $customerId, productCode: $productCode) !== null);
    }

    /** @return list<FavoriteEntity> */
    #[Override]
    public function listByCustomer(string $customerId): array
    {
        if (! ctype_digit($customerId)) {
            return [];
        }
        return array_map(
            static fn (array $row): FavoriteEntity => new FavoriteEntity(
                customerId: (string) $row['customer_id'],
                productCode: (string) ($row['product_code'] ?? ''),
                productName: (string) $row['product_name'],
                unitPrice: (int) $row['unit_price'],
                fileName: $row['main_image'] !== null ? (string) $row['main_image'] : null,
            ),
            $this->db->favorite_list(customerId: (int) $customerId),
        );
    }

    #[Override]
    public function remove(string $customerId, string $productCode): void
    {
        $productId = $this->resolveProductId($productCode);
        if ($productId === null || ! ctype_digit($customerId)) {
            return;
        }
        $this->db->favorite_remove(customerId: (int) $customerId, productId: $productId);
    }

    private function resolveProductId(string $productCode): int|null
    {
        $row = $this->db->favorite_resolve_product(productCode: $productCode);
        return $row === null ? null : (int) $row['product_id'];
    }
}
