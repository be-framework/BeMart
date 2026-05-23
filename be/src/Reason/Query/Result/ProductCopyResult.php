<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use MyVendor\BeMart\Be\Reason\Entity\ProductEntity;
use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;
use RuntimeException;

final readonly class ProductCopyResult implements PostQueryInterface
{
    public function __construct(public ProductEntity $product) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? null;
        if (! $row instanceof ProductEntity) {
            $source = (string) ($context->values['sourceCode'] ?? '');
            throw new RuntimeException(sprintf('Product not found: %s', $source));
        }

        return new static($row);
    }
}
