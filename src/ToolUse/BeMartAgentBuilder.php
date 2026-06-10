<?php

declare(strict_types=1);

namespace MyVendor\BeMart\ToolUse;

use BEAR\Resource\ResourceInterface;
use BEAR\ToolUse\Dispatch\Dispatcher;
use BEAR\ToolUse\Dispatch\ToolCallObserverInterface;
use BEAR\ToolUse\Dispatch\ToolRegistryInterface;
use BEAR\ToolUse\Llm\LlmClientInterface;
use BEAR\ToolUse\Runtime\AgentDelegator;
use BEAR\ToolUse\Runtime\AgentFactory;
use BEAR\ToolUse\Runtime\AgentInterface;
use BEAR\ToolUse\Runtime\AgentOptions;
use BEAR\ToolUse\Runtime\AgentPool;
use BEAR\ToolUse\Runtime\AgentProfile;
use BEAR\ToolUse\Runtime\AlpsContextInputProcessor;
use BEAR\ToolUse\Runtime\AlpsToolPolicyInputProcessor;
use BEAR\ToolUse\Schema\AlpsSemanticDictionary;
use BEAR\ToolUse\Schema\ToolCollectorInterface;

use function dirname;

/** Builds BeMart agents using BEAR.ToolUse PR #22 Agent-as-Tool APIs. */
final readonly class BeMartAgentBuilder
{
    /** @var list<string> */
    private const CATALOG_RESOURCES = [
        'app://self/agent/catalog',
        'app://self/agent/product',
    ];

    public function __construct(
        private LlmClientInterface $client,
        private ResourceInterface $resource,
        private ToolCollectorInterface $collector,
        private ToolRegistryInterface $registry,
        private ToolCallObserverInterface $observer,
    ) {
    }

    public function createCoordinator(): AgentInterface
    {
        $resourceDispatcher = new Dispatcher($this->resource, $this->registry, $this->observer);
        $pool = $this->catalogPool($resourceDispatcher);
        $delegator = new AgentDelegator($pool, $resourceDispatcher);
        $factory = new AgentFactory($this->client, $delegator, $this->collector, $this->registry);

        return $factory
            ->addSubagents($pool)
            ->addResources(self::CATALOG_RESOURCES)
            ->create('あなたはBeMartの商品案内を行う調整役エージェントです。必要に応じて専門Agentへ委譲し、Resource toolの結果を統合して日本語で短く回答してください。', 6);
    }

    public function createCatalogAgent(): AgentInterface
    {
        $resourceDispatcher = new Dispatcher($this->resource, $this->registry, $this->observer);
        $factory = new AgentFactory($this->client, $resourceDispatcher, $this->collector, $this->registry);

        return $factory
            ->addResources(self::CATALOG_RESOURCES)
            ->create('あなたはBeMartの商品カタログ専門Agentです。catalog_search と product_lookup だけを使い、商品情報を正確に返してください。', 4);
    }

    public function readOnlyOptions(): AgentOptions
    {
        $dictionary = new AlpsSemanticDictionary(dirname(__DIR__, 2) . '/alps.json');

        return AgentOptions::withProcessors(inputProcessors: [
            new AlpsContextInputProcessor($dictionary),
            AlpsToolPolicyInputProcessor::safeOnlyAllowingUnknownTools($dictionary),
        ]);
    }

    /** @return list<string> */
    public function resourceUris(): array
    {
        return self::CATALOG_RESOURCES;
    }

    private function catalogPool(Dispatcher $resourceDispatcher): AgentPool
    {
        $pool = new AgentPool($this->client, $resourceDispatcher, $this->collector);
        $pool->register(new AgentProfile(
            name: 'catalog',
            description: 'BeMartの商品検索と商品詳細確認を行う専門Agent。商品候補や商品コードの確認が必要なときに使います。',
            systemPrompt: 'あなたはBeMartの商品カタログ専門Agentです。Resource toolの結果だけに基づいて、商品名・価格・在庫・説明を短く返してください。',
            resources: self::CATALOG_RESOURCES,
            maxIterations: 4,
            options: $this->readOnlyOptions(),
        ));

        return $pool;
    }
}
