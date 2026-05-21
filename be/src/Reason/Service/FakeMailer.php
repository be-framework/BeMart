<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

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
    private array $sent = [];

    /** @var list<ContactEntity> */
    private array $contactInquiries = [];

    /** @var list<array{email: string, resetKey: string}> */
    private array $passwordResets = [];

    /** @var list<array{email: string, name01: string, name02: string}> */
    private array $withdrawConfirmations = [];

    /** @var list<array{order: FinalizedOrderEntity, trackingNumber: string|null}> */
    private array $shippingNotifications = [];

    #[Override]
    public function sendOrderConfirmation(FinalizedOrderEntity $order): void
    {
        $this->sent[] = $order;
    }

    /** @return list<FinalizedOrderEntity> */
    public function sent(): array
    {
        return $this->sent;
    }

    #[Override]
    public function sendContactInquiry(ContactEntity $contact): void
    {
        $this->contactInquiries[] = $contact;
    }

    /** @return list<ContactEntity> */
    public function contactInquiries(): array
    {
        return $this->contactInquiries;
    }

    #[Override]
    public function sendPasswordReset(string $email, string $resetKey): void
    {
        $this->passwordResets[] = ['email' => $email, 'resetKey' => $resetKey];
    }

    /** @return list<array{email: string, resetKey: string}> */
    public function passwordResets(): array
    {
        return $this->passwordResets;
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

    /** @return list<array{email: string, name01: string, name02: string}> */
    public function withdrawConfirmations(): array
    {
        return $this->withdrawConfirmations;
    }

    #[Override]
    public function sendShippingNotification(FinalizedOrderEntity $order, string|null $trackingNumber): void
    {
        $this->shippingNotifications[] = [
            'order' => $order,
            'trackingNumber' => $trackingNumber,
        ];
    }

    /** @return list<array{order: FinalizedOrderEntity, trackingNumber: string|null}> */
    public function shippingNotifications(): array
    {
        return $this->shippingNotifications;
    }
}
