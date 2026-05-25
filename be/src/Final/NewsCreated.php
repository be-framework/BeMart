<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Provider\NewsIdProvider;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * News created — Final (Wave 9).
 */
final readonly class NewsCreated
{
    public string $newsId;
    public string $newsTitle;
    public string|null $newsDescription;
    public string|null $newsUrl;
    public string $publishDate;
    public bool $linkMethod;

    public function __construct(
        #[Input] string $newsTitle,
        #[Input] string $publishDate,
        #[Input] string|null $newsDescription,
        #[Input] string|null $newsUrl,
        #[Input] bool $linkMethod,
        #[Inject] AdminSession $adminSession,
        #[Inject] NewsStorageInterface $news,
        #[Inject] NewsIdProvider $ids,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new NewsEntity(
            newsId: $ids->get(),
            newsTitle: $newsTitle,
            newsDescription: $newsDescription,
            newsUrl: $newsUrl,
            publishDate: $publishDate,
            linkMethod: $linkMethod,
        );

        $news->put($entity);

        $this->newsId = $entity->newsId;
        $this->newsTitle = $entity->newsTitle;
        $this->newsDescription = $entity->newsDescription;
        $this->newsUrl = $entity->newsUrl;
        $this->publishDate = $entity->publishDate;
        $this->linkMethod = $entity->linkMethod;
    }
}
