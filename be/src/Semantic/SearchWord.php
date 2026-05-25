<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\SearchWordFormatException;

use function mb_strlen;

/**
 * Product search keyword (検索ワード) — EC-CUBE 4.3
 * dtb_product.search_word. Wave 8 (doCreateProduct /
 * doUpdateProduct).
 *
 * Optional admin-side metadata used to boost a product's hit rate
 * for the front-side search. Length-bounded; EC-CUBE's column is
 * TEXT (effectively unbounded), the 1000-char cap here is a safety
 * rail.
 */
final class SearchWord
{
    #[Validate]
    public function validate(string|null $searchWord): void
    {
        if ($searchWord === null) {
            return;
        }

        if (mb_strlen($searchWord) > 1000) {
            throw new SearchWordFormatException();
        }
    }
}
