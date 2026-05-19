<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\LayoutEntity;
use MyVendor\BeMart\Be\Reason\Query\LayoutStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Admin layout list fetched — Final (Wave 9).
 */
final readonly class AdminLayoutListFetched
{
    public int $count;

    /** @var list<array{layoutId: string, layoutName: string, deviceType: int}> */
    public array $layouts;

    public function __construct(
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] LayoutStorageInterface $layouts,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $layouts->list();

        $this->count = count($rows);
        $this->layouts = array_map(
            static fn (LayoutEntity $row): array => [
                'layoutId' => $row->layoutId,
                'layoutName' => $row->layoutName,
                'deviceType' => $row->deviceType,
            ],
            $rows,
        );
    }
}
