<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Final\ProductFetched;
use MyVendor\BeMart\Be\Input\GetProductInput;

use function assert;

/**
 * EC-CUBE goProduct —商品詳細ページ。
 *
 * Resource is the HTTP entry point: it builds a Be Input, hands it to
 * Becoming, and projects the resulting Final into the response body.
 * All validation and DB access live in the Be domain layer.
 */
class Product extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * Phase B Slice 9: `$productCode` is user input (URI / query param);
     * declared explicitly so Psalm taint analysis can trace it through
     * Becoming into any downstream sink. The Be Semantic\ProductCode
     * constructor format-validates but does not escape — sinks downstream
     * still need to defend (e.g. bound parameters for SQL).
     *
     * @psalm-taint-source input $productCode
     */
    #[Link(rel: 'goProductList', href: 'page://self/products')]
    #[Link(rel: 'doAddCartItem', href: 'page://self/cart/item', method: 'post')]
    public function onGet(string $productCode): static
    {
        try {
            $final = ($this->becoming)(new GetProductInput($productCode));
        } catch (ProductNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Product not found.', 'productCode' => $productCode];

            return $this;
        }

        assert($final instanceof ProductFetched);

        $this->body = [
            'productCode' => $final->productCode,
            'productName' => $final->productName,
            'price02' => $final->price02,
            'stock' => $final->stock,
        ];

        return $this;
    }
}
