<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function count;

/**
 * Customer list fetched — Final, admin-side filter search projection.
 *
 *   GetCustomerListInput → CustomerListFetched  (Direct, safe read)
 *
 * AUTHZ — admin firewall (Wave 4 contract):
 *   AdminSessionInterface::adminId() === null → UnauthorizedAdminAccess
 *
 * Admin-only endpoint. Unlike the customer dashboard (which carries 401
 * for "no session"), this is a cross-firewall check: a logged-in
 * customer who tries to call /admin/customer-list is NOT lacking
 * authentication, they are unauthorized to enter the admin firewall.
 * The exception type carries that distinction across the AAA boundary
 * (BEAR maps it to 403 by convention).
 *
 * Filter scope (Wave 5 first iteration): the two keyword filters are
 * delegated to CustomerQueryInterface::search; both nullable, ANDed
 * when both are present. The remaining EC-CUBE filters (phoneNumber,
 * dateRange, purchaseAmount) are documented as Phase 2 deferral on
 * {@see \MyVendor\BeMart\Be\Input\GetCustomerListInput}.
 *
 * Public surface — shallow projection of CustomerEntity, mirroring the
 * Pilot 12 `MypageHistoryFetched::items` convention. The full
 * CustomerEntity (passwordHash, secretKey, …) deliberately does NOT
 * leak into the HTTP body; the admin grid only needs the identification
 * and contact fields.
 */
final readonly class CustomerListFetched
{
    /** @var list<array{customerId: string, email: string, name01: string, name02: string, customerStatus: int, postalCode: string|null}> */
    public array $customers;

    public int $count;

    /** @var array{nameKeyword: string|null, emailKeyword: string|null} */
    public array $filters;

    public function __construct(
        #[Input] string|null $nameKeyword,
        #[Input] string|null $emailKeyword,
        #[Input] int $limit,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] CustomerQueryInterface $customerQuery,
    ) {
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $rows = $customerQuery->search($nameKeyword, $emailKeyword, $limit);

        $this->customers = array_map(
            static fn (CustomerEntity $c): array => [
                'customerId' => $c->customerId,
                'email' => $c->email,
                'name01' => $c->name01,
                'name02' => $c->name02,
                'customerStatus' => $c->customerStatus,
                'postalCode' => $c->postalCode,
            ],
            $rows,
        );
        $this->count = count($rows);
        $this->filters = [
            'nameKeyword' => $nameKeyword,
            'emailKeyword' => $emailKeyword,
        ];
    }
}
