<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\InsufficientAuthorityException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AuthorityRoleUpdated;
use MyVendor\BeMart\Be\Input\UpdateAuthorityRoleInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doUpdateAuthorityRole — 権限ルール更新 (Wave 8).
 *
 *   POST → flip the persisted `authority` column on one admin.
 *
 * Role-flip is a sub-resource of the admin member rather than a
 * method on {@see Member} because its semantics carry distinct
 * privilege-escalation risk (the Final enforces
 * `caller.authority < target.authority`). Surfacing a separate URL
 * (`/admin/authority-role`) keeps the AUTHZ story explicit and
 * matches the ALPS-level separation of `doUpdateMember` vs
 * `doUpdateAuthorityRole`. Same architectural choice as Wave 7's
 * `doUpdateOrderStatus` → {@see OrderStatus}.
 *
 * Choice of POST (not PATCH): BEAR.Sunday's natural verb set is GET /
 * POST / PUT / DELETE — PATCH is not first-class. POST against this
 * sub-resource carries the same shape as Wave 7 OrderStatus and Wave
 * 6 DeleteCustomer (POST + CSRF + target id + new value).
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (authority format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - AdminNotFoundException                → 404 (unknown loginId)
 *   - InsufficientAuthorityException        → 403 (priv-escalation refused)
 *
 * Idempotency: when the supplied `authority` matches the persisted
 * value, the projection carries `changed=false` and the storage is
 * untouched. Replay returns 200 with the same body shape — mirrors
 * AdminOrderStatusUpdated's `changed` discipline (Wave 7).
 *
 * Mass-assignment safety: only `loginId` (target) and `authority`
 * (new value) are accepted; no path here reaches the other
 * dtb_member columns.
 */
class AuthorityRole extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * Wave 8: both `loginId` and `authority` are admin-form input
     * (loginId from the row selection, authority from a dropdown of
     * mtb_authority values).
     *
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $authority
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goMember', href: 'page://self/admin/member', method: 'get')]
    #[Link(rel: 'goMemberList', href: 'page://self/admin/member-list')]
    public function onPost(
        string $loginId,
        int $authority,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateAuthorityRoleInput(
                loginId: $loginId,
                authority: $authority,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
            ];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (AdminNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された管理者は見つかりませんでした。'];

            return $this;
        } catch (InsufficientAuthorityException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作を行う権限がありません。'];

            return $this;
        }

        assert($final instanceof AuthorityRoleUpdated);

        $this->code = Code::OK;
        $this->body = [
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'previousAuthority' => $final->previousAuthority,
            'authority' => $final->authority,
            'changed' => $final->changed,
        ];

        return $this;
    }
}
