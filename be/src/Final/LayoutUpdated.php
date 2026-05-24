<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\LayoutNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\LayoutEntity;
use MyVendor\BeMart\Be\Reason\Query\LayoutStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Layout updated — Final (Wave 9, idempotent).
 */
final readonly class LayoutUpdated
{
    public string $layoutId;
    public string $layoutName;
    public int $deviceType;

    public function __construct(
        #[Input] string $layoutId,
        #[Input] string|null $layoutName,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] LayoutStorageInterface $layouts,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $current = $layouts->item($layoutId);
        if ($current === null) {
            throw new LayoutNotFoundException();
        }

        $merged = new LayoutEntity(
            layoutId: $current->layoutId,
            layoutName: $layoutName ?? $current->layoutName,
            deviceType: $current->deviceType,
        );

        $layouts->put($merged);

        $this->layoutId = $merged->layoutId;
        $this->layoutName = $merged->layoutName;
        $this->deviceType = $merged->deviceType;
    }
}
