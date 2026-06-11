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
use MyVendor\BeMart\Be\Final\ClassCategoryCsvImported;
use MyVendor\BeMart\Be\Input\ImportClassCategoryCsvInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Support\Resource\AbstractCsvUpload;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Override;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE 規格分類CSV登録 — Product Tier-2
 * (`admin/Product/csv_class_category.twig`).
 *
 *   GET  /admin/product/csv-class-category → CSV-upload screen
 *   POST /admin/product/csv-class-category → doImportClassCategoryCsv
 *
 * Hard ActionRedirect completion: `onGet` is the upload shell
 * ({@see AbstractCsvUpload}); `onPost` drives the Be
 * `doImportClassCategoryCsv` transition, the parse/persist isolated
 * behind {@see \MyVendor\BeMart\Be\Reason\Service\ClassCsvCompatibilityInterface}.
 */
class CsvClassCategory extends AbstractCsvUpload
{
    public function __construct(
        AdminSession $adminSession,
        FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
        parent::__construct($adminSession, $formFactory);
    }

    /** ALPS `goExportClassCategory` に対応する GET 操作。 */
    #[Override]
    #[Alps('goExportClassCategory')]
    #[JsonSchema(schema: 'get-admin-product-csv-class-category.json')]
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    public function onGet(): static
    {
        parent::onGet();

        return $this;
    }

    /**
     * Imports the 規格分類 CSV (doImportClassCategoryCsv).
     *
     * @psalm-taint-source input $csv
     */
    #[Alps('doImportClassCategoryCsv')]
    #[JsonSchema(schema: 'post-admin-product-csv-class-category.json', params: 'post-admin-product-csv-class-category.param.json')]
    #[CsrfProtected]
    public function onPost(string $csv = ''): static
    {
        $final = ($this->becoming)(new ImportClassCategoryCsvInput(csv: $csv));

        assert($final instanceof ClassCategoryCsvImported);

        ($this->mutationResponse)($this, Code::OK);
        $this->headers['Location'] = '/admin/class-category/class-category-list';
        $this->body = [
            'transitionId' => 'doImportClassCategoryCsv',
            'accepted' => $final->accepted,
            'message' => '規格分類CSVを取り込みました。',
        ];

        return $this;
    }

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
