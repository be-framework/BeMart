<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Param;

use Override;
use MyVendor\BeMart\Be\Reason\Entity\CsvColumnConfigEntity;
use Ray\MediaQuery\ToScalarInterface;

use function array_map;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class CsvColumnConfigList implements ToScalarInterface
{
    /** @param list<CsvColumnConfigEntity> $entries */
    public function __construct(private array $entries) {}

    /** @param list<CsvColumnConfigEntity> $entries */
    public static function fromArray(array $entries): self
    {
        return new self($entries);
    }

    /** @return list<CsvColumnConfigEntity> */
    public function values(): array
    {
        return $this->entries;
    }

    #[Override]
    public function toScalar(): string
    {
        return json_encode(array_map(
            static fn (CsvColumnConfigEntity $entry): array => [
                'csvType' => $entry->csvType,
                'columnName' => $entry->columnName,
                'enabled' => $entry->enabled,
                'sortNo' => $entry->sortNo,
            ],
            $this->entries,
        ), JSON_THROW_ON_ERROR);
    }
}
