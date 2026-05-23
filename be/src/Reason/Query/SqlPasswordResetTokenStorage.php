<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use DateTimeImmutable;
use MyVendor\BeMart\Be\Reason\Entity\PasswordResetTokenEntity;
use Override;

use function ctype_digit;

final class SqlPasswordResetTokenStorage implements PasswordResetTokenStorageInterface
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private readonly InternalDbQueryInterface $db) {}

    #[Override]
    public function put(PasswordResetTokenEntity $token): void
    {
        if (! ctype_digit($token->customerId)) {
            return;
        }
        $this->db->password_reset_put(id: (int) $token->customerId, resetKey: $token->resetKey, resetExpire: $token->expiresAt->format(self::DATETIME_FORMAT));
    }

    #[Override]
    public function getByResetKey(string $resetKey): PasswordResetTokenEntity|null
    {
        $row = $this->db->password_reset_get(resetKey: $resetKey);
        if ($row === null) {
            return null;
        }
        $expiresAt = $row['reset_expire'] === null ? new DateTimeImmutable('-1 second') : new DateTimeImmutable((string) $row['reset_expire']);
        return new PasswordResetTokenEntity((string) $row['id'], (string) $row['reset_key'], $expiresAt);
    }

    #[Override]
    public function delete(string $resetKey): void
    {
        $this->db->password_reset_delete(resetKey: $resetKey);
    }
}
