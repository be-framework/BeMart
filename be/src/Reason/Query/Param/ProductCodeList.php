<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Param;

use Override;
use JsonException;
use Ray\MediaQuery\ToScalarInterface;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class ProductCodeList implements ToScalarInterface
{
    /** @param list<string> $productCodes */
    public function __construct(private array $productCodes) {}

    /** @param list<string> $productCodes */
    public static function fromArray(array $productCodes): self
    {
        return new self($productCodes);
    }

    /** @return list<string> */
    public function values(): array
    {
        return $this->productCodes;
    }

    #[Override]
    public function toScalar(): string
    {
        return json_encode($this->productCodes, JSON_THROW_ON_ERROR);
    }
}
