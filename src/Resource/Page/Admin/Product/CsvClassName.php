<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Product;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ClassNameCsvImported;
use MyVendor\BeMart\Be\Input\ImportClassNameCsvInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Support\Resource\AbstractCsvUpload;
use Override;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE 規格CSV登録 — Product Tier-2
 * (`admin/Product/csv_class_name.twig`).
 *
 *   GET  /admin/product/csv-class-name → CSV-upload screen
 *   POST /admin/product/csv-class-name → doImportClassNameCsv
 *
 * Hard ActionRedirect completion: `onGet` is the upload shell
 * ({@see AbstractCsvUpload}); `onPost` drives the Be
 * `doImportClassNameCsv` transition, the parse/persist isolated behind
 * {@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.
 */
class CsvClassName extends AbstractCsvUpload
{
    public function __construct(
        AdminSession $adminSession,
        FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
    ) {
        parent::__construct($adminSession, $formFactory);
    }

    /** ALPS `goExportClassName` に対応する GET 操作。 */
    #[Override]
    #[Alps('goExportClassName')]
    #[JsonSchema(schema: 'get-admin-product-csv-class-name.json')]
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    public function onGet(): static
    {
        parent::onGet();

        return $this;
    }

    /**
     * Imports the 規格名 CSV (doImportClassNameCsv).
     *
     * @psalm-taint-source input $csv
     */
    #[Alps('doImportClassNameCsv')]
    #[JsonSchema(schema: 'post-admin-product-csv-class-name.json', params: 'post-admin-product-csv-class-name.param.json')]
    #[Link(rel: 'goExportClassCategory', href: 'page://self/admin/class-category/class-category-export')]
    #[CsrfProtected]
    public function onPost(string $csv = ''): static
    {
        $final = ($this->becoming)(new ImportClassNameCsvInput(csv: $csv));

        assert($final instanceof ClassNameCsvImported);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin/class-name/class-name-list';
        $this->body = [
            'transitionId' => 'doImportClassNameCsv',
            'accepted' => $final->accepted,
            'message' => '規格名CSVを取り込みました。',
        ];

        return $this;
    }

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
