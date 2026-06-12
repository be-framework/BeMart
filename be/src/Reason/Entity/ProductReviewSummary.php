<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/** Aggregate review score/count for one product. */
final readonly class ProductReviewSummary implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $productCode,
        public float|null $averageRating,
        public int $reviewCount,
    ) {
    }
}
