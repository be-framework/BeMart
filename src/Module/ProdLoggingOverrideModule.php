<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Be\Framework\Becoming;
use Be\Framework\BecomingInterface;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/**
 * Production logging bindings.
 *
 * AppModule (dev default) wraps BecomingInterface with DevBecoming, which
 * unconditionally writes var/log/bemart.json after every Becoming invocation.
 * The log captures every #[Input] value and Being public property — i.e.
 * customerIds, prices, line items, payment totals — in plaintext.
 *
 * In production this file write must be off. ProdModule installs AppModule
 * then overrides with this module, which rebinds BecomingInterface directly
 * to Be\Framework\Becoming (no DevBecoming wrapper, no file write) and
 * SemanticLoggerInterface to the plain SemanticLogger (no profiling).
 *
 * In-memory semantic traces are still produced by the framework — they are
 * just never persisted.
 */
final class ProdLoggingOverrideModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(BecomingInterface::class)->to(Becoming::class);
        $this->bind(SemanticLoggerInterface::class)->to(SemanticLogger::class)->in(Scope::SINGLETON);
    }
}
