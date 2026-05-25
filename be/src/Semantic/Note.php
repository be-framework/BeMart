<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\NoteFormatException;

use function mb_strlen;

/**
 * Product internal note (商品備考) — EC-CUBE 4.3 dtb_product.note,
 * admin-only memo never shown to customers. Wave 8 (doCreateProduct /
 * doUpdateProduct).
 *
 * Length-bounded as a safety rail.
 */
final class Note
{
    #[Validate]
    public function validate(string|null $note): void
    {
        if ($note === null) {
            return;
        }

        if (mb_strlen($note) > 4000) {
            throw new NoteFormatException();
        }
    }
}
