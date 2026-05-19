<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use MyVendor\BeMart\Be\Final\CustomerRegistered;
use MyVendor\BeMart\Be\Input\RegisterCustomerInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;
use function sprintf;

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
 * @see RegisterCustomerInput  Pilot 4 scope note
 */
class Entry extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
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
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onPost(
        string $email,
        string $password,
        string $name01,
        string $name02,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $companyName = null,
        string|null $phoneNumber = null,
        string|null $postalCode = null,
        int|null $pref = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $birth = null,
        int|null $sex = null,
        int|null $job = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new RegisterCustomerInput(
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
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'email' => $email,
            ];

            return $this;
        } catch (EmailAlreadyRegisteredException) {
            // BEAR\Resource\Code lacks CONFLICT; use the integer literal
            // (same convention as Pilot 2's OutOfStockException).
            $this->code = 409;
            $this->body = ['message' => 'The email is already registered.', 'email' => $email];

            return $this;
        }

        assert($final instanceof CustomerRegistered);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/customer/%s', $final->customerId);
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
}
