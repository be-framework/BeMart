<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductsStatusBulkUpdated;
use MyVendor\BeMart\Be\Input\AdminBulkUpdateProductStatusInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @param list<string> $productCodes
     *
     * @psalm-taint-source input $productCodes
     * @psalm-taint-source input $productStatus
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    public function onPost(
        array $productCodes,
        int $productStatus,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

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
