<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\NewsNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin news detail fetched (Wave 9).
 */
final readonly class AdminNewsFetched
{
    public string $newsId;
    public string $newsTitle;
    public string|null $newsDescription;
    public string|null $newsUrl;
    public string $publishDate;
    public bool $linkMethod;

    public function __construct(
        #[Input] string $newsId,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] NewsStorageInterface $news,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $row = $news->getById($newsId);
        if ($row === null) {
            throw new NewsNotFoundException();
        }

        $this->newsId = $row->newsId;
        $this->newsTitle = $row->newsTitle;
        $this->newsDescription = $row->newsDescription;
        $this->newsUrl = $row->newsUrl;
        $this->publishDate = $row->publishDate;
        $this->linkMethod = $row->linkMethod;
    }
}
