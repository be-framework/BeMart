<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Entity\PasswordResetTokenEntity;
use Override;

final class FakePasswordResetTokenStorage implements PasswordResetTokenStorageInterface
{
    /** @var array<string, PasswordResetTokenEntity> keyed by customerId */
    private array $byCustomerId = [];

    #[Override]
    public function put(PasswordResetTokenEntity $token): void
    {
        $this->byCustomerId[$token->customerId] = $token;
    }

    #[Override]
    public function getByResetKey(string $resetKey): PasswordResetTokenEntity|null
    {
        foreach ($this->byCustomerId as $token) {
            if ($token->resetKey === $resetKey) {
                return $token;
            }
        }

        return null;
    }

    #[Override]
    public function delete(string $resetKey): void
    {
        foreach ($this->byCustomerId as $customerId => $token) {
            if ($token->resetKey === $resetKey) {
                unset($this->byCustomerId[$customerId]);

                return;
            }
        }
    }
}
