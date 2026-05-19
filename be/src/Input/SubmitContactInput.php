<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Input;

use Be\Framework\Attribute\Be;
use MyVendor\BeMart\Be\Final\ContactSubmitted;

/**
 * Input for doSubmitContact — Pilot 15 (contact form).
 *
 * Direct pattern. The Final renders both the shop-bound mail and the
 * auto-reply via MailerInterface::sendContactInquiry.
 *
 *   SubmitContactInput → ContactSubmitted (Final)
 *
 * No AUTHN — anonymous customers can submit inquiries.
 *
 * @link https://schema.org/ContactPoint
 */
#[Be(ContactSubmitted::class)]
final readonly class SubmitContactInput
{
    /**
     * @psalm-taint-source input $contactName01
     * @psalm-taint-source input $contactName02
     * @psalm-taint-source input $contactEmail
     * @psalm-taint-source input $contactContents
     */
    public function __construct(
        public string $contactName01,
        public string $contactName02,
        public string $contactEmail,
        public string $contactContents,
    ) {
    }
}
