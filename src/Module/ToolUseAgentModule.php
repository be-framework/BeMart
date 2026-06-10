<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\ToolUse\Llm\LlmClientInterface;
use BEAR\ToolUse\Module\ToolUseModule;
use MyVendor\BeMart\ToolUse\DemoLlmClient;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/** Optional BEAR.ToolUse agent-runtime bindings for BeMart. */
final class ToolUseAgentModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new ToolUseModule());
        $this->bind(LlmClientInterface::class)->to(DemoLlmClient::class)->in(Scope::SINGLETON);
    }
}
