<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\ToolUse;

use BEAR\ToolUse\Runtime\AgentInterface;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Module\ToolUseAgentModule;
use MyVendor\BeMart\ToolUse\BeMartAgentBuilder;
use PHPUnit\Framework\TestCase;

final class BeMartToolUseTest extends TestCase
{
    public function testCoordinatorDelegatesToCatalogAgent(): void
    {
        $builder = $this->builder();
        $agent = $builder->createCoordinator();
        $this->assertInstanceOf(AgentInterface::class, $agent);

        $response = $agent->run('sample-001 の商品を教えてください', $builder->readOnlyOptions());

        $this->assertTrue($response->completed);
        $this->assertStringContainsString('sample-001', $response->getText());
    }

    public function testCatalogAgentCanSearchProducts(): void
    {
        $builder = $this->builder();
        $agent = $builder->createCatalogAgent();

        $response = $agent->run('商品候補を5件探してください', $builder->readOnlyOptions());

        $this->assertTrue($response->completed);
        $this->assertStringContainsString('カタログ候補:', $response->getText());
    }

    private function builder(): BeMartAgentBuilder
    {
        $injector = Injector::getOverrideInstance('cli-fake-hal-app', new ToolUseAgentModule());
        $builder = $injector->getInstance(BeMartAgentBuilder::class);
        $this->assertInstanceOf(BeMartAgentBuilder::class, $builder);

        return $builder;
    }
}
