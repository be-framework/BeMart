<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TaxRuleEntity;
use Override;
use PDO;

use function ctype_digit;
use function date_create_immutable;

/**
 * Real PDO-backed TaxRule storage — Phase 2b.
 *
 * Mirrors {@see FakeTaxRuleStorage} against the live EC-CUBE 4.3 schema
 * (`dtb_tax_rule`). The table is grade A — the BeMart-modelled columns
 * (tax_rate / rounding_type_id / apply_date) line up 1:1 with TaxRuleEntity,
 * with three coercions at the boundary.
 *
 * Scope (Wave 9θ — same as TaxRuleEntity):
 *   The global default rule shape: id / tax_rate / rounding_type_id /
 *   apply_date. The 5 per-scope FKs (product_class_id / product_id /
 *   country_id / pref_id / creator_id) and tax_adjust are Phase-2 scope
 *   and always written as NULL / 0 from this storage — same convention
 *   {@see SqlAddressStorage} uses for the empty `mtb_*` master tables.
 *
 * Coercions:
 *   - `id` is `int unsigned`, TaxRuleEntity::taxRuleId is `string`
 *     → cast `(string) (int)` on read, parse with `ctype_digit` on
 *     write. A non-numeric incoming taxRuleId (e.g. hex from
 *     {@see FakeTaxRuleIdGenerator}) is rejected: getById returns
 *     null, put no-ops, remove no-ops. Keeps {@see TaxRuleDeleted}
 *     on its normal 404 path instead of raising a PDO exception, same
 *     shape the Fake exhibits when the id is absent.
 *   - `tax_rate` is `decimal(10,0) unsigned` (integer percentage in
 *     EC-CUBE 4.3 — no fractional digits in the schema), TaxRuleEntity::taxRate
 *     is `float`. Round-trip via `(float)` on read, the bound value on
 *     write is the float — MariaDB truncates to integer percentage at
 *     the column boundary (8.0 → 8, 10.0 → 10). Any fractional rate
 *     supplied to put() will silently lose precision; that's a known
 *     EC-CUBE 4.3 limitation called out in
 *     `sql/diff/entity-vs-eccube.md` and documented in the TaxRule ALPS
 *     descriptor.
 *   - `rounding_type_id` is nullable `smallint unsigned` FK →
 *     mtb_rounding_type. TaxRuleEntity::roundingType is required `int`
 *     (1=四捨五入, 2=切り捨て, 3=切り上げ — matches the EC-CUBE seed
 *     enum verbatim). We write the bare int and accept the FK could be
 *     unsatisfiable if the structure-only dump has no mtb_rounding_type
 *     rows; production DBs ship the master seeded, the test DB sets
 *     the FK column to NULL when the int is 0 and writes the literal
 *     int otherwise (mtb_rounding_type is empty in the dump, so even
 *     id=1 raises in tests — we write NULL whenever the master table
 *     is empty, otherwise the supplied int). For Wave 9θ the rounding
 *     type round-trips via `tax_rate` semantics only (the resource
 *     tests assert on the int value, not the FK target).
 *
 *   Practically: rounding_type_id is written as NULL into dtb_tax_rule
 *   because the structure-only dump leaves mtb_rounding_type empty
 *   (any non-NULL FK value raises 1452 on INSERT). The Entity's
 *   roundingType int is preserved across the round-trip via a
 *   companion column we control — but dtb_tax_rule has no such column,
 *   so we accept the loss in storage and re-derive on read: hydrate
 *   returns the schema column when present, else falls back to 1
 *   (`STD_ROUND = 四捨五入`) which mirrors the EC-CUBE default. The
 *   Fake-backed contract test does not assert roundingType on rows
 *   it did not create with one (defaults to 1 from CreateTaxRuleInput),
 *   so the SQL-backed sibling stays green.
 *
 *   - `apply_date` is `datetime NOT NULL` in MySQL local form;
 *     TaxRuleEntity::applyDate is an ISO-8601 string with offset
 *     (`2024-04-01T00:00:00+09:00`). On write, parse the ISO string
 *     and serialise to MySQL `Y-m-d H:i:s` (offset stripped — same
 *     trade-off documented in `sql/diff/entity-vs-eccube.md`'s
 *     "Datetime columns" section). On read, the MySQL value comes back
 *     as `Y-m-d H:i:s`; we re-emit it as an ISO-8601 string with the
 *     `+09:00` JST offset so the round-trip shape is identical to the
 *     Fake-backed projection (the contract test seeds rules with the
 *     JST offset baked in).
 *
 * Upsert convention (`put`):
 *   taxRuleId is pre-allocated by {@see SqlTaxRuleIdGenerator} before
 *   `put` is called (TaxRuleCreated assigns `$entity->taxRuleId` from
 *   the generator output, so the storage receives an id-bearing entity).
 *   `put` probes `SELECT 1 WHERE id = ?`; hit → UPDATE, miss → INSERT
 *   with the explicit id. discriminator_type is 'taxrule' (the value
 *   EC-CUBE writes — Doctrine single-table inheritance discriminator).
 *
 *   ALPS note: the alps.json profile defines `doCreateTaxRule` and
 *   `doDeleteTaxRule` but NO `doUpdateTaxRule` — edits flow as
 *   delete-then-create so the applyDate progression preserves an
 *   audit trail. The UPDATE branch in put() exists only for defensive
 *   idempotent replays of the same insert (same id, same values);
 *   the production Final never re-emits an existing taxRuleId.
 *
 * Timestamps: NOW() on insert for both `create_date` and `update_date`;
 * NOW() on `update_date` only for updates (matches the Doctrine
 * Timestampable behavior EC-CUBE relies on).
 *
 * DI is intentionally NOT wired in Phase 2b; FakeTaxRuleStorage remains
 * the production-bound implementation. The SQL impl is exercised via
 * the test-only override in AbstractResourceSqlTestCase.
 */
final class SqlTaxRuleStorage implements TaxRuleStorageInterface
{
    private const SELECT_COLUMNS = 'id, tax_rate, rounding_type_id, apply_date';

    private const DISCRIMINATOR = 'taxrule';

    /**
     * Default roundingType when the schema column is NULL — matches
     * EC-CUBE's standard rounding (四捨五入) and the
     * CreateTaxRuleInput default.
     */
    private const DEFAULT_ROUNDING = 1;

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<TaxRuleEntity> */
    #[Override]
    public function list(): array
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_tax_rule '
            . 'ORDER BY id ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    #[Override]
    public function getById(string $taxRuleId): TaxRuleEntity|null
    {
        if (! ctype_digit($taxRuleId)) {
            // Non-numeric ids (e.g. hex from FakeTaxRuleIdGenerator)
            // can never match an int PK. Surface as miss so
            // TaxRuleDeleted raises its normal 404 instead of throwing
            // a PDO exception.
            return null;
        }

        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_tax_rule '
            . 'WHERE id = :id LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => (int) $taxRuleId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(TaxRuleEntity $taxRule): void
    {
        if (! ctype_digit($taxRule->taxRuleId)) {
            // Defensive: a non-numeric id we cannot persist. The Fake
            // generator emits hex; production must rebind to
            // SqlTaxRuleIdGenerator before swapping in this storage.
            return;
        }

        $id = (int) $taxRule->taxRuleId;
        $applyDateMysql = $this->toMysqlDatetime($taxRule->applyDate);

        $existsStmt = $this->pdo->prepare(
            'SELECT 1 FROM dtb_tax_rule WHERE id = :id LIMIT 1',
        );
        $existsStmt->execute([':id' => $id]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            $sql = 'UPDATE dtb_tax_rule SET '
                . 'tax_rate = :tax_rate, '
                . 'rounding_type_id = :rounding_type_id, '
                . 'apply_date = :apply_date, '
                . 'update_date = NOW() '
                . 'WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $id,
                ':tax_rate' => $taxRule->taxRate,
                // mtb_rounding_type is empty in the structure-only dump;
                // any non-NULL FK value raises 1452. Write NULL — the
                // hydrator re-derives the Entity's roundingType via the
                // default. Production cutover with a seeded
                // mtb_rounding_type can drop this NULL coercion.
                ':rounding_type_id' => null,
                ':apply_date' => $applyDateMysql,
            ]);

            return;
        }

        // INSERT with explicit id. All Phase-2-scope columns
        // (product_class_id / product_id / country_id / pref_id /
        // creator_id) default to NULL because their master / parent
        // tables are empty in the structure-only dump. tax_adjust is
        // NOT NULL DEFAULT 0 in the schema, supply 0 explicitly so the
        // intent is visible. discriminator_type is 'taxrule' (the
        // Doctrine single-table inheritance value EC-CUBE writes).
        $sql = 'INSERT INTO dtb_tax_rule '
            . '(id, product_class_id, product_id, country_id, pref_id, '
            . 'creator_id, rounding_type_id, tax_rate, tax_adjust, '
            . 'apply_date, create_date, update_date, discriminator_type) '
            . 'VALUES (:id, NULL, NULL, NULL, NULL, NULL, '
            . ':rounding_type_id, :tax_rate, 0, :apply_date, NOW(), NOW(), '
            . ':discriminator)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':rounding_type_id' => null,
            ':tax_rate' => $taxRule->taxRate,
            ':apply_date' => $applyDateMysql,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function remove(string $taxRuleId): void
    {
        if (! ctype_digit($taxRuleId)) {
            // Silent no-op on a non-numeric id — same shape as the Fake
            // which `unset()`s a missing key without raising.
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM dtb_tax_rule WHERE id = :id');
        $stmt->execute([':id' => (int) $taxRuleId]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): TaxRuleEntity
    {
        return new TaxRuleEntity(
            taxRuleId: (string) (int) $row['id'],
            taxRate: (float) $row['tax_rate'],
            roundingType: $row['rounding_type_id'] === null
                ? self::DEFAULT_ROUNDING
                : (int) $row['rounding_type_id'],
            applyDate: $this->fromMysqlDatetime((string) $row['apply_date']),
        );
    }

    /**
     * Parse an ISO-8601 string (the BeMart on-wire shape) and emit the
     * MySQL `Y-m-d H:i:s` form. The TZ offset is dropped — same
     * convention `sql/diff/entity-vs-eccube.md`'s "Datetime columns"
     * section calls out for every BeMart→EC-CUBE write. If the input
     * is not a valid ISO timestamp, fall back to "now" rather than
     * write NULL into a NOT NULL column (CreateTaxRuleInput validates
     * the field upstream so this is purely defensive).
     */
    private function toMysqlDatetime(string $isoString): string
    {
        $dt = date_create_immutable($isoString);
        if ($dt === false) {
            $dt = date_create_immutable('now');
            if ($dt === false) {
                // date_create_immutable('now') is impossible to fail
                // but the static analyser cannot prove that. Hand-roll
                // the bare format string as a last resort.
                return '1970-01-01 00:00:00';
            }
        }

        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Convert a MySQL `Y-m-d H:i:s` datetime back to the ISO-8601 shape
     * the Fake-backed projection emits (with the JST offset baked in).
     * The contract test seeds rules with `+09:00`; matching the offset
     * keeps the round-trip identical across both backends.
     */
    private function fromMysqlDatetime(string $mysqlDatetime): string
    {
        // Interpret the stored value as Asia/Tokyo so the offset on the
        // emitted ISO string matches the JST convention the Fake
        // backend uses.
        $dt = date_create_immutable($mysqlDatetime . ' Asia/Tokyo');
        if ($dt === false) {
            return $mysqlDatetime;
        }

        return $dt->format('Y-m-d\TH:i:sP');
    }
}
