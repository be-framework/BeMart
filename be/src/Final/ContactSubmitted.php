<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Reason\Entity\ContactEntity;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function uniqid;

/**
 * Contact submitted — Final, proof the contact mails were dispatched.
 *
 *   SubmitContactInput → ContactSubmitted
 *
 * EC-CUBE spec (ALPS doc): "店舗のshopEmail02宛にメール送信し、送信者
 * にも自動返信メールを送る". Both are encapsulated inside
 * MailerInterface::sendContactInquiry; the impl is responsible for
 * fanning out to recipients (Phase 2 with EC-CUBE's MailService will
 * implement the actual recipient routing).
 */
final readonly class ContactSubmitted
{
    public string $contactName01;
    public string $contactName02;
    public string $contactEmail;
    public string $ticketId;

    public function __construct(
        #[Input]
        string $contactName01,
        #[Input]
        string $contactName02,
        #[Input]
        string $contactEmail,
        #[Input]
        string $contactContents,
        #[Inject]
        MailerInterface $mailer,
    ) {
        $mailer->sendContactInquiry(new ContactEntity(
            contactName01: $contactName01,
            contactName02: $contactName02,
            contactEmail: $contactEmail,
            contactContents: $contactContents,
        ));

        $this->contactName01 = $contactName01;
        $this->contactName02 = $contactName02;
        $this->contactEmail = $contactEmail;
        $this->ticketId = uniqid('INQ-', true);
    }
}
