<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Entry;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\SecretKeyNotFoundException;
use MyVendor\BeMart\Be\Final\CustomerActivated;
use MyVendor\BeMart\Be\Input\ActivateCustomerInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;
use function sprintf;

/**
 * EC-CUBE doActivateCustomer — provisional → active (Pilot 7).
 *
 * The email-link UX in EC-CUBE is GET, but the operation has side
 * effects (status flip + secretKey clear) so the Be migration uses
 * onPost behind a one-button confirmation form. Both the secretKey
 * and a CSRF token are submitted; the secretKey is the per-customer
 * proof-of-email-receipt, and the CSRF token guards against drive-by
 * activation triggered by another origin.
 *
 * Failure mapping:
 *   - SemanticVariableException    → 400 (secretKey malformed)
 *   - SecretKeyNotFoundException   → 404 (wrong key / expired / already used)
 *
 * Idempotent: re-activating a customer is a no-op on the storage side
 * but still returns 200 from this resource — the caller cannot tell
 * "first activate" from "second activate", which is correct.
 */
class Activate extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $secretKey
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goLogin', href: 'page://self/login')]
    public function onPost(string $secretKey, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new ActivateCustomerInput(secretKey: $secretKey));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (SecretKeyNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '本登録リンクが無効か、既に使用済みです。'];

            return $this;
        }

        assert($final instanceof CustomerActivated);

        $this->code = Code::OK;
        $this->headers['Location'] = sprintf('/customer/%s', $final->customerId);
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'customerStatus' => $final->customerStatus,
        ];

        return $this;
    }
}
