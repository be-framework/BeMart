<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Exception\MasterOperationNotSupportedException;
use MyVendor\BeMart\Be\Exception\MasterTypeFormatException;
use Override;

use function in_array;

/**
 * Dispatcher implementation of {@see AdminMasterRegistryInterface}.
 *
 * Holds a reference to each per-master storage and routes the generic
 * `doSortNoMove` / `doToggleVisible` operations to the right one keyed
 * by `masterType`. Because it depends only on the storage interfaces,
 * the same class works whether the Fake or the SQL storages are bound
 * — no Fake / Sql split is needed for the registry itself.
 */
final class AdminMasterRegistry implements AdminMasterRegistryInterface
{
    /** @var list<string> masters with a `sort_no` column */
    private const SORTABLE = ['payment', 'delivery', 'tag', 'className', 'classCategory'];

    /** @var list<string> masters with a `visible` column */
    private const VISIBLE_TOGGLEABLE = ['payment', 'delivery', 'classCategory', 'news'];

    public function __construct(
        private readonly PaymentMethodAdminStorageInterface $payments,
        private readonly DeliveryStorageInterface $deliveries,
        private readonly TagStorageInterface $tags,
        private readonly ClassNameStorageInterface $classNames,
        private readonly ClassCategoryStorageInterface $classCategories,
        private readonly NewsStorageInterface $news,
    ) {
    }

    #[Override]
    public function supportsReorder(string $masterType): bool
    {
        return in_array($masterType, self::SORTABLE, true);
    }

    #[Override]
    public function supportsVisible(string $masterType): bool
    {
        return in_array($masterType, self::VISIBLE_TOGGLEABLE, true);
    }

    #[Override]
    public function rowExists(string $masterType, string $rowId): bool
    {
        return match ($masterType) {
            'payment' => $this->payments->getById($rowId) !== null,
            'delivery' => $this->deliveries->getById($rowId) !== null,
            'tag' => $this->tags->getById($rowId) !== null,
            'className' => $this->classNames->getById($rowId) !== null,
            'classCategory' => $this->classCategories->getById($rowId) !== null,
            'news' => $this->news->getById($rowId) !== null,
            default => throw new MasterTypeFormatException(),
        };
    }

    #[Override]
    public function reorder(string $masterType, string $rowId, int $sortNo): void
    {
        if (! $this->supportsReorder($masterType)) {
            // Either an unknown master, or a known master with no
            // sort_no column (news). The Semantic layer already rejects
            // an unknown masterType, so in practice this is the
            // "news has no sort_no" guard.
            throw new MasterOperationNotSupportedException();
        }

        match ($masterType) {
            'payment' => $this->payments->reorder($rowId, $sortNo),
            'delivery' => $this->deliveries->reorder($rowId, $sortNo),
            'tag' => $this->tags->reorder($rowId, $sortNo),
            'className' => $this->classNames->reorder($rowId, $sortNo),
            'classCategory' => $this->classCategories->reorder($rowId, $sortNo),
            default => throw new MasterTypeFormatException(),
        };
    }

    #[Override]
    public function setVisible(string $masterType, string $rowId, bool $visible): void
    {
        if (! $this->supportsVisible($masterType)) {
            // Either an unknown master, or a known master with no
            // visible column (tag / className).
            throw new MasterOperationNotSupportedException();
        }

        match ($masterType) {
            'payment' => $this->payments->setVisible($rowId, $visible),
            'delivery' => $this->deliveries->setVisible($rowId, $visible),
            'classCategory' => $this->classCategories->setVisible($rowId, $visible),
            'news' => $this->news->setVisible($rowId, $visible),
            default => throw new MasterTypeFormatException(),
        };
    }
}
