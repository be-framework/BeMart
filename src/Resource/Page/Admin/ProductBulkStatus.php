<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductsStatusBulkUpdated;
use MyVendor\BeMart\Be\Input\AdminBulkUpdateProductStatusInput;
use BEAR\Resource\Annotation\JsonSchema;

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
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * ALPS `doBulkUpdateProductStatus` に対応する POST 操作。
     * @param list<string> $productCodes
     *
     * @psalm-taint-source input $productCodes
     * @psalm-taint-source input $productStatus
     */
    #[Alps('doBulkUpdateProductStatus')]
    #[JsonSchema(schema: 'post-admin-product-bulk-status.json', params: 'post-admin-product-bulk-status.param.json')]
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    #[CsrfProtected]
    public function onPost(
        array $productCodes,
        int $productStatus,
    ): static {
        $final = ($this->becoming)(new AdminBulkUpdateProductStatusInput(
            productCodes: $productCodes,
            productStatus: $productStatus,
        ));

        assert($final instanceof AdminProductsStatusBulkUpdated);

        ($this->mutationResponse)($this, Code::OK);
        if ($this->code === Code::SEE_OTHER) {
            $this->headers['Location'] = '/admin/product-list';
        }
        $this->body = [
            'productCodes' => $final->productCodes,
            'productStatus' => $final->productStatus,
            'requestedCount' => $final->requestedCount,
            'changedCount' => $final->changedCount,
        ];

        return $this;
    }
}
