<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Product;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use MyVendor\BeMart\Support\Resource\AbstractCsvUpload;
use Override;

/**
 * EC-CUBE カテゴリCSV登録 — Product Tier-2
 * (`admin/Product/csv_category.twig`).
 *
 *   GET /admin/product/csv-category → CSV-upload screen
 *
 * Thin GET renderer — see {@see AbstractCsvUpload}. The matching
 * `doImportCategoryCsv` write transition is a Phase-A stub (the
 * action-only {@see \MyVendor\BeMart\Resource\Page\Admin\Category\Csv}
 * carries it); the export download is on that same resource's onGet.
 */
class CsvCategory extends AbstractCsvUpload
{
    /** ALPS `goExportCategory` に対応する GET 操作。 */
    #[Override]
    #[Alps('goExportCategory')]
    #[JsonSchema(schema: 'get-admin-product-csv-category.json')]
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    public function onGet(): static
    {
        parent::onGet();

        return $this;
    }

    #[Override]
    protected function csvTitle(): string
    {
        return 'カテゴリCSV登録';
    }

    #[Override]
    protected function skeletonRoute(): string
    {
        return 'admin_product_csv_category_skeleton';
    }

    /** {@inheritDoc} */
    #[Override]
    protected function columns(): array
    {
        return [
            ['name' => 'カテゴリID', 'description' => '新規登録の場合は空にしてください。既存のカテゴリを更新する場合は、カテゴリIDを指定してください。'],
            ['name' => 'カテゴリ名', 'description' => ''],
            ['name' => '親カテゴリID', 'description' => '登録済みのカテゴリIDを数字で指定してください'],
            ['name' => 'カテゴリ削除フラグ', 'description' => '0:登録 1:削除を指定します。未指定の場合、0として扱います。'],
        ];
    }
}
