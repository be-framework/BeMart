<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\BaseInfoEntity;
use Override;
use PDO;

/**
 * Real PDO-backed BaseInfo storage — Phase 2b.
 *
 * Mirrors {@see FakeBaseInfoStorage} against the live EC-CUBE 4.3
 * schema (`dtb_base_info`). The table is a single-row config singleton
 * — EC-CUBE's installer writes `id = 1` with the wizard defaults and
 * every read is `WHERE id = 1`. There is no "list", no owner key, no
 * generator — the row identity is fixed.
 *
 * Scope (Wave 8 / Wave 9 — same as BaseInfoEntity):
 *   12 columns out of ~35: shop_name / shop_kana / shop_name_eng /
 *   company_name / postal_code / pref_id / addr01 / addr02 /
 *   phone_number / business_hour / email01 (→ shopEmail01) /
 *   message (→ shopMessage). The remaining columns (point /
 *   tax / option_* / delivery_free_* / invoice_registration_number /
 *   ga_id / company_kana / email02..04 / good_traded) are Phase 2
 *   scope and left untouched by this storage — UPDATE only touches
 *   the 12 columns the Entity carries, INSERT supplies NULL for the
 *   nullable ones and the schema's `DEFAULT` values cover the
 *   NOT NULL flags (option_* tinyints, basic_point_rate /
 *   point_conversion_rate decimals, discriminator_type).
 *
 * Schema-vs-Entity column name divergence:
 *   - Entity::shopEmail01  ↔ dtb_base_info.email01
 *   - Entity::shopMessage  ↔ dtb_base_info.message
 *   - Entity::pref         ↔ dtb_base_info.pref_id
 *   (the Entity uses the user-facing names from the admin form;
 *    the schema uses Doctrine's column-naming convention.)
 *
 * Coercions:
 *   - shop_name is column-nullable but Entity::shopName is required
 *     `string` → on read, NULL or missing-row → fall back to the
 *     installer-default constant. On write, we always supply a value
 *     because BaseInfoUpdated validates non-empty before reaching the
 *     storage.
 *   - pref_id is `smallint unsigned`, Entity::pref is `int|null` →
 *     cast `(int)` on read when present.
 *   - update_date is NOT NULL → we always set it to NOW() (NOW() is
 *     used in lieu of CURRENT_TIMESTAMP because dtb_base_info has no
 *     auto-on-update column attribute in the dump).
 *
 * The "never null" get contract:
 *   BaseInfoStorageInterface::get() must return a BaseInfoEntity even
 *   when dtb_base_info.id=1 is missing (the structure-only test dump
 *   doesn't seed it). This impl mirrors {@see FakeBaseInfoStorage}'s
 *   constructor defaults so that both backends produce the IDENTICAL
 *   `installer defaults` projection on a first read — that's what
 *   keeps the Fake-backed and SQL-backed hypermedia tests asserting
 *   the same shape without per-suite fixture divergence (G-23).
 *
 * Idempotency surface:
 *   BaseInfoUpdated compares old vs new and only calls update() when
 *   they differ, so this storage does not itself short-circuit identical
 *   writes — it does the INSERT-or-UPDATE unconditionally. The Final
 *   reports `changed=false` upstream when the comparison matches.
 *
 * DI is intentionally NOT wired in Phase 2b; FakeBaseInfoStorage stays
 * the production-bound implementation. The SQL impl is exercised via
 * the test-only override in AbstractResourceSqlTestCase.
 */
final class SqlBaseInfoStorage implements BaseInfoStorageInterface
{
    /**
     * Singleton row id — EC-CUBE's installer always writes id=1 and
     * every admin screen reads it back the same way.
     */
    private const ROW_ID = 1;

    private const DISCRIMINATOR = 'baseinfo';

    /**
     * Installer-default shop name — used when dtb_base_info.id=1 is
     * missing or shop_name is NULL. Matches the constant
     * {@see FakeBaseInfoStorage} writes in its constructor, so both
     * backends produce the same default-read projection.
     */
    private const DEFAULT_SHOP_NAME = 'EC-CUBE SHOP';

    private const SELECT_COLUMNS = 'shop_name, shop_kana, shop_name_eng, '
        . 'company_name, postal_code, pref_id, addr01, addr02, '
        . 'phone_number, business_hour, email01, message';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    #[Override]
    public function get(): BaseInfoEntity
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_base_info '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => self::ROW_ID]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            // Installer-default projection. Mirrors
            // FakeBaseInfoStorage::__construct so the Fake-backed and
            // SQL-backed hypermedia tests see the same shape on a
            // first read with no seeded row (G-23).
            return $this->installerDefaults();
        }

        return $this->hydrate($row);
    }

    #[Override]
    public function update(BaseInfoEntity $entity): void
    {
        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_base_info WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => self::ROW_ID]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            $sql = 'UPDATE dtb_base_info SET '
                . 'shop_name = :shop_name, '
                . 'shop_kana = :shop_kana, '
                . 'shop_name_eng = :shop_name_eng, '
                . 'company_name = :company_name, '
                . 'postal_code = :postal_code, '
                . 'pref_id = :pref_id, '
                . 'addr01 = :addr01, '
                . 'addr02 = :addr02, '
                . 'phone_number = :phone_number, '
                . 'business_hour = :business_hour, '
                . 'email01 = :email01, '
                . 'message = :message, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => self::ROW_ID,
                ':shop_name' => $entity->shopName,
                ':shop_kana' => $entity->shopKana,
                ':shop_name_eng' => $entity->shopNameEng,
                ':company_name' => $entity->companyName,
                ':postal_code' => $entity->postalCode,
                ':pref_id' => $entity->pref,
                ':addr01' => $entity->addr01,
                ':addr02' => $entity->addr02,
                ':phone_number' => $entity->phoneNumber,
                ':business_hour' => $entity->businessHour,
                ':email01' => $entity->shopEmail01,
                ':message' => $entity->shopMessage,
            ]);

            return;
        }

        // First write — INSERT with explicit id=1. All Phase-2-scope
        // columns (option_*, point rates, tax flags, ga_id, etc.) rely
        // on the schema's column DEFAULTs; only the 12 fields the
        // Entity carries are supplied explicitly. discriminator_type
        // is 'baseinfo' (the Doctrine single-table inheritance value
        // EC-CUBE writes).
        $sql = 'INSERT INTO dtb_base_info '
            . '(id, shop_name, shop_kana, shop_name_eng, company_name, '
            . 'postal_code, pref_id, addr01, addr02, phone_number, '
            . 'business_hour, email01, message, update_date, discriminator_type) '
            . 'VALUES (:id, :shop_name, :shop_kana, :shop_name_eng, '
            . ':company_name, :postal_code, :pref_id, :addr01, :addr02, '
            . ':phone_number, :business_hour, :email01, :message, NOW(), '
            . ':discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => self::ROW_ID,
            ':shop_name' => $entity->shopName,
            ':shop_kana' => $entity->shopKana,
            ':shop_name_eng' => $entity->shopNameEng,
            ':company_name' => $entity->companyName,
            ':postal_code' => $entity->postalCode,
            ':pref_id' => $entity->pref,
            ':addr01' => $entity->addr01,
            ':addr02' => $entity->addr02,
            ':phone_number' => $entity->phoneNumber,
            ':business_hour' => $entity->businessHour,
            ':email01' => $entity->shopEmail01,
            ':message' => $entity->shopMessage,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    /**
     * Installer-default Entity returned when dtb_base_info.id=1 is
     * missing. Matches {@see FakeBaseInfoStorage::__construct}.
     */
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

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): BaseInfoEntity
    {
        return new BaseInfoEntity(
            shopName: $row['shop_name'] === null
                ? self::DEFAULT_SHOP_NAME
                : (string) $row['shop_name'],
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
