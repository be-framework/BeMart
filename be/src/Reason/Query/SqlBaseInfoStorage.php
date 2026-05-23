<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\BaseInfoEntity;
use Override;

final class SqlBaseInfoStorage implements BaseInfoStorageInterface
{
    private const DEFAULT_SHOP_NAME = 'EC-CUBE SHOP';

    public function __construct(private readonly MediaQueryExecutor $db) {}

    #[Override]
    public function get(): BaseInfoEntity
    {
        $row = $this->db->row('tbase_info_get');
        return $row === null ? $this->installerDefaults() : $this->hydrate($row);
    }

    #[Override]
    public function update(BaseInfoEntity $entity): void
    {
        $values = [
            'shopName' => $entity->shopName,
            'shopKana' => $entity->shopKana,
            'shopNameEng' => $entity->shopNameEng,
            'companyName' => $entity->companyName,
            'postalCode' => $entity->postalCode,
            'pref' => $entity->pref,
            'addr01' => $entity->addr01,
            'addr02' => $entity->addr02,
            'phoneNumber' => $entity->phoneNumber,
            'businessHour' => $entity->businessHour,
            'shopEmail01' => $entity->shopEmail01,
            'shopMessage' => $entity->shopMessage,
        ];
        $this->db->exec($this->db->row('tbase_info_exists') === null ? 'tbase_info_insert' : 'tbase_info_update', $values);
    }

    private function installerDefaults(): BaseInfoEntity
    {
        return new BaseInfoEntity(
            shopName: self::DEFAULT_SHOP_NAME,
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

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): BaseInfoEntity
    {
        return new BaseInfoEntity(
            shopName: $row['shop_name'] === null ? self::DEFAULT_SHOP_NAME : (string) $row['shop_name'],
            shopKana: $row['shop_kana'] === null ? null : (string) $row['shop_kana'],
            shopNameEng: $row['shop_name_eng'] === null ? null : (string) $row['shop_name_eng'],
            companyName: $row['company_name'] === null ? null : (string) $row['company_name'],
            postalCode: $row['postal_code'] === null ? null : (string) $row['postal_code'],
            pref: $row['pref_id'] === null ? null : (int) $row['pref_id'],
            addr01: $row['addr01'] === null ? null : (string) $row['addr01'],
            addr02: $row['addr02'] === null ? null : (string) $row['addr02'],
            phoneNumber: $row['phone_number'] === null ? null : (string) $row['phone_number'],
            businessHour: $row['business_hour'] === null ? null : (string) $row['business_hour'],
            shopEmail01: $row['email01'] === null ? null : (string) $row['email01'],
            shopMessage: $row['message'] === null ? null : (string) $row['message'],
        );
    }
}
