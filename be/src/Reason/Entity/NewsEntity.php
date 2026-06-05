<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * News post — projection of EC-CUBE dtb_news (Wave 9 CMS slice).
 *
 * `publishDate` is stored as an ISO-8601 string for the in-memory store
 * (the storage layer is opaque about timezone). `linkMethod` mirrors
 * EC-CUBE's boolean: false = same window, true = target="_blank".
 */
final readonly class NewsEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public bool $linkMethod;

    public function __construct(
        public string $newsId,
        public string $newsTitle,
        public string|null $newsDescription,
        public string|null $newsUrl,
        public string $publishDate,
        bool|int|string $linkMethod,
    ) {
        $this->linkMethod = (bool) $linkMethod;
    }
}
