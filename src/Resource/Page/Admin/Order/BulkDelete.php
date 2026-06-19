<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrdersBulkDeleted;
use MyVendor\BeMart\Be\Input\AdminBulkDeleteOrderInput;
use BEAR\Resource\Annotation\JsonSchema;

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
    ) {
    }

    /**
     * ALPS `doBulkDeleteOrder` に対応する POST 操作。
     * @param list<string> $orderNos
     * @param list<string> $ids EC-CUBE-compatible HTML form alias (`ids[]`).
     *
     * @psalm-taint-source input $orderNos
     */
    #[Alps('doBulkDeleteOrder')]
    #[JsonSchema(schema: 'post-admin-order-bulk-delete.json', params: 'post-admin-order-bulk-delete.param.json')]
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    #[CsrfToken]
    public function onPost(
        array $orderNos = [],
        array $ids = [],
        string|null $mode = null,
    ): static {
        $targetOrderNos = $orderNos === [] ? $ids : $orderNos;
        $final = ($this->becoming)(new AdminBulkDeleteOrderInput(
            orderNos: $targetOrderNos,
        ));

        assert($final instanceof AdminOrdersBulkDeleted);

        $this->code = $mode === 'order_bulk_delete_form' ? Code::SEE_OTHER : Code::OK;
        $this->headers['Location'] = '/admin/order-list';
        $this->body = [
            'orderNos' => $final->orderNos,
            'requestedCount' => $final->requestedCount,
            'changedCount' => $final->changedCount,
        ];

        return $this;
    }
}
