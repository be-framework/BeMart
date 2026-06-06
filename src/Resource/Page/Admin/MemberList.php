<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MemberListFetched;
use MyVendor\BeMart\Be\Input\GetMemberListInput;

use function assert;

/**
 * EC-CUBE goMemberList — 管理者一覧 (Wave 8, admin member grid).
 *
 * Safe read. No CSRF (read-only). Admin-only — the Be Final raises
 * {@see UnauthorizedAdminAccessException} when
 * {@see \MyVendor\BeMart\Be\Reason\Service\AdminSession}
 * reports no admin session; we map that to 403. Distinct from
 * customer-side 401 (admin and customer firewalls are parallel,
 * Wave 4 decision).
 *
 * Failure mapping:
 *   - SemanticVariableException             → 400 (filter / paging format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *
 * Filter scope (Wave 8 first iteration):
 *   - nameKeyword  — substring on admin's display `name`
 *   - limit / offset — paginated grid
 *
 * Hypermedia: links to per-admin detail (goMember), to the create
 * affordance (doCreateMember), and to the role-flip surface
 * (doUpdateAuthorityRole). The latter two are admin sub-resources
 * surfaced here per the bear-hypermedia discipline.
 */
class MemberList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * Wave 8: filter / paging knobs are admin-form input. Same taint
     * discipline as the customer-list and order-list variants.
     *
     * @psalm-taint-source input $nameKeyword
     * @psalm-taint-source input $limit
     * @psalm-taint-source input $offset
     */
    #[Link(rel: 'goMember', href: 'page://self/admin/member', method: 'get')]
    #[Link(rel: 'doCreateMember', href: 'page://self/admin/member', method: 'post')]
    #[Link(rel: 'doUpdateAuthorityRole', href: 'page://self/admin/authority-role', method: 'post')]
    public function onGet(
        string|null $nameKeyword = null,
        int $limit = 50,
        int $offset = 0,
    ): static {
        $final = ($this->becoming)(new GetMemberListInput(
            nameKeyword: $nameKeyword,
            limit: $limit,
            offset: $offset,
        ));

        assert($final instanceof MemberListFetched);

        $this->code = Code::OK;
        $this->body = [
            'members' => $final->members,
            'count' => $final->count,
            'filters' => $final->filters,
        ];

        return $this;
    }
}
