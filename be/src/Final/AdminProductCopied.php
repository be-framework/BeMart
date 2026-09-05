<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\ProductCodeAlreadyInUseException;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\ProductCommandInterface;
use MyVendor\BeMart\Be\Reason\Service\ProductCacheInvalidatorInterface;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin product copied — Final, proof an admin cloned one product
 * under a fresh productCode.
 *
 *   AdminCopyProductInput → AdminProductCopied  (Direct, unsafe)
 *
 * ALPS doc verbatim: "商品マスタを複製する。基本情報・規格（ProductClass）・画像を
 * 新規 Product として複製。タイトルは「(コピー) 」プレフィクス付き。"
 *
 * AUTHZ → existence → uniqueness ladder:
 *
 *   1. No admin session              → UnauthorizedAdminAccessException  (403)
 *   2. Unknown productCode           → ProductNotFoundException           (404)
 *   3. newProductCode already in use → ProductCodeAlreadyInUseException  (409)
 *
 * ALPS `doCopyProduct.type=unsafe` — re-submitting with the same
 * newProductCode MUST fail, never silently overwrite. The 409 mirrors
 * doCreateProduct's collision branch (Wave 5O parity with the
 * customer-create flow).
 *
 * The "(コピー) " prefix discipline lives behind
 * ProductCommandInterface::copy(), so every context mirrors the prefix
 * verbatim. The copy
 * starts in STATUS_VISIBLE regardless of the source's status — the
 * admin convention is that a copied product is a fresh draft to be
 * reviewed before publishing/hiding.
 */
final readonly class AdminProductCopied
{
    public string $productCode;
    public string $newProductCode;
    public string $newProductName;
    public int $price02;
    public int|null $stock;

    public function __construct(
        #[Input] string $productCode,
        #[Input] string $newProductCode,
        #[Inject] AdminSession $adminSession,
        #[Inject] ProductQueryInterface $productQuery,
        #[Inject] ProductCommandInterface $productCommand,
        #[Inject] ProductCacheInvalidatorInterface $cacheInvalidator,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($productQuery->item($productCode) === null) {
            throw new ProductNotFoundException();
        }

        if ($productQuery->item($newProductCode) !== null) {
            throw new ProductCodeAlreadyInUseException();
        }

        $copy = $productCommand->copy($productCode, $newProductCode)->product;
        $cacheInvalidator->invalidateCorpus();

        $this->productCode = $productCode;
        $this->newProductCode = $copy->productCode;
        $this->newProductName = $copy->productName;
        $this->price02 = $copy->price02;
        $this->stock = $copy->stock;
    }
}
