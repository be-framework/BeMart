<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final class EmailUniqueness implements PostQueryInterface
{
    public function __construct(private readonly bool $unique) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        return new static($context->rows === []);
    }

    /**
     * @throws EmailAlreadyRegisteredException
     * @psalm-suppress InvalidDocblock Psalm treats assert* methods as assertion helpers.
     */
    public function assertUnique(): void
    {
        if (! $this->unique) {
            throw new EmailAlreadyRegisteredException();
        }
    }
}
