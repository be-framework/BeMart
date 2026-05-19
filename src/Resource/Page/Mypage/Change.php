<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\CustomerUpdated;
use MyVendor\BeMart\Be\Input\UpdateCustomerInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doUpdateCustomer — マイページから会員情報を更新 (Pilot 8).
 *
 * AUTHZ via the Be layer: the customerId for the update target is
 * the SessionInterface's value — never the request body — so an
 * authenticated customer cannot edit another customer's record by
 * tampering with form fields (Pilot 5 F-2 lesson).
 *
 * Failure mapping:
 *   - SemanticVariableException        → 400 (field format invalid)
 *   - UnauthenticatedException         → 401 (no session)
 *   - EmailAlreadyRegisteredException  → 409 (email change collides)
 */
class Change extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $email
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
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    public function onPost(
        string $email,
        string|null $name01 = null,
        string|null $name02 = null,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $companyName = null,
        string|null $phoneNumber = null,
        string|null $postalCode = null,
        int|null $pref = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateCustomerInput(
                email: $email,
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
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthenticatedException) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        } catch (EmailAlreadyRegisteredException) {
            $this->code = 409;
            $this->body = ['message' => 'The new email is already registered.', 'email' => $email];

            return $this;
        }

        assert($final instanceof CustomerUpdated);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'name01' => $final->name01,
            'name02' => $final->name02,
        ];

        return $this;
    }
}
