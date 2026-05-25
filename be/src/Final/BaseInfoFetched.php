<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Query\BaseInfoStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;

/**
 * BaseInfo fetched — Final, the read-side projection of dtb_base_info
 * (Wave 9, goBaseInfo).
 *
 *   GetBaseInfoInput → BaseInfoFetched  (Direct, safe read)
 *
 * AUTHZ — admin firewall (Wave 4 contract):
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess
 *
 * Public surface mirrors the Wave 8ε {@see BaseInfoUpdated} Final so the
 * admin form pre-population matches the round-trip POST payload field
 * for field. dtb_base_info is a single-row table — no count / paging.
 */
final readonly class BaseInfoFetched
{
    public string $shopName;
    public string|null $shopKana;
    public string|null $shopNameEng;
    public string|null $companyName;
    public string|null $postalCode;
    public int|null $pref;
    public string|null $addr01;
    public string|null $addr02;
    public string|null $phoneNumber;
    public string|null $businessHour;
    public string|null $shopEmail01;
    public string|null $shopMessage;

    public function __construct(
        #[Inject] AdminSession $adminSession,
        #[Inject] BaseInfoStorageInterface $baseInfoStorage,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $row = $baseInfoStorage->item();
        $this->shopName = $row->shopName;
        $this->shopKana = $row->shopKana;
        $this->shopNameEng = $row->shopNameEng;
        $this->companyName = $row->companyName;
        $this->postalCode = $row->postalCode;
        $this->pref = $row->pref;
        $this->addr01 = $row->addr01;
        $this->addr02 = $row->addr02;
        $this->phoneNumber = $row->phoneNumber;
        $this->businessHour = $row->businessHour;
        $this->shopEmail01 = $row->shopEmail01;
        $this->shopMessage = $row->shopMessage;
    }
}
