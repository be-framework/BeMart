<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\MasterOperationNotSupportedException;
use MyVendor\BeMart\Be\Exception\MasterRowNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\SortNoMoved;
use MyVendor\BeMart\Be\Input\SortNoMoveInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doSortNoMove — 並び順を変更する (Phase 3 ALPS-audit
 * remediation).
 *
 *   PUT /admin/sort-no-move
 *
 * The generic admin-list reorder transition. EC-CUBE has a per-master
 * *_sort_no_move route for each list screen (Payment / Delivery / Tag /
 * ClassName / ClassCategory); BeMart folds them into this one resource
 * keyed by `masterType`. ALPS marks it `idempotent` — PUT is the verb.
 *
 * Failure mapping:
 *   - Invalid CSRF                            → 403
 *   - SemanticVariableException               → 400 (masterType / sortNo)
 *   - UnauthorizedAdminAccessException        → 403 (no admin session)
 *   - MasterRowNotFoundException              → 404
 *   - MasterOperationNotSupportedException    → 400 (master lacks sort_no)
 */
class SortNoMove extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `doSortNoMove` に対応する PUT 操作。
     * @psalm-taint-source input $masterType
     * @psalm-taint-source input $rowId
     * @psalm-taint-source input $sortNo
     */
    #[Alps('doSortNoMove')]
    #[JsonSchema(schema: 'put-admin-sort-no-move.json', params: 'put-admin-sort-no-move.param.json')]
    #[CsrfProtected]
    public function onPut(
        string $masterType,
        string $rowId,
        int $sortNo,
    ): static {
        $final = ($this->becoming)(new SortNoMoveInput(
            masterType: $masterType,
            rowId: $rowId,
            sortNo: $sortNo,
        ));

        assert($final instanceof SortNoMoved);

        $this->code = Code::OK;
        $this->body = [
            'masterType' => $final->masterType,
            'rowId' => $final->rowId,
            'sortNo' => $final->sortNo,
        ];

        return $this;
    }
}
