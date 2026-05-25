<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Entity\ContactEntity;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use Override;

final class NoopMailer implements MailerInterface
{
    #[Override]
    public function sendOrderConfirmation(FinalizedOrderEntity $order): void
    {
    }

    #[Override]
    public function sendContactInquiry(ContactEntity $contact): void
    {
    }

    #[Override]
    public function sendPasswordReset(string $email, string $resetKey): void
    {
    }

    #[Override]
    public function sendWithdrawConfirmation(string $email, string $name01, string $name02): void
    {
    }

    #[Override]
    public function sendShippingNotification(FinalizedOrderEntity $order, string|null $trackingNumber): void
    {
    }

    #[Override]
    public function sendCustomerActivation(string $email, string $secretKey): void
    {
    }
}
