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
 * Page deleted — Final, proof one CMS page was removed (Wave 9,
 * idempotent).
 *
 * EC-CUBE refuses deletion when pageEditType >= 2 (system pages).
 * For this iteration the in-memory store enforces the EDIT_TYPE_USER
 * guard so attempted system-page deletion throws PageNotFoundException
 * (404 to the caller — masking system-page existence). Phase 2 can
 * differentiate via a dedicated exception once a real consumer needs
 * the distinction.
 */
final readonly class PageDeleted
{
    public string $pageId;

    public function __construct(
        #[Input] string $pageId,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] PageStorageInterface $pages,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $row = $pages->getById($pageId);
        if ($row === null || $row->pageEditType >= 2) {
            throw new PageNotFoundException();
        }

        $pages->remove($pageId);

        $this->pageId = $pageId;
    }
}
