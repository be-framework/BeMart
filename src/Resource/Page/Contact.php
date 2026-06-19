<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Ray\Csrf\Attribute\CsrfToken;
use MyVendor\BeMart\Be\Exception\ContactContentsFormatException;
use MyVendor\BeMart\Be\Exception\EmailFormatException;
use MyVendor\BeMart\Be\Exception\Name01FormatException;
use MyVendor\BeMart\Be\Exception\Name02FormatException;
use MyVendor\BeMart\Be\Final\ContactSubmitted;
use MyVendor\BeMart\Be\Input\SubmitContactInput;
use Ray\Csrf\CsrfTokenInterface;
use MyVendor\BeMart\Form\ContactForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function array_filter;
use function array_values;
use function assert;
use function trim;
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
            'csrfToken' => $this->csrf->issue(),
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
    #[CsrfToken]
    public function onPost(
        string|null $contactName01 = null,
        string|null $contactName02 = null,
        string|null $contactEmail = null,
        string|null $contactContents = null,
        string|null $mode = null,
    ): static {
        $values = [
            'contactName01' => $contactName01 ?? '',
            'contactName02' => $contactName02 ?? '',
            'contactEmail' => $contactEmail ?? '',
            'contactContents' => $contactContents ?? '',
        ];
        $browserForm = $mode !== null;
        if ($browserForm) {
            $errors = $this->formErrors($values);
            if ($errors !== []) {
                return $this->rejectForm($values, $errors);
            }
        }

        try {
            $final = ($this->becoming)(new SubmitContactInput(
                contactName01: $values['contactName01'],
                contactName02: $values['contactName02'],
                contactEmail: $values['contactEmail'],
                contactContents: $values['contactContents'],
            ));
        } catch (SemanticVariableException $e) {
            if (! $browserForm) {
                throw $e;
            }

            [$field, $message] = self::semanticError($e);

            return $this->rejectForm($values, [$field => $message]);
        }

        assert($final instanceof ContactSubmitted);

        // Post/Redirect/Get: a successful submit redirects to the
        // completion page. A browser form post (mode set) gets 303 so the
        // browser actually follows the `Location`; JSON/Resource clients
        // keep the projected body with 200 OK (mirrors Admin\Login::onPost).
        $this->headers['Location'] = '/contact/complete?ticketId=' . rawurlencode($final->ticketId);
        $this->code = $browserForm ? Code::SEE_OTHER : Code::OK;
        $this->body = [
            'contactName01' => $final->contactName01,
            'contactName02' => $final->contactName02,
            'contactEmail' => $final->contactEmail,
            'ticketId' => $final->ticketId,
        ];

        return $this;
    }

    /** @param array<string, string> $values */
    private function formErrors(array $values): array
    {
        $errors = [];
        foreach ([
            'contactName01' => '入力してください。',
            'contactName02' => '入力してください。',
            'contactEmail' => '入力してください。',
            'contactContents' => '入力してください。',
        ] as $field => $message) {
            if (trim($values[$field]) === '') {
                $errors[$field] = $message;
            }
        }

        return $errors;
    }

    /** @param array<string, string> $values */
    private function rejectForm(array $values, array $errors): static
    {
        $this->code = Code::BAD_REQUEST;
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
            'csrfToken' => $this->csrf->issue(),
            'message' => array_values($errors)[0] ?? '入力内容を確認してください。',
            'errors' => $errors,
            'form' => $this->failedForm($values, $errors),
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
    /** @param array<string, string> $values */
    private function failedForm(array $values, array $errors): ContactForm
    {
        $form = $this->formFactory->newInstance(ContactForm::class);
        assert($form instanceof ContactForm);

        $form->fillValues(array_filter([
            'contactName01' => $values['contactName01'],
            'contactName02' => $values['contactName02'],
            'contactEmail' => $values['contactEmail'],
            'contactContents' => $values['contactContents'],
        ], static fn (string $v): bool => $v !== ''));
        foreach ($errors as $field => $message) {
            $form->setDomainError($field, $message);
        }

        return $form;
    }

    /** @return array{0: string, 1: string} */
    private static function semanticError(SemanticVariableException $e): array
    {
        $exception = $e->getErrors()->exceptions[0] ?? null;
        $message = $e->getErrors()->getMessages('ja')[0] ?? '入力内容を確認してください。';

        $field = match (true) {
            $exception instanceof Name01FormatException => 'contactName01',
            $exception instanceof Name02FormatException => 'contactName02',
            $exception instanceof EmailFormatException => 'contactEmail',
            $exception instanceof ContactContentsFormatException => 'contactContents',
            default => 'contactEmail',
        };

        return [$field, $message];
    }
}
