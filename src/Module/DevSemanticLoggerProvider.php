<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Koriym\SemanticLogger\DevSemanticLogger;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Override;
use Ray\Di\ProviderInterface;

/** @implements ProviderInterface<SemanticLoggerInterface> */
final class DevSemanticLoggerProvider implements ProviderInterface
{
    #[Override]
    public function get(): SemanticLoggerInterface
    {
        return new DevSemanticLogger(new SemanticLogger());
    }
}
