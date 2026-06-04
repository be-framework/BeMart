<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Product;

use MyVendor\BeMart\Support\Resource\AbstractCsvUpload;
use Override;

/**
 * EC-CUBE 商品CSV登録 — Product Tier-2 (`admin/Product/csv_product.twig`).
 *
 *   GET /admin/product/csv-product → CSV-upload screen
 *
 * Thin GET renderer — see {@see AbstractCsvUpload}. The matching
 * `doImportProductCsv` write transition is a Phase-A stub; the export
 * download lives at the sibling action-only
 * {@see \MyVendor\BeMart\Resource\Page\Admin\ProductCsv}.
 */
class CsvProduct extends AbstractCsvUpload
{
    #[Override]
    protected function csvTitle(): string
    {
        return '商品CSV登録';
    }

    #[Override]
    protected function skeletonRoute(): string
    {
        return 'admin_product_csv_product_skeleton';
    }

    /** {@inheritDoc} */
    #[Override]
    protected function columns(): array
    {
        return [
            ['name' => '商品ID', 'description' => '新規登録の場合は空にしてください。既存の商品を更新する場合は、商品IDを指定してください。'],
            ['name' => '公開ステータス(ID)', 'description' => '1:公開 2:非公開 を指定します。'],
            ['name' => '商品名', 'description' => ''],
            ['name' => '商品コード', 'description' => ''],
            ['name' => '販売価格', 'description' => '数字で指定してください。'],
            ['name' => '在庫数', 'description' => '数字で指定してください。在庫無制限の場合は空にしてください。'],
        ];
    }
}
