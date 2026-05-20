<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Exception\PluginNotFoundException;
use MyVendor\BeMart\Be\Exception\PluginNotInstalledException;
use MyVendor\BeMart\Be\Reason\Entity\PluginEntity;
use Override;
use PDO;

/**
 * Real PDO-backed plugin storage — Phase 2b.
 *
 * Mirrors {@see FakePluginStorage} against the live EC-CUBE 4.3 schema
 * (`dtb_plugin`). Pure prepared statements: no Doctrine, no ORM.
 *
 * `dtb_plugin` row model
 * ----------------------
 * Each `dtb_plugin` row is ONE plugin registry entry. The BeMart
 * {@see PluginEntity} projects five fields off the row:
 *
 *   - `pluginCode` ← `code`         (varchar — the NATURAL KEY; every
 *                                    lookup / lifecycle method probes it)
 *   - `pluginName` ← `name`         (varchar)
 *   - `version`    ← `version`      (varchar)
 *   - `installed`  ← `initialized`  (tinyint(1) → bool)
 *   - `enabled`    ← `enabled`      (tinyint(1) → bool)
 *
 * NOTE the column is `code`, NOT `plugin_code` — `dtb_plugin` predates
 * EC-CUBE's later `dtb_*_code` naming convention.
 *
 * The `initialized` ↔ `installed` mapping
 * ---------------------------------------
 * EC-CUBE's `dtb_plugin` has no `installed` column: a row's mere
 * presence is "the plugin is registered", and `initialized` flags
 * whether its install pipeline (migrations + setup) has run. The BeMart
 * {@see PluginEntity} two-axis lifecycle (installed / enabled) maps
 * `installed` onto `initialized`: a row with `initialized=1` is an
 * installed plugin, `initialized=0` is a registered-but-not-installed
 * plugin. {@see uninstall} removes the row entirely (so a subsequent
 * listAll() does not surface the uninstalled stub) rather than flipping
 * `initialized` back — same shape as FakePluginStorage::uninstall.
 *
 * The remaining NOT NULL columns EC-CUBE carries — `source`,
 * `discriminator_type` — are not in the PluginEntity. The INSERT
 * defaults them: `source` = 0 (the EC-CUBE store-download origin —
 * 1 means a hand-uploaded ZIP; the migration-scope install stub does
 * neither so 0 is the neutral default), `discriminator_type` = 'plugin'
 * (the Doctrine single-table inheritance value EC-CUBE writes).
 *
 * INSTALL STUB: the real EC-CUBE install pipeline is non-trivial
 * (download ZIP → unzip into app/Plugin → composer require → run
 * migrations → cache clear → container/routes regen). The migration
 * scope only requires the BEAR layer to call into the storage; the SQL
 * impl STUBS the actual install by simply writing the row with
 * `initialized=1`, or flipping `initialized` on a pre-existing
 * registered-but-not-installed row. enable/disable are equivalent flag
 * flips with no container/routes regen wired in. Same intentional stub
 * the Fake documents.
 *
 * Idempotency surface (matches the {@see PluginStorageInterface} doc):
 *   - install   — re-install of an already-installed plugin is a no-op
 *                 (probe `code`; INSERT only if absent, UPDATE only if
 *                 the row exists but is not yet installed). The existing
 *                 row's name/version are NOT overwritten on re-install.
 *   - uninstall — silently a no-op when the plugin is unknown or already
 *                 uninstalled.
 *   - setEnabled — no-op when the enabled value already matches; raises
 *                 PluginNotFoundException for an unknown code,
 *                 PluginNotInstalledException for a known-but-not-
 *                 installed row.
 *
 * DI is intentionally NOT wired in production (FakePluginStorage
 * remains the bound implementation). The SQL impl is exercised via the
 * test-only override in AbstractResourceSqlTestCase.
 */
final class SqlPluginStorage implements PluginStorageInterface
{
    private const SELECT_COLUMNS = 'code, name, version, initialized, enabled';

    private const DISCRIMINATOR = 'plugin';

    /**
     * EC-CUBE `dtb_plugin.source`: 0 = store-download, 1 = hand-uploaded
     * ZIP. The migration-scope install stub does neither, so 0 is the
     * neutral default.
     */
    private const SOURCE_DEFAULT = 0;

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /** @return list<PluginEntity> sorted by pluginCode ascending */
    #[Override]
    public function listAll(): array
    {
        // ORDER BY code ASC — the Fake sorts by pluginCode with strcmp,
        // and the contract test asserts DisabledPlugin before
        // SamplePlugin. utf8mb4_bin collation on dtb_plugin makes the
        // SQL ORDER BY a byte-wise compare, matching strcmp exactly.
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_plugin '
            . 'ORDER BY code ASC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = $this->hydrate($row);
        }

        return $out;
    }

    #[Override]
    public function findByCode(string $pluginCode): PluginEntity|null
    {
        $sql = 'SELECT ' . self::SELECT_COLUMNS . ' FROM dtb_plugin '
            . 'WHERE code = :code LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':code' => $pluginCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    #[Override]
    public function install(string $pluginCode, string $pluginName, string $version): void
    {
        $existing = $this->findByCode($pluginCode);

        if ($existing !== null && $existing->installed) {
            // Idempotent: re-install of an already-installed plugin is a
            // no-op. The existing row's name/version are NOT overwritten
            // — the EC-CUBE pipeline also short-circuits when the row is
            // already present and initialized.
            return;
        }

        if ($existing !== null) {
            // Row exists but is registered-but-not-installed
            // (initialized=0). Flip it to installed. The real pipeline
            // would run the migrations here; the stub just sets the flag.
            $update = $this->pdo->prepare(
                'UPDATE dtb_plugin SET '
                . 'initialized = 1, '
                . 'update_date = NOW() '
                . 'WHERE code = :code',
            );
            $update->execute([':code' => $pluginCode]);

            return;
        }

        // Brand-new plugin — INSERT with initialized=1 (installed) and
        // enabled=0 (EC-CUBE convention: install does not auto-enable;
        // the admin must explicitly call doEnablePlugin).
        $insert = $this->pdo->prepare(
            'INSERT INTO dtb_plugin '
            . '(name, code, enabled, version, source, initialized, '
            . 'create_date, update_date, discriminator_type) '
            . 'VALUES (:name, :code, 0, :version, :source, 1, '
            . 'NOW(), NOW(), :discriminator)',
        );
        $insert->execute([
            ':name' => $pluginName,
            ':code' => $pluginCode,
            ':version' => $version,
            ':source' => self::SOURCE_DEFAULT,
            ':discriminator' => self::DISCRIMINATOR,
        ]);
    }

    #[Override]
    public function uninstall(string $pluginCode): void
    {
        // Idempotent: a DELETE that matches zero rows is silently a
        // no-op, so an unknown / already-uninstalled plugin needs no
        // pre-probe. Removes the row entirely so a subsequent listAll()
        // does not surface the uninstalled stub — same shape as
        // FakePluginStorage::uninstall.
        $stmt = $this->pdo->prepare(
            'DELETE FROM dtb_plugin WHERE code = :code',
        );
        $stmt->execute([':code' => $pluginCode]);
    }

    #[Override]
    public function setEnabled(string $pluginCode, bool $enabled): void
    {
        $existing = $this->findByCode($pluginCode);

        if ($existing === null) {
            throw new PluginNotFoundException();
        }

        if (! $existing->installed) {
            // setEnabled is only valid for installed plugins — the
            // (installed=false, enabled=true) combination is forbidden.
            throw new PluginNotInstalledException();
        }

        if ($existing->enabled === $enabled) {
            // Idempotent: no-op when the value already matches — no
            // spurious write, no update_date bump.
            return;
        }

        $stmt = $this->pdo->prepare(
            'UPDATE dtb_plugin SET '
            . 'enabled = :enabled, '
            . 'update_date = NOW() '
            . 'WHERE code = :code',
        );
        $stmt->execute([
            ':enabled' => $enabled ? 1 : 0,
            ':code' => $pluginCode,
        ]);
    }

    /**
     * @param array<string, mixed> $row dtb_plugin columns.
     */
    private function hydrate(array $row): PluginEntity
    {
        return new PluginEntity(
            pluginCode: (string) $row['code'],
            pluginName: (string) $row['name'],
            version: (string) $row['version'],
            // `installed` is projected off `initialized` — see class doc.
            installed: (bool) (int) $row['initialized'],
            enabled: (bool) (int) $row['enabled'],
        );
    }
}
