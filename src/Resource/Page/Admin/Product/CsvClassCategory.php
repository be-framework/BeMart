<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Product;

use Override;

/**
 * EC-CUBE 規格分類CSV登録 — Product Tier-2
 * (`admin/Product/csv_class_category.twig`).
 *
 *   GET /admin/product/csv-class-category → CSV-upload screen
 *
 * Thin GET renderer — see {@see AbstractCsvUpload}. EC-CUBE has no
 * dedicated class-category CSV resource; this GET renderer is the HTML
 * upload shell only, the import transition staying Phase-A scope.
 */
class CsvClassCategory extends AbstractCsvUpload
{
    #[Override]
    protected function csvTitle(): string
    {
        return '規格分類CSV登録';
    }

    #[Override]
    protected function skeletonRoute(): string
    {
        return 'admin_product_csv_class_category_skeleton';
    }

    /** {@inheritDoc} */
    #[Override]
    protected function columns(): array
    {
        return [
            ['name' => '規格分類ID', 'description' => '新規登録の場合は空にしてください。既存の規格分類を更新する場合は、規格分類IDを指定してください。'],
            ['name' => '規格名ID', 'description' => '登録済みの規格名IDを数字で指定してください'],
            ['name' => '分類名', 'description' => ''],
            ['name' => '管理名', 'description' => '管理者用に別名を登録できます。フロント画面には表示されません。'],
        ];
    }
}
