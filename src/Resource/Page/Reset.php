<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ResetKeyInvalidException;
use MyVendor\BeMart\Be\Final\PasswordResetCompleted;
use MyVendor\BeMart\Be\Input\ResetPasswordInput;
use MyVendor\BeMart\Form\ResetForm;
use Ray\WebFormModule\FormFactory;

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
     * @psalm-taint-source input $resetKey
     * @psalm-taint-source input $password
     */
    #[Link(rel: 'goLogin', href: 'page://self/login')]
    #[CsrfProtected]
    public function onPost(string $resetKey, string $password): static
    {
        $final = ($this->becoming)(new ResetPasswordInput(
            resetKey: $resetKey,
            password: $password,
        ));

        assert($final instanceof PasswordResetCompleted);

        $this->code = Code::OK;
        $this->body = ['customerId' => $final->customerId];

        return $this;
    }
}
