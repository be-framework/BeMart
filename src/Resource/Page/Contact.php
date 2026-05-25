<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Final\ContactSubmitted;
use MyVendor\BeMart\Be\Input\SubmitContactInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doSubmitContact — お問い合わせ送信 (Pilot 15).
 *
 * Anonymous-accessible: no AUTHN, no AUTHZ. CSRF guard remains
 * (Slice 8 uniformity).
 */
class Contact extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * EC-CUBE goContactForm — show the contact form scaffolding.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state). `csrfToken` body field stays `null` for the same reason
     * described on Login::onGet — EventListener mirrors the Symfony
     * token into the session for the subsequent POST.
     */
    #[Link(rel: 'doSubmitContact', href: 'page://self/contact', method: 'post')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goContactForm',
            'fields' => [
                'contactName01',
                'contactName02',
                'contactEmail',
                'contactContents',
                'csrfToken',
            ],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/contact',
            ],
            'csrfToken' => null,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $contactName01
     * @psalm-taint-source input $contactName02
     * @psalm-taint-source input $contactEmail
     * @psalm-taint-source input $contactContents
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onPost(
        string $contactName01,
        string $contactName02,
        string $contactEmail,
        string $contactContents,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new SubmitContactInput(
                contactName01: $contactName01,
                contactName02: $contactName02,
                contactEmail: $contactEmail,
                contactContents: $contactContents,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        }

        assert($final instanceof ContactSubmitted);

        $this->code = Code::CREATED;
        $this->body = [
            'contactName01' => $final->contactName01,
            'contactName02' => $final->contactName02,
            'contactEmail' => $final->contactEmail,
        ];

        return $this;
    }
}
