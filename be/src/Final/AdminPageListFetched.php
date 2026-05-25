<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\PageEntity;
use MyVendor\BeMart\Be\Reason\Query\PageStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Admin page list fetched — Final, the back-office view of every CMS
 * page as a flat list (Wave 9 CMS slice).
 *
 *   GetAdminPageListInput → AdminPageListFetched (Direct, safe read)
 *
 * AUTHZ: refuses non-admin requests via
 * {@see UnauthorizedAdminAccessException} (mapped to 403 by the
 * Resource layer).
 */
final readonly class AdminPageListFetched
{
    public int $count;

    /** @var list<array{pageId: string, pageName: string, pageUrl: string, pageFileName: string, pageEditType: int}> */
    public array $pages;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] PageStorageInterface $pages,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $pages->list();

        $this->count = count($rows);
        $this->pages = array_map(
            static fn (PageEntity $row): array => [
                'pageId' => $row->pageId,
                'pageName' => $row->pageName,
                'pageUrl' => $row->pageUrl,
                'pageFileName' => $row->pageFileName,
                'pageEditType' => $row->pageEditType,
            ],
            $rows,
        );
    }
}
