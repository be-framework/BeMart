<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Final\NonMemberSubmitted;
use MyVendor\BeMart\Be\Input\SubmitNonMemberInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\NonMemberForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function array_filter;
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
 * Wave 7W is the FORM ENTRY only. The Final intentionally does NOT
 * persist a Cart / PreOrder under the guest's identity, and Pilot 5's
 * doCheckout still requires a customer session — so the preOrderId
 * returned by onPost will currently 403 on the subsequent checkout.
 * Closing that gap is Phase 2's job (dedicated GuestProfile entity +
 * non-member branch in CheckoutPrepared). See NonMemberSubmitted's
 * docblock for the full rationale.
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
    private const REQUIRED_MESSAGE = '入力してください。';
    private const INVALID_MESSAGE = '正しく入力してください。';

    /** @var list<string> */
    private const REQUIRED_FIELDS = [
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
    ];

    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfToken $csrf,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE goShoppingNonMember — show the guest-info entry form.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state). Fields mirror SubmitNonMemberInput. `csrfToken` carries
     * the trusted reference the HTML form echoes back on POST.
     */
    #[Alps('goShoppingNonMember')]
    #[JsonSchema(schema: 'get-shopping-non-member.json')]
    #[Link(rel: 'doSubmitNonMember', href: 'page://self/shopping/non-member', method: 'post')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
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
            // Phase 3: an empty NonMemberForm for the HTML port to render
            // the guest-info inputs. JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(NonMemberForm::class),
        ];

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
        string $sessionPrefix = 'session-prefix-1',
    ): static {
        $values = [
            'name01' => $name01,
            'name02' => $name02,
            'kana01' => $kana01,
            'kana02' => $kana02,
            'email' => $email,
            'phoneNumber' => $phoneNumber,
            'postalCode' => $postalCode,
            'pref' => $pref,
            'addr01' => $addr01,
            'addr02' => $addr02,
        ];
        $messages = $this->requiredMessages($values);
        $prefId = $this->prefId($pref);
        if ($pref !== null && ! $this->isBlank((string) $pref) && $prefId === null) {
            $messages['pref'] = self::INVALID_MESSAGE;
        }

        if ($messages !== []) {
            return $this->badRequestForm($values, $messages);
        }

        assert($name01 !== null && $name02 !== null);
        assert($kana01 !== null && $kana02 !== null);
        assert($email !== null && $phoneNumber !== null);
        assert($postalCode !== null && $addr01 !== null && $addr02 !== null);
        assert($prefId !== null);

        $final = ($this->becoming)(new SubmitNonMemberInput(
            name01: $name01,
            name02: $name02,
            kana01: $kana01,
            kana02: $kana02,
            email: $email,
            phoneNumber: $phoneNumber,
            postalCode: $postalCode,
            pref: $prefId,
            addr01: $addr01,
            addr02: $addr02,
            sessionPrefix: $sessionPrefix,
        ));

        assert($final instanceof NonMemberSubmitted);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/shopping?preOrderId=%s', $final->preOrderId);
        $this->body = [
            'preOrderId' => $final->preOrderId,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'email' => $final->email,
        ];

        return $this;
    }

    /**
     * @param array<string, string|int|null> $values
     * @return array<string, string>
     */
    private function requiredMessages(array $values): array
    {
        $messages = [];
        foreach (self::REQUIRED_FIELDS as $field) {
            /** @var string|int|null $value */
            $value = $values[$field] ?? null;
            if ($this->isBlank((string) $value)) {
                $messages[$field] = self::REQUIRED_MESSAGE;
            }
        }

        return $messages;
    }

    private function prefId(int|string|null $pref): int|null
    {
        if (is_int($pref)) {
            return $pref;
        }

        if (! is_string($pref)) {
            return null;
        }

        $pref = trim($pref);
        if ($pref === '' || ! ctype_digit($pref)) {
            return null;
        }

        return (int) $pref;
    }

    private function isBlank(string $value): bool
    {
        return trim($value) === '';
    }

    /**
     * @param array<string, string|int|null> $values
     * @param array<string, string>          $messages
     */
    private function badRequestForm(array $values, array $messages): static
    {
        $this->code = Code::BAD_REQUEST;
        $this->body = [
            'transitionId' => 'goShoppingNonMember',
            'fields' => self::REQUIRED_FIELDS,
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/shopping/non-member',
            ],
            'csrfToken' => $this->csrf->token,
            'message' => self::REQUIRED_MESSAGE,
            'form' => $this->failedForm($values, $messages),
        ];

        return $this;
    }

    /**
     * @param array<string, string|int|null> $values
     * @param array<string, string>          $messages
     */
    private function failedForm(array $values, array $messages): NonMemberForm
    {
        $form = $this->formFactory->newInstance(NonMemberForm::class);
        assert($form instanceof NonMemberForm);

        $form->fillValues(array_filter(
            $values,
            static fn (string|int|null $value): bool => $value !== null,
        ));

        foreach ($messages as $field => $message) {
            $form->setDomainError($field, $message);
        }

        return $form;
    }
}
