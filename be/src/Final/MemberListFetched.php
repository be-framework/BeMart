<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Entity\AdminEntity;
use MyVendor\BeMart\Be\Reason\Query\AdminQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function array_map;
use function array_slice;
use function count;

/**
 * Member list fetched — Final, admin grid projection of admins.
 *
 *   GetMemberListInput → MemberListFetched  (Direct, safe read)
 *
 * AUTHZ — admin firewall (Wave 4 contract):
 *   AdminSession::$adminId === null → UnauthorizedAdminAccess
 *
 * Admin-only endpoint. Mirrors Wave 5 CustomerListFetched: no
 * `customer-self` 401 here — a logged-in customer who hits the admin
 * endpoint is 403 (cross-firewall), not 401 (no session).
 *
 * Filter scope (Wave 8 first iteration): substring `nameKeyword` on
 * `name` plus offset / limit pagination. When nameKeyword is null we
 * use listAll directly; otherwise we fetch the search results and
 * slice in the Final so the storage's search method stays minimal.
 *
 * Public surface — SAFE projection of AdminEntity. The full entity
 * carries `passwordHash` which MUST NOT leak into the HTTP body; the
 * admin grid only needs the identification + role + work fields.
 */
final readonly class MemberListFetched
{
    /** @var list<array{adminId: string, loginId: string, name: string, authority: int, work: int, sortNo: int}> */
    public array $members;

    public int $count;

    /** @var array{nameKeyword: string|null, limit: int, offset: int} */
    public array $filters;

    public function __construct(
        #[Input] string|null $nameKeyword,
        #[Input] int $limit,
        #[Input] int $offset,
        #[Inject] AdminSession $adminSession,
        #[Inject] AdminQueryInterface $adminQuery,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        if ($nameKeyword === null || $nameKeyword === '') {
            $rows = $adminQuery->list($limit, $offset);
        } else {
            $matches = $adminQuery->search($nameKeyword);
            $rows = array_slice($matches, $offset, $limit);
        }

        $this->members = array_map(
            static fn (AdminEntity $a): array => [
                'adminId' => $a->adminId,
                'loginId' => $a->loginId,
                'name' => $a->name,
                'authority' => $a->authority,
                'work' => $a->work,
                'sortNo' => $a->sortNo,
            ],
            $rows,
        );
        $this->count = count($rows);
        $this->filters = [
            'nameKeyword' => $nameKeyword,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }
}
