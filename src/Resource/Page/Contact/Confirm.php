<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Contact;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Form\ContactConfirmForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

/**
 * EC-CUBE goContactConfirm — お問い合わせ(確認)
 * (Phase 3 — thin pure renderer).
 *
 * NEW RESOURCE — flagged as a follow-up. EC-CUBE's contact flow has a
 * `Contact::confirm` step between `goContactForm` (the form) and
 * `doSubmitContact` (the send): the customer reviews the inquiry before
 * it is sent. EC-CUBE keeps that step on the SAME controller action,
 * branching on the `mode` POST param (`confirm` / `complete` / `back`);
 * BeMart's Pilot 15 collapsed the flow — `Contact::onGet` (form) hands
 * straight to `Contact::onPost` (doSubmitContact) — so no
 * `ContactConfirm` resource existed. Phase 3 needs a page to render
 * `Contact/confirm.twig` against, so this THIN PURE RENDERER is added:
 * no Be Framework, no domain logic, no Reasons.
 *
 * FORM page (the form-page recipe — see var/templates/README.md). The
 * confirm screen re-shows the submitted inquiry as plain text AND
 * carries it forward as HIDDEN inputs so the final submit re-posts the
 * inquiry to `doSubmitContact`. The resource exposes a
 * {@see ContactConfirmForm} (every inquiry field declared `hidden`) as
 * `body['form']`.
 *
 * FOLLOW-UP — the confirm screen's plain-text value cells show the
 * submitted inquiry; a pure `onGet` renderer has no submitted values, so
 * those cells render empty. Threading the submitted payload into the
 * confirm step is a dedicated vertical slice, tracked in the enrichment
 * backlog. Recorded as a MISSING BODY FIELD residual in the render test.
 *
 * Maps to `page://self/contact/confirm`. The submit target is
 * doSubmitContact (`page://self/contact`).
 */
class Confirm extends ResourceObject
{
    public function __construct(
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * ALPS `goContactForm` に対応する GET 操作。
     * @todo Enrichment backlog: thread the submitted inquiry payload into
     *     the confirm step so the value cells re-show the entered data.
     *     Requires a `mode=confirm` POST handler ahead of doSubmitContact.
     */
    #[Alps('goContactForm')]
    #[JsonSchema(schema: 'get-contact-confirm.json')]
    #[Link(rel: 'doSubmitContact', href: 'page://self/contact', method: 'post')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goContactConfirm',
            'fields' => [],
            'submitTo' => [
                'method' => 'POST',
                'href' => 'page://self/contact',
            ],
            'staticContent' => [
                'page' => 'contact-confirm',
                'title' => 'お問い合わせ',
            ],
            // Phase 3: the confirm screen carries the inquiry payload as
            // hidden inputs — a ContactConfirmForm (every field `hidden`).
            // JSON contexts ignore `body['form']`.
            'form' => $this->formFactory->newInstance(ContactConfirmForm::class),
        ];

        return $this;
    }
}
