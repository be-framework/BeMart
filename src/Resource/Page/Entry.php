<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\Addr01FormatException;
use MyVendor\BeMart\Be\Exception\Addr02FormatException;
use MyVendor\BeMart\Be\Exception\BirthFormatException;
use MyVendor\BeMart\Be\Exception\CompanyNameFormatException;
use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use MyVendor\BeMart\Be\Exception\EmailFormatException;
use MyVendor\BeMart\Be\Exception\JobFormatException;
use MyVendor\BeMart\Be\Exception\Kana01FormatException;
use MyVendor\BeMart\Be\Exception\Kana02FormatException;
use MyVendor\BeMart\Be\Exception\Name01FormatException;
use MyVendor\BeMart\Be\Exception\Name02FormatException;
use MyVendor\BeMart\Be\Exception\PasswordFormatException;
use MyVendor\BeMart\Be\Exception\PhoneNumberFormatException;
use MyVendor\BeMart\Be\Exception\PostalCodeFormatException;
use MyVendor\BeMart\Be\Exception\PrefFormatException;
use MyVendor\BeMart\Be\Exception\SexFormatException;
use MyVendor\BeMart\Be\Final\CustomerRegistered;
use MyVendor\BeMart\Be\Input\RegisterCustomerInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\EntryForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function array_filter;
use function array_key_exists;
use function array_values;
use function assert;
use function ctype_digit;
use function is_int;
use function is_string;
use function sprintf;
use function trim;

/**
 * EC-CUBE doRegisterCustomer —会員登録 (Entry/Register).
 *
 * Resource is the HTTP entry point: it builds RegisterCustomerInput, hands
 * it to Becoming, and projects the resulting CustomerRegistered into the
 * response body. The 4 required EC-CUBE form fields (email / password /
 * name01 / name02) are positional; the 11 optional fields are passed
 * through unchanged with `null` defaults — see RegisterCustomerInput.
 *
 * Pilot 4 implements the email-verification-OFF flow only
 * (customerStatus = 2 = Active). The OFF path lands on the
 * `CustomerRegistrationComplete` state, whose ALPS surface declares the
 * single transition `goTop`. The verification-ON branch (provisional →
 * email confirm → activate) is deferred to a future Branching pilot.
 *
 * Phase 3 — HTML FORM page. The resource builds an {@see EntryForm}
 * (Ray.WebFormModule AbstractForm) and exposes it as `body['form']` so
 * the HTML port renders real `<input>`s via `{{ form.input(...) }}`. The
 * form is a field-definition + renderer only — VALIDATION AUTHORITY STAYS
 * WITH the Be Framework Becoming chain. On a domain rejection the
 * resource bridges the verdict onto the form (repopulated values + inline
 * error). The JSON contexts (`app`, `prod`, `test`) ignore `body['form']`.
 *
 * @see RegisterCustomerInput  Pilot 4 scope note
 */
class Entry extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfToken $csrf,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE goCustomerRegistration — show the customer registration
     * form scaffolding.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state). Fields mirror RegisterCustomerInput: 4 required + 11
     * optional. In the dev/html fake-CSRF environment we expose the fake
     * token into the hidden `_token` input so a real browser form submit
     * can exercise the POST path instead of failing at the boundary.
     */
    #[Alps('goCustomerRegistration')]
    #[JsonSchema(schema: 'get-entry.json')]
    #[Link(rel: 'goCustomerRegistrationConfirm', href: 'page://self/entry/confirm')]
    #[Link(rel: 'doRegisterCustomer', href: 'page://self/entry', method: 'post')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goCustomerRegistration',
            'fields' => [
                'email',
                'password',
                'name01',
                'name02',
                'kana01',
                'kana02',
                'companyName',
                'phoneNumber',
                'postalCode',
                'pref',
                'addr01',
                'addr02',
                'birth',
                'sex',
                'job',
                'csrfToken',
            ],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/entry',
            ],
            'csrfToken' => $this->csrfTokenForForm(),
            // Phase 3: an empty EntryForm for the HTML port to render via
            // `{{ form.input(...) }}`. JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(EntryForm::class),
        ];

        return $this;
    }

    /**
     * Phase B Slice 9: every form field is user-controlled input. Declared
     * as taint sources so Psalm can trace them. Semantic value objects
     * format-validate but do not universally escape — sinks downstream
     * still need their own defense (bound params, HTML escape on render).
     *
     * @psalm-taint-source input $email
     * @psalm-taint-source input $password
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $companyName
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $birth
     * @psalm-taint-source input $sex
     * @psalm-taint-source input $job
     */
    #[Alps('doRegisterCustomer')]
    #[JsonSchema(schema: 'post-entry.json', params: 'post-entry.param.json')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    #[CsrfProtected]
    public function onPost(
        string|null $email = null,
        string|null $password = null,
        string|null $name01 = null,
        string|null $name02 = null,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $companyName = null,
        string|null $phoneNumber = null,
        string|null $postalCode = null,
        int|string|null $pref = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $birth = null,
        int|string|null $sex = null,
        int|string|null $job = null,
        string|null $email_confirm = null,
        string|null $password_confirm = null,
        string|null $birth_year = null,
        string|null $birth_month = null,
        string|null $birth_day = null,
        string|null $user_policy_check = null,
        string|null $mode = null,
    ): static {
        $values = $this->submittedValues(
            email: $email,
            password: $password,
            name01: $name01,
            name02: $name02,
            kana01: $kana01,
            kana02: $kana02,
            companyName: $companyName,
            phoneNumber: $phoneNumber,
            postalCode: $postalCode,
            pref: $pref,
            addr01: $addr01,
            addr02: $addr02,
            birth: $birth,
            sex: $sex,
            job: $job,
            email_confirm: $email_confirm,
            password_confirm: $password_confirm,
            birth_year: $birth_year,
            birth_month: $birth_month,
            birth_day: $birth_day,
            user_policy_check: $user_policy_check,
        );

        $browserForm = $this->isBrowserFormSubmission($values, $mode);
        if ($browserForm) {
            $errors = $this->formErrors($values);
            if ($errors !== []) {
                return $this->rejectForm($values, $errors);
            }
        }

        try {
            $final = ($this->becoming)(new RegisterCustomerInput(
                email: $values['email'],
                password: $values['password'],
                name01: $values['name01'],
                name02: $values['name02'],
                kana01: $values['kana01'] !== '' ? $values['kana01'] : null,
                kana02: $values['kana02'] !== '' ? $values['kana02'] : null,
                companyName: $values['companyName'] !== '' ? $values['companyName'] : null,
                phoneNumber: $values['phoneNumber'] !== '' ? $values['phoneNumber'] : null,
                postalCode: $values['postalCode'] !== '' ? $values['postalCode'] : null,
                pref: self::optionalInt($values['pref']),
                addr01: $values['addr01'] !== '' ? $values['addr01'] : null,
                addr02: $values['addr02'] !== '' ? $values['addr02'] : null,
                birth: $values['birth'] !== '' ? $values['birth'] : null,
                sex: self::optionalInt($values['sex']),
                job: self::optionalInt($values['job']),
            ));
        } catch (SemanticVariableException $e) {
            if (! $browserForm) {
                throw $e;
            }

            [$field, $message] = self::semanticError($e);

            return $this->rejectForm($values, [$field => $message]);
        } catch (EmailAlreadyRegisteredException $e) {
            if (! $browserForm) {
                throw $e;
            }

            return $this->rejectForm($values, ['email' => 'このメールアドレスは既に登録されています。'], 409);
        }

        assert($final instanceof CustomerRegistered);

        $this->code = $browserForm ? Code::SEE_OTHER : Code::CREATED;
        $this->headers['Location'] = '/entry/complete';
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'initialPoint' => $final->initialPoint,
            'customerStatus' => $final->customerStatus,
        ];

        return $this;
    }

    /**
     * @return array{
     *   email: string,
     *   password: string,
     *   name01: string,
     *   name02: string,
     *   kana01: string,
     *   kana02: string,
     *   companyName: string,
     *   phoneNumber: string,
     *   postalCode: string,
     *   pref: string,
     *   addr01: string,
     *   addr02: string,
     *   birth: string,
     *   sex: string,
     *   job: string,
     *   email_confirm: string,
     *   password_confirm: string,
     *   birth_year: string,
     *   birth_month: string,
     *   birth_day: string,
     *   user_policy_check: string
     * }
     */
    private function submittedValues(
        string|null $email,
        string|null $password,
        string|null $name01,
        string|null $name02,
        string|null $kana01,
        string|null $kana02,
        string|null $companyName,
        string|null $phoneNumber,
        string|null $postalCode,
        int|string|null $pref,
        string|null $addr01,
        string|null $addr02,
        string|null $birth,
        int|string|null $sex,
        int|string|null $job,
        string|null $email_confirm,
        string|null $password_confirm,
        string|null $birth_year,
        string|null $birth_month,
        string|null $birth_day,
        string|null $user_policy_check,
    ): array {
        $birthValue = $birth ?? '';
        if ($birthValue === '') {
            $birthValue = self::birthFromParts($birth_year, $birth_month, $birth_day);
        }

        return [
            'email' => $email ?? '',
            'password' => $password ?? '',
            'name01' => $name01 ?? '',
            'name02' => $name02 ?? '',
            'kana01' => $kana01 ?? '',
            'kana02' => $kana02 ?? '',
            'companyName' => $companyName ?? '',
            'phoneNumber' => $phoneNumber ?? '',
            'postalCode' => $postalCode ?? '',
            'pref' => self::stringValue($pref),
            'addr01' => $addr01 ?? '',
            'addr02' => $addr02 ?? '',
            'birth' => $birthValue,
            'sex' => self::stringValue($sex),
            'job' => self::stringValue($job),
            'email_confirm' => $email_confirm ?? '',
            'password_confirm' => $password_confirm ?? '',
            'birth_year' => $birth_year ?? '',
            'birth_month' => $birth_month ?? '',
            'birth_day' => $birth_day ?? '',
            'user_policy_check' => $user_policy_check ?? '',
        ];
    }

    /** @param array<string, string> $values */
    private function isBrowserFormSubmission(array $values, string|null $mode): bool
    {
        return $mode !== null
            || array_key_exists('email_confirm', $this->uri->query)
            || array_key_exists('password_confirm', $this->uri->query)
            || array_key_exists('user_policy_check', $this->uri->query)
            || $values['birth_year'] !== ''
            || $values['birth_month'] !== ''
            || $values['birth_day'] !== '';
    }

    /** @param array<string, string> $values */
    private function formErrors(array $values): array
    {
        $errors = [];
        foreach ([
            'name01' => '入力してください。',
            'name02' => '入力してください。',
            'email' => '入力してください。',
            'password' => '入力してください。',
        ] as $field => $message) {
            if (trim($values[$field]) === '') {
                $errors[$field] = $message;
            }
        }

        if (array_key_exists('email_confirm', $this->uri->query)) {
            if (trim($values['email_confirm']) === '') {
                $errors['email_confirm'] = '入力してください。';
            } elseif ($values['email'] !== $values['email_confirm']) {
                $errors['email_confirm'] = 'メールアドレスが一致しません。';
            }
        }

        if (array_key_exists('password_confirm', $this->uri->query)) {
            if (trim($values['password_confirm']) === '') {
                $errors['password_confirm'] = '入力してください。';
            } elseif ($values['password'] !== $values['password_confirm']) {
                $errors['password_confirm'] = 'パスワードが一致しません。';
            }
        }

        if (array_key_exists('user_policy_check', $this->uri->query) && $values['user_policy_check'] === '') {
            $errors['user_policy_check'] = '利用規約に同意してください。';
        }

        foreach (['pref', 'sex', 'job'] as $field) {
            if ($values[$field] !== '' && ! ctype_digit($values[$field])) {
                $errors[$field] = '選択してください。';
            }
        }

        return $errors;
    }

    /** @param array<string, string> $values */
    private function rejectForm(array $values, array $errors, int $code = Code::BAD_REQUEST): static
    {
        $this->code = $code;
        $this->body = [
            'transitionId' => 'goCustomerRegistration',
            'fields' => [
                'email',
                'password',
                'name01',
                'name02',
                'kana01',
                'kana02',
                'companyName',
                'phoneNumber',
                'postalCode',
                'pref',
                'addr01',
                'addr02',
                'birth',
                'sex',
                'job',
                'csrfToken',
            ],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/entry',
            ],
            'csrfToken' => $this->csrfTokenForForm(),
            'message' => array_values($errors)[0] ?? '入力内容を確認してください。',
            'errors' => $errors,
            'form' => $this->failedForm($values, $errors),
        ];

        return $this;
    }

    /** @param array<string, string> $values */
    private function failedForm(array $values, array $errors): EntryForm
    {
        $form = $this->formFactory->newInstance(EntryForm::class);
        assert($form instanceof EntryForm);

        $form->fillValues(array_filter([
            'email' => $values['email'],
            'name01' => $values['name01'],
            'name02' => $values['name02'],
            'kana01' => $values['kana01'],
            'kana02' => $values['kana02'],
            'companyName' => $values['companyName'],
            'phoneNumber' => $values['phoneNumber'],
            'postalCode' => $values['postalCode'],
            'pref' => $values['pref'],
            'addr01' => $values['addr01'],
            'addr02' => $values['addr02'],
            'birth_year' => $values['birth_year'],
            'birth_month' => $values['birth_month'],
            'birth_day' => $values['birth_day'],
            'sex' => $values['sex'],
            'job' => $values['job'],
            'user_policy_check' => $values['user_policy_check'],
        ], static fn (string $v): bool => $v !== ''));
        foreach ($errors as $field => $message) {
            $form->setDomainError($field, $message);
        }

        return $form;
    }

    private static function birthFromParts(string|null $year, string|null $month, string|null $day): string
    {
        if ($year === null || $month === null || $day === null) {
            return '';
        }

        if ($year === '' || $month === '' || $day === '') {
            return '';
        }

        if (! ctype_digit($year) || ! ctype_digit($month) || ! ctype_digit($day)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', (int) $year, (int) $month, (int) $day);
    }

    private static function optionalInt(string $value): int|null
    {
        if ($value === '' || ! ctype_digit($value)) {
            return null;
        }

        return (int) $value;
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
            $exception instanceof CompanyNameFormatException => 'companyName',
            $exception instanceof PhoneNumberFormatException => 'phoneNumber',
            $exception instanceof PostalCodeFormatException => 'postalCode',
            $exception instanceof PrefFormatException => 'pref',
            $exception instanceof Addr01FormatException => 'addr01',
            $exception instanceof Addr02FormatException => 'addr02',
            $exception instanceof BirthFormatException => 'birth_year',
            $exception instanceof SexFormatException => 'sex',
            $exception instanceof JobFormatException => 'job',
            $exception instanceof PasswordFormatException => 'password',
            $exception instanceof EmailFormatException => 'email',
            default => 'email',
        };

        return [$field, $message];
    }

    private function csrfTokenForForm(): string
    {
        return $this->csrf->token;
    }
}
