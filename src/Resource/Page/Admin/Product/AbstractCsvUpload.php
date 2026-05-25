<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Product;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminCsvUploadForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * Shared base for the four EC-CUBE 商品-section CSV-upload screens —
 * Product Tier-2 (`admin/Product/csv_product.twig`, `csv_category.twig`,
 * `csv_class_name.twig`, `csv_class_category.twig`).
 *
 * The four screens share an identical structure — a CSV file-upload
 * form plus a format-description table — so the GET renderer is
 * defined once here and each concrete resource supplies its own
 * heading, 雛形-download route and format-table rows
 * ({@see csvTitle()}, {@see skeletonRoute()}, {@see columns()}).
 *
 * The matching write transition (`doImportProductCsv` etc.) is a
 * Phase-A stub — the upload screen is the HTML shell only, mirroring
 * the existing action-only CSV resources. AUTHZ is a direct
 * admin-session check (Pattern B — no Be transition is invoked on the
 * GET path). No admin session → 403.
 */
abstract class AbstractCsvUpload extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /** Page heading — e.g. "商品CSV登録". */
    abstract protected function csvTitle(): string;

    /** Deterministic route id for the 雛形 (template) CSV download. */
    abstract protected function skeletonRoute(): string;

    /**
     * Format-description table rows ported from EC-CUBE's csv_* screen.
     *
     * @return list<array{name: string, description: string}>
     */
    abstract protected function columns(): array;

    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminCsvUploadForm::class);
        assert($form instanceof AdminCsvUploadForm);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'csvTitle' => $this->csvTitle(),
            'skeletonRoute' => $this->skeletonRoute(),
            'columns' => $this->columns(),
        ];

        return $this;
    }
}
