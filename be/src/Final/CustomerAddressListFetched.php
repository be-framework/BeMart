<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Reason\Entity\AddressEntity;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use Ray\Di\Di\Inject;

use function array_map;
use function count;

/**
 * Customer address list fetched — Final, the projected book of
 * shipping addresses for the logged-in customer.
 *
 *   GetCustomerAddressListInput → CustomerAddressListFetched
 *     (Direct, safe read)
 *
 * AUTHN: customerId comes from CustomerSession. A null session
 * raises UnauthenticatedException — the BEAR layer maps this to 401.
 *
 * `addresses` is exposed as a flat projection list (not the
 * AddressEntity itself) so the HTTP body shape stays stable across
 * any future schema additions on the entity side — same convention
 * as MypageHistoryFetched::items.
 */
final readonly class CustomerAddressListFetched
{
    public string $customerId;
    public int $count;

    /** @var list<array{addressId: string, name01: string, name02: string, kana01: string|null, kana02: string|null, companyName: string|null, phoneNumber: string|null, postalCode: string, pref: int, prefName: string|null, addr01: string, addr02: string}> */
    public array $addresses;

    public function __construct(
        #[Inject] CustomerSession $session,
        #[Inject] AddressStorageInterface $addresses,
    ) {
        $sessionCustomerId = $session->customerId;
        if ($sessionCustomerId === null) {
            throw new UnauthenticatedException();
        }

        $rows = $addresses->listByCustomer($sessionCustomerId);

        $this->customerId = $sessionCustomerId;
        $this->count = count($rows);
        $this->addresses = array_map(
            static fn (AddressEntity $row): array => [
                'addressId' => $row->addressId,
                'name01' => $row->name01,
                'name02' => $row->name02,
                'kana01' => $row->kana01,
                'kana02' => $row->kana02,
                'companyName' => $row->companyName,
                'phoneNumber' => $row->phoneNumber,
                'postalCode' => $row->postalCode,
                'pref' => $row->pref,
                'prefName' => $row->prefName,
                'addr01' => $row->addr01,
                'addr02' => $row->addr02,
            ],
            $rows,
        );
    }
}
