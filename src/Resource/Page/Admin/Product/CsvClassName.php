<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Product;

use Override;

/**
 * EC-CUBE 規格CSV登録 — Product Tier-2
 * (`admin/Product/csv_class_name.twig`).
 *
 *   GET /admin/product/csv-class-name → CSV-upload screen
 *
 * Thin GET renderer — see {@see AbstractCsvUpload}. EC-CUBE has no
 * dedicated class-name CSV resource; this GET renderer is the HTML
 * upload shell only, the import transition staying Phase-A scope.
 */
class CsvClassName extends AbstractCsvUpload
{
    #[Override]
    protected function csvTitle(): string
    {
        return '規格CSV登録';
    }

    #[Override]
    protected function skeletonRoute(): string
    {
        return 'admin_product_csv_class_name_skeleton';
    }

    /** {@inheritDoc} */
    #[Override]
    protected function columns(): array
    {
        return [
            ['name' => '規格名ID', 'description' => '新規登録の場合は空にしてください。既存の規格名を更新する場合は、規格名IDを指定してください。'],
            ['name' => '規格名', 'description' => ''],
            ['name' => '管理名', 'description' => '管理者用に別名を登録できます。フロント画面には表示されません。'],
        ];
    }
}
