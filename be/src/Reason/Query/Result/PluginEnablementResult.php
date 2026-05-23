<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query\Result;

use MyVendor\BeMart\Be\Exception\PluginNotFoundException;
use MyVendor\BeMart\Be\Exception\PluginNotInstalledException;
use Override;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

final readonly class PluginEnablementResult implements PostQueryInterface
{
    public function __construct(public bool $changed) {}

    #[Override]
    public static function fromContext(PostQueryContext $context): static
    {
        $row = $context->rows[0] ?? [];
        if (! is_array($row) || (int) ($row['found'] ?? 0) !== 1) {
            throw new PluginNotFoundException();
        }

        if ((int) ($row['installed'] ?? 0) !== 1) {
            throw new PluginNotInstalledException();
        }

        return new static((int) ($row['changed'] ?? 0) === 1);
    }
}
