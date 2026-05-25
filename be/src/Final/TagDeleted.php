<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\TagNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Tag deleted — Final (Wave 9, idempotent). ProductTag detachment is
 * Phase 2 scope; the in-memory store only drops the tag row.
 */
final readonly class TagDeleted
{
    public string $tagId;

    public function __construct(
        #[Input] string $tagId,
        #[Inject] AdminSession $adminSession,
        #[Inject] TagStorageInterface $tags,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($tags->item($tagId) === null) {
            throw new TagNotFoundException();
        }

        $tags->delete($tagId);

        $this->tagId = $tagId;
    }
}
