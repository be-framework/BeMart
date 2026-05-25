<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlProductCommand;
use MyVendor\BeMart\Be\Reason\Query\SqlProductQuery;
use RuntimeException;

/**
 * Storage-layer coverage for {@see SqlProductCommand} (Phase 2b).
 *
 * Mirrors the shape of {@see SqlDeliveryStorageTest}. Per G-23 the
 * client-observable contract lives in the Resource-layer hypermedia
 * tests under `tests/Resource/Sql/AdminProduct*ResourceSqlTest`; the
 * cases below verify the per-method SQL paths in isolation —
 * specifically the create / copy two-row write (dtb_product header +
 * default dtb_product_class), the soft-delete status flip, the
 * idempotent-replay no-op, and the bulkUpdateStatus changed-count.
 */
final class SqlProductCommandTest extends AbstractSqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // mtb_product_status is empty in the structure-only dump and
        // dtb_product.product_status_id carries an enforced FK — every
        // create / status flip writes a value, so seed the master once.
        $this->seedProductStatus();
    }

    public function testCreateWritesBothProductAndDefaultClassRows(): void
    {
        $command = new SqlProductCommand($this->pdo);
        $command->create(new ProductEntity(
            productCode: 'P-CREATE-001',
            productName: 'New Product',
            price02: 2500,
            stock: 30,
            productStatus: ProductEntity::STATUS_VISIBLE,
            description: '詳細説明',
            searchWord: 'search',
            note: 'note',
        ));

        // Read back via SqlProductQuery — proves both rows landed and
        // the flattened JOIN re-assembles the entity.
        $read = (new SqlProductQuery($this->pdo))->item('P-CREATE-001');
        $this->assertInstanceOf(ProductEntity::class, $read);
        $this->assertSame('New Product', $read->productName);
        $this->assertSame(2500, $read->price02);
        $this->assertSame(30, $read->stock);
        $this->assertSame(ProductEntity::STATUS_VISIBLE, $read->productStatus);
        $this->assertSame('詳細説明', $read->description);
        $this->assertSame('search', $read->searchWord);
        $this->assertSame('note', $read->note);

        // Raw probe — exactly one dtb_product and one default
        // dtb_product_class row exist.
        $this->assertSame(1, $this->countProductRows('P-CREATE-001'));
        $this->assertSame(1, $this->countDefaultClassRows('P-CREATE-001'));
    }

    public function testCreateWritesUnlimitedStockFlagForNullStock(): void
    {
        $command = new SqlProductCommand($this->pdo);
        $command->create(new ProductEntity(
            productCode: 'P-CREATE-NULLSTOCK',
            productName: 'Unlimited',
            price02: 1000,
            stock: null,
        ));

        $stmt = $this->pdo->prepare(
            'SELECT stock, stock_unlimited FROM dtb_product_class '
            . 'WHERE product_code = :code',
        );
        $stmt->execute([':code' => 'P-CREATE-NULLSTOCK']);
        $row = $stmt->fetch();
        $this->assertNotFalse($row);
        $this->assertNull($row['stock']);
        $this->assertSame(1, (int) $row['stock_unlimited']);
    }

    public function testUpdateMergesBothTables(): void
    {
        $command = new SqlProductCommand($this->pdo);
        $command->create(new ProductEntity(
            productCode: 'P-UPDATE-001',
            productName: 'Before',
            price02: 1000,
            stock: 5,
            productStatus: ProductEntity::STATUS_VISIBLE,
            description: 'before-desc',
        ));

        $command->update(new ProductEntity(
            productCode: 'P-UPDATE-001',
            productName: 'After',
            price02: 9999,
            stock: 99,
            productStatus: ProductEntity::STATUS_HIDDEN,
            description: 'after-desc',
            searchWord: 'after-word',
            note: 'after-note',
        ));

        $read = (new SqlProductQuery($this->pdo))->item('P-UPDATE-001');
        $this->assertInstanceOf(ProductEntity::class, $read);
        $this->assertSame('After', $read->productName);
        $this->assertSame(9999, $read->price02);
        $this->assertSame(99, $read->stock);
        $this->assertSame(ProductEntity::STATUS_HIDDEN, $read->productStatus);
        $this->assertSame('after-desc', $read->description);
        $this->assertSame('after-word', $read->searchWord);
        $this->assertSame('after-note', $read->note);

        // No duplicate rows — UPDATE in place.
        $this->assertSame(1, $this->countProductRows('P-UPDATE-001'));
        $this->assertSame(1, $this->countDefaultClassRows('P-UPDATE-001'));
    }

    public function testUpdateIsNoOpForUnknownCode(): void
    {
        $command = new SqlProductCommand($this->pdo);
        // No exception — silent no-op (the Final has already verified
        // existence before reaching update).
        $command->update(new ProductEntity(
            productCode: 'does-not-exist',
            productName: 'X',
            price02: 1,
            stock: null,
        ));

        $this->assertNull((new SqlProductQuery($this->pdo))->item('does-not-exist'));
    }

    public function testDeleteSoftDeletesByFlippingStatus(): void
    {
        $command = new SqlProductCommand($this->pdo);
        $command->create(new ProductEntity(
            productCode: 'P-DELETE-001',
            productName: 'Doomed',
            price02: 1000,
            stock: 1,
            productStatus: ProductEntity::STATUS_VISIBLE,
        ));

        $command->delete('P-DELETE-001');

        // The row still exists (soft delete) — status flipped to 3.
        $read = (new SqlProductQuery($this->pdo))->item('P-DELETE-001');
        $this->assertInstanceOf(ProductEntity::class, $read);
        $this->assertSame(ProductEntity::STATUS_WITHDRAWN, $read->productStatus);
        $this->assertSame(1, $this->countProductRows('P-DELETE-001'));
    }

    public function testDeleteIsIdempotentOnReplay(): void
    {
        $command = new SqlProductCommand($this->pdo);
        $command->create(new ProductEntity(
            productCode: 'P-DELETE-REPLAY',
            productName: 'Doomed',
            price02: 1000,
            stock: 1,
        ));

        $command->delete('P-DELETE-REPLAY');
        // Second call is a genuine no-op — no exception, status stays 3.
        $command->delete('P-DELETE-REPLAY');

        $read = (new SqlProductQuery($this->pdo))->item('P-DELETE-REPLAY');
        $this->assertInstanceOf(ProductEntity::class, $read);
        $this->assertSame(ProductEntity::STATUS_WITHDRAWN, $read->productStatus);
    }

    public function testDeleteIsNoOpForUnknownCode(): void
    {
        $command = new SqlProductCommand($this->pdo);
        // No exception.
        $command->delete('does-not-exist');
        $this->assertTrue(true);
    }

    public function testCopyClonesUnderNewCodeWithPrefixedName(): void
    {
        $command = new SqlProductCommand($this->pdo);
        $command->create(new ProductEntity(
            productCode: 'P-COPY-SRC',
            productName: 'Original',
            price02: 4200,
            stock: 7,
            productStatus: ProductEntity::STATUS_HIDDEN,
            description: 'orig-desc',
            searchWord: 'orig-word',
            note: 'orig-note',
        ));

        $copy = $command->copy('P-COPY-SRC', 'P-COPY-NEW');

        $this->assertSame('P-COPY-NEW', $copy->productCode);
        $this->assertSame('(コピー) Original', $copy->productName);
        // The copy is a fresh draft — STATUS_VISIBLE regardless of the
        // source's status.
        $this->assertSame(ProductEntity::STATUS_VISIBLE, $copy->productStatus);

        $read = (new SqlProductQuery($this->pdo))->item('P-COPY-NEW');
        $this->assertInstanceOf(ProductEntity::class, $read);
        $this->assertSame('(コピー) Original', $read->productName);
        $this->assertSame(4200, $read->price02);
        $this->assertSame(7, $read->stock);
        $this->assertSame(ProductEntity::STATUS_VISIBLE, $read->productStatus);
        $this->assertSame('orig-desc', $read->description);

        // The source is untouched.
        $source = (new SqlProductQuery($this->pdo))->item('P-COPY-SRC');
        $this->assertInstanceOf(ProductEntity::class, $source);
        $this->assertSame('Original', $source->productName);
        $this->assertSame(ProductEntity::STATUS_HIDDEN, $source->productStatus);
    }

    public function testCopyRaisesForUnknownSource(): void
    {
        $command = new SqlProductCommand($this->pdo);

        $this->expectException(RuntimeException::class);
        $command->copy('does-not-exist', 'P-COPY-FAIL');
    }

    public function testBulkUpdateStatusFlipsStatusAndCountsChanges(): void
    {
        $command = new SqlProductCommand($this->pdo);
        foreach (['P-BULK-001', 'P-BULK-002'] as $code) {
            $command->create(new ProductEntity(
                productCode: $code,
                productName: $code,
                price02: 1000,
                stock: 1,
                productStatus: ProductEntity::STATUS_VISIBLE,
            ));
        }

        $changed = $command->bulkUpdateStatus(
            ['P-BULK-001', 'P-BULK-002'],
            ProductEntity::STATUS_WITHDRAWN,
        );

        $this->assertSame(2, $changed);
        $query = new SqlProductQuery($this->pdo);
        foreach (['P-BULK-001', 'P-BULK-002'] as $code) {
            $entity = $query->item($code);
            $this->assertInstanceOf(ProductEntity::class, $entity);
            $this->assertSame(ProductEntity::STATUS_WITHDRAWN, $entity->productStatus);
        }
    }

    public function testBulkUpdateStatusSkipsUnknownCodes(): void
    {
        $command = new SqlProductCommand($this->pdo);
        $command->create(new ProductEntity(
            productCode: 'P-BULK-PARTIAL',
            productName: 'P-BULK-PARTIAL',
            price02: 1000,
            stock: 1,
            productStatus: ProductEntity::STATUS_VISIBLE,
        ));

        $changed = $command->bulkUpdateStatus(
            ['P-BULK-PARTIAL', 'does-not-exist'],
            ProductEntity::STATUS_HIDDEN,
        );

        // Only the known code is counted.
        $this->assertSame(1, $changed);
    }

    public function testBulkUpdateStatusDoesNotCountIdempotentReapplication(): void
    {
        $command = new SqlProductCommand($this->pdo);
        $command->create(new ProductEntity(
            productCode: 'P-BULK-IDEMP',
            productName: 'P-BULK-IDEMP',
            price02: 1000,
            stock: 1,
            productStatus: ProductEntity::STATUS_HIDDEN,
        ));

        // The product is already STATUS_HIDDEN — re-applying the same
        // status changes 0 rows.
        $changed = $command->bulkUpdateStatus(
            ['P-BULK-IDEMP'],
            ProductEntity::STATUS_HIDDEN,
        );

        $this->assertSame(0, $changed);
    }

    private function countProductRows(string $productCode): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_product p '
            . 'INNER JOIN dtb_product_class pc ON pc.product_id = p.id '
            . 'WHERE pc.product_code = :code '
            . 'AND pc.class_category_id1 IS NULL '
            . 'AND pc.class_category_id2 IS NULL',
        );
        $stmt->execute([':code' => $productCode]);

        return (int) $stmt->fetchColumn();
    }

    private function countDefaultClassRows(string $productCode): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM dtb_product_class '
            . 'WHERE product_code = :code '
            . 'AND class_category_id1 IS NULL '
            . 'AND class_category_id2 IS NULL',
        );
        $stmt->execute([':code' => $productCode]);

        return (int) $stmt->fetchColumn();
    }
}
