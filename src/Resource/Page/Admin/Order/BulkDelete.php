<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrdersBulkDeleted;
use MyVendor\BeMart\Be\Input\AdminBulkDeleteOrderInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doBulkDeleteOrder — 受注を一括削除する (Wave 9η).
 *
 *   POST /admin/order/bulk-delete
 *
 * Soft-delete semantics: each targeted row's `orderStatus` flips to
 * CANCEL(3). ALPS doc says "物理削除" but EC-CUBE keeps the row for
 * downstream reporting — see {@see AdminOrdersBulkDeleted} for the
 * full rationale.
 *
 * Unknown orderNos are silently skipped; `requestedCount` vs
 * `changedCount` lets the UI surface stale-grid anomalies. Mirrors
 * Wave 8 {@see \MyVendor\BeMart\Resource\Page\Admin\ProductBulkStatus}.
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (list size / element format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 */
class BulkDelete extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @param list<string> $orderNos
     *
     * @psalm-taint-source input $orderNos
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    public function onPost(
        array $orderNos,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminBulkDeleteOrderInput(
                orderNos: $orderNos,
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

        assert($final instanceof AdminOrdersBulkDeleted);

        $this->code = Code::OK;
        $this->body = [
            'orderNos' => $final->orderNos,
            'requestedCount' => $final->requestedCount,
            'changedCount' => $final->changedCount,
        ];

        return $this;
    }
}
