<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use Be\Framework\SemanticVariable\ValidationMessageHandler;
use MyVendor\BeMart\Be\Exception\ResetKeyInvalidException;
use MyVendor\BeMart\Be\Final\PasswordResetCompleted;
use MyVendor\BeMart\Be\Input\ResetPasswordInput;
use MyVendor\BeMart\Form\ResetForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function array_values;
use function assert;

/**
 * EC-CUBE doResetPassword — リセットキーを検証して新しいパスワードを
 * 保存する (Pilot 15, consumer pair to Pilot 14 doRequestPasswordReset).
 *
 * Failure mapping (both -> 400, same code on purpose):
 *   - SemanticVariableException  → 400 (resetKey or password malformed)
 *   - ResetKeyInvalidException   → 400 (wrong key / expired / already used)
 *
 * Both failures collapse to the same HTTP status (400 rather than
 * 404) so an attacker cannot distinguish "format-invalid" from
 * "value-invalid" by status alone — same anti-enumeration design as
 * the merged ResetKeyInvalid exception itself.
 *
 * Single-use is enforced inside the Be Final (token consumed via
 * `PasswordResetTokenStorageInterface::delete()` immediately on
 * success); this resource only translates the failure modes.
 */
class Reset extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE goResetPassword — show the new-password form scaffolding
     * (EC-CUBE `Forgot/reset.twig`).
     *
     * Pure form-info endpoint: no Be Framework, no domain logic.
     * Anonymous-accessible (the reset-key check is the POST's job). The
     * `resetKey` arrives as a query param on the emailed reset link and
     * is carried into a hidden form field for the subsequent POST.
     * `csrfToken` stays `null` — the EventListener mirrors the Symfony
     * token into the session for the POST (same as Login).
     *
     * @psalm-taint-source input $resetKey
     */
    #[Alps('doResetPassword')]
    #[JsonSchema(schema: 'get-reset.json', params: 'get-reset.param.json')]
    #[Link(rel: 'doResetPassword', href: 'page://self/reset', method: 'post')]
    #[Link(rel: 'goLogin', href: 'page://self/login')]
    public function onGet(string|null $resetKey = null): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goResetPassword',
            'fields' => ['resetKey', 'password', 'csrfToken'],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/reset',
            ],
            'resetKey' => $resetKey,
            'csrfToken' => null,
            // Phase 3: an empty ResetForm for the HTML port to render
            // via `{{ form.input(...) }}`. JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(ResetForm::class),
        ];

        return $this;
    }

    /**
     * ALPS `doResetPassword` に対応する POST 操作。
     *
     * Browser-form vs hypermedia split (mode set ⇔ browser form):
     *   - A browser submit (登録する button carries name="mode") that fails
     *     — mismatched `password_confirm`, an invalid/expired/used reset key,
     *     or a malformed key/password — re-renders the SAME reset form with an
     *     inline `ec-errorMessage` so the user can correct and retry. EC-CUBE's
     *     ForgotController::reset re-renders `Forgot/reset.twig` with the form
     *     errors on a failed submit rather than throwing to an error page.
     *   - A JSON / hypermedia client sends no `mode`: it keeps throwing
     *     SemanticVariableException / ResetKeyInvalidException so the
     *     ExceptionStatusMapper translates the verdict to a 400 body (the
     *     ResetResourceTest expectations stay intact).
     *
     * `password_confirm` is the re-entered new password (EC-CUBE's
     * RepeatedPasswordType `.second` leaf). It is NOT part of
     * ResetPasswordInput — a confirm typo is a form-level concern, checked
     * here before Becoming so a mistyped confirmation never silently resets
     * the password.
     *
     * @psalm-taint-source input $resetKey
     * @psalm-taint-source input $password
     */
    #[Alps('doResetPassword')]
    #[JsonSchema(schema: 'post-reset.json', params: 'post-reset.param.json')]
    #[Link(rel: 'goLogin', href: 'page://self/login')]
    #[CsrfToken]
    public function onPost(
        string $resetKey,
        string $password,
        string|null $password_confirm = null,
        string|null $mode = null,
    ): static {
        $browserForm = $mode !== null;

        // Form-level: a re-typed confirmation that does not match must NOT
        // reset the password. Only enforced for the browser form (the
        // hypermedia client posts a single `password` and owns its own
        // confirmation, exactly as the Entry/Mypage change-password pages do).
        if ($browserForm && ($password_confirm ?? '') !== $password) {
            return $this->rejectForm($resetKey, ['password_confirm' => 'パスワードが一致しません。']);
        }

        try {
            $final = ($this->becoming)(new ResetPasswordInput(
                resetKey: $resetKey,
                password: $password,
            ));
        } catch (SemanticVariableException $e) {
            if (! $browserForm) {
                throw $e;
            }

            // A malformed resetKey or password is surfaced on the visible
            // password field (the resetKey is a hidden input with no error
            // slot); the message is also echoed as the top-level form message.
            $message = $e->getErrors()->getMessages('ja')[0] ?? '入力内容を確認してください。';

            return $this->rejectForm($resetKey, ['password' => $message]);
        } catch (ResetKeyInvalidException $e) {
            if (! $browserForm) {
                throw $e;
            }

            $message = (new ValidationMessageHandler())->getMessage($e, 'ja');

            return $this->rejectForm($resetKey, ['password' => $message]);
        }

        assert($final instanceof PasswordResetCompleted);

        // Post/Redirect/Get: a browser form submit follows a 303 to the login
        // page so the user gets an observable result and can sign in with the
        // new password. Previously onPost returned 200 and the Reset template
        // re-rendered the same form, so a successful reset showed no feedback.
        // JSON / hypermedia clients keep the 200 body.
        $this->headers['Location'] = '/login';
        $this->code = $browserForm ? Code::SEE_OTHER : Code::OK;
        $this->body = ['customerId' => $final->customerId];

        return $this;
    }

    /**
     * Re-render the reset form with an inline error so a browser user can
     * correct the input and retry, instead of landing on a generic error
     * page. The reset key is carried back into the hidden field; the password
     * inputs are intentionally NOT repopulated.
     *
     * @param array<string, string> $errors field name => ja message
     */
    private function rejectForm(string $resetKey, array $errors, int $code = Code::BAD_REQUEST): static
    {
        $form = $this->formFactory->newInstance(ResetForm::class);
        assert($form instanceof ResetForm);
        foreach ($errors as $field => $message) {
            $form->setDomainError($field, $message);
        }

        $this->code = $code;
        $this->headers = [];
        $this->body = [
            'transitionId' => 'goResetPassword',
            'fields' => ['resetKey', 'password', 'csrfToken'],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/reset',
            ],
            'resetKey' => $resetKey,
            'csrfToken' => null,
            'message' => array_values($errors)[0] ?? '入力内容を確認してください。',
            'errors' => $errors,
            'form' => $form,
        ];

        return $this;
    }
}
