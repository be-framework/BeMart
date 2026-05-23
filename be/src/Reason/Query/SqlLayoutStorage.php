<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\LayoutEntity;
use Override;

use function ctype_digit;

final class SqlLayoutStorage implements LayoutStorageInterface
{
    public function __construct(private readonly InternalDbQueryInterface $db) {}

    /** @return list<LayoutEntity> */
    #[Override]
    public function list(): array
    {
        return array_map($this->hydrate(...), $this->db->tlayout_list());
    }

    #[Override]
    public function getById(string $layoutId): LayoutEntity|null
    {
        if (! ctype_digit($layoutId)) {
            return null;
        }
        $row = $this->db->tlayout_get(id: (int) $layoutId);
        return $row === null ? null : $this->hydrate($row);
    }

    #[Override]
    public function put(LayoutEntity $layout): void
    {
        if (! ctype_digit($layout->layoutId)) {
            return;
        }
        $id = (int) $layout->layoutId;
        if ($this->db->tlayout_exists(id: $id) === null) {
            $this->db->tlayout_insert(id: $id, layoutName: $layout->layoutName, deviceType: $layout->deviceType);

            return;
        }

        $this->db->tlayout_update(id: $id, layoutName: $layout->layoutName, deviceType: $layout->deviceType);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): LayoutEntity
    {
        return new LayoutEntity((string) (int) $row['id'], (string) ($row['layout_name'] ?? ''), (int) $row['device_type_id']);
    }
}
