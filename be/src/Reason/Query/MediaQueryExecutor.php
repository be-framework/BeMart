<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use Aura\Sql\ExtendedPdoInterface;
use Ray\MediaQuery\Result\AffectedRows;
use Ray\MediaQuery\Result\InsertedRow;
use Ray\MediaQuery\SqlQueryInterface;

use function assert;
use function is_array;

final class MediaQueryExecutor
{
    public function __construct(
        private readonly SqlQueryInterface $sql,
        private readonly ExtendedPdoInterface $connection,
    ) {
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>|null
     */
    public function row(string $queryId, array $values = []): array|null
    {
        $row = $this->sql->getRow($queryId, $values);
        if ($row === null) {
            return null;
        }

        assert(is_array($row));

        /** @var array<string, mixed> $row */
        return $row;
    }

    /**
     * @param array<string, mixed> $values
     * @return list<array<string, mixed>>
     */
    public function rows(string $queryId, array $values = []): array
    {
        $rows = $this->sql->getRowList($queryId, $values);

        /** @var list<array<string, mixed>> $rows */
        return $rows;
    }

    /** @param array<string, mixed> $values */
    public function exec(string $queryId, array $values = []): void
    {
        $this->sql->exec($queryId, $values);
    }

    /** @param array<string, mixed> $values */
    public function affected(string $queryId, array $values = []): int
    {
        $result = $this->sql->execPostQuery($queryId, $values, AffectedRows::class);
        assert($result instanceof AffectedRows);

        return $result->count;
    }

    /** @param array<string, mixed> $values */
    public function insertedId(string $queryId, array $values = []): string|null
    {
        $result = $this->sql->execPostQuery($queryId, $values, InsertedRow::class);
        assert($result instanceof InsertedRow);

        return $result->id;
    }

    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }
}
