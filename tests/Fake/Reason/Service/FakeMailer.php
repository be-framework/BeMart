<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use MyVendor\BeMart\Be\Reason\Entity\ContactEntity;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use Override;

/**
 * Phase 1 fake mailer. Records every order-confirmation request so tests
 * can prove the Final invoked sendOrderConfirmation exactly once even when
 * several convergence Reasons run side-by-side.
 *
 * Production binding wraps EC-CUBE's MailService::sendOrderMail.
 */
final class FakeMailer implements MailerInterface
{
    /** @var list<FinalizedOrderEntity> */
    public array $sent = [];

    /** @var list<ContactEntity> */
    public array $contactInquiries = [];

    /** @var list<array{email: string, resetKey: string}> */
    public array $passwordResets = [];

    /** @var list<array{email: string, name01: string, name02: string}> */
    public array $withdrawConfirmations = [];

    /** @var list<array{order: FinalizedOrderEntity, trackingNumber: string|null}> */
    public array $shippingNotifications = [];

    /** @var list<array{email: string, secretKey: string}> */
    public array $customerActivations = [];

    #[Override]
    public function sendOrderConfirmation(FinalizedOrderEntity $order): void
    {
        $this->sent[] = $order;
    }

    #[Override]
    public function sendContactInquiry(ContactEntity $contact): void
    {
        $this->contactInquiries[] = $contact;
    }

    #[Override]
    public function sendPasswordReset(string $email, string $resetKey): void
    {
        $this->passwordResets[] = ['email' => $email, 'resetKey' => $resetKey];
    }

    #[Override]
    public function sendWithdrawConfirmation(string $email, string $name01, string $name02): void
    {
        $this->withdrawConfirmations[] = [
            'email' => $email,
            'name01' => $name01,
            'name02' => $name02,
        ];
    }

    #[Override]
    public function sendShippingNotification(FinalizedOrderEntity $order, string|null $trackingNumber): void
    {
        $this->shippingNotifications[] = [
            'order' => $order,
            'trackingNumber' => $trackingNumber,
        ];
    }

    #[Override]
    public function sendCustomerActivation(string $email, string $secretKey): void
    {
        $this->customerActivations[] = ['email' => $email, 'secretKey' => $secretKey];
    }
}
