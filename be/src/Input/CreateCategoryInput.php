<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\CategoryCreated;

/**
 * Input for doCreateCategory — admin creates a new catalog category
 * (Wave 7).
 *
 *   CreateCategoryInput → CategoryCreated (Direct, admin AUTHZ)
 *
 * Mass-assignment safety:
 *   - `categoryId` is INTENTIONALLY ABSENT — generated server-side
 *     by {@see \MyVendor\BeMart\Be\Reason\Service\CategoryIdGeneratorInterface}.
 *
 * `parentId` is nullable (root-level node when null). `sortNo` is
 * required so the admin commits to a display position up front.
 */
#[Be(CategoryCreated::class)]
final readonly class CreateCategoryInput
{
    /**
     * @psalm-taint-source input $categoryName
     * @psalm-taint-source input $parentId
     * @psalm-taint-source input $sortNo
     */
    public function __construct(
        public string $categoryName,
        public int $sortNo,
        public string|null $parentId = null,
    ) {
    }
}
