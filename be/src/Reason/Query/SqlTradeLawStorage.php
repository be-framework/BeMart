<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TradeLawEntity;
use Override;

final class SqlTradeLawStorage implements TradeLawStorageInterface
{
    private const DEFAULT_BODY = "販売業者: 株式会社EC-CUBE\n所在地: 大阪市北区梅田1-1-1\n連絡先: 06-1234-5678";

    public function __construct(private readonly MediaQueryExecutor $db) {}

    #[Override]
    public function get(): TradeLawEntity
    {
        $row = $this->db->row('ttrade_law_get');
        if ($row === null || $row['description'] === null) {
            return new TradeLawEntity(self::DEFAULT_BODY);
        }
        return new TradeLawEntity((string) $row['description']);
    }

    #[Override]
    public function update(TradeLawEntity $entity): void
    {
        $query = $this->db->row('ttrade_law_exists') === null ? 'ttrade_law_insert' : 'ttrade_law_update';
        $this->db->exec($query, ['description' => $entity->body]);
    }
}
