<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\ClassNameEntity;
use MyVendor\BeMart\Be\Reason\Query\ClassNameStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Admin class-name list fetched — Final, the back-office view of every
 * product variant axis (Wave 7).
 *
 *   GetAdminClassNameListInput → AdminClassNameListFetched
 *     (Direct, safe read, admin AUTHZ)
 */
final readonly class AdminClassNameListFetched
{
    public int $count;

    /** @var list<array{classNameId: string, name: string}> */
    public array $classNames;

    public function __construct(
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] ClassNameStorageInterface $classNames,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $classNames->list();

        $this->count = count($rows);
        $this->classNames = array_map(
            static fn (ClassNameEntity $row): array => [
                'classNameId' => $row->classNameId,
                'name' => $row->name,
            ],
            $rows,
        );
    }
}
