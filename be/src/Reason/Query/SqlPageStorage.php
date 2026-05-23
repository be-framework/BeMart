<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PageEntity;
use Override;

use function ctype_digit;

final class SqlPageStorage implements PageStorageInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    /** @return list<PageEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->rows('tpage_list'));
    }

    #[Override]
    public function getById(string $pageId): PageEntity|null
    {
        if (! ctype_digit($pageId)) {
            return null;
        }
        $row = $this->db->row('tpage_get', ['id' => (int) $pageId]);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(PageEntity $page): void
    {
        if (! ctype_digit($page->pageId)) {
            return;
        }
        $id = (int) $page->pageId;
        $values = [
            'id' => $id,
            'pageName' => $page->pageName,
            'url' => $page->pageUrl,
            'fileName' => $page->pageFileName,
            'editType' => $page->pageEditType,
        ];
        $this->db->exec($this->db->row('tpage_exists', ['id' => $id]) === null ? 'tpage_insert' : 'tpage_update', $values);
    }

    #[Override]
    public function remove(string $pageId): void
    {
        if (! ctype_digit($pageId)) {
            return;
        }
        $id = (int) $pageId;
        $this->db->exec('tpage_layout_delete', ['id' => $id]);
        $this->db->exec('tpage_delete', ['id' => $id]);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): PageEntity
    {
        return new PageEntity(
            pageId: (string) (int) $row['id'],
            pageName: (string) ($row['page_name'] ?? ''),
            pageUrl: (string) ($row['url'] ?? ''),
            pageFileName: (string) ($row['file_name'] ?? ''),
            pageEditType: (int) $row['edit_type'],
        );
    }
}
