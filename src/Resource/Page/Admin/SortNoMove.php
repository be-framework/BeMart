<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\MasterOperationNotSupportedException;
use MyVendor\BeMart\Be\Exception\MasterRowNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\SortNoMoved;
use MyVendor\BeMart\Be\Input\SortNoMoveInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $masterType
     * @psalm-taint-source input $rowId
     * @psalm-taint-source input $sortNo
     * @psalm-taint-source input $csrfToken
     */
    public function onPut(
        string $masterType,
        string $rowId,
        int $sortNo,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new SortNoMoveInput(
                masterType: $masterType,
                rowId: $rowId,
                sortNo: $sortNo,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (MasterOperationNotSupportedException) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => 'このマスタは並び順の変更に対応していません。'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (MasterRowNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された行は見つかりませんでした。'];

            return $this;
        }

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
