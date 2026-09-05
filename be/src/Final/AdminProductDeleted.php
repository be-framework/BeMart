<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductCommandInterface;
use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin product deleted — Final, proof an admin soft-deleted one
 * product.
 *
 *   AdminDeleteProductInput → AdminProductDeleted  (Direct, idempotent)
 *
 * ALPS doc verbatim: "商品をステータス変更による論理削除する（visible=false）。
 * 受注履歴側のスナップショットには影響しない。" — flip productStatus
 * to STATUS_WITHDRAWN (=3); never physically remove the row. Order
 * history snapshots reference frozen product-copy data and are
 * unaffected.
 *
 * AUTHZ — cross-firewall (Wave 4 / Wave 6 ladder):
 *
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown productCode  → ProductNotFoundException          (404)
 *
 * The admin firewall check happens before existence is probed so an
 * admin-anonymous client has no business learning whether a given
 * productCode resolves.
 *
 * Idempotency (ALPS `type=idempotent`, pattern from Wave 6S
 * AdminCustomerDeleted): a second delete against an already-withdrawn
 * product is a no-op — no second update, no audit churn. The Final
 * still constructs successfully and surfaces `alreadyDeleted=true`.
 *
 * Mass-assignment safety: the adminId comes from AdminSession; the
 * only request-controlled input is the target productCode.
 */
final readonly class AdminProductDeleted
{
    /** Withdrawn product status (EC-CUBE dtb_product.product_status_id=3). */
    public const int STATUS_WITHDRAWN = ProductEntity::STATUS_WITHDRAWN;

    public string $productCode;
    public string $productName;
    public bool $alreadyDeleted;

    public function __construct(
        #[Input] string $productCode,
        #[Inject] AdminSession $adminSession,
        #[Inject] ProductQueryInterface $productQuery,
        #[Inject] ProductCommandInterface $productCommand,
        #[Inject] ProductCacheInvalidatorInterface $cacheInvalidator,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $productQuery->item($productCode);
        if ($current === null) {
            throw new ProductNotFoundException();
        }

        // Idempotent replay: an already-withdrawn product short-circuits.
        // Mirrors AdminCustomerDeleted's alreadyDeleted branch — same
        // shape so the resource layer can use a single 200 response.
        if ($current->productStatus === self::STATUS_WITHDRAWN) {
            $this->productCode = $current->productCode;
            $this->productName = $current->productName;
            $this->alreadyDeleted = true;

            return;
        }

        $productCommand->delete($productCode);
        $cacheInvalidator->invalidateCorpus();

        $this->productCode = $current->productCode;
        $this->productName = $current->productName;
        $this->alreadyDeleted = false;
    }
}
