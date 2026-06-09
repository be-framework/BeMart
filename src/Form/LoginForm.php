<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE goLogin の会員ログインフォーム — Ray.WebFormModule pilot.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Front/CustomerLoginType` + the
 * `Mypage/login.twig` `form_widget` calls. EC-CUBE renders these inputs
 * through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html).
 *
 * Role of this class (the form-page recipe — see var/templates/README.md):
 *
 *  - **Field definition** — `init()` declares the two inputs with the
 *    EC-CUBE field names / ids / attributes so the rendered `<input>`
 *    markup reproduces EC-CUBE's `ec-*` form.
 *  - **HTML rendering** — `{{ form.input('email') }}` /
 *    `{{ form.error('email') }}` in `Login.html.twig`.
 *  - **Repopulation** — after a failed POST the resource calls
 *    {@see fillValues()} so the page re-renders with the entered email
 *    (EC-CUBE's `getLastUsername()` UX).
 *
 * What this class deliberately does NOT do — **validation authority**:
 *
 *   BeMart validates login in the domain via Be Framework Semantics
 *   (Be\Semantic\Email, Be\Semantic\Password) and the
 *   CustomerAuthenticated Final (credential check). Those ALPS-derived
 *   rules are the single source of truth. Duplicating them into
 *   Aura.Filter would drift from the spec, so the filter here carries
 *   only NON-AUTHORITATIVE structural checks (required / blank) for a
 *   future fast-UX pre-check. The Login resource never consults the
 *   filter verdict: it hands the raw input to the Becoming chain and,
 *   on rejection, bridges the domain error onto this form via
 *   {@see setDomainError()}. Hence the `#[FormValidation]` aspect is NOT
 *   used — that aspect would make Aura.Filter own the verdict, which
 *   conflicts with the Be Becoming flow. This form is a
 *   field-definition + renderer only.
 *
 * @link https://schema.org/LoginAction
 */
final class LoginForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name. Populated by {@see setDomainError()}; consulted by
     * {@see error()} so `{{ form.error('email') }}` shows the
     * Be-domain message, not an Aura.Filter message.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the login form fields.
     *
     * Field names / ids / attributes are ported verbatim from EC-CUBE's
     * `Mypage/login.twig` `form_widget(form.email / password)`
     * calls so the rendered markup carries EC-CUBE's `ec-*` form shape.
     * EC-CUBE's controller builds the form with `createNamedBuilder('')`
     * — an empty form name — so the children render with the bare field
     * name as both `id` and `name` (`email`, `password`).
     */
    #[Override]
    public function init(): void
    {
        $this->setField('email', 'text')
            ->setAttribs([
                'id' => 'email',
                'style' => 'ime-mode: disabled;',
                'placeholder' => 'メールアドレス',
                'autofocus' => 'autofocus',
            ]);

        $this->setField('password', 'password')
            ->setAttribs([
                'id' => 'password',
                'placeholder' => 'パスワード',
            ]);

        // NON-AUTHORITATIVE structural checks only. The authoritative
        // email-format / password-length / credential rules live in the
        // Be domain (Semantics + CustomerAuthenticated Final). The Login
        // resource does not consult this filter; it is wired so a future
        // client-side / pre-submit UX could reuse it without re-deriving
        // the structural shape.
        $this->filter->validate('email')->isNotBlank();
        $this->filter->validate('password')->isNotBlank();
    }

    /**
     * Repopulates the form inputs with submitted values.
     *
     * Called by the Login resource after a failed POST so the page
     * re-renders with the entered email — EC-CUBE's `getLastUsername()`
     * behaviour. The password is intentionally NOT repopulated (browsers
     * do not echo password fields and re-showing a password is poor
     * practice); pass only the email.
     *
     * @param array<string, string> $values field name => submitted value
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }

    /**
     * Bridges a Be-domain rejection onto the form's error state.
     *
     * The Login resource calls this when the Becoming chain rejects the
     * credentials (a SemanticVariableException for malformed input, or a
     * LoginFailedException for wrong credentials). The message then
     * surfaces through `{{ form.error(field) }}` so the Login page
     * re-renders inline exactly as EC-CUBE's `ec-errorMessage` does.
     *
     * Validation authority stays with Be: this method only transports a
     * verdict the domain already reached.
     */
    public function setDomainError(string $field, string $message): void
    {
        $this->domainErrors[$field] = $message;
    }

    /**
     * Returns the error message for a field.
     *
     * Be-domain errors (bridged via {@see setDomainError()}) take
     * precedence — they are the authoritative verdict. Falls back to the
     * Aura.Filter structural message only if no domain error is present.
     */
    #[Override]
    public function error(string $input): string
    {
        if (isset($this->domainErrors[$input])) {
            return $this->domainErrors[$input];
        }

        return parent::error($input);
    }
}
