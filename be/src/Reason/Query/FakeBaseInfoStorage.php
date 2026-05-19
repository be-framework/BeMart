<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\BaseInfoEntity;
use Override;

/**
 * In-memory BaseInfo store, seeded with EC-CUBE's installer defaults
 * (the constants the post-install wizard writes when no shop info has
 * been entered yet).
 *
 * Singleton-bound so a Becoming-chain's read sees its own write.
 */
final class FakeBaseInfoStorage implements BaseInfoStorageInterface
{
    private BaseInfoEntity $row;

    public function __construct()
    {
        $this->row = new BaseInfoEntity(
            shopName: 'EC-CUBE SHOP',
            shopKana: 'イーシーキューブショップ',
            shopNameEng: 'EC-CUBE SHOP',
            companyName: '株式会社EC-CUBE',
            postalCode: '5300001',
            pref: 27,
            addr01: '大阪市北区',
            addr02: '梅田1-1-1',
            phoneNumber: '0612345678',
            businessHour: '10:00-19:00',
            shopEmail01: 'shop@example.com',
            shopMessage: 'ようこそ、EC-CUBE SHOP へ。',
        );
    }

    #[Override]
    public function get(): BaseInfoEntity
    {
        return $this->row;
    }

    #[Override]
    public function update(BaseInfoEntity $entity): void
    {
        $this->row = $entity;
    }
}
