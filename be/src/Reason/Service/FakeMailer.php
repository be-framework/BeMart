<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

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
}
