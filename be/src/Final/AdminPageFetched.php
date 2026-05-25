<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\PageNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin page fetched — Final, single-row detail view (Wave 9).
 *
 * AUTHZ ladder:
 *   1. No admin session → UnauthorizedAdminAccessException (403)
 *   2. Unknown pageId   → PageNotFoundException            (404)
 */
final readonly class AdminPageFetched
{
    public string $pageId;
    public string $pageName;
    public string $pageUrl;
    public string $pageFileName;
    public int $pageEditType;

    public function __construct(
        #[Input] string $pageId,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] PageStorageInterface $pages,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $row = $pages->getById($pageId);
        if ($row === null) {
            throw new PageNotFoundException();
        }

        $this->pageId = $row->pageId;
        $this->pageName = $row->pageName;
        $this->pageUrl = $row->pageUrl;
        $this->pageFileName = $row->pageFileName;
        $this->pageEditType = $row->pageEditType;
    }
}
