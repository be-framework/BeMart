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
use MyVendor\BeMart\Form\ContactForm;
use Ray\WebFormModule\FormFactory;

use function array_filter;
use function assert;

/**
 * EC-CUBE doSubmitContact — お問い合わせ送信 (Pilot 15).
 *
 * Anonymous-accessible: no AUTHN, no AUTHZ. CSRF guard remains
 * (Slice 8 uniformity).
 *
 * Phase 3 — HTML FORM page. The resource builds a {@see ContactForm}
 * (Ray.WebFormModule AbstractForm) and exposes it as `body['form']` so
 * the HTML port renders real `<input>` / `<textarea>` via
 * `{{ form.input(...) }}`. VALIDATION AUTHORITY STAYS WITH the Be
 * Framework Becoming chain. The JSON contexts ignore `body['form']`.
 */
class Contact extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE goContactForm — show the contact form scaffolding.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state). `csrfToken` carries the trusted reference
     * {@see CsrfTokenInterface::getToken()} issues — the HTML port
     * renders it into the form's hidden `_token` input so the
     * subsequent POST passes CSRF validation.
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
            'csrfToken' => $this->csrf->getToken(),
            // Phase 3: an empty ContactForm for the HTML port to render
            // via `{{ form.input(...) }}`. JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(ContactForm::class),
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
            $message = $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.';
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $message,
                'form' => $this->failedForm(
                    $contactName01,
                    $contactName02,
                    $contactEmail,
                    $contactContents,
                    $message,
                ),
            ];

            return $this;
        }

        assert($final instanceof ContactSubmitted);

        // Post/Redirect/Get: a successful submit redirects to the
        // completion page. The resource returns `Code::OK` + a `Location`
        // header (mirrors Admin\Login::onPost) — the HTTP layer turns
        // that into a browser redirect, while JSON clients still read the
        // projected body. Rendering Contact.html.twig against this body
        // is never attempted: the redirect supersedes it.
        $this->code = Code::OK;
        $this->headers['Location'] = '/contact/complete';
        $this->body = [
            'contactName01' => $final->contactName01,
            'contactName02' => $final->contactName02,
            'contactEmail' => $final->contactEmail,
        ];

        return $this;
    }

    /**
     * Builds a ContactForm reflecting a rejected POST.
     *
     * The Becoming chain has already reached the verdict; this only
     * transports it onto the form so the HTML page re-renders with the
     * entered values and the inline error. Validation authority remains
     * with Be — the form is a renderer here, never a validator.
     */
    private function failedForm(
        string $contactName01,
        string $contactName02,
        string $contactEmail,
        string $contactContents,
        string $message,
    ): ContactForm {
        $form = $this->formFactory->newInstance(ContactForm::class);
        assert($form instanceof ContactForm);

        $form->fillValues(array_filter([
            'contactName01' => $contactName01,
            'contactName02' => $contactName02,
            'contactEmail' => $contactEmail,
            'contactContents' => $contactContents,
        ], static fn (string $v): bool => $v !== ''));
        $form->setDomainError('contactEmail', $message);

        return $form;
    }
}
