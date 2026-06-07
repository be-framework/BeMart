<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Final\ContactSubmitted;
use MyVendor\BeMart\Be\Input\SubmitContactInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\ContactForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function array_filter;
use function assert;
use function rawurlencode;

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
        private readonly CsrfToken $csrf,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE goContactForm — show the contact form scaffolding.
     *
     * Pure form-info endpoint: no Be Framework involved, no domain
     * logic. Anonymous-accessible (returns 200 regardless of session
     * state). `csrfToken` carries the trusted reference
     * {@see CsrfToken::$token} issues — the HTML port
     * renders it into the form's hidden `_token` input so the
     * subsequent POST passes CSRF validation.
     */
    #[Alps('goContactForm')]
    #[JsonSchema(schema: 'get-contact.json')]
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
                'rel' => 'doSubmitContact',
                'method' => 'POST',
                'href' => 'page://self/contact',
            ],
            'csrfToken' => $this->csrf->token,
            // Phase 3: an empty ContactForm for the HTML port to render
            // via `{{ form.input(...) }}`. JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(ContactForm::class),
        ];

        return $this;
    }

    /**
     * ALPS `doSubmitContact` に対応する POST 操作。
     * @psalm-taint-source input $contactName01
     * @psalm-taint-source input $contactName02
     * @psalm-taint-source input $contactEmail
     * @psalm-taint-source input $contactContents
     */
    #[Alps('doSubmitContact')]
    #[JsonSchema(schema: 'post-contact.json', params: 'post-contact.param.json')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    #[CsrfProtected]
    public function onPost(
        string $contactName01,
        string $contactName02,
        string $contactEmail,
        string $contactContents,
    ): static {
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
        $this->headers['Location'] = '/contact/complete?ticketId=' . rawurlencode($final->ticketId);
        $this->body = [
            'contactName01' => $final->contactName01,
            'contactName02' => $final->contactName02,
            'contactEmail' => $final->contactEmail,
            'ticketId' => $final->ticketId,
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
