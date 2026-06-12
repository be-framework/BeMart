<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Contact;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Resource\Annotation\JsonSchema;

/**
 * EC-CUBE goContactComplete — お問い合わせ(完了)
 * (Phase 3 — thin pure renderer).
 *
 * NEW RESOURCE — flagged as a follow-up. EC-CUBE lands on
 * `Contact/complete.twig` after a successful `doSubmitContact`. BeMart's
 * `Contact::onPost` (Pilot 15) returns the `ContactSubmitted` projection
 * and the ALPS surface declares the single transition `goTop` — no
 * `ContactComplete` SCREEN resource ever existed. Phase 3 needs a page
 * to render `Contact/complete.twig` against, so this THIN PURE RENDERER
 * is added: no Be Framework, no domain logic, no Reasons. It exposes
 * only the complete-screen shape + the outbound `goTop` transition.
 *
 * `Contact/complete.twig` is mostly static (the completion message +
 * a top-page button), but the Resource also carries the public receipt
 * `ticketId` issued by doSubmitContact.
 *
 * Maps to `page://self/contact/complete`.
 */
class Complete extends ResourceObject
{
    /** ALPS `goContactComplete` に対応する GET 操作。 */
    #[Alps('goContactComplete')]
    #[JsonSchema(schema: 'get-contact-complete.json', params: 'get-contact-complete.param.json')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(string $ticketId = ''): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goContactComplete',
            'fields' => ['ticketId'],
            'ticketId' => $ticketId,
            'submitTo' => null,
            'staticContent' => [
                'page' => 'contact-complete',
                'title' => 'お問い合わせ(完了)',
            ],
        ];

        return $this;
    }
}
