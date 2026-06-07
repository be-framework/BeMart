<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\CsvConfigUpdated;
use MyVendor\BeMart\Be\Input\UpdateCsvInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminCsvConfigForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doUpdateCsv — CSV出力設定を更新する (Wave 9).
 *
 * POST. Admin replaces the column vector for one csvType (order=1,
 * customer=2, product=3, shipping=4) — each column carries
 * `columnName`, `enabled`, `sortNo`. The storage replaces the per-type
 * row set atomically so the column vector cannot drift.
 *
 * Wave 9 first iteration scope:
 *   - persists the configuration (the storage holds it; a subsequent
 *     read sees the write)
 *   - the export Finals (Wave 8α product, Wave 8β category, Wave 9
 *     customer) still emit the hardcoded column list — consuming this
 *     configuration in the exporters is Phase 2.
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (csvType / column shape)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 */
class CsvConfig extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE CSV出力項目設定 — Setting/Shop Tier-2.
     *
     * Thin GET renderer for `Setting/Shop/csv.twig`. The existing POST
     * persists a submitted vector; this GET serves the editor body.
     */
    #[Alps('doUpdateCsv')]
    #[JsonSchema(schema: 'get-admin-csv-config.json', params: 'get-admin-csv-config.param.json')]
    #[Link(rel: 'doUpdateCsv', href: 'page://self/admin/csv-config', method: 'post')]
    public function onGet(int $id = 1): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminCsvConfigForm::class);
        assert($form instanceof AdminCsvConfigForm);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'id' => $id,
            'outputColumns' => AdminCsvConfigForm::outputColumns(),
            'notOutputColumns' => AdminCsvConfigForm::notOutputColumns(),
        ];

        return $this;
    }

    /**
     * Wave 9: admin-form input. The columns list is sanitized by Be /
     * Semantic; the column entries themselves carry user-supplied
     * column names so the taint mark applies to the whole payload.
     *
     * @param list<array{columnName: string, enabled: bool, sortNo: int}> $columns
     *
     * @psalm-taint-source input $csvType
     * @psalm-taint-source input $columns
     */
    #[Alps('doUpdateCsv')]
    #[JsonSchema(schema: 'post-admin-csv-config.json', params: 'post-admin-csv-config.param.json')]
    #[Link(rel: 'goTop', href: 'page://self/admin')]
    #[Link(rel: 'goExportProduct', href: 'page://self/admin/product-csv', method: 'get')]
    #[CsrfProtected]
    public function onPost(
        int $csvType,
        array $columns,
    ): static {
        $final = ($this->becoming)(new UpdateCsvInput(
            csvType: $csvType,
            columns: $columns,
        ));

        assert($final instanceof CsvConfigUpdated);

        $this->code = Code::OK;
        $this->body = [
            'csvType' => $final->csvType,
            'columns' => $final->columns,
            'count' => $final->count,
        ];

        return $this;
    }
}
