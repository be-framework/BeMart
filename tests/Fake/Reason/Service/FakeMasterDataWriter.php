<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\MasterDataWriterInterface;
use Override;

use function count;

/** Recording fake for the master-data bulk-write boundary. */
final class FakeMasterDataWriter implements MasterDataWriterInterface
{
    /** @var list<array{masterType: string, rows: list<array{id: string, name: string, sortNo?: int}>}> */
    public array $writes = [];

    /**
     * @param list<array{id: string, name: string, sortNo?: int}> $rows
     */
    #[Override]
    public function update(string $masterType, array $rows): int
    {
        $this->writes[] = ['masterType' => $masterType, 'rows' => $rows];

        return count($rows);
    }
}
