<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\LayoutNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\LayoutStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Admin layout fetched — Final, single-row edit view.
 */
final readonly class AdminLayoutFetched
{
    public string $layoutId;
    public string $layoutName;
    public int $deviceType;

    public function __construct(
        #[Input] string $layoutId,
        #[Inject] AdminSession $adminSession,
        #[Inject] LayoutStorageInterface $layouts,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $row = $layouts->item($layoutId);
        if ($row === null) {
            throw new LayoutNotFoundException();
        }

        $this->layoutId = $row->layoutId;
        $this->layoutName = $row->layoutName;
        $this->deviceType = $row->deviceType;
    }
}
