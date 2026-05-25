<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductsStatusBulkUpdated;
use MyVendor\BeMart\Be\Input\AdminBulkUpdateProductStatusInput;

use function assert;

/**
 * EC-CUBE doBulkUpdateProductStatus — 商品ステータスを一括変更する
 * (Wave 8 admin).
 *
 * onPost only. CSRF enforced. The Final silently skips unknown codes;
 * `requestedCount` vs `changedCount` lets the UI surface anomalies
 * (a stale grid row, an already-aligned status, etc.).
 */
class ProductBulkStatus extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * @param list<string> $productCodes
     *
     * @psalm-taint-source input $productCodes
     * @psalm-taint-source input $productStatus
     */
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    #[CsrfProtected]
    public function onPost(
        array $productCodes,
        int $productStatus,
    ): static {
        try {
            $final = ($this->becoming)(new AdminBulkUpdateProductStatusInput(
                productCodes: $productCodes,
                productStatus: $productStatus,
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

        assert($final instanceof AdminProductsStatusBulkUpdated);

        $this->code = Code::OK;
        $this->body = [
            'productCodes' => $final->productCodes,
            'productStatus' => $final->productStatus,
            'requestedCount' => $final->requestedCount,
            'changedCount' => $final->changedCount,
        ];

        return $this;
    }
}
