<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\PageEntity;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\PageIdGeneratorInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Page created — Final, proof a new free CMS page was persisted by an
 * admin operation (Wave 9). New pages are always EDIT_TYPE_USER (0).
 *
 * Phase 2 scope: template-file generation, route registration, and
 * sitemap regeneration. The in-memory store only records the row.
 */
final readonly class PageCreated
{
    public string $pageId;
    public string $pageName;
    public string $pageUrl;
    public string $pageFileName;
    public int $pageEditType;

    public function __construct(
        #[Input] string $pageName,
        #[Input] string $pageUrl,
        #[Input] string $pageFileName,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] PageStorageInterface $pages,
        #[Inject] PageIdGeneratorInterface $idGenerator,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $entity = new PageEntity(
            pageId: $idGenerator->generate()->value,
            pageName: $pageName,
            pageUrl: $pageUrl,
            pageFileName: $pageFileName,
            pageEditType: 0, // EDIT_TYPE_USER
        );

        $pages->put($entity);

        $this->pageId = $entity->pageId;
        $this->pageName = $entity->pageName;
        $this->pageUrl = $entity->pageUrl;
        $this->pageFileName = $entity->pageFileName;
        $this->pageEditType = $entity->pageEditType;
    }
}
