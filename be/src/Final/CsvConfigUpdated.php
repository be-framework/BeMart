<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CsvColumnConfigEntity;
use MyVendor\BeMart\Be\Reason\Query\CsvColumnConfigStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\Param\CsvColumnConfigList;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function count;

/**
 * CSV column configuration updated — Final, proof an admin replaced
 * the column vector for one csvType (Wave 9, doUpdateCsv).
 *
 *   UpdateCsvInput → CsvConfigUpdated  (Direct, idempotent)
 *
 * AUTHZ — admin firewall:
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess
 *
 * Public surface mirrors the input shape so the admin form's "saved
 * what?" confirmation is a straight echo. `count` reports how many
 * column rows were persisted for the csvType.
 *
 * Idempotency: replaying the same body lands the same row set; the
 * storage replaces the per-type vector atomically (no merge).
 */
final readonly class CsvConfigUpdated
{
    public int $csvType;

    /** @var list<array{columnName: string, enabled: bool, sortNo: int}> */
    public array $columns;

    public int $count;

    /**
     * @param list<array{columnName: string, enabled: bool, sortNo: int}> $columns
     */
    public function __construct(
        #[Input] int $csvType,
        #[Input] array $columns,
        #[Inject] AdminSession $adminSession,
        #[Inject] CsvColumnConfigStorageInterface $csvColumnConfigStorage,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entries = [];
        foreach ($columns as $column) {
            $entries[] = new CsvColumnConfigEntity(
                csvType: $csvType,
                columnName: $column['columnName'],
                enabled: $column['enabled'],
                sortNo: $column['sortNo'],
            );
        }

        $csvColumnConfigStorage->replaceType($csvType, CsvColumnConfigList::fromArray($entries));

        $this->csvType = $csvType;
        $this->columns = $columns;
        $this->count = count($entries);
    }
}
