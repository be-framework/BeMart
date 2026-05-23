<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\TagEntity;
use MyVendor\BeMart\Be\Reason\Query\TagStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\TagIdGeneratorInterface;
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
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] TagStorageInterface $tags,
        #[Inject] TagIdGeneratorInterface $idGenerator,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new TagEntity(
            tagId: $idGenerator->generate()->value(),
            tagName: $tagName,
        );

        $tags->put($entity);

        $this->tagId = $entity->tagId;
        $this->tagName = $entity->tagName;
    }
}
