<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\ProductCodeFormatException;

use function mb_strlen;
use function preg_match;
use function trim;

/**
 * Variant of {@see ProductCode} used as the target-code parameter
 * name in copy / rename flows (Wave 8 doCopyProduct). Format rules
 * are identical to ProductCode; the existence of this separate
 * Semantic class is purely to keep the framework's
 * "parameter-name → Semantic-class" resolution happy when an Input
 * carries two product-code-shaped fields.
 *
 * @link https://schema.org/sku
 */
final class NewProductCode
{
    #[Validate]
    public function validate(string $newProductCode): void
    {
        if (trim($newProductCode) === '' || mb_strlen($newProductCode) > 50) {
            throw new ProductCodeFormatException();
        }

        if (! preg_match('/^[A-Za-z0-9._-]+$/', $newProductCode)) {
            throw new ProductCodeFormatException();
        }
    }
}
