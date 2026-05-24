<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\ProductStatusCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\Param\ProductCodeList;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function count;

/**
 * Admin product status bulk-updated — Final, proof an admin flipped
 * the productStatus column across one or more products.
 *
 *   AdminBulkUpdateProductStatusInput → AdminProductsStatusBulkUpdated  (Direct, unsafe)
 *
 * ALPS doc verbatim: "選択した商品のステータス（公開・非公開・廃止）を一括変更する。"
 * ALPS `doBulkUpdateProductStatus.type=unsafe`.
 *
 * AUTHZ — cross-firewall:
 *   1. No admin session → UnauthorizedAdminAccessException (403)
 *
 * Unknown productCodes in the list are silently skipped (the
 * `requestedCount` vs `changedCount` projection lets the admin UI
 * surface the discrepancy). This mirrors EC-CUBE's bulk-action
 * behaviour: a stale list never aborts the whole batch.
 *
 * Idempotency note: re-running with the same status against an
 * already-aligned product does NOT count toward `changedCount`. So
 * `changedCount=0` is a valid outcome when every targeted product was
 * already in the requested status — a no-op replay is safe.
 *
 * Format validation: the Semantic\ProductCodes class enforces the
 * 1—100 list-size cap + per-element ProductCode format; the Semantic\
 * ProductStatus class enforces the enum value (1/2/3).
 */
final readonly class AdminProductsStatusBulkUpdated
{
    /** @var list<string> */
    public array $productCodes;
    public int $productStatus;
    public int $requestedCount;
    public int $changedCount;

    /** @param list<string> $productCodes */
    public function __construct(
        #[Input] array $productCodes,
        #[Input] int $productStatus,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] ProductStatusCommandInterface $productStatusCommand,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $changed = $productStatusCommand->update(ProductCodeList::fromArray($productCodes), $productStatus);

        $this->productCodes = $productCodes;
        $this->productStatus = $productStatus;
        $this->requestedCount = count($productCodes);
        $this->changedCount = $changed->changedCount;
    }
}
