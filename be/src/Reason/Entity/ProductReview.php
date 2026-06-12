<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/** Customer-facing product review used by IDEA STORE catalogue screens. */
final readonly class ProductReview implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $reviewId,
        public string $productCode,
        public int $rating,
        public string $title,
        public string $body,
        public string $reviewer,
        public string $createdAt,
    ) {
    }
}
