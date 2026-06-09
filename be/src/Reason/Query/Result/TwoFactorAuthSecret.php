<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class TwoFactorAuthSecret implements PostQueryInterface
{
    public string|null $secret;

    /** @param string|null|array<int, self|array<string, mixed>> $secret */
    public function __construct(string|null|array $secret)
    {
        if (is_array($secret)) {
            $row = $secret[0] ?? null;
            $secret = $row instanceof self ? $row->secret : (is_array($row) && isset($row['two_factor_auth_key']) ? (string) $row['two_factor_auth_key'] : null);
        }

        $this->secret = $secret;
    }

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        $value = is_array($row) && isset($row['two_factor_auth_key']) ? (string) $row['two_factor_auth_key'] : null;

        return new static($value);
    }
}
