<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\NewsUrlFormatException;

use function preg_match;

/**
 * External link URL on a news entry — EC-CUBE 4.3 dtb_news.url (Wave 9).
 *
 * The admin news list renders this value as a bare `href`, so the scheme
 * is part of the contract: an http/https absolute URL, or a site-relative
 * path such as `/products`. `javascript:` and `data:` URLs are refused
 * here rather than at the template, which cannot tell them apart from a
 * legitimate link. `null` and the empty string both mean "no link".
 *
 * @link https://schema.org/url
 */
final class NewsUrl
{
    private const PATTERN = '#\A(?:https?://[^\s/?\#]+\S*|/\S*)\z#i';

    #[Validate]
    public function validate(string|null $newsUrl): void
    {
        if ($newsUrl === null || $newsUrl === '') {
            return;
        }

        if (! preg_match(self::PATTERN, $newsUrl)) {
            throw new NewsUrlFormatException();
        }
    }
}
