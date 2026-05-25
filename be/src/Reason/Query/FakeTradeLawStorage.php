<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TradeLawEntity;
use Override;

/**
 * In-memory TradeLaw store, seeded with EC-CUBE's installer default
 * for the Specified Commercial Transactions ("特定商取引法") page.
 *
 * Singleton-bound so a Becoming-chain's read sees its own write.
 */
final class FakeTradeLawStorage implements TradeLawStorageInterface
{
    private TradeLawEntity $row;

    public function __construct()
    {
        $this->row = new TradeLawEntity(
            body: "販売業者: 株式会社EC-CUBE\n所在地: 大阪市北区梅田1-1-1\n連絡先: 06-1234-5678",
        );
    }

    #[Override]
    public function get(): TradeLawEntity
    {
        return $this->row;
    }

    #[Override]
    public function update(TradeLawEntity $entity): void
    {
        $this->row = $entity;
    }
}
