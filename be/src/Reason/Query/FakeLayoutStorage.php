<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\LayoutEntity;
use Override;

use function array_values;
use function ksort;

/**
 * In-memory Layout store. Seeded with EC-CUBE's two stock layouts
 * (PC default + Mobile default) so the admin list view is non-empty
 * on a fresh install.
 */
final class FakeLayoutStorage implements LayoutStorageInterface
{
    public const SEED_PC_LAYOUT_ID = 'lo-pc-default';
    public const SEED_SP_LAYOUT_ID = 'lo-sp-default';

    /** @var array<string, LayoutEntity> keyed by layoutId */
    private array $byId;

    public function __construct()
    {
        $this->byId = [
            self::SEED_PC_LAYOUT_ID => new LayoutEntity(
                layoutId: self::SEED_PC_LAYOUT_ID,
                layoutName: 'PC標準',
                deviceType: 10,
            ),
            self::SEED_SP_LAYOUT_ID => new LayoutEntity(
                layoutId: self::SEED_SP_LAYOUT_ID,
                layoutName: 'スマホ標準',
                deviceType: 2,
            ),
        ];
    }

    /** @return list<LayoutEntity> */
    #[Override]
    public function list(): array
    {
        $rows = $this->byId;
        ksort($rows);

        return array_values($rows);
    }

    #[Override]
    public function getById(string $layoutId): LayoutEntity|null
    {
        return $this->byId[$layoutId] ?? null;
    }

    #[Override]
    public function put(LayoutEntity $layout): void
    {
        $this->byId[$layout->layoutId] = $layout;
    }
}
