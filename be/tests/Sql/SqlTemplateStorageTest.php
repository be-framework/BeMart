<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\TemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlTemplateStorage;

/**
 * Storage-layer coverage for {@see SqlTemplateStorage} (Phase 2b).
 *
 * Per G-23 the client-observable contract lives in
 * {@see \MyVendor\BeMart\Tests\Resource\Sql\AdminTemplateResourceSqlTest};
 * the cases below verify the single SQL path (`list()`) in isolation —
 * id ordering / empty / device-type projection / NULL device_type_id
 * coercion / hydrate shape. TemplateStorageInterface is `list()` only
 * (ALPS exposes a single `goTemplateList` affordance — no create /
 * update / delete), so there is no getById / put / remove to cover and
 * no miss / boundary write path to exercise.
 */
final class SqlTemplateStorageTest extends AbstractSqlTestCase
{
    public function testListReturnsRowsInIdOrder(): void
    {
        $this->seedDeviceTypes();
        $first = $this->insertTemplate(['template_name' => 'デフォルト (PC)', 'device_type_id' => 10]);
        $second = $this->insertTemplate(['template_name' => 'デフォルト (スマホ)', 'device_type_id' => 2]);

        $storage = new SqlTemplateStorage($this->pdo);
        $rows = $storage->list();

        $this->assertCount(2, $rows);
        $this->assertContainsOnlyInstancesOf(TemplateEntity::class, $rows);
        $this->assertSame((string) $first, $rows[0]->templateId);
        $this->assertSame((string) $second, $rows[1]->templateId);
        $this->assertSame('デフォルト (PC)', $rows[0]->templateName);
        $this->assertSame('デフォルト (スマホ)', $rows[1]->templateName);
    }

    public function testListReturnsEmptyArrayOnEmptyTable(): void
    {
        $storage = new SqlTemplateStorage($this->pdo);
        $this->assertSame([], $storage->list());
    }

    public function testListProjectsDeviceTypeEnum(): void
    {
        // deviceType mirrors EC-CUBE's mtb_device_type enum: 10=PC,
        // 2=Mobile. The fixture seeds the master rows and writes a
        // non-NULL device_type_id; the projection round-trips it.
        $this->seedDeviceTypes();
        $this->insertTemplate(['device_type_id' => 10]);
        $this->insertTemplate(['device_type_id' => 2]);

        $storage = new SqlTemplateStorage($this->pdo);
        $rows = $storage->list();

        $this->assertSame(10, $rows[0]->deviceType);
        $this->assertSame(2, $rows[1]->deviceType);
    }

    public function testListCoercesNullDeviceTypeToZero(): void
    {
        // device_type_id is nullable; TemplateEntity::deviceType is a
        // non-null int — a row with NULL projects deviceType = 0. No
        // mtb_device_type seed is required for a NULL FK value.
        $this->insertTemplate(['device_type_id' => null]);

        $storage = new SqlTemplateStorage($this->pdo);
        $rows = $storage->list();

        $this->assertCount(1, $rows);
        $this->assertSame(0, $rows[0]->deviceType);
    }

    public function testListHydratesTemplateIdAsStringHandle(): void
    {
        // dtb_template.id is int unsigned; TemplateEntity::templateId is
        // the opaque string handle — same convention as layoutId.
        $this->seedDeviceTypes();
        $id = $this->insertTemplate(['template_name' => 'デフォルト (PC)']);

        $storage = new SqlTemplateStorage($this->pdo);
        $rows = $storage->list();

        $this->assertCount(1, $rows);
        $this->assertSame((string) $id, $rows[0]->templateId);
        $this->assertIsString($rows[0]->templateId);
        $this->assertSame('デフォルト (PC)', $rows[0]->templateName);
    }

    public function testListReturnsAllSeededRows(): void
    {
        // dtb_template is a flat registry — list() returns every
        // installed flavour with no filtering.
        $this->seedDeviceTypes();
        $this->insertTemplate(['device_type_id' => 10]);
        $this->insertTemplate(['device_type_id' => 10]);
        $this->insertTemplate(['device_type_id' => 2]);

        $storage = new SqlTemplateStorage($this->pdo);
        $this->assertCount(3, $storage->list());
    }
}
