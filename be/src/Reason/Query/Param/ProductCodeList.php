<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Param;

use Override;
use Ray\MediaQuery\ToScalarInterface;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class ProductCodeList implements ToScalarInterface
{
    /** @param list<string> $productCodes */
    public function __construct(public array $productCodes) {}

    /** @param list<string> $productCodes */
    public static function fromArray(array $productCodes): self
    {
        return new self($productCodes);
    }

    #[Override]
    public function toScalar(): string
    {
        return json_encode($this->productCodes, JSON_THROW_ON_ERROR);
    }
}
