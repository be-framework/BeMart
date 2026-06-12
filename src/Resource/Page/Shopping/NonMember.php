<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Auth\CartSessionPrefixInterface;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\Addr01FormatException;
use MyVendor\BeMart\Be\Exception\Addr02FormatException;
use MyVendor\BeMart\Be\Exception\EmailFormatException;
use MyVendor\BeMart\Be\Exception\Kana01FormatException;
use MyVendor\BeMart\Be\Exception\Kana02FormatException;
use MyVendor\BeMart\Be\Exception\Name01FormatException;
use MyVendor\BeMart\Be\Exception\Name02FormatException;
use MyVendor\BeMart\Be\Exception\PhoneNumberFormatException;
use MyVendor\BeMart\Be\Exception\PostalCodeFormatException;
use MyVendor\BeMart\Be\Exception\PrefFormatException;
use MyVendor\BeMart\Be\Final\NonMemberSubmitted;
use MyVendor\BeMart\Be\Input\SubmitNonMemberInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\NonMemberForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function array_key_exists;
use function array_values;
use function assert;
use function ctype_digit;
use function is_int;
use function is_string;
use function sprintf;
use function trim;

/**
 * EC-CUBE goShoppingNonMember / doSubmitNonMember —非会員購入 (Wave 7W).
 *
 *   onGet  → goShoppingNonMember (safe form-info, anonymous-accessible)
 *   onPost → doSubmitNonMember   (unsafe, Direct, Semantic-validated)
 *
 * Wave 7W started as the FORM ENTRY slice. The guest branch now persists
 * a processing order and exposes a preOrderId, so an HTML form submission
 * must redirect to the order-confirmation screen instead of returning a
 * Resource-style 201 body that browsers cannot navigate from.
 *
 * Failure mapping (onPost):
 *   - CSRF invalid              → 403 (boundary)
 *   - SemanticVariableException → 400 (any guest field malformed)
 *
 * Coexists with `Resource\Page\Shopping\Checkout.php` (Pilot 5) under
 * the same `Shopping/` directory — the same file-plus-sibling-directory
 * pattern as Mypage / Entry.
 *
 * Phase 3 — HTML FORM page. `Shopping/nonmember.twig` renders the
 * guest-info inputs through the Symfony FormView; BeMart exposes a
 * {@see NonMemberForm} (Ray.WebFormModule AbstractForm) as `body['form']`
 * so the HTML port renders real `<input>`s via `{{ form.input(...) }}`.
 * The form is a field-definition + renderer only — VALIDATION AUTHORITY
 * STAYS WITH the Be Becoming chain (doSubmitNonMember /
 * SubmitNonMemberInput). On a domain rejection the resource bridges the
 * verdict onto the form. JSON contexts ignore `body['form']`.
 */
class NonMember extends ResourceObject
{
    /** @var array<string, string> */
    private const REQUIRED_MESSAGES = [
        'name01' => '入力してください。',
        'name02' => '入力してください。',
        'kana01' => '入力してください。',
        'kana02' => '入力してください。',
        'email' => '入力してください。',
        'phoneNumber' => '入力してください。',
        'postalCode' => '入力してください。',
        'pref' => '入力してください。',
        'addr01' => '入力してください。',
        'addr02' => '入力してください。',
    ];

    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfToken $csrf,
        private readonly FormFactory $formFactory,
        private readonly CartSessionPrefixInterface $cartSessionPrefix,
    ) {
    }

    /**
     * EC-CUBE goShoppingNonMember — show the guest-info entry form.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state). Fields mirror SubmitNonMemberInput. `csrfToken` carries
     * the trusted reference token so the HTML form can pass the
     * subsequent POST's CSRF boundary.
     */
    #[Alps('goShoppingNonMember')]
    #[JsonSchema(schema: 'get-shopping-non-member.json')]
    #[Link(rel: 'doSubmitNonMember', href: 'page://self/shopping/non-member', method: 'post')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    public function onGet(): static
    {
        $form = $this->formFactory->newInstance(NonMemberForm::class);
        assert($form instanceof NonMemberForm);

        $this->code = Code::OK;
        $this->body = $this->formBody($form);

        return $this;
    }

    /**
     * EC-CUBE doSubmitNonMember — accept guest shipping info and return
     * the synthesised preOrderId.
     *
     * Phase B Slice 9: every guest form field is user-controlled input.
     * Declared as taint sources so Psalm can trace them downstream.
     * Semantic value objects format-validate but do not universally
     * escape — sinks downstream remain responsible for their own
     * defence (bound params, HTML escape on render).
     *
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $email
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $sessionPrefix
     */
    #[Alps('doSubmitNonMember')]
    #[JsonSchema(schema: 'post-shopping-non-member.json', params: 'post-shopping-non-member.param.json')]
    #[Link(rel: 'doConfirmOrder', href: 'page://self/shopping/confirm', method: 'post')]
    #[Link(rel: 'goShopping', href: 'page://self/shopping')]
    #[CsrfProtected]
    public function onPost(
        string|null $name01 = null,
        string|null $name02 = null,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $email = null,
        string|null $phoneNumber = null,
        string|null $postalCode = null,
        int|string|null $pref = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $sessionPrefix = 'session-prefix-1',
    ): static {
        $values = $this->submittedValues(
            name01: $name01,
            name02: $name02,
            kana01: $kana01,
            kana02: $kana02,
            email: $email,
            phoneNumber: $phoneNumber,
            postalCode: $postalCode,
            pref: $pref,
            addr01: $addr01,
            addr02: $addr02,
        );

        $errors = $this->formErrors($values);
        if ($errors !== []) {
            return $this->rejectForm($values, $errors);
        }

        $prefValue = self::prefValue($values['pref']);
        assert($prefValue !== null);

        try {
            $final = ($this->becoming)(new SubmitNonMemberInput(
                name01: $values['name01'],
                name02: $values['name02'],
                kana01: $values['kana01'],
                kana02: $values['kana02'],
                email: $values['email'],
                phoneNumber: $values['phoneNumber'],
                postalCode: $values['postalCode'],
                pref: $prefValue,
                addr01: $values['addr01'],
                addr02: $values['addr02'],
                sessionPrefix: $this->effectiveSessionPrefix($sessionPrefix),
            ));
        } catch (SemanticVariableException $e) {
            [$field, $message] = self::semanticError($e);

            return $this->rejectForm($values, [$field => $message]);
        }

        assert($final instanceof NonMemberSubmitted);

        $browserForm = $this->isBrowserFormSubmission();
        $this->code = $browserForm ? Code::SEE_OTHER : Code::CREATED;
        $this->headers['Location'] = $browserForm
            ? sprintf('/shopping/confirm?preOrderId=%s&paymentMethodId=%d', $final->preOrderId, $final->paymentMethodId)
            : sprintf('/shopping?preOrderId=%s', $final->preOrderId);
        $this->body = [
            'preOrderId' => $final->preOrderId,
            'paymentMethodId' => $final->paymentMethodId,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'email' => $final->email,
        ];

        return $this;
    }

    private function isBrowserFormSubmission(): bool
    {
        return array_key_exists('email_confirm', $this->uri->query);
    }

    private function effectiveSessionPrefix(string|null $submittedPrefix): string
    {
        return $this->cartSessionPrefix->prefix() ?? $submittedPrefix ?? 'session-prefix-1';
    }

    private function formBody(NonMemberForm $form): array
    {
        return [
            'transitionId' => 'goShoppingNonMember',
            'fields' => [
                'name01',
                'name02',
                'kana01',
                'kana02',
                'email',
                'phoneNumber',
                'postalCode',
                'pref',
                'addr01',
                'addr02',
                'csrfToken',
            ],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/shopping/non-member',
            ],
            'csrfToken' => $this->csrf->token,
            // Phase 3: NonMemberForm renders the guest-info inputs. JSON
            // contexts ignore it, while the HTML transfer uses it.
            'form' => $form,
        ];
    }

    /**
     * @return array{
     *   name01: string,
     *   name02: string,
     *   kana01: string,
     *   kana02: string,
     *   companyName: string,
     *   email: string,
     *   email_confirm: string,
     *   phoneNumber: string,
     *   postalCode: string,
     *   pref: string,
     *   addr01: string,
     *   addr02: string
     * }
     */
    private function submittedValues(
        string|null $name01,
        string|null $name02,
        string|null $kana01,
        string|null $kana02,
        string|null $email,
        string|null $phoneNumber,
        string|null $postalCode,
        int|string|null $pref,
        string|null $addr01,
        string|null $addr02,
    ): array {
        return [
            'name01' => $name01 ?? '',
            'name02' => $name02 ?? '',
            'kana01' => $kana01 ?? '',
            'kana02' => $kana02 ?? '',
            'companyName' => $this->submittedString('companyName'),
            'email' => $email ?? '',
            'email_confirm' => $this->submittedString('email_confirm'),
            'phoneNumber' => $phoneNumber ?? '',
            'postalCode' => $postalCode ?? '',
            'pref' => self::stringValue($pref),
            'addr01' => $addr01 ?? '',
            'addr02' => $addr02 ?? '',
        ];
    }

    private function submittedString(string $field): string
    {
        $value = $this->uri->query[$field] ?? null;

        return self::stringValue($value);
    }

    /** @param array<string, string> $values */
    private function formErrors(array $values): array
    {
        $errors = [];
        foreach (self::REQUIRED_MESSAGES as $field => $message) {
            if (trim($values[$field] ?? '') === '') {
                $errors[$field] = $message;
            }
        }

        if (trim($values['pref']) !== '' && ! ctype_digit($values['pref'])) {
            $errors['pref'] = '都道府県を選択してください。';
        }

        if (array_key_exists('email_confirm', $this->uri->query)) {
            if (trim($values['email_confirm']) === '') {
                $errors['email_confirm'] = '入力してください。';
            } elseif ($values['email'] !== $values['email_confirm']) {
                $errors['email_confirm'] = 'メールアドレスが一致しません。';
            }
        }

        return $errors;
    }

    /** @param array<string, string> $values */
    private function rejectForm(array $values, array $errors): static
    {
        $form = $this->formFactory->newInstance(NonMemberForm::class);
        assert($form instanceof NonMemberForm);
        $form->fillValues($values);
        foreach ($errors as $field => $message) {
            $form->setDomainError($field, $message);
        }

        $this->code = Code::BAD_REQUEST;
        $this->body = $this->formBody($form);
        $this->body['message'] = array_values($errors)[0] ?? '入力内容を確認してください。';
        $this->body['errors'] = $errors;

        return $this;
    }

    private static function prefValue(string $pref): int|null
    {
        if ($pref === '' || ! ctype_digit($pref)) {
            return null;
        }

        return (int) $pref;
    }

    private static function stringValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        return '';
    }

    /** @return array{0: string, 1: string} */
    private static function semanticError(SemanticVariableException $e): array
    {
        $exception = $e->getErrors()->exceptions[0] ?? null;
        $message = $e->getErrors()->getMessages('ja')[0] ?? '入力内容を確認してください。';

        $field = match (true) {
            $exception instanceof Name01FormatException => 'name01',
            $exception instanceof Name02FormatException => 'name02',
            $exception instanceof Kana01FormatException => 'kana01',
            $exception instanceof Kana02FormatException => 'kana02',
            $exception instanceof EmailFormatException => 'email',
            $exception instanceof PhoneNumberFormatException => 'phoneNumber',
            $exception instanceof PostalCodeFormatException => 'postalCode',
            $exception instanceof PrefFormatException => 'pref',
            $exception instanceof Addr01FormatException => 'addr01',
            $exception instanceof Addr02FormatException => 'addr02',
            default => 'email',
        };

        return [$field, $message];
    }
}
