<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\BaseInfoEntity;
use MyVendor\BeMart\Be\Reason\Query\BaseInfoStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * BaseInfo updated — Final, proof an admin updated the shop base info.
 *
 *   UpdateBaseInfoInput → BaseInfoUpdated (Direct, idempotent)
 *
 * AUTHZ — admin firewall: AdminSession::adminId() === null →
 * UnauthorizedAdminAccessException (403).
 *
 * dtb_base_info is a single-row table; the update is a wholesale
 * replacement. Idempotency surfaces as `changed=false` when the new
 * row is field-by-field identical to the old.
 *
 * Mass-assignment safety: only the shop-info columns (shopName +
 * address + contact + free-form shop message) are accepted; the
 * non-shop-info dtb_base_info columns (point rate, tax settings, …)
 * are not reachable through this transition.
 */
final readonly class BaseInfoUpdated
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
    public bool $changed;

    public function __construct(
        #[Input] string $shopName,
        #[Input] string|null $shopKana,
        #[Input] string|null $shopNameEng,
        #[Input] string|null $companyName,
        #[Input] string|null $postalCode,
        #[Input] int|null $pref,
        #[Input] string|null $addr01,
        #[Input] string|null $addr02,
        #[Input] string|null $phoneNumber,
        #[Input] string|null $businessHour,
        #[Input] string|null $shopEmail01,
        #[Input] string|null $shopMessage,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] BaseInfoStorageInterface $baseInfoStorage,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $previous = $baseInfoStorage->item();
        $next = new BaseInfoEntity(
            shopName: $shopName,
            shopKana: $shopKana,
            shopNameEng: $shopNameEng,
            companyName: $companyName,
            postalCode: $postalCode,
            pref: $pref,
            addr01: $addr01,
            addr02: $addr02,
            phoneNumber: $phoneNumber,
            businessHour: $businessHour,
            shopEmail01: $shopEmail01,
            shopMessage: $shopMessage,
        );

        $changed = $previous != $next; // value-equality (readonly DTOs)

        if ($changed) {
            $baseInfoStorage->put($next);
        }

        $this->shopName = $next->shopName;
        $this->shopKana = $next->shopKana;
        $this->shopNameEng = $next->shopNameEng;
        $this->companyName = $next->companyName;
        $this->postalCode = $next->postalCode;
        $this->pref = $next->pref;
        $this->addr01 = $next->addr01;
        $this->addr02 = $next->addr02;
        $this->phoneNumber = $next->phoneNumber;
        $this->businessHour = $next->businessHour;
        $this->shopEmail01 = $next->shopEmail01;
        $this->shopMessage = $next->shopMessage;
        $this->changed = $changed;
    }
}
