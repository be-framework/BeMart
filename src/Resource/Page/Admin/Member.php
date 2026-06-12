<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use Be\Framework\SemanticVariable\ValidationMessageHandler;
use MyVendor\BeMart\Be\Exception\AdminNotFoundException;
use MyVendor\BeMart\Be\Exception\AuthorityFormatException;
use MyVendor\BeMart\Be\Exception\InsufficientAuthorityException;
use MyVendor\BeMart\Be\Exception\LoginIdFormatException;
use MyVendor\BeMart\Be\Exception\LoginIdAlreadyTakenException;
use MyVendor\BeMart\Be\Exception\MemberNameFormatException;
use MyVendor\BeMart\Be\Exception\PasswordFormatException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MemberCreated;
use MyVendor\BeMart\Be\Final\MemberDeleted;
use MyVendor\BeMart\Be\Final\MemberFetched;
use MyVendor\BeMart\Be\Final\MemberUpdated;
use MyVendor\BeMart\Be\Input\CreateMemberInput;
use MyVendor\BeMart\Be\Input\DeleteMemberInput;
use MyVendor\BeMart\Be\Input\GetMemberInput;
use MyVendor\BeMart\Be\Input\UpdateMemberInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\AdminMemberForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function array_values;
use function assert;
use function sprintf;
use function trim;
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
        private readonly CsrfToken $csrf,
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * Wave 8: the loginId comes from the admin UI (typed input or
     * query string) — user-controlled.
     *
     * @psalm-taint-source input $loginId
     */
    #[Alps('goMember')]
    #[JsonSchema(schema: 'get-admin-member.json', params: 'get-admin-member.param.json')]
    #[Link(rel: 'goMemberList', href: 'page://self/admin/member-list')]
    #[Link(rel: 'doUpdateMember', href: 'page://self/admin/member', method: 'put')]
    #[Link(rel: 'doDeleteMember', href: 'page://self/admin/member', method: 'delete')]
    public function onGet(string|null $loginId = null): static
    {
        if ($loginId === null || $loginId === '') {
            if ($this->adminSession->adminId === null) {
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
                'sortNo' => 0,
                'csrfToken' => $this->csrf->token,
                'form' => $form,
            ];

            return $this;
        }

        $final = ($this->becoming)(new GetMemberInput(loginId: $loginId));

        assert($final instanceof MemberFetched);

        $this->code = Code::OK;
        $this->body = [
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'name' => $final->name,
            'authority' => $final->authority,
            'work' => $final->work,
            'sortNo' => $final->sortNo,
            'csrfToken' => $this->csrf->token,
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
     */
    #[Alps('doCreateMember')]
    #[JsonSchema(schema: 'post-admin-member.json', params: 'post-admin-member.param.json')]
    #[Link(rel: 'goMember', href: 'page://self/admin/member', method: 'get')]
    #[CsrfProtected]
    public function onPost(
        string $loginId,
        string $password,
        string $name,
        int $authority,
        string|null $passwordConfirm = null,
        string|null $mode = null,
    ): static {
        $browserForm = $mode === 'member_form';
        if ($browserForm) {
            $errors = $this->createFormErrors($loginId, $password, $name, $passwordConfirm, $authority);
            if ($errors !== []) {
                return $this->rejectForm(
                    [
                        'loginId' => $loginId,
                        'name' => $name,
                        'authority' => (string) $authority,
                    ],
                    $errors,
                );
            }
        }

        try {
            $final = ($this->becoming)(new CreateMemberInput(
                loginId: $loginId,
                password: $password,
                name: $name,
                authority: $authority,
            ));
        } catch (SemanticVariableException $e) {
            if (! $browserForm) {
                throw $e;
            }

            [$field, $message] = self::semanticError($e);

            return $this->rejectForm(
                [
                    'loginId' => $loginId,
                    'name' => $name,
                    'authority' => (string) $authority,
                ],
                [$field => $message],
            );
        } catch (LoginIdAlreadyTakenException $e) {
            if (! $browserForm) {
                throw $e;
            }

            return $this->rejectForm(
                [
                    'loginId' => $loginId,
                    'name' => $name,
                    'authority' => (string) $authority,
                ],
                ['loginId' => self::domainMessage($e)],
                409,
            );
        }

        assert($final instanceof MemberCreated);

        $this->code = $browserForm ? Code::SEE_OTHER : Code::CREATED;
        // goMember is keyed by loginId.
        $this->headers['Location'] = sprintf('/admin/member?loginId=%s', urlencode($final->loginId));
        $this->body = [
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'name' => $final->name,
            'authority' => $final->authority,
            'work' => $final->work,
            'sortNo' => $final->sortNo,
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
     */
    #[Alps('doUpdateMember')]
    #[JsonSchema(schema: 'put-admin-member.json', params: 'put-admin-member.param.json')]
    #[Link(rel: 'goMember', href: 'page://self/admin/member', method: 'get')]
    #[CsrfProtected]
    public function onPut(
        string $loginId,
        string|null $name = null,
        string|null $mode = null,
    ): static {
        $browserForm = $mode === 'member_form';
        if ($browserForm && trim((string) $name) === '') {
            return $this->rejectForm(
                [
                    'loginId' => $loginId,
                    'name' => (string) $name,
                ],
                ['name' => '入力してください。'],
            );
        }

        try {
            $final = ($this->becoming)(new UpdateMemberInput(
                loginId: $loginId,
                name: $name,
            ));
        } catch (SemanticVariableException $e) {
            if (! $browserForm) {
                throw $e;
            }

            [$field, $message] = self::semanticError($e);

            return $this->rejectForm(
                [
                    'loginId' => $loginId,
                    'name' => (string) $name,
                ],
                [$field => $message],
            );
        }

        assert($final instanceof MemberUpdated);

        $this->code = $browserForm ? Code::SEE_OTHER : Code::OK;
        $this->headers['Location'] = sprintf('/admin/member?loginId=%s', urlencode($final->loginId));
        $this->body = [
            'adminId' => $final->adminId,
            'loginId' => $final->loginId,
            'name' => $final->name,
            'authority' => $final->authority,
            'work' => $final->work,
            'sortNo' => $final->sortNo,
        ];

        return $this;
    }

    /**
     * @return array<string, string>
     */
    private function createFormErrors(
        string $loginId,
        string $password,
        string $name,
        string|null $passwordConfirm,
        int $authority,
    ): array {
        $errors = [];
        foreach ([
            'name' => $name,
            'loginId' => $loginId,
            'password' => $password,
            'passwordConfirm' => (string) $passwordConfirm,
        ] as $field => $value) {
            if (trim($value) === '') {
                $errors[$field] = '入力してください。';
            }
        }

        if ($passwordConfirm !== null && $password !== '' && $passwordConfirm !== '' && $password !== $passwordConfirm) {
            $errors['passwordConfirm'] = 'パスワードが一致しません。';
        }

        if ($authority !== 0 && $authority !== 1) {
            $errors['authority'] = '権限を選択してください。';
        }

        return $errors;
    }

    /** @param array<string, string> $values */
    private function rejectForm(array $values, array $errors, int $code = Code::BAD_REQUEST): static
    {
        $form = $this->formFactory->newInstance(AdminMemberForm::class);
        assert($form instanceof AdminMemberForm);
        $form->fillValues($values);
        foreach ($errors as $field => $message) {
            $form->setDomainError($field, $message);
        }

        $this->code = $code;
        $this->body = [
            'adminId' => '',
            'loginId' => $values['loginId'] ?? '',
            'name' => $values['name'] ?? '',
            'authority' => (int) ($values['authority'] ?? 1),
            'work' => 0,
            'sortNo' => 0,
            'csrfToken' => $this->csrf->token,
            'message' => array_values($errors)[0] ?? '入力内容を確認してください。',
            'errors' => $errors,
            'form' => $form,
        ];

        return $this;
    }

    /** @return array{0: string, 1: string} */
    private static function semanticError(SemanticVariableException $e): array
    {
        $exception = $e->getErrors()->exceptions[0] ?? null;
        $message = $e->getErrors()->getMessages('ja')[0] ?? '入力内容を確認してください。';

        $field = match (true) {
            $exception instanceof PasswordFormatException => 'password',
            $exception instanceof MemberNameFormatException => 'name',
            $exception instanceof LoginIdFormatException => 'loginId',
            $exception instanceof AuthorityFormatException => 'authority',
            default => 'name',
        };

        return [$field, $message];
    }

    private static function domainMessage(LoginIdAlreadyTakenException $e): string
    {
        $message = (new ValidationMessageHandler())->getMessage($e, 'ja');

        return $message !== '' && $message !== 'Validation error'
            ? $message
            : 'このログインIDは既に使用されています。';
    }

    /**
     * Wave 8: doDeleteMember — soft-delete (work=0). Idempotent
     * replay returns 200 with `alreadyDeleted=true`. Self-target
     * raises {@see InsufficientAuthorityException} → 403.
     *
     * @psalm-taint-source input $loginId
     */
    #[Alps('doDeleteMember')]
    #[JsonSchema(schema: 'delete-admin-member.json', params: 'delete-admin-member.param.json')]
    #[Link(rel: 'goMemberList', href: 'page://self/admin/member-list')]
    #[CsrfProtected]
    public function onDelete(string $loginId, string|null $mode = null): static
    {
        $final = ($this->becoming)(new DeleteMemberInput(loginId: $loginId));

        assert($final instanceof MemberDeleted);

        $browserForm = $mode === 'member_form';
        $this->code = $browserForm ? Code::SEE_OTHER : Code::OK;
        if ($browserForm) {
            $this->headers['Location'] = '/admin/member-list';
        }
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
