<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Admin tag list fetched — Final (Wave 9).
 */
final readonly class AdminTagListFetched
{
    public int $count;

    /** @var list<array{tagId: string, tagName: string}> */
    public array $tags;

    public function __construct(
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] TagStorageInterface $tags,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $tags->list();

        $this->count = count($rows);
        $this->tags = array_map(
            static fn (TagEntity $row): array => [
                'tagId' => $row->tagId,
                'tagName' => $row->tagName,
            ],
            $rows,
        );
    }
}
