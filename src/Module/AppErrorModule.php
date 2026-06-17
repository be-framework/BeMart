<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use MyVendor\BeMart\Provide\Error\AppThrowableHandler;
use MyVendor\BeMart\Provide\Error\ExceptionStatusMapper;
use Override;
use Ray\Di\AbstractModule;

final class AppErrorModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        // Shared by AppThrowableHandler (JSON) and the html-context
        // HtmlThrowableHandler so both agree on status + message.
        $this->bind(ExceptionStatusMapper::class);
        $this->bind(ThrowableHandlerInterface::class)->to(AppThrowableHandler::class);
    }
}
