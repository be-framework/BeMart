<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TemplateEntity;
use Override;
use PDO;

/**
 * Real PDO-backed Template storage — Phase 2b.
 *
 * Mirrors {@see FakeTemplateStorage} against the live EC-CUBE 4.3
 * schema (`dtb_template`). dtb_template is grade A — its column shape
 * is the same as dtb_layout (id / device_type_id / *_code / *_name /
 * create_date / update_date / discriminator_type), so this impl is the
 * read-only sibling of {@see SqlLayoutStorage}.
 *
 * Interface shape (Wave 9 — TemplateStorageInterface):
 *   `list()` only. ALPS exposes a single affordance for templates
 *   (`goTemplateList`) — no create / update / delete, no upload flow.
 *   So there is no SqlTemplateIdGenerator, no getById, no put / remove,
 *   and the FK-cascade question (which referent tables would need
 *   pre-clearing on delete) does not arise — a template is never
 *   mutated through this slice. Templates are filesystem-backed in
 *   EC-CUBE; dtb_template is only the registry of installed flavours.
 *
 * Scope (Wave 9 — same as TemplateEntity):
 *   The 3-field projection templateId / templateName / deviceType.
 *   dtb_template has four more columns (`template_code`, `create_date`,
 *   `update_date`, `discriminator_type`); none are part of
 *   TemplateStorageInterface. template_code is the install-time unique
 *   code ('default' for the stock template) — it is NOT projected:
 *   TemplateEntity::templateId is the opaque numeric `id` handle, the
 *   same convention as layoutId / blockId / categoryId (a stringified
 *   int unsigned AUTO_INCREMENT primary key). create_date / update_date
 *   / discriminator_type are install-time bookkeeping outside the
 *   client-visible projection.
 *
 * Coercions:
 *   - `id` is `int unsigned`, TemplateEntity::templateId is `string`
 *     → cast `(string) (int)` on read. (No write path exists, so the
 *     `ctype_digit` write-side guard SqlLayoutStorage carries is not
 *     needed here — list() never parses an incoming id.)
 *   - `template_name` is `varchar(255)` NOT NULL in EC-CUBE and
 *     non-null on TemplateEntity — no coalesce needed, but the hydrator
 *     still `(string)`-casts defensively.
 *   - `device_type_id` is `smallint(5) unsigned` nullable, with an FK
 *     to `mtb_device_type` (FK_94C12A694FFA550E). TemplateEntity::
 *     deviceType is a non-null `int` (10=PC, 2=Mobile). mtb_device_type
 *     is EMPTY in the structure-only dump, so a fixture that writes a
 *     non-NULL device_type_id must seed the master rows first (the
 *     shared seedDeviceTypes helper). A NULL read coalesces to 0 so the
 *     projection always has a non-null int — same shape as
 *     SqlLayoutStorage.
 *
 * List ordering: `ORDER BY id ASC` — the contract test asserts count,
 * not order. Same parity convention as SqlLayoutStorage / SqlBlockStorage.
 *
 * DI is intentionally NOT wired in production (FakeTemplateStorage
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlTemplateStorage implements TemplateStorageInterface
{
    private const SELECT_COLUMNS = 'id, template_name, device_type_id';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<TemplateEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_template '
            . 'ORDER BY id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): TemplateEntity
    {
        return new TemplateEntity(
            // id is int unsigned; TemplateEntity::templateId is the
            // opaque string handle — same convention as layoutId.
            templateId: (string) (int) $row['id'],
            // template_name is NOT NULL in EC-CUBE; cast defensively.
            templateName: (string) $row['template_name'],
            // device_type_id is nullable; TemplateEntity::deviceType is
            // a non-null int (10=PC, 2=Mobile). Coalesce NULL → 0 so the
            // projection always has an int — fixture-seeded rows carry
            // the real EC-CUBE enum value directly.
            deviceType: $row['device_type_id'] === null ? 0 : (int) $row['device_type_id'],
        );
    }
}
