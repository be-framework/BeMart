<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\App\Agent;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\ToolUse\Attribute\Tool;
use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use MyVendor\BeMart\Be\Reason\Query\ProductQueryInterface;
use MyVendor\BeMart\Support\ProductImageCatalog;

/** LLM-readable product detail lookup for BEAR.ToolUse agents. */
final class Product extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery,
    ) {
    }

    /**
     * Look up one product for an agent.
     *
     * @param string $productCode Product code such as sample-001, CODE000001, or IDEA000018.
     */
    #[Tool(name: 'product_lookup', description: 'Look up one BeMart product by product code. Returns an LLM-readable product detail shape.')]
    public function onGet(string $productCode): static
    {
        $product = $this->productQuery->item($productCode);
        if (! $product instanceof ProductEntity || $product->productStatus !== ProductEntity::STATUS_VISIBLE) {
            $this->code = Code::NOT_FOUND;
            $this->body = [
                'error' => 'Product not found',
                'productCode' => $productCode,
            ];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'productCode' => $product->productCode,
            'productName' => $product->productName,
            'price02' => $product->price02,
            'stock' => $product->stock,
            'stockFind' => $product->stock === null || $product->stock > 0,
            'description' => $product->description ?? '',
            'categories' => $product->categoryNames,
            'tags' => $product->tagNames,
            'classNames' => $product->classNames,
            'image' => $product->imagePath ?? ProductImageCatalog::forProductCode($product->productCode),
        ];

        return $this;
    }
}
