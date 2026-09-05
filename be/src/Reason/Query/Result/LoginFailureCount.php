<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use MyVendor\BeMart\Be\Exception\LoginAttemptsExceededException;
use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class LoginFailureCount implements PostQueryInterface
{
    public function __construct(public int $count)
    {
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        /** @var array<string, mixed> $row */
        $row = $context->rows[0] ?? [];

        return new static((int) ($row['failures'] ?? 0));
    }

    /**
     * @throws LoginAttemptsExceededException
     *
     * @psalm-suppress InvalidDocblock Psalm treats assert* methods as assertion helpers.
     */
    public function assertBelow(int $threshold): void
    {
        if ($this->count >= $threshold) {
            throw new LoginAttemptsExceededException();
        }
    }
}
