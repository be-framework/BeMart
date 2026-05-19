<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\FavoriteEntity;
use Override;

/**
 * In-memory favorite store. Starts empty (no JSON fixture) — tests
 * call add() to seed. Singleton-bound so a request's reads see its
 * writes within the same Becoming chain.
 */
final class FakeFavoriteStorage implements FavoriteStorageInterface
{
    /** @var array<string, FavoriteEntity> keyed by "customerId\x00productCode" */
    private array $byKey = [];

    private static function key(string $customerId, string $productCode): string
    {
        return $customerId . "\x00" . $productCode;
    }

    #[Override]
    public function add(FavoriteEntity $favorite): void
    {
        $this->byKey[self::key($favorite->customerId, $favorite->productCode)] = $favorite;
    }

    #[Override]
    public function has(string $customerId, string $productCode): bool
    {
        return isset($this->byKey[self::key($customerId, $productCode)]);
    }

    /** @return list<FavoriteEntity> */
    #[Override]
    public function listByCustomer(string $customerId): array
    {
        $out = [];
        foreach ($this->byKey as $favorite) {
            if ($favorite->customerId === $customerId) {
                $out[] = $favorite;
            }
        }

        return $out;
    }

    #[Override]
    public function remove(string $customerId, string $productCode): void
    {
        unset($this->byKey[self::key($customerId, $productCode)]);
    }
}
