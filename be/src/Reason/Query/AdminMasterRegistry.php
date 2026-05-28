<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Exception\MasterOperationNotSupportedException;
use MyVendor\BeMart\Be\Exception\MasterTypeFormatException;
use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;
use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
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
    /** @var list<array{value: string, label: string, table: string}> */
    private const MASTER_TYPES = [
        ['value' => 'payment', 'label' => '支払方法', 'table' => 'dtb_payment'],
        ['value' => 'delivery', 'label' => '配送方法', 'table' => 'dtb_delivery'],
        ['value' => 'tag', 'label' => 'タグ', 'table' => 'dtb_tag'],
        ['value' => 'className', 'label' => '規格', 'table' => 'dtb_class_name'],
        ['value' => 'classCategory', 'label' => '規格分類', 'table' => 'dtb_class_category'],
        ['value' => 'member', 'label' => '管理者', 'table' => 'dtb_member'],
        ['value' => 'news', 'label' => '新着情報', 'table' => 'dtb_news'],
    ];

    /** @var list<string> masters with a `sort_no` column */
    private const SORTABLE = ['payment', 'delivery', 'tag', 'className', 'classCategory', 'member'];

    /** @var list<string> masters with a `visible` column */
    private const VISIBLE_TOGGLEABLE = ['payment', 'delivery', 'classCategory', 'news'];

    public function __construct(
        private readonly PaymentMethodAdminStorageInterface $payments,
        private readonly DeliveryStorageInterface $deliveries,
        private readonly TagStorageInterface $tags,
        private readonly ClassNameStorageInterface $classNames,
        private readonly ClassCategoryStorageInterface $classCategories,
        private readonly AdminQueryInterface $admins,
        private readonly AdminCommandInterface $adminCommands,
        private readonly NewsStorageInterface $news,
    ) {
    }

    /**
     * @return list<array{value: string, label: string, table: string}>
     */
    #[Override]
    public function listMasterTypes(): array
    {
        return self::MASTER_TYPES;
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    #[Override]
    public function listRows(string $masterType): array
    {
        return match ($masterType) {
            'payment' => array_map(
                static fn (PaymentMethodAdminEntity $row): array => [
                    'id' => $row->paymentId,
                    'name' => $row->paymentMethodName,
                ],
                $this->payments->list(),
            ),
            'delivery' => array_map(
                static fn (DeliveryEntity $row): array => [
                    'id' => $row->deliveryId,
                    'name' => $row->deliveryName,
                ],
                $this->deliveries->list(),
            ),
            'tag' => array_map(
                static fn (TagEntity $row): array => [
                    'id' => $row->tagId,
                    'name' => $row->tagName,
                ],
                $this->tags->list(),
            ),
            'className' => array_map(
                static fn (ClassNameEntity $row): array => [
                    'id' => $row->classNameId,
                    'name' => $row->name,
                ],
                $this->classNames->list(),
            ),
            'classCategory' => array_map(
                static fn (ClassCategoryEntity $row): array => [
                    'id' => $row->classCategoryId,
                    'name' => $row->name,
                ],
                $this->classCategories->list(),
            ),
            'member' => array_map(
                static fn (AdminEntity $row): array => [
                    'id' => $row->adminId,
                    'name' => $row->name !== '' ? $row->name : $row->loginId,
                ],
                $this->admins->list(),
            ),
            'news' => array_map(
                static fn (NewsEntity $row): array => [
                    'id' => $row->newsId,
                    'name' => $row->newsTitle,
                ],
                $this->news->list(),
            ),
            default => throw new MasterTypeFormatException(),
        };
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
            'payment' => $this->payments->item($rowId) !== null,
            'delivery' => $this->deliveries->item($rowId) !== null,
            'tag' => $this->tags->item($rowId) !== null,
            'className' => $this->classNames->item($rowId) !== null,
            'classCategory' => $this->classCategories->item($rowId) !== null,
            'member' => $this->admins->item($rowId) !== null,
            'news' => $this->news->item($rowId) !== null,
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
            'member' => $this->adminCommands->reorder($rowId, $sortNo),
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
