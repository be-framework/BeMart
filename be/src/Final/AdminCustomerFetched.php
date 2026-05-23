<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\FavoriteEntity;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function array_sum;
use function count;

/**
 * Admin customer fetched — Final, the back-office detail view of one
 * customer.
 *
 *   GetAdminCustomerInput → AdminCustomerFetched  (Direct, safe read)
 *
 * Aggregates three independent reads:
 *
 *   - `CustomerQuery::findById` or `findByEmail` → full profile (richer than the
 *      customer's own goMypage projection — admins see birth, sex, job,
 *      full address, point balance, registrationDate analogue)
 *   - `OrderQuery::listByCustomer`     → full purchase history (capped
 *      at 50 — admins drill down further via dedicated screens) plus
 *      derived totalSpent across completed orders
 *   - `FavoriteStorage::listByCustomer` → full favorites list (not just
 *      the count, unlike goMypage's shallow projection)
 *
 * AUTHZ — cross-firewall (Wave 4 lesson, Wave 5 first consumer):
 *
 *   1. No admin session     → UnauthorizedAdminAccessException  (403)
 *   2. Unknown email        → CustomerNotFoundException         (404)
 *
 * The admin firewall check happens before existence is probed: an
 * anonymous-as-admin client has no business learning whether a given
 * email resolves to a customer. Existence precedes any further work so
 * the 404 distinction is preserved for legitimate admin callers — same
 * sequencing as MypageHistoryFetched's AUTHN → 404 → AUTHZ ladder.
 *
 * Unlike MypageHistoryFetched there is no ownership AUTHZ step: an
 * admin who has crossed the admin firewall is permitted to view ANY
 * customer's record (that is the point of the back-office screen).
 *
 * The `orders` and `favorites` lists are projections (not the
 * FinalizedOrderEntity / FavoriteEntity themselves) so the HTTP body
 * stays flat and the entities' internal layouts do not leak across the
 * AAA boundary — same convention as goMypage's `recentOrders` and
 * goMypageHistory's `items`.
 *
 * Mass-assignment safety: the adminId is read exclusively from the
 * AdminSession; it is NOT a constructor parameter. The only request-
 * controlled input is the customer selector (`customerId` preferred,
 * legacy `email` accepted). A malicious client cannot bypass admin
 * firewall by tampering with body fields.
 */
final readonly class AdminCustomerFetched
{
    public string $customerId;
    public string $email;
    public string $name01;
    public string $name02;
    public string|null $kana01;
    public string|null $kana02;
    public string|null $companyName;
    public string|null $phoneNumber;
    public string|null $postalCode;
    public int|null $pref;
    public string|null $addr01;
    public string|null $addr02;
    public string|null $birth;
    public int|null $sex;
    public int|null $job;
    public int $customerStatus;
    public int $initialPoint;

    /** @var list<array{orderNo: string, total: int, paymentTotal: int, orderDate: string, orderStatus: int}> */
    public array $orders;

    public int $orderCount;
    public int $totalSpent;

    /** @var list<array{productCode: string, productName: string, unitPrice: int}> */
    public array $favorites;

    public int $favoriteCount;

    public function __construct(
        #[Input] string $selector,
        #[Input] string $selectorType,
        #[Inject] AdminSessionInterface $adminSession,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] OrderQueryInterface $orderQuery,
        #[Inject] FavoriteStorageInterface $favorites,
    ) {
        // AUTHZ cross-firewall first — refuse non-admin requests before
        // probing existence (no enumeration via 404 vs 403 distinction).
        if ($adminSession->adminId() === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $customer = $selectorType === 'customerId'
            ? $customerQuery->findById($selector)
            : $customerQuery->findByEmail($selector);
        if ($customer === null) {
            throw new CustomerNotFoundException();
        }

        $orderList = $orderQuery->listByCustomer($customer->customerId, 50);
        $favoriteList = $favorites->listByCustomer($customer->customerId);

        $this->customerId = $customer->customerId;
        $this->email = $customer->email;
        $this->name01 = $customer->name01;
        $this->name02 = $customer->name02;
        $this->kana01 = $customer->kana01;
        $this->kana02 = $customer->kana02;
        $this->companyName = $customer->companyName;
        $this->phoneNumber = $customer->phoneNumber;
        $this->postalCode = $customer->postalCode;
        $this->pref = $customer->pref;
        $this->addr01 = $customer->addr01;
        $this->addr02 = $customer->addr02;
        $this->birth = $customer->birth;
        $this->sex = $customer->sex;
        $this->job = $customer->job;
        $this->customerStatus = $customer->customerStatus;
        $this->initialPoint = $customer->initialPoint;

        $this->orders = array_map(
            static fn (FinalizedOrderEntity $order): array => [
                'orderNo' => $order->orderNo,
                'total' => $order->total,
                'paymentTotal' => $order->paymentTotal,
                'orderDate' => $order->orderDate,
                'orderStatus' => $order->orderStatus,
            ],
            $orderList,
        );
        $this->orderCount = count($orderList);
        $this->totalSpent = array_sum(array_map(
            static fn (FinalizedOrderEntity $order): int => $order->total,
            $orderList,
        ));

        $this->favorites = array_map(
            static fn (FavoriteEntity $favorite): array => [
                'productCode' => $favorite->productCode,
                'productName' => $favorite->productName,
                'unitPrice' => $favorite->unitPrice,
            ],
            $favoriteList,
        );
        $this->favoriteCount = count($favoriteList);
    }
}
