<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Provider\TagIdProvider;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Tag created — Final (Wave 9).
 */
final readonly class TagCreated
{
    public string $tagId;
    public string $tagName;

    public function __construct(
        #[Input] string $tagName,
        #[Inject] AdminSession $adminSession,
        #[Inject] TagStorageInterface $tags,
        #[Inject] TagIdProvider $ids,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new TagEntity(
            tagId: $ids->get(),
            tagName: $tagName,
        );

        $tags->put($entity);

        $this->tagId = $entity->tagId;
        $this->tagName = $entity->tagName;
    }
}
