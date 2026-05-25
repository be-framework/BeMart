<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use DateTimeImmutable;
use MyVendor\BeMart\Be\Reason\Entity\PasswordResetTokenEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerQueryInterface;
use MyVendor\BeMart\Be\Reason\Query\PasswordResetTokenStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\MailerInterface;
use MyVendor\BeMart\Be\Reason\Service\ResetKeyGeneratorInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

/**
 * Password reset requested — Final, proof the request was processed.
 *
 *   RequestPasswordResetInput → PasswordResetRequested
 *
 * Anti-enumeration: existence of this object does NOT imply the
 * email was registered. When the email maps to a real customer:
 *   - a fresh reset key is generated and persisted
 *   - the reset email is dispatched
 * When it does not, the Final still constructs successfully and
 * NO mail is sent. The caller cannot distinguish the two cases.
 *
 * The token TTL is 1 hour (matches EC-CUBE's default
 * `reset_expire` setting). The exact moment is captured server-side;
 * the doResetPassword consumer (deferred to a future pilot) will
 * compare against `now()`.
 */
final readonly class PasswordResetRequested
{
    public string $email;
    public bool $issued;

    public function __construct(
        #[Input] string $email,
        #[Inject] CustomerQueryInterface $customerQuery,
        #[Inject] PasswordResetTokenStorageInterface $tokenStorage,
        #[Inject] ResetKeyGeneratorInterface $resetKeyGenerator,
        #[Inject] MailerInterface $mailer,
    ) {
        $customer = $customerQuery->byEmail($email);
        $this->email = $email;

        if ($customer === null) {
            $this->issued = false;

            return;
        }

        $resetKey = $resetKeyGenerator->generate();
        $tokenStorage->put(new PasswordResetTokenEntity(
            customerId: $customer->customerId,
            resetKey: $resetKey,
            expiresAt: (new DateTimeImmutable('now'))->modify('+1 hour'),
        ));
        $mailer->sendPasswordReset($email, $resetKey);

        $this->issued = true;
    }
}
