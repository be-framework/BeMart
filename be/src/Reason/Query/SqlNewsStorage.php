<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;
use Override;

use function ctype_digit;

final class SqlNewsStorage implements NewsStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    /** @return list<NewsEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->tnews_list());
    }

    #[Override]
    public function getById(string $newsId): NewsEntity|null
    {
        if (! ctype_digit($newsId)) {
            return null;
        }
        $row = $this->db->tnews_get(id: (int) $newsId);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(NewsEntity $news): void
    {
        if (! ctype_digit($news->newsId)) {
            return;
        }
        $id = (int) $news->newsId;
        if ($this->db->tnews_exists(id: $id) === null) {
            $this->db->tnews_insert(
                id: $id,
                title: $news->newsTitle,
                description: $news->newsDescription,
                url: $news->newsUrl,
                publishDate: $news->publishDate,
                linkMethod: (int) $news->linkMethod,
            );

            return;
        }

        $this->db->tnews_update(
            id: $id,
            title: $news->newsTitle,
            description: $news->newsDescription,
            url: $news->newsUrl,
            publishDate: $news->publishDate,
            linkMethod: (int) $news->linkMethod,
        );
    }

    #[Override]
    public function remove(string $newsId): void
    {
        if (ctype_digit($newsId)) {
            $this->db->tnews_delete(id: (int) $newsId);
        }
    }

    #[Override]
    public function setVisible(string $newsId, bool $visible): void
    {
        if (ctype_digit($newsId)) {
            $this->db->tnews_visible(id: (int) $newsId, visible: (int) $visible);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): NewsEntity
    {
        return new NewsEntity(
            newsId: (string) (int) $row['id'],
            newsTitle: (string) ($row['title'] ?? ''),
            newsDescription: $row['description'] === null ? null : (string) $row['description'],
            newsUrl: $row['url'] === null ? null : (string) $row['url'],
            publishDate: (string) ($row['publish_date'] ?? ''),
            linkMethod: (bool) $row['link_method'],
        );
    }
}
