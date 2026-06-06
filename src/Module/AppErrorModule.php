<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use MyVendor\BeMart\Provide\Error\AppThrowableHandler;
use Override;
use Ray\Di\AbstractModule;

final class AppErrorModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(ThrowableHandlerInterface::class)->to(AppThrowableHandler::class);
    }
}
