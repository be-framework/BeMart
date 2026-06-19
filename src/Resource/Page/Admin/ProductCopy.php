<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ProductCodeAlreadyInUseException;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminProductCopied;
use MyVendor\BeMart\Be\Input\AdminCopyProductInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE doCopyProduct — 商品をコピーする (Wave 8 admin).
 *
 * onPost only. CSRF enforced. The Be Final raises (in this order)
 * UnauthorizedAdmin (403), ProductNotFound (404 — source missing),
 * ProductCodeAlreadyInUse (409 — target slot occupied). Success: 201
 * with a Location header pointing at the new product's admin detail
 * URL.
 */
class ProductCopy extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * ALPS `doCopyProduct` に対応する POST 操作。
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $newProductCode
     */
    #[Alps('doCopyProduct')]
    #[JsonSchema(schema: 'post-admin-product-copy.json', params: 'post-admin-product-copy.param.json')]
    #[Link(rel: 'goProduct', href: 'page://self/admin/product', method: 'get')]
    #[Link(rel: 'goProductList', href: 'page://self/admin/product-list')]
    #[CsrfToken]
    public function onPost(
        string $productCode,
        string $newProductCode,
    ): static {
        $final = ($this->becoming)(new AdminCopyProductInput(
            productCode: $productCode,
            newProductCode: $newProductCode,
        ));

        assert($final instanceof AdminProductCopied);

        ($this->mutationResponse)($this, Code::CREATED, sprintf('/admin/product?productCode=%s', urlencode($final->newProductCode)));
        $this->body = [
            'productCode' => $final->productCode,
            'newProductCode' => $final->newProductCode,
            'newProductName' => $final->newProductName,
            'price02' => $final->price02,
            'stock' => $final->stock,
        ];

        return $this;
    }
}
