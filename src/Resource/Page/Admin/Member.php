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
use MyVendor\BeMart\Be\Exception\LoginIdAlreadyTakenException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MemberCreated;
use MyVendor\BeMart\Be\Final\MemberDeleted;
use MyVendor\BeMart\Be\Final\MemberFetched;
use MyVendor\BeMart\Be\Final\MemberUpdated;
use MyVendor\BeMart\Be\Input\CreateMemberInput;
use MyVendor\BeMart\Be\Input\DeleteMemberInput;
use MyVendor\BeMart\Be\Input\GetMemberInput;
use MyVendor\BeMart\Be\Input\UpdateMemberInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\AdminMemberForm;
use Ray\WebFormModule\FormFactory;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goMember / doCreateMember / doUpdateMember / doDeleteMember
 * — 管理者 (Wave 8). The four verbs on the admin-member detail
 * resource share one URL (`page://self/admin/member`) and dispatch by
 * HTTP method:
 *
 *   - GET    → goMember            (safe read, no CSRF)
 *   - POST   → doCreateMember      (unsafe, CSRF, multi-Reason Being)
 *   - PUT    → doUpdateMember      (idempotent, CSRF, name/mail merge)
 *   - DELETE → doDeleteMember      (idempotent, CSRF, soft-delete)
 *
 * All four are admin-only. The Be Finals raise
 * {@see UnauthorizedAdminAccessException} when no admin session is
 * present; we map that to 403 here.
 *
 * Distinct from the role-flip surface ({@see AuthorityRole}) — that
 * goes through its own URL because the privilege-escalation guard
 * needs to be observable in the resource layout.
 *
 * Failure mapping (common to all four):
 *   - Invalid CSRF                          → 403 (POST/PUT/DELETE)
 *   - SemanticVariableException             → 400 (any field format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - AdminNotFoundException                → 404 (no such loginId)
 *
 * POST-only:
 *   - LoginIdAlreadyTakenException          → 409 (loginId conflict)
 *
 * DELETE-only:
 *   - InsufficientAuthorityException        → 403 (caller targeting self)
 */
class Member extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
        private readonly AdminSessionInterface $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * Wave 8: the loginId comes from the admin UI (typed input or
     * query string) — user-controlled.
     *
     * @psalm-taint-source input $loginId
     */
    #[Link(rel: 'goMemberList', href: 'page://self/admin/member-list')]
    public function onGet(string|null $loginId = null): static
    {
        if ($loginId === null || $loginId === '') {
            if ($this->adminSession->adminId() === null) {
                $this->code = Code::FORBIDDEN;
                $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

                return $this;
            }

            $form = $this->formFactory->newInstance(AdminMemberForm::class);
            assert($form instanceof AdminMemberForm);
            $this->code = Code::OK;
            $this->body = [
                'adminId' => '',
                'loginId' => '',
                'name' => '',
                'authority' => 0,
                'work' => 0,
                'csrfToken' => FakeCsrfToken::TOKEN,
                'form' => $form,
            ];

            return $this;
        }

        try {
            $final = ($this->becoming)(new GetMemberInput(loginId: $loginId));
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
        }

        assert($final instanceof MemberFetched);

        $this->code = Code::OK;
        $this->body = [
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'name' => $final->name,
            'authority' => $final->authority,
            'work' => $final->work,
        ];
        // Phase 3: an AdminMemberForm pre-filled with the persisted row,
        // for the HTML edit page (var/templates/Page/Admin/Member.html.twig)
        // to render via `{{ form.input(...) }}`. The form is a renderer
        // here, never a validator — VALIDATION AUTHORITY STAYS WITH the
        // Be Becoming chain. JSON contexts (`app`, `prod`, `test`) ignore
        // `body['form']`; the resource tests assert key-wise on `body`.
        $form = $this->formFactory->newInstance(AdminMemberForm::class);
        assert($form instanceof AdminMemberForm);
        $form->fillValues($this->body);
        $this->body['form'] = $form;

        return $this;
    }

    /**
     * Wave 8: all form fields are user-controlled. The admin AUTHZ
     * check lives inside the first Being (MemberCreating), so this
     * method just maps the exceptions.
     *
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $password
     * @psalm-taint-source input $name
     * @psalm-taint-source input $authority
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goMember', href: 'page://self/admin/member', method: 'get')]
    public function onPost(
        string $loginId,
        string $password,
        string $name,
        int $authority,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new CreateMemberInput(
                loginId: $loginId,
                password: $password,
                name: $name,
                authority: $authority,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'loginId' => $loginId,
            ];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (LoginIdAlreadyTakenException) {
            // BEAR\Resource\Code lacks CONFLICT; use the integer
            // literal (same convention as Pilot 4 Entry resource).
            $this->code = 409;
            $this->body = ['message' => 'このログインIDは既に使用されています。', 'loginId' => $loginId];

            return $this;
        }

        assert($final instanceof MemberCreated);

        $this->code = Code::CREATED;
        // goMember is keyed by loginId.
        $this->headers['Location'] = sprintf('/admin/member?loginId=%s', urlencode($final->loginId));
        $this->body = [
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'name' => $final->name,
            'authority' => $final->authority,
            'work' => $final->work,
        ];

        return $this;
    }

    /**
     * Wave 8: doUpdateMember — edits `name` only. The other admin
     * fields (authority, work, passwordHash) have their own dedicated
     * transitions / are out of scope for Phase 1. EC-CUBE 4.3
     * dtb_member has no email column, so no mailAddress field is
     * accepted.
     *
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $name
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goMember', href: 'page://self/admin/member', method: 'get')]
    public function onPut(
        string $loginId,
        string|null $name = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateMemberInput(
                loginId: $loginId,
                name: $name,
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
        }

        assert($final instanceof MemberUpdated);

        $this->code = Code::OK;
        $this->body = [
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'name' => $final->name,
            'authority' => $final->authority,
            'work' => $final->work,
        ];

        return $this;
    }

    /**
     * Wave 8: doDeleteMember — soft-delete (work=0). Idempotent
     * replay returns 200 with `alreadyDeleted=true`. Self-target
     * raises {@see InsufficientAuthorityException} → 403.
     *
     * @psalm-taint-source input $loginId
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goMemberList', href: 'page://self/admin/member-list')]
    public function onDelete(string $loginId, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new DeleteMemberInput(loginId: $loginId));
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

        assert($final instanceof MemberDeleted);

        $this->code = Code::OK;
        $this->body = [
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'alreadyDeleted' => $final->alreadyDeleted,
            'message' => $final->alreadyDeleted
                ? '指定された管理者は既に削除されています。'
                : '管理者を削除しました。',
        ];

        return $this;
    }
}
