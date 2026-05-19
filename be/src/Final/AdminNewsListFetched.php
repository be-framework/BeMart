<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Admin news list fetched — Final (Wave 9).
 */
final readonly class AdminNewsListFetched
{
    public int $count;

    /** @var list<array{newsId: string, newsTitle: string, newsDescription: string|null, newsUrl: string|null, publishDate: string, linkMethod: bool}> */
    public array $news;

    public function __construct(
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] NewsStorageInterface $news,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $news->list();

        $this->count = count($rows);
        $this->news = array_map(
            static fn (NewsEntity $row): array => [
                'newsId' => $row->newsId,
                'newsTitle' => $row->newsTitle,
                'newsDescription' => $row->newsDescription,
                'newsUrl' => $row->newsUrl,
                'publishDate' => $row->publishDate,
                'linkMethod' => $row->linkMethod,
            ],
            $rows,
        );
    }
}
