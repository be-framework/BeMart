<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\InsufficientAuthorityException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AuthorityRulesUpdated;
use MyVendor\BeMart\Be\Final\AuthorityRoleUpdated;
use MyVendor\BeMart\Be\Input\UpdateAuthorityRulesInput;
use MyVendor\BeMart\Be\Input\UpdateAuthorityRoleInput;
use MyVendor\BeMart\Be\Reason\Query\AuthorityRoleRuleStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Csrf\CsrfTokenInterface;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function array_map;

/**
 * EC-CUBE doUpdateAuthorityRole — 権限ルール更新 (Wave 8).
 *
 *   GET  → render URL deny rules from `dtb_authority_role`.
 *   POST → either update URL deny rules or flip one admin member's
 *          persisted `authority` column.
 *
 * EC-CUBE's HTML form posts `AuthorityRoles[*][Authority]` and
 * `AuthorityRoles[*][deny_url]` to edit URL deny rules. BeMart stores
 * those rows in `dtb_authority_role` and redirects back to the same
 * page for browser PRG/readback. The same ALPS transition also keeps
 * the legacy member role-flip shape (`loginId`, `authority`) because
 * existing member-management workflow uses this resource as a distinct
 * authorization-sensitive action.
 *
 * Choice of POST (not PATCH): BEAR.Sunday's natural verb set is GET /
 * POST / PUT / DELETE — PATCH is not first-class. POST carries the
 * same browser-form shape as Wave 7 OrderStatus and Wave 6
 * DeleteCustomer (POST + CSRF + form body).
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (authority format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - AdminNotFoundException                → 404 (unknown loginId)
 *   - InsufficientAuthorityException        → 403 (priv-escalation refused)
 *
 * Idempotency: when the supplied member `authority` matches the
 * persisted value, the projection carries `changed=false` and the
 * storage is untouched. URL deny rule updates replace the submitted
 * rule set and return the saved rows.
 *
 * Mass-assignment safety: member role-flip accepts only `loginId`
 * (target) and `authority` (new value). URL deny rule edit accepts
 * only `AuthorityRoles[*][Authority]` and `AuthorityRoles[*][deny_url]`.
 */
class AuthorityRole extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly AdminSession $adminSession,
        private readonly AuthorityRoleRuleStorageInterface $authorityRules,
        private readonly CsrfTokenInterface $csrf,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * Phase 3 admin HTML Tier-2: render the authority-rule management
     * screen. The ALPS transition covers `doUpdateAuthorityRole`; EC-CUBE
     * uses the same resource to edit URL-deny rules stored in
     * `dtb_authority_role`.
     */
    #[Alps('doUpdateAuthorityRole')]
    #[JsonSchema(schema: 'get-admin-authority-role.json')]
    #[Link(rel: 'doUpdateAuthorityRole', href: 'page://self/admin/authority-role', method: 'post')]
    #[Link(rel: 'goMemberList', href: 'page://self/admin/member-list')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $rules = $this->authorityRules->list();
        $ruleRows = $rules === []
            ? [['authority' => 1, 'denyUrl' => '/setting/system/security']]
            : array_map(
                static fn ($rule): array => [
                    'authority' => $rule->authority,
                    'denyUrl' => $rule->denyUrl,
                ],
                $rules,
            );

        $this->code = Code::OK;
        $this->body = [
            'authorityOptions' => [
                ['id' => 0, 'label' => 'システム管理者'],
                ['id' => 1, 'label' => '店舗オーナー'],
            ],
            'rules' => $ruleRows,
            'csrfToken' => $this->csrf->issue(),
        ];

        return $this;
    }

    /**
     * Wave 8: browser form input for URL deny rules. The primary HTML
     * shape carries CSRF at the request boundary plus
     * `AuthorityRoles[*][Authority]` / `AuthorityRoles[*][deny_url]`.
     * The legacy member role-flip shape (`loginId`, `authority`)
     * remains supported for member workflow.
     *
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $authority
     */
    #[Alps('doUpdateAuthorityRole')]
    #[JsonSchema(schema: 'post-admin-authority-role.json', params: 'post-admin-authority-role.param.json')]
    #[Link(rel: 'goMember', href: 'page://self/admin/member', method: 'get')]
    #[Link(rel: 'goMemberList', href: 'page://self/admin/member-list')]
    #[Link(rel: 'goLoginHistoryList', href: 'page://self/admin/login-history')]
    #[CsrfToken]
    public function onPost(
        string|null $loginId = null,
        int|null $authority = null,
        array $AuthorityRoles = [],
    ): static {
        if ($AuthorityRoles !== []) {
            $final = ($this->becoming)(new UpdateAuthorityRulesInput(
                authorityRoles: $AuthorityRoles,
            ));

            assert($final instanceof AuthorityRulesUpdated);

            ($this->mutationResponse)($this, Code::OK, '/admin/authority-role');
            $this->body = [
                'transitionId' => 'doUpdateAuthorityRole',
                'count' => $final->count,
                'rules' => $final->rules,
                'message' => '権限設定を更新しました。',
            ];

            return $this;
        }

        if ($loginId === null || $authority === null) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => 'loginId と authority が必要です。'];

            return $this;
        }

        $final = ($this->becoming)(new UpdateAuthorityRoleInput(
            loginId: $loginId,
            authority: $authority,
        ));

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
