<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
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
use function mb_strlen;
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
        private readonly ResourceInterface $resource,
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
    public function onGet(
        string|null $contactName01 = null,
        string|null $contactName02 = null,
        string|null $contactEmail = null,
        string|null $contactContents = null,
    ): static {
        $form = $this->formFactory->newInstance(ContactForm::class);
        assert($form instanceof ContactForm);
        // When the editable form is re-shown (EC-CUBE `mode=back` 戻る), the
        // submitted inquiry is pre-filled so the customer can edit it. A
        // plain GET passes no values and renders the empty form. The values
        // ride on the form object only — the response body shape is unchanged.
        $form->fillValues(array_filter([
            'contactName01' => $contactName01 ?? '',
            'contactName02' => $contactName02 ?? '',
            'contactEmail' => $contactEmail ?? '',
            'contactContents' => $contactContents ?? '',
        ], static fn (string $v): bool => $v !== ''));

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
            // Phase 3: a ContactForm for the HTML port to render via
            // `{{ form.input(...) }}`. JSON contexts ignore it.
            'form' => $form,
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

        // EC-CUBE ContactController state machine (mode POST param):
        //   confirm  -> render the read-only CONFIRM (review) screen, NO send.
        //   back     -> return to the editable input form (戻る button).
        //   complete -> actually send + redirect to the completion page.
        //   commit   -> alias for complete (BeMart submit-button convention).
        // A JSON / hypermedia client sends no `mode`: it keeps the collapsed
        // doSubmitContact behaviour (send immediately, 200 + body).
        if ($browserForm && $mode === 'back') {
            return $this->reeditForm($values);
        }

        if ($browserForm) {
            $errors = $this->formErrors($values);
            if ($errors !== []) {
                return $this->rejectForm($values, $errors);
            }

            if ($mode === 'confirm') {
                return $this->renderConfirm($values);
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
     * EC-CUBE `mode=confirm` — render the read-only CONFIRM (review) screen.
     *
     * No send happens here (the becoming chain is NOT run): the entered
     * inquiry is handed to the Confirm resource, which renders
     * `Contact/Confirm.html.twig` with the values re-shown as plain text and
     * carried forward as hidden inputs. The rendered confirm page becomes
     * this response's body/view, so the browser sees the review screen at
     * `/contact` without a redirect (mirrors EC-CUBE ContactController's
     * `render('Contact/confirm.twig', ...)`). The response body stays
     * `post-contact.json`-shaped (ticketId empty — nothing sent yet) so the
     * JSON-schema response contract still holds.
     *
     * @param array<string, string> $values
     */
    private function renderConfirm(array $values): static
    {
        $confirm = $this->resource->get('page://self/contact/confirm', $values);

        $this->code = Code::OK;
        $this->view = $confirm->toString();
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->body = [
            'contactName01' => $values['contactName01'],
            'contactName02' => $values['contactName02'],
            // Schema projection only; the real value is re-shown in the
            // rendered confirm view. Echo it into the `email`-typed body
            // field only when it satisfies the transport length floor.
            'contactEmail' => mb_strlen($values['contactEmail']) >= 3 ? $values['contactEmail'] : null,
            'ticketId' => '',
        ];

        return $this;
    }

    /**
     * EC-CUBE `mode=back` (戻る) — return to the editable input form.
     *
     * The confirm screen's 戻る button re-posts the inquiry with
     * `mode=back`; EC-CUBE falls through its switch and re-renders the input
     * form (`Contact/index.twig`) with the submitted data. Here the entered
     * values are re-shown in the editable {@see ContactForm}; nothing is
     * sent and no inline error is raised.
     *
     * @param array<string, string> $values
     */
    private function reeditForm(array $values): static
    {
        // Re-render the editable input form (Contact::onGet) with the entered
        // inquiry pre-filled, so the customer can edit and re-confirm. The
        // rendered input page becomes this response's view; the body stays
        // `post-contact.json`-shaped (ticketId empty — nothing sent) so the
        // response contract holds.
        $form = $this->resource->get('page://self/contact', $values);

        $this->code = Code::OK;
        $this->view = $form->toString();
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        // Body is the schema-satisfying projection only — the editable form
        // (with the real, possibly still-unvalidated values) lives in the
        // rendered view. `back` runs before format validation, so the raw
        // email is not echoed into the `format:email` body field.
        $this->body = [
            'contactName01' => $values['contactName01'],
            'contactName02' => $values['contactName02'],
            'contactEmail' => null,
            'ticketId' => '',
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
