<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\PageNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\PageEntity;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Page updated — Final, proof one CMS page row was edited (Wave 9,
 * idempotent).
 *
 * EC-CUBE refuses name / URL edits on system pages (pageEditType >= 2);
 * for this iteration the merge is unconditional once AUTHZ + existence
 * pass. Phase 2 will add the EDIT_TYPE guard when wiring the real
 * Twig regeneration.
 */
final readonly class PageUpdated
{
    public string $pageId;
    public string $pageName;
    public string $pageUrl;
    public string $pageFileName;
    public int $pageEditType;

    public function __construct(
        #[Input] string $pageId,
        #[Input] string|null $pageName,
        #[Input] string|null $pageUrl,
        #[Input] string|null $pageFileName,
        #[Inject] AdminSession $adminSession,
        #[Inject] PageStorageInterface $pages,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $pages->item($pageId);
        if ($current === null) {
            throw new PageNotFoundException();
        }

        $merged = new PageEntity(
            pageId: $current->pageId,
            pageName: $pageName ?? $current->pageName,
            pageUrl: $pageUrl ?? $current->pageUrl,
            pageFileName: $pageFileName ?? $current->pageFileName,
            pageEditType: $current->pageEditType,
        );

        $pages->put($merged);

        $this->pageId = $merged->pageId;
        $this->pageName = $merged->pageName;
        $this->pageUrl = $merged->pageUrl;
        $this->pageFileName = $merged->pageFileName;
        $this->pageEditType = $merged->pageEditType;
    }
}
