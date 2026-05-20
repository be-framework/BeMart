<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

/**
 * Seeds the dtb_product + dtb_product_class rows the Product hypermedia
 * tests assert against — the SQL equivalent of the Fake-backed
 * `var/fake/products.json` seed.
 *
 * The Fake-backed Product Resource tests read five products from the
 * JSON fixture: two Pilot-1 happy-path rows (`sample-001`, `sample-002`)
 * and three admin-grid rows exercising each productStatus branch
 * (`admin-active-001` = 1 公開, `admin-hidden-001` = 2 非公開,
 * `admin-withdrawn-001` = 3 廃止). This trait inserts the exact same
 * five rows via the SQL fixture helpers so the SQL-backed hypermedia
 * sibling starts from an identical client-observable baseline — same
 * codes, names, prices, statuses.
 *
 * Consumers MUST be {@see AbstractResourceSqlTestCase} subclasses (the
 * fixture helpers — `seedProductStatus`, `insertProduct` — come from
 * {@see \MyVendor\BeMart\Be\Tests\Sql\SqlFixturesTrait}, which the base
 * class `use`s) and call {@see seedProducts} from their own `setUp`
 * after `parent::setUp()`.
 */
trait SqlProductSeedTrait
{
    /**
     * Insert the five canonical products. mtb_product_status is seeded
     * first — dtb_product.product_status_id is an enforced FK to that
     * (empty-in-the-dump) master.
     */
    protected function seedProducts(): void
    {
        $this->seedProductStatus();

        $this->insertProduct([
            'product_code' => 'sample-001',
            'name' => 'サンプル商品 A',
            'price02' => 1200,
            'stock' => 50,
            'product_status_id' => 1,
            'description_detail' => 'Pilot 1 happy-path fixture',
        ]);

        $this->insertProduct([
            'product_code' => 'sample-002',
            'name' => 'Sample Product B',
            'price02' => 9800,
            'stock' => null,
            'stock_unlimited' => 1,
            'product_status_id' => 1,
            'description_detail' => 'Stock-unlimited fixture',
        ]);

        $this->insertProduct([
            'product_code' => 'admin-active-001',
            'name' => '管理画面用 商品A',
            'price02' => 3500,
            'stock' => 20,
            'product_status_id' => 1,
            'description_detail' => 'Wave 8 admin grid: visible row',
            'search_word' => '管理 active',
            'note' => 'internal note A',
        ]);

        $this->insertProduct([
            'product_code' => 'admin-hidden-001',
            'name' => '管理画面用 非公開商品B',
            'price02' => 5000,
            'stock' => 10,
            'product_status_id' => 2,
            'description_detail' => 'Wave 8 admin grid: hidden row',
            'search_word' => '管理 hidden',
            'note' => 'internal note B',
        ]);

        $this->insertProduct([
            'product_code' => 'admin-withdrawn-001',
            'name' => '管理画面用 廃止商品C',
            'price02' => 800,
            'stock' => 0,
            'product_status_id' => 3,
            'description_detail' => 'Wave 8 admin grid: withdrawn row',
            'search_word' => '管理 withdrawn',
            'note' => 'internal note C',
        ]);
    }
}
