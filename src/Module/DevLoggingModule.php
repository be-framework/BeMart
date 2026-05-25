<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Be\Framework\Becoming;
use Be\Framework\BecomingInterface;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/** Dev/test logging wrapper that writes var/log/bemart.json. */
final class DevLoggingModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(Becoming::class);
        $this->bind(BecomingInterface::class)->toProvider(DevBecomingProvider::class);
        $this->bind(SemanticLoggerInterface::class)
            ->toProvider(DevSemanticLoggerProvider::class)
            ->in(Scope::SINGLETON);
    }
}
