<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
use Override;

use function ctype_digit;

final class SqlTagStorage implements TagStorageInterface
{
    public function __construct(private readonly MediaQueryExecutor $db) {}

    /** @return list<TagEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->rows('tag_list'));
    }

    #[Override]
    public function getById(string $tagId): TagEntity|null
    {
        if (! ctype_digit($tagId)) {
            return null;
        }

        $row = $this->db->row('tag_get', ['id' => (int) $tagId]);

        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(TagEntity $tag): void
    {
        if (! ctype_digit($tag->tagId)) {
            return;
        }

        $id = (int) $tag->tagId;
        $values = ['id' => $id, 'name' => $tag->tagName, 'sortNo' => 0];
        $this->db->exec($this->db->row('tag_exists', ['id' => $id]) === null ? 'tag_insert' : 'tag_update', $values);
    }

    #[Override]
    public function remove(string $tagId): void
    {
        if (ctype_digit($tagId)) {
            $this->db->exec('tag_delete', ['id' => (int) $tagId]);
        }
    }

    #[Override]
    public function reorder(string $tagId, int $sortNo): void
    {
        if (ctype_digit($tagId)) {
            $this->db->exec('tag_reorder', ['id' => (int) $tagId, 'sortNo' => $sortNo]);
        }
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): TagEntity
    {
        return new TagEntity((string) (int) $row['id'], (string) $row['name']);
    }
}
