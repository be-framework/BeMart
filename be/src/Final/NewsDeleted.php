<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\NewsNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * News deleted — Final (Wave 9, idempotent).
 */
final readonly class NewsDeleted
{
    public string $newsId;

    public function __construct(
        #[Input] string $newsId,
        #[Inject] AdminSession $adminSession,
        #[Inject] NewsStorageInterface $news,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($news->item($newsId) === null) {
            throw new NewsNotFoundException();
        }

        $news->delete($newsId);

        $this->newsId = $newsId;
    }
}
