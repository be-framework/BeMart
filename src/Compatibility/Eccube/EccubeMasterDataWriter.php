<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Compatibility\Eccube;

use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use MyVendor\BeMart\Be\Reason\Entity\ClassCategoryEntity;
use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use MyVendor\BeMart\Be\Reason\Entity\DeliveryEntity;
use MyVendor\BeMart\Be\Reason\Entity\NewsEntity;
use MyVendor\BeMart\Be\Reason\Entity\PaymentMethodAdminEntity;
use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
use MyVendor\BeMart\Be\Reason\Query\AdminCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassCategoryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\NewsStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\MasterDataWriterInterface;
use Override;

use function trim;

/**
 * EC-CUBE-compatible master-data bulk-write boundary.
 *
 * The generic EC-CUBE master-data form edits only `id` + `name`, so each
 * per-master branch reads the current row, preserves non-form columns, and
 * writes back through the same storage surface that `AdminMasterRegistry`
 * uses for readback.
 */
final class EccubeMasterDataWriter implements MasterDataWriterInterface
{
    /** @var array<string, list<array{id: string, name: string, sortNo?: int}>> */
    private array $written = [];

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
     * @param list<array{id: string, name: string, sortNo?: int}> $rows
     */
    #[Override]
    public function update(string $masterType, array $rows): int
    {
        $this->written[$masterType] = $rows;

        return match ($masterType) {
            'payment' => $this->updatePayments($rows),
            'delivery' => $this->updateDeliveries($rows),
            'tag' => $this->updateTags($rows),
            'className' => $this->updateClassNames($rows),
            'classCategory' => $this->updateClassCategories($rows),
            'member' => $this->updateMembers($rows),
            'news' => $this->updateNews($rows),
            default => 0,
        };
    }

    /** @param list<array{id: string, name: string, sortNo?: int}> $rows */
    private function updatePayments(array $rows): int
    {
        $updated = 0;
        foreach ($rows as $row) {
            [$id, $name] = $this->rowIdAndName($row);
            $current = $this->payments->item($id);
            if ($current === null) {
                continue;
            }

            $this->payments->put(new PaymentMethodAdminEntity(
                paymentId: $current->paymentId,
                paymentMethodName: $name,
                charge: $current->charge,
                ruleMin: $current->ruleMin,
                ruleMax: $current->ruleMax,
                visible: $current->visible,
            ));
            $updated++;
        }

        return $updated;
    }

    /** @param list<array{id: string, name: string, sortNo?: int}> $rows */
    private function updateDeliveries(array $rows): int
    {
        $updated = 0;
        foreach ($rows as $row) {
            [$id, $name] = $this->rowIdAndName($row);
            $current = $this->deliveries->item($id);
            if ($current === null) {
                continue;
            }

            $this->deliveries->put(new DeliveryEntity(
                deliveryId: $current->deliveryId,
                deliveryName: $name,
                visible: $current->visible,
            ));
            $updated++;
        }

        return $updated;
    }

    /** @param list<array{id: string, name: string, sortNo?: int}> $rows */
    private function updateTags(array $rows): int
    {
        $updated = 0;
        foreach ($rows as $row) {
            [$id, $name] = $this->rowIdAndName($row);
            $current = $this->tags->item($id);
            if ($current === null) {
                continue;
            }

            $this->tags->put(new TagEntity(
                tagId: $current->tagId,
                tagName: $name,
            ));
            $updated++;
        }

        return $updated;
    }

    /** @param list<array{id: string, name: string, sortNo?: int}> $rows */
    private function updateClassNames(array $rows): int
    {
        $updated = 0;
        foreach ($rows as $row) {
            [$id, $name] = $this->rowIdAndName($row);
            $current = $this->classNames->item($id);
            if ($current === null) {
                continue;
            }

            $this->classNames->put(new ClassNameEntity(
                classNameId: $current->classNameId,
                name: $name,
            ));
            $updated++;
        }

        return $updated;
    }

    /** @param list<array{id: string, name: string, sortNo?: int}> $rows */
    private function updateClassCategories(array $rows): int
    {
        $updated = 0;
        foreach ($rows as $row) {
            [$id, $name] = $this->rowIdAndName($row);
            $current = $this->classCategories->item($id);
            if ($current === null) {
                continue;
            }

            $this->classCategories->put(new ClassCategoryEntity(
                classCategoryId: $current->classCategoryId,
                classNameId: $current->classNameId,
                name: $name,
            ));
            $updated++;
        }

        return $updated;
    }

    /** @param list<array{id: string, name: string, sortNo?: int}> $rows */
    private function updateMembers(array $rows): int
    {
        $updated = 0;
        foreach ($rows as $row) {
            [$id, $name] = $this->rowIdAndName($row);
            $current = $this->admins->item($id);
            if ($current === null) {
                continue;
            }

            $this->adminCommands->update(new AdminEntity(
                adminId: $current->adminId,
                loginId: $current->loginId,
                passwordHash: $current->passwordHash,
                name: $name,
                authority: $current->authority,
                work: $current->work,
                sortNo: $current->sortNo,
            ));
            $updated++;
        }

        return $updated;
    }

    /** @param list<array{id: string, name: string, sortNo?: int}> $rows */
    private function updateNews(array $rows): int
    {
        $updated = 0;
        foreach ($rows as $row) {
            [$id, $name] = $this->rowIdAndName($row);
            $current = $this->news->item($id);
            if ($current === null) {
                continue;
            }

            $this->news->put(new NewsEntity(
                newsId: $current->newsId,
                newsTitle: $name,
                newsDescription: $current->newsDescription,
                newsUrl: $current->newsUrl,
                publishDate: $current->publishDate,
                linkMethod: $current->linkMethod,
            ));
            $updated++;
        }

        return $updated;
    }

    /**
     * @param array{id: string, name: string, sortNo?: int} $row
     *
     * @return array{0: string, 1: string}
     */
    private function rowIdAndName(array $row): array
    {
        return [trim($row['id']), trim($row['name'])];
    }
}
