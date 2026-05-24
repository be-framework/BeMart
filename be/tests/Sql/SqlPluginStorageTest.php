<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Exception\PluginNotFoundException;
use MyVendor\BeMart\Be\Exception\PluginNotInstalledException;
use MyVendor\BeMart\Be\Reason\Entity\PluginEntity;
use MyVendor\BeMart\Be\Reason\Query\PluginStorageInterface;

/**
 * Storage-layer coverage for {@see PluginStorageInterface} (Phase 2b).
 *
 * Per G-23 the client-observable contract lives in the Resource-layer
 * sibling ({@see \MyVendor\BeMart\Tests\Resource\Sql\AdminPluginListResourceSqlTest});
 * the cases below pin the per-method SQL paths in isolation —
 * per-method coverage including miss / empty / install idempotency /
 * enable-disable round-trip / the not-found vs not-installed 404↔409
 * split.
 *
 * Surprises this suite locks in:
 *  - The natural key column is `code`, NOT `plugin_code` — dtb_plugin
 *    predates EC-CUBE's later `dtb_*_code` naming convention.
 *  - dtb_plugin has no `installed` column; the BeMart PluginEntity
 *    `installed` axis maps onto `dtb_plugin.initialized` (a row with
 *    initialized=1 is an installed plugin).
 *  - `source` is NOT NULL with NO DEFAULT — every INSERT must supply it
 *    (the storage writes 0, the store-download origin).
 *  - `install` is idempotent on an already-installed row (no-op, no
 *    name/version overwrite) and PROMOTES a registered-but-not-installed
 *    row (initialized 0→1) rather than INSERTing a duplicate.
 *  - `uninstall` removes the row entirely (a subsequent listAll does not
 *    surface the stub) and is a silent no-op on an unknown code.
 */
final class SqlPluginStorageTest extends AbstractSqlTestCase
{
    public function testListAllReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = $this->sql(PluginStorageInterface::class);
        $this->assertSame([], $storage->list());
    }

    public function testListAllReturnsRowsSortedByPluginCode(): void
    {
        // Insert out of code order — listAll must return them sorted
        // ascending (the contract test asserts DisabledPlugin first).
        $this->insertPlugin(['code' => 'Sample/SamplePlugin', 'name' => 'Sample']);
        $this->insertPlugin(['code' => 'Sample/DisabledPlugin', 'name' => 'Disabled']);
        $this->insertPlugin(['code' => 'Acme/FirstPlugin', 'name' => 'Acme']);

        $storage = $this->sql(PluginStorageInterface::class);
        $rows = $storage->list();

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(PluginEntity::class, $rows);
        $this->assertSame('Acme/FirstPlugin', $rows[0]->pluginCode);
        $this->assertSame('Sample/DisabledPlugin', $rows[1]->pluginCode);
        $this->assertSame('Sample/SamplePlugin', $rows[2]->pluginCode);
    }

    public function testListAllHydratesEveryField(): void
    {
        $this->insertPlugin([
            'code' => 'Vendor/Full',
            'name' => 'Full Plugin',
            'version' => '2.3.4',
            'initialized' => 1,
            'enabled' => 1,
        ]);

        $storage = $this->sql(PluginStorageInterface::class);
        $rows = $storage->list();

        $this->assertCount(1, $rows);
        $this->assertInstanceOf(PluginEntity::class, $rows[0]);
        $this->assertSame('Vendor/Full', $rows[0]->pluginCode);
        $this->assertSame('Full Plugin', $rows[0]->pluginName);
        $this->assertSame('2.3.4', $rows[0]->version);
        $this->assertTrue($rows[0]->installed);
        $this->assertTrue($rows[0]->enabled);
    }

    public function testFindByCodeReturnsHydratedEntity(): void
    {
        $this->insertPlugin([
            'code' => 'Vendor/Target',
            'name' => 'Target Plugin',
            'version' => '1.2.0',
            'initialized' => 1,
            'enabled' => 0,
        ]);

        $storage = $this->sql(PluginStorageInterface::class);
        $entity = $storage->item('Vendor/Target');

        $this->assertInstanceOf(PluginEntity::class, $entity);
        $this->assertSame('Vendor/Target', $entity->pluginCode);
        $this->assertSame('Target Plugin', $entity->pluginName);
        $this->assertSame('1.2.0', $entity->version);
        $this->assertTrue($entity->installed);
        $this->assertFalse($entity->enabled);
    }

    public function testFindByCodeReturnsNullForUnknownCode(): void
    {
        $storage = $this->sql(PluginStorageInterface::class);
        $this->assertNull($storage->item('NoSuch/Plugin'));
    }

    public function testFindByCodeMapsInitializedZeroToInstalledFalse(): void
    {
        // A registered-but-not-installed row (initialized=0) projects to
        // installed=false — the (installed=false, enabled=false) state.
        $this->insertPlugin([
            'code' => 'Vendor/Registered',
            'initialized' => 0,
            'enabled' => 0,
        ]);

        $storage = $this->sql(PluginStorageInterface::class);
        $entity = $storage->item('Vendor/Registered');

        $this->assertInstanceOf(PluginEntity::class, $entity);
        $this->assertFalse($entity->installed);
        $this->assertFalse($entity->enabled);
    }

    public function testInstallInsertsNewRowAsInstalledAndDisabled(): void
    {
        $storage = $this->sql(PluginStorageInterface::class);
        $storage->install('NewVendor/Plugin', '新規プラグイン', '1.0.0');

        $entity = $storage->item('NewVendor/Plugin');
        $this->assertInstanceOf(PluginEntity::class, $entity);
        $this->assertSame('NewVendor/Plugin', $entity->pluginCode);
        $this->assertSame('新規プラグイン', $entity->pluginName);
        $this->assertSame('1.0.0', $entity->version);
        // Install lands installed=true (initialized=1) but enabled=false:
        // EC-CUBE convention — install does NOT auto-enable.
        $this->assertTrue($entity->installed);
        $this->assertFalse($entity->enabled);

        // listAll sees it too.
        $this->assertCount(1, $storage->list());
    }

    public function testInstallWritesSourceAndDiscriminatorColumns(): void
    {
        // source is NOT NULL with no DEFAULT — the storage must supply
        // it. Raw column probe confirms the INSERT contract.
        $storage = $this->sql(PluginStorageInterface::class);
        $storage->install('Probe/Plugin', 'Probe', '1.0.0');

        $stmt = $this->pdo->prepare(
            'SELECT source, initialized, enabled, discriminator_type '
            . 'FROM dtb_plugin WHERE code = :code',
        );
        $stmt->execute([':code' => 'Probe/Plugin']);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertSame(0, (int) $row['source']);
        $this->assertSame(1, (int) $row['initialized']);
        $this->assertSame(0, (int) $row['enabled']);
        $this->assertSame('plugin', $row['discriminator_type']);
    }

    public function testInstallIsIdempotentOnAlreadyInstalledPlugin(): void
    {
        // Re-install of an already-installed plugin is a no-op — the
        // existing row's name/version MUST survive untouched.
        $this->insertPlugin([
            'code' => 'Sample/SamplePlugin',
            'name' => 'Sample Plugin',
            'version' => '1.0.0',
            'initialized' => 1,
            'enabled' => 1,
        ]);

        $storage = $this->sql(PluginStorageInterface::class);
        $storage->install('Sample/SamplePlugin', 'Whatever', '9.9.9');

        $entity = $storage->item('Sample/SamplePlugin');
        $this->assertInstanceOf(PluginEntity::class, $entity);
        // Original metadata preserved — re-install does NOT overwrite.
        $this->assertSame('Sample Plugin', $entity->pluginName);
        $this->assertSame('1.0.0', $entity->version);
        // enabled state also untouched.
        $this->assertTrue($entity->enabled);

        // No duplicate row.
        $this->assertCount(1, $storage->list());
    }

    public function testInstallPromotesRegisteredButNotInstalledRow(): void
    {
        // A row that exists with initialized=0 (registered, not yet
        // installed) is PROMOTED to initialized=1, not duplicated.
        $this->insertPlugin([
            'code' => 'Vendor/Registered',
            'name' => 'Registered Plugin',
            'version' => '1.0.0',
            'initialized' => 0,
            'enabled' => 0,
        ]);

        $storage = $this->sql(PluginStorageInterface::class);
        $before = $storage->item('Vendor/Registered');
        $this->assertInstanceOf(PluginEntity::class, $before);
        $this->assertFalse($before->installed);

        $storage->install('Vendor/Registered', 'Ignored', '2.0.0');

        $after = $storage->item('Vendor/Registered');
        $this->assertInstanceOf(PluginEntity::class, $after);
        $this->assertTrue($after->installed);
        // The promote path keeps the existing name/version — it does not
        // overwrite from the install args.
        $this->assertSame('Registered Plugin', $after->pluginName);
        $this->assertSame('1.0.0', $after->version);

        // Still exactly one row (UPDATE, not INSERT).
        $this->assertCount(1, $storage->list());
    }

    public function testUninstallRemovesInstalledRow(): void
    {
        $this->insertPlugin(['code' => 'Vendor/Doomed', 'initialized' => 1]);
        $storage = $this->sql(PluginStorageInterface::class);
        $this->assertNotNull($storage->item('Vendor/Doomed'));

        $storage->uninstall('Vendor/Doomed');

        // Row is gone entirely — a subsequent listAll does not surface
        // the uninstalled stub.
        $this->assertNull($storage->item('Vendor/Doomed'));
        $this->assertSame([], $storage->list());
    }

    public function testUninstallIsSilentNoOpForUnknownCode(): void
    {
        $storage = $this->sql(PluginStorageInterface::class);
        // Idempotent: a DELETE matching zero rows raises nothing.
        $storage->uninstall('NoSuch/Plugin');
        $this->assertSame([], $storage->list());
    }

    public function testUninstallReplayIsIdempotent(): void
    {
        $this->insertPlugin(['code' => 'Vendor/Twice', 'initialized' => 1]);
        $storage = $this->sql(PluginStorageInterface::class);

        $storage->uninstall('Vendor/Twice');
        // Second call — already gone, still a silent no-op.
        $storage->uninstall('Vendor/Twice');

        $this->assertNull($storage->item('Vendor/Twice'));
    }

    public function testSetEnabledEnablesAndDisablesRoundTrip(): void
    {
        // Start disabled, enable, then disable again — the full
        // round-trip both UPDATE branches drive.
        $this->insertPlugin([
            'code' => 'Vendor/Toggle',
            'initialized' => 1,
            'enabled' => 0,
        ]);
        $storage = $this->sql(PluginStorageInterface::class);

        $storage->setEnabled('Vendor/Toggle', true);
        $afterEnable = $storage->item('Vendor/Toggle');
        $this->assertInstanceOf(PluginEntity::class, $afterEnable);
        $this->assertTrue($afterEnable->enabled);

        $storage->setEnabled('Vendor/Toggle', false);
        $afterDisable = $storage->item('Vendor/Toggle');
        $this->assertInstanceOf(PluginEntity::class, $afterDisable);
        $this->assertFalse($afterDisable->enabled);

        // installed flag never changes across enable/disable.
        $this->assertTrue($afterDisable->installed);
    }

    public function testSetEnabledIsIdempotentWhenValueAlreadyMatches(): void
    {
        // Setting enabled=true on an already-enabled plugin is a no-op
        // (no exception, and the value stays true).
        $this->insertPlugin([
            'code' => 'Vendor/AlreadyOn',
            'initialized' => 1,
            'enabled' => 1,
        ]);
        $storage = $this->sql(PluginStorageInterface::class);

        $storage->setEnabled('Vendor/AlreadyOn', true);

        $entity = $storage->item('Vendor/AlreadyOn');
        $this->assertInstanceOf(PluginEntity::class, $entity);
        $this->assertTrue($entity->enabled);
    }

    public function testSetEnabledRaisesNotFoundForUnknownCode(): void
    {
        $storage = $this->sql(PluginStorageInterface::class);
        $this->expectException(PluginNotFoundException::class);
        $storage->setEnabled('NoSuch/Plugin', true);
    }

    public function testSetEnabledRaisesNotInstalledForRegisteredButNotInstalledRow(): void
    {
        // A row exists but is registered-but-not-installed
        // (initialized=0) — setEnabled refuses the forbidden
        // (installed=false, enabled=true) combination with a 409-mapped
        // exception, distinct from the 404 not-found case.
        $this->insertPlugin([
            'code' => 'Partial/Plugin',
            'initialized' => 0,
            'enabled' => 0,
        ]);
        $storage = $this->sql(PluginStorageInterface::class);

        $this->expectException(PluginNotInstalledException::class);
        $storage->setEnabled('Partial/Plugin', true);
    }
}
