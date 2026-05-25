<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Sql;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\SqlProductQuery;

/**
 * Storage-layer coverage for {@see SqlProductQuery} (Phase 2b).
 *
 * Mirrors the shape of {@see SqlProductClassQueryTest}. Per G-23 the
 * client-observable contract lives in the Resource-layer hypermedia
 * tests under `tests/Resource/Sql/AdminProduct*ResourceSqlTest`; the
 * cases below verify the per-method SQL paths in isolation — the
 * flattened Product × default-ProductClass JOIN, the default-class
 * filter, the productStatus / decimal-money / nullable coercions, and
 * miss / empty / pagination / keyword-filter boundaries.
 */
final class SqlProductQueryTest extends AbstractSqlTestCase
{
    public function testItemReturnsFlattenedEntity(): void
    {
        $this->seedProductStatus();
        $this->insertProduct([
            'product_code' => 'P-ITEM-001',
            'name' => 'サンプル商品 A',
            'price02' => 1200,
            'stock' => 50,
            'product_status_id' => 1,
            'description_detail' => '詳細説明文',
            'search_word' => 'keyword',
            'note' => 'internal note',
        ]);

        $query = new SqlProductQuery($this->pdo);
        $entity = $query->item('P-ITEM-001');

        $this->assertInstanceOf(ProductEntity::class, $entity);
        $this->assertSame('P-ITEM-001', $entity->productCode);
        $this->assertSame('サンプル商品 A', $entity->productName);
        $this->assertSame(1200, $entity->price02);
        $this->assertSame(50, $entity->stock);
        $this->assertSame(1, $entity->productStatus);
        $this->assertSame('詳細説明文', $entity->description);
        $this->assertSame('keyword', $entity->searchWord);
        $this->assertSame('internal note', $entity->note);
    }

    public function testItemReturnsNullForUnknownCode(): void
    {
        $query = new SqlProductQuery($this->pdo);
        $this->assertNull($query->item('does-not-exist'));
    }

    public function testItemCoercesNullProductStatusToVisible(): void
    {
        // product_status_id is a nullable FK — a NULL value coalesces
        // to STATUS_VISIBLE (1), the same default the Fake loader uses.
        $this->insertProduct([
            'product_code' => 'P-NULLSTATUS-001',
            'product_status_id' => null,
        ]);

        $query = new SqlProductQuery($this->pdo);
        $entity = $query->item('P-NULLSTATUS-001');

        $this->assertInstanceOf(ProductEntity::class, $entity);
        $this->assertSame(ProductEntity::STATUS_VISIBLE, $entity->productStatus);
    }

    public function testItemKeepsNullStockForUnlimitedProduct(): void
    {
        $this->insertProduct([
            'product_code' => 'P-UNLIMITED-001',
            'stock' => null,
            'stock_unlimited' => 1,
        ]);

        $query = new SqlProductQuery($this->pdo);
        $entity = $query->item('P-UNLIMITED-001');

        $this->assertInstanceOf(ProductEntity::class, $entity);
        $this->assertNull($entity->stock);
    }

    public function testItemSkipsVariationOnlyProductCode(): void
    {
        // A productCode that ONLY appears on a non-default variation
        // row must NOT resolve — SqlProductQuery restricts to the
        // default class (both class_category_id* axes NULL).
        $productId = $this->insertProduct(['product_code' => 'P-DEFAULT-001']);
        $classCategoryId = $this->insertClassCategory();
        $this->insertProductClassVariation($productId, [
            'product_code' => 'P-VARIATION-ONLY',
            'class_category_id1' => $classCategoryId,
        ]);

        $query = new SqlProductQuery($this->pdo);
        $this->assertNull($query->item('P-VARIATION-ONLY'));
        // The default class still resolves.
        $this->assertInstanceOf(ProductEntity::class, $query->item('P-DEFAULT-001'));
    }

    public function testListAllReturnsEveryDefaultClassRow(): void
    {
        $this->insertProduct(['product_code' => 'P-LIST-001']);
        $this->insertProduct(['product_code' => 'P-LIST-002']);
        $this->insertProduct(['product_code' => 'P-LIST-003']);

        $query = new SqlProductQuery($this->pdo);
        $rows = $query->listAll(50, 0);

        $this->assertCount(3, $rows);
        $this->assertContainsOnlyInstancesOf(ProductEntity::class, $rows);
    }

    public function testListAllReturnsEmptyArrayOnEmptyTable(): void
    {
        $query = new SqlProductQuery($this->pdo);
        $this->assertSame([], $query->listAll(50, 0));
    }

    public function testListAllRespectsLimitAndOffset(): void
    {
        $this->insertProduct(['product_code' => 'P-PAGE-001']);
        $this->insertProduct(['product_code' => 'P-PAGE-002']);
        $this->insertProduct(['product_code' => 'P-PAGE-003']);

        $query = new SqlProductQuery($this->pdo);

        $firstPage = $query->listAll(2, 0);
        $this->assertCount(2, $firstPage);

        $secondPage = $query->listAll(2, 2);
        $this->assertCount(1, $secondPage);

        // No overlap between pages (ORDER BY pc.id ASC).
        $this->assertNotSame(
            $firstPage[0]->productCode,
            $secondPage[0]->productCode,
        );
    }

    public function testListAllReturnsEmptyArrayWhenOffsetPastEnd(): void
    {
        $this->insertProduct(['product_code' => 'P-OFFSET-001']);

        $query = new SqlProductQuery($this->pdo);
        $this->assertSame([], $query->listAll(50, 100));
    }

    public function testSearchFiltersByNameSubstring(): void
    {
        $this->insertProduct(['product_code' => 'P-SRCH-001', 'name' => '管理画面用 商品A']);
        $this->insertProduct(['product_code' => 'P-SRCH-002', 'name' => '管理画面用 商品B']);
        $this->insertProduct(['product_code' => 'P-SRCH-003', 'name' => 'Unrelated Product']);

        $query = new SqlProductQuery($this->pdo);
        $rows = $query->search('管理画面用', 50);

        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertStringContainsString('管理画面用', $row->productName);
        }
    }

    public function testSearchWithNullKeywordBehavesLikeListAll(): void
    {
        $this->insertProduct(['product_code' => 'P-NULLKW-001']);
        $this->insertProduct(['product_code' => 'P-NULLKW-002']);

        $query = new SqlProductQuery($this->pdo);
        $this->assertCount(2, $query->search(null, 50));
        $this->assertCount(2, $query->search('', 50));
    }

    public function testSearchEscapesLikeMetacharacters(): void
    {
        // A `%` in the keyword must match literally — not as the SQL
        // LIKE wildcard.
        $this->insertProduct(['product_code' => 'P-PCT-001', 'name' => '50% OFF Sale']);
        $this->insertProduct(['product_code' => 'P-PCT-002', 'name' => 'Plain Product']);

        $query = new SqlProductQuery($this->pdo);
        $rows = $query->search('50%', 50);

        $this->assertCount(1, $rows);
        $this->assertSame('50% OFF Sale', $rows[0]->productName);
    }

    public function testSearchRespectsLimit(): void
    {
        $this->insertProduct(['product_code' => 'P-LIM-001', 'name' => 'Common Name 1']);
        $this->insertProduct(['product_code' => 'P-LIM-002', 'name' => 'Common Name 2']);
        $this->insertProduct(['product_code' => 'P-LIM-003', 'name' => 'Common Name 3']);

        $query = new SqlProductQuery($this->pdo);
        $this->assertCount(2, $query->search('Common Name', 2));
    }

    public function testListForExportReturnsEveryProduct(): void
    {
        $this->seedProductStatus();
        $this->insertProduct(['product_code' => 'P-EXP-001', 'product_status_id' => 1]);
        $this->insertProduct(['product_code' => 'P-EXP-002', 'product_status_id' => 2]);
        // Even a withdrawn product is in the export — admin scope.
        $this->insertProduct(['product_code' => 'P-EXP-003', 'product_status_id' => 3]);

        $query = new SqlProductQuery($this->pdo);
        $rows = $query->listForExport();

        $this->assertCount(3, $rows);
        $statuses = [];
        foreach ($rows as $row) {
            $statuses[] = $row->productStatus;
        }

        $this->assertContains(3, $statuses);
    }
}
