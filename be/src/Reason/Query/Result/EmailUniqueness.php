<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class EmailUniqueness implements PostQueryInterface
{
    public bool $unique;

    /** @param bool|array<int, self|array<string, mixed>> $unique */
    public function __construct(bool|array $unique)
    {
        if (is_array($unique)) {
            $row = $unique[0] ?? null;
            $unique = $row instanceof self ? $row->unique : $unique === [];
        }

        $this->unique = $unique;
    }

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
