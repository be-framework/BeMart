<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\ProductReview;
use MyVendor\BeMart\Be\Reason\Entity\ProductReviewSummary;
use Ray\MediaQuery\Annotation\DbQuery;

/** Product-review read side shared by FakeQuery and DB MediaQuery backends. */
interface ProductReviewQueryInterface
{
    /** @return list<ProductReview> */
    #[DbQuery('product_review_list', factory: ProductReview::class)]
    public function listByProduct(string $productCode, int $limit = 5, int $offset = 0): array;

    #[DbQuery('product_review_summary', factory: ProductReviewSummary::class)]
    public function summaryByProduct(string $productCode): ProductReviewSummary|null;
}
