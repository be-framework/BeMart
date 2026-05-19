<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\CsvConfigUpdated;
use MyVendor\BeMart\Be\Input\UpdateCsvInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
        private readonly CsrfTokenInterface $csrf,
    ) {
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
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goTop', href: 'page://self/admin')]
    public function onPost(
        int $csvType,
        array $columns,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateCsvInput(
                csvType: $csvType,
                columns: $columns,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

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
