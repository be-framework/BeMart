<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Be\Reason\Fake\Query\SessionCartStorage;
use MyVendor\BeMart\Be\Reason\Query\CartCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/** Installs session-backed mutable carts for browser Fake contexts only. */
final class HtmlFakeCartModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(SessionCartStorage::class)->in(Scope::SINGLETON);
        $this->bind(CartQueryInterface::class)->to(SessionCartStorage::class)->in(Scope::SINGLETON);
        $this->bind(CartCommandInterface::class)->to(SessionCartStorage::class)->in(Scope::SINGLETON);
    }
}
