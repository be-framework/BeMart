<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\NewsNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * News updated — Final (Wave 9, idempotent).
 */
final readonly class NewsUpdated
{
    public string $newsId;
    public string $newsTitle;
    public string|null $newsDescription;
    public string|null $newsUrl;
    public string $publishDate;
    public bool $linkMethod;

    public function __construct(
        #[Input] string $newsId,
        #[Input] string|null $newsTitle,
        #[Input] string|null $newsDescription,
        #[Input] string|null $newsUrl,
        #[Input] string|null $publishDate,
        #[Input] bool|null $linkMethod,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] NewsStorageInterface $news,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $news->item($newsId);
        if ($current === null) {
            throw new NewsNotFoundException();
        }

        $merged = new NewsEntity(
            newsId: $current->newsId,
            newsTitle: $newsTitle ?? $current->newsTitle,
            newsDescription: $newsDescription ?? $current->newsDescription,
            newsUrl: $newsUrl ?? $current->newsUrl,
            publishDate: $publishDate ?? $current->publishDate,
            linkMethod: $linkMethod ?? $current->linkMethod,
        );

        $news->put($merged);

        $this->newsId = $merged->newsId;
        $this->newsTitle = $merged->newsTitle;
        $this->newsDescription = $merged->newsDescription;
        $this->newsUrl = $merged->newsUrl;
        $this->publishDate = $merged->publishDate;
        $this->linkMethod = $merged->linkMethod;
    }
}
