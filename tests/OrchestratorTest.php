<?php

declare(strict_types=1);

use MigrationOrchestrator\PlanningGuard;
use MigrationOrchestrator\PacketRepository;
use MigrationOrchestrator\ProjectPaths;
use MigrationOrchestrator\QueueWorker;
use MigrationOrchestrator\RunEngine;
use MigrationOrchestrator\RunRepository;
use MigrationOrchestrator\SchemaValidator;
use MigrationOrchestrator\TaskRepository;
use PHPUnit\Framework\TestCase;

final class OrchestratorTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (glob(sys_get_temp_dir() . '/migration-orchestrator-*') ?: [] as $directory) {
            $this->removeDirectory($directory);
        }
    }

    public function testSchemaValidatorAcceptsValidWorkflowAndRejectsUnknownProperties(): void
    {
        $root = $this->createProjectRoot();
        [$paths, $validator] = $this->buildServices($root);

        $workflowPath = $paths->workflowDir() . '/packet-lifecycle.json';
        $this->writeJson($workflowPath, $this->successWorkflow());
        $this->writeJson($paths->exampleTaskDir() . '/catalog-product-list.json', $this->baseTask());

        $this->assertSame([], $validator->validateFile($workflowPath, 'workflow'));
        $this->assertSame([], $validator->validateFile($paths->exampleTaskDir() . '/catalog-product-list.json', 'task'));
        $this->assertSame([], $validator->validateFile($paths->packetDir() . '/catalog-product-list.json', 'packet'));
        $this->assertSame([], $validator->validateFile($paths->packetDir() . '/cart-quantity.json', 'packet'));
        $this->assertSame([], $validator->validateFile($paths->packetDir() . '/cart-add-cart-item-input.json', 'packet'));

        $invalidWorkflow = $this->successWorkflow();
        $invalidWorkflow['extra'] = 'not-allowed';
        $this->writeJson($paths->workflowDir() . '/invalid.json', $invalidWorkflow);

        $errors = $validator->validateFile($paths->workflowDir() . '/invalid.json', 'workflow');
        $this->assertNotSame([], $errors);
    }

    public function testTaskAddQueuesTaskWithRuntimeMetadata(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks] = $this->buildServices($root);

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $this->successWorkflow());
        $sourceTask = $paths->exampleTaskDir() . '/catalog-product-list.json';
        $this->writeJson($sourceTask, $this->baseTask());

        $queued = $tasks->queueTask($sourceTask);
        $queuedTask = $tasks->loadTask('catalog-product-list');

        $this->assertSame('queued', $queued['queue_status']);
        $this->assertSame('queued', $queuedTask['queue_status']);
        $this->assertNotEmpty($queuedTask['queued_at']);
        $this->assertSame(realpath($sourceTask), $queuedTask['source_path']);
    }

    public function testRunNextCompletesHappyPathAndRecordsArtifacts(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, $runs, $engine] = $this->buildServices($root);

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $this->successWorkflow());
        $sourceTask = $paths->exampleTaskDir() . '/catalog-product-list.json';
        $this->writeJson($sourceTask, $this->baseTask());
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $state = $engine->runNext();

        $this->assertSame('completed', $state['status']);
        $this->assertSame('COMPLETE', $state['current_step']);

        $task = $tasks->loadTask('catalog-product-list');
        $this->assertSame('completed', $task['queue_status']);

        $runId = $state['run_id'];
        $this->assertFileExists($paths->runPath($runId) . '/state.json');
        $this->assertFileExists($paths->runPath($runId) . '/events.ndjson');

        $reloaded = $runs->loadState($runId);
        $this->assertSame('completed', $reloaded['status']);
        $this->assertGreaterThanOrEqual(4, count($reloaded['step_history']));
    }

    public function testRunStatusReturnsSummaryAndState(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, , $engine] = $this->buildServices($root);

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $this->successWorkflow());
        $sourceTask = $paths->exampleTaskDir() . '/catalog-product-list.json';
        $this->writeJson($sourceTask, $this->baseTask());
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $completed = $engine->runNext();
        $status = $engine->status($completed['run_id']);

        $this->assertArrayHasKey('summary', $status);
        $this->assertArrayHasKey('state', $status);
        $this->assertSame($completed['run_id'], $status['summary']['run_id']);
        $this->assertSame('completed', $status['summary']['status']);
        $this->assertSame('COMPLETE', $status['summary']['current_step']);
        $this->assertSame($completed['run_id'], $status['state']['run_id']);
    }

    public function testResumeRequiresPlanningSyncAfterTerminalFailure(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, $runs, $engine] = $this->buildServices($root);

        $workflow = $this->successWorkflow();
        foreach ($workflow['steps'] as &$step) {
            if ($step['name'] === 'implement') {
                $step['adapter']['command'] = ['php', '-r', 'fwrite(STDERR, "implement failed\n"); exit(1);'];
            }
        }
        unset($step);

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $workflow);
        $sourceTask = $paths->exampleTaskDir() . '/catalog-product-list.json';
        $this->writeJson($sourceTask, $this->baseTask());
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $failedState = $engine->runNext();
        $this->assertSame('failed', $failedState['status']);
        $this->assertSame('implement', $failedState['current_step']);

        $failureThreshold = strtotime($failedState['plan_sync_required_since']) ?: time();
        $this->touchPlanningFiles($root, $failureThreshold - 5);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Planning guard failed');
        try {
            $engine->resumeRun($failedState['run_id']);
        } finally {
            foreach ($workflow['steps'] as &$step) {
                if ($step['name'] === 'implement') {
                    $step['adapter']['command'] = ['php', '-r', 'fwrite(STDOUT, "implement recovered\n");'];
                }
            }
            unset($step);
            $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $workflow);
            $this->touchPlanningFiles($root, $failureThreshold + 5);

            $completedState = $engine->resumeRun($failedState['run_id']);
            $this->assertSame('completed', $completedState['status']);

            $reloaded = $runs->loadState($failedState['run_id']);
            $this->assertSame('completed', $reloaded['status']);
            $implementAttempts = array_values(array_filter(
                $reloaded['step_history'],
                static fn (array $entry): bool => $entry['step'] === 'implement'
            ));
            $this->assertGreaterThanOrEqual(2, count($implementAttempts));
        }
    }

    public function testTrackedProductListWorkflowProducesPacketArtifacts(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, $runs, $engine] = $this->buildServices($root);

        copy(__DIR__ . '/../alps.json', $root . '/alps.json');
        $workflow = $this->trackedWorkflowUsingRealExecutor();
        $task = $this->trackedTask(
            '001-catalog-product-list',
            'Catalog ProductList packet',
            'Establish the first storefront work packet for ProductList.',
            'catalog-product-list',
            [
                'ProductList resource test passes.',
                'At least one hypermedia test exists for ProductList.',
            ],
            100
        );

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $workflow);
        $sourceTask = $paths->exampleTaskDir() . '/001-catalog-product-list.json';
        $this->writeJson($sourceTask, $task);
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $state = $engine->runNext();
        $this->assertSame('completed', $state['status']);

        $runPath = $paths->runPath($state['run_id']) . '/packet';
        $this->assertFileExists($runPath . '/semantic.json');
        $this->assertFileExists($runPath . '/generate.json');
        $this->assertFileExists($runPath . '/implement.json');
        $this->assertFileExists($runPath . '/review.json');

        $semantic = json_decode((string) file_get_contents($runPath . '/semantic.json'), true, 512, JSON_THROW_ON_ERROR);
        $generate = json_decode((string) file_get_contents($runPath . '/generate.json'), true, 512, JSON_THROW_ON_ERROR);
        $review = json_decode((string) file_get_contents($runPath . '/review.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('ProductList', $semantic['resource']);
        $this->assertSame('catalog', $generate['implementation_brief']['bounded_context']);
        $this->assertSame('approved', $review['status']);

        $status = $engine->status($state['run_id']);
        $this->assertSame('completed', $status['summary']['status']);

        $reloaded = $runs->loadState($state['run_id']);
        $this->assertSame('completed', $reloaded['status']);
    }

    public function testTrackedProductWorkflowProducesPacketArtifacts(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, $runs, $engine] = $this->buildServices($root);

        copy(__DIR__ . '/../alps.json', $root . '/alps.json');
        $workflow = $this->trackedWorkflowUsingRealExecutor();
        $task = $this->trackedTask(
            '002-catalog-product',
            'Catalog Product packet',
            'Establish the storefront work packet for Product detail.',
            'catalog-product',
            [
                'Product resource test passes.',
                'At least one hypermedia test exists for Product.',
                'The Product packet preserves the add-to-cart transition contract.',
            ],
            95
        );

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $workflow);
        $sourceTask = $paths->exampleTaskDir() . '/002-catalog-product.json';
        $this->writeJson($sourceTask, $task);
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $state = $engine->runNext();
        $this->assertSame('completed', $state['status']);

        $runPath = $paths->runPath($state['run_id']) . '/packet';
        $this->assertFileExists($runPath . '/semantic.json');
        $this->assertFileExists($runPath . '/generate.json');
        $this->assertFileExists($runPath . '/implement.json');
        $this->assertFileExists($runPath . '/review.json');

        $semantic = json_decode((string) file_get_contents($runPath . '/semantic.json'), true, 512, JSON_THROW_ON_ERROR);
        $generate = json_decode((string) file_get_contents($runPath . '/generate.json'), true, 512, JSON_THROW_ON_ERROR);
        $implement = json_decode((string) file_get_contents($runPath . '/implement.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Product', $semantic['resource']);
        $this->assertTrue($generate['implementation_brief']['allows_add_to_cart']);
        $this->assertContains('doAddCartItem', $implement['contract']['transition_ids']);

        $status = $engine->status($state['run_id']);
        $this->assertSame('completed', $status['summary']['status']);

        $reloaded = $runs->loadState($state['run_id']);
        $this->assertSame('completed', $reloaded['status']);
    }

    public function testTrackedCategoryWorkflowProducesPacketArtifacts(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, $runs, $engine] = $this->buildServices($root);

        copy(__DIR__ . '/../alps.json', $root . '/alps.json');
        $workflow = $this->trackedWorkflowUsingRealExecutor();
        $task = $this->trackedTask(
            '003-catalog-category',
            'Catalog Category packet',
            'Establish the storefront work packet for Category detail.',
            'catalog-category',
            [
                'Category resource test passes.',
                'At least one hypermedia test exists for Category.',
                'The Category packet preserves the product-list transition contract.',
            ],
            90
        );

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $workflow);
        $sourceTask = $paths->exampleTaskDir() . '/003-catalog-category.json';
        $this->writeJson($sourceTask, $task);
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $state = $engine->runNext();
        $this->assertSame('completed', $state['status']);

        $runPath = $paths->runPath($state['run_id']) . '/packet';
        $this->assertFileExists($runPath . '/semantic.json');
        $this->assertFileExists($runPath . '/generate.json');
        $this->assertFileExists($runPath . '/implement.json');
        $this->assertFileExists($runPath . '/review.json');

        $semantic = json_decode((string) file_get_contents($runPath . '/semantic.json'), true, 512, JSON_THROW_ON_ERROR);
        $generate = json_decode((string) file_get_contents($runPath . '/generate.json'), true, 512, JSON_THROW_ON_ERROR);
        $implement = json_decode((string) file_get_contents($runPath . '/implement.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Category', $semantic['resource']);
        $this->assertTrue($generate['implementation_brief']['exposes_product_list_link']);
        $this->assertContains('goProductList', $implement['contract']['transition_ids']);
        $this->assertContains('doUpdateCategory', $implement['contract']['transition_ids']);

        $status = $engine->status($state['run_id']);
        $this->assertSame('completed', $status['summary']['status']);

        $reloaded = $runs->loadState($state['run_id']);
        $this->assertSame('completed', $reloaded['status']);
    }

    public function testTrackedCartWorkflowProducesPacketArtifacts(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, $runs, $engine] = $this->buildServices($root);

        copy(__DIR__ . '/../alps.json', $root . '/alps.json');
        $workflow = $this->trackedWorkflowUsingRealExecutor();
        $task = [
            'id' => '004-cart-cart',
            'title' => 'Cart packet',
            'goal' => 'Establish the storefront work packet for Cart.',
            'packet' => 'cart',
            'workflow' => 'packet-lifecycle',
            'success_criteria' => [
                'Cart resource test passes.',
                'At least one workflow test exists for Cart.',
                'The Cart packet preserves add/update/remove item transitions.',
                'The Cart packet preserves the goShopping transition contract.',
            ],
            'priority' => 88,
        ];

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $workflow);
        $sourceTask = $paths->exampleTaskDir() . '/004-cart-cart.json';
        $this->writeJson($sourceTask, $task);
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $state = $engine->runNext();
        $this->assertSame('completed', $state['status']);

        $runPath = $paths->runPath($state['run_id']) . '/packet';
        $this->assertFileExists($runPath . '/semantic.json');
        $this->assertFileExists($runPath . '/generate.json');
        $this->assertFileExists($runPath . '/implement.json');
        $this->assertFileExists($runPath . '/review.json');

        $semantic = json_decode((string) file_get_contents($runPath . '/semantic.json'), true, 512, JSON_THROW_ON_ERROR);
        $generate = json_decode((string) file_get_contents($runPath . '/generate.json'), true, 512, JSON_THROW_ON_ERROR);
        $implement = json_decode((string) file_get_contents($runPath . '/implement.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Cart', $semantic['resource']);
        $this->assertNotSame('', $semantic['purchase_flow_note']);
        $this->assertTrue($generate['implementation_brief']['requires_purchase_flow']);
        $this->assertContains('doUpdateCartItemQuantity', $implement['contract']['transition_ids']);
        $this->assertContains('goShopping', $implement['contract']['transition_ids']);

        $status = $engine->status($state['run_id']);
        $this->assertSame('completed', $status['summary']['status']);

        $reloaded = $runs->loadState($state['run_id']);
        $this->assertSame('completed', $reloaded['status']);
    }

    public function testTrackedShoppingWorkflowProducesPacketArtifacts(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, $runs, $engine] = $this->buildServices($root);

        copy(__DIR__ . '/../alps.json', $root . '/alps.json');
        $workflow = $this->trackedWorkflowUsingRealExecutor();
        $task = [
            'id' => '005-checkout-shopping',
            'title' => 'Checkout Shopping packet',
            'goal' => 'Establish the storefront work packet for Shopping.',
            'packet' => 'checkout-shopping',
            'workflow' => 'packet-lifecycle',
            'success_criteria' => [
                'Shopping resource test passes.',
                'At least one workflow test exists for Shopping.',
                'The Shopping packet preserves shipping selection transitions.',
                'The Shopping packet preserves the doConfirmOrder transition contract.',
            ],
            'priority' => 87,
        ];

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $workflow);
        $sourceTask = $paths->exampleTaskDir() . '/005-checkout-shopping.json';
        $this->writeJson($sourceTask, $task);
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $state = $engine->runNext();
        $this->assertSame('completed', $state['status']);

        $runPath = $paths->runPath($state['run_id']) . '/packet';
        $this->assertFileExists($runPath . '/semantic.json');
        $this->assertFileExists($runPath . '/generate.json');
        $this->assertFileExists($runPath . '/implement.json');
        $this->assertFileExists($runPath . '/review.json');

        $semantic = json_decode((string) file_get_contents($runPath . '/semantic.json'), true, 512, JSON_THROW_ON_ERROR);
        $generate = json_decode((string) file_get_contents($runPath . '/generate.json'), true, 512, JSON_THROW_ON_ERROR);
        $implement = json_decode((string) file_get_contents($runPath . '/implement.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Shopping', $semantic['resource']);
        $this->assertNotSame('', $semantic['checkout_flow_note']);
        $this->assertTrue($generate['implementation_brief']['requires_checkout_flow']);
        $this->assertContains('doConfirmOrder', $implement['contract']['transition_ids']);
        $this->assertContains('goShoppingShipping', $implement['contract']['transition_ids']);

        $status = $engine->status($state['run_id']);
        $this->assertSame('completed', $status['summary']['status']);

        $reloaded = $runs->loadState($state['run_id']);
        $this->assertSame('completed', $reloaded['status']);
    }

    public function testTrackedBeSemanticWorkflowProducesBeArtifacts(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, $runs, $engine] = $this->buildServices($root);

        copy(__DIR__ . '/../alps.json', $root . '/alps.json');
        $workflow = $this->trackedWorkflowUsingRealExecutor();
        $task = [
            'id' => '101-cart-add-cart-item-input',
            'title' => 'Cart AddCartItemInput semantic packet',
            'goal' => 'Establish the first Be-first semantic packet for AddCartItemInput.',
            'packet' => 'cart-add-cart-item-input',
            'workflow' => 'packet-lifecycle',
            'success_criteria' => [
                'Semantic variables are extracted for AddCartItemInput.',
                'Input and Final targets are defined for Be.',
                'Reason dependencies are listed before HTTP resource work starts.',
            ],
            'priority' => 110,
        ];

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $workflow);
        $sourceTask = $paths->exampleTaskDir() . '/101-cart-add-cart-item-input.json';
        $this->writeJson($sourceTask, $task);
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $state = $engine->runNext();
        $this->assertSame('completed', $state['status']);

        $runPath = $paths->runPath($state['run_id']) . '/packet';
        $semantic = json_decode((string) file_get_contents($runPath . '/semantic.json'), true, 512, JSON_THROW_ON_ERROR);
        $generate = json_decode((string) file_get_contents($runPath . '/generate.json'), true, 512, JSON_THROW_ON_ERROR);
        $implement = json_decode((string) file_get_contents($runPath . '/implement.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('AddCartItemInput', $semantic['subject']);
        $this->assertSame('AddCartItemInput', $generate['be_plan']['subject']);
        $this->assertSame(['cart-quantity'], $generate['be_plan']['depends_on_packets']);
        $this->assertSame('AddCartItemInput', $implement['input']['name']);
        $this->assertSame('CartUpdated', $implement['final']['name']);
        $this->assertSame(['ProductClassId'], array_column($implement['semantic_variables'], 'name'));
        $this->assertContains('PurchaseFlow', $implement['reason_dependencies']);

        $status = $engine->status($state['run_id']);
        $this->assertSame('completed', $status['summary']['status']);

        $reloaded = $runs->loadState($state['run_id']);
        $this->assertSame('completed', $reloaded['status']);
    }

    public function testTrackedQuantityBeSemanticWorkflowProducesMinimalBeArtifacts(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, $runs, $engine] = $this->buildServices($root);

        copy(__DIR__ . '/../alps.json', $root . '/alps.json');
        $workflow = $this->trackedWorkflowUsingRealExecutor();
        $task = [
            'id' => '102-cart-quantity',
            'title' => 'Cart Quantity semantic packet',
            'goal' => 'Establish the smallest Be-first semantic packet for Quantity.',
            'packet' => 'cart-quantity',
            'workflow' => 'packet-lifecycle',
            'success_criteria' => [
                'Semantic variables are extracted for Quantity.',
                'Input and Final targets are defined for the smallest Be packet.',
                'No Reason dependency is required at this stage.',
            ],
            'priority' => 120,
        ];

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $workflow);
        $sourceTask = $paths->exampleTaskDir() . '/102-cart-quantity.json';
        $this->writeJson($sourceTask, $task);
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $state = $engine->runNext();
        $this->assertSame('completed', $state['status']);

        $runPath = $paths->runPath($state['run_id']) . '/packet';
        $semantic = json_decode((string) file_get_contents($runPath . '/semantic.json'), true, 512, JSON_THROW_ON_ERROR);
        $generate = json_decode((string) file_get_contents($runPath . '/generate.json'), true, 512, JSON_THROW_ON_ERROR);
        $implement = json_decode((string) file_get_contents($runPath . '/implement.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Quantity', $semantic['subject']);
        $this->assertSame('Quantity', $generate['be_plan']['subject']);
        $this->assertSame([], $generate['be_plan']['reason_dependencies']);
        $this->assertSame(['Quantity'], array_column($implement['semantic_variables'], 'name'));
        $this->assertSame('QuantityInput', $implement['input']['name']);
        $this->assertSame('ValidatedQuantity', $implement['final']['name']);
        $this->assertSame([], $implement['reason_dependencies']);

        $status = $engine->status($state['run_id']);
        $this->assertSame('completed', $status['summary']['status']);

        $reloaded = $runs->loadState($state['run_id']);
        $this->assertSame('completed', $reloaded['status']);
    }

    public function testReviewFailureTransitionLoopsThroughFixAndCompletes(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, $runs, $engine] = $this->buildServices($root);

        $workflow = $this->successWorkflow();
        foreach ($workflow['steps'] as &$step) {
            if ($step['name'] === 'review') {
                $step['adapter']['command'] = [
                    'php',
                    '-r',
                    '$attempt=(int) getenv("ORCH_STEP_ATTEMPT"); if ($attempt === 1) { fwrite(STDERR, "review requested fix\n"); exit(10); } fwrite(STDOUT, "review approved\n");',
                ];
            }
        }
        unset($step);

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $workflow);
        $sourceTask = $paths->exampleTaskDir() . '/catalog-product-list.json';
        $this->writeJson($sourceTask, $this->baseTask());
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $state = $engine->runNext();
        $this->assertSame('completed', $state['status']);

        $reloaded = $runs->loadState($state['run_id']);
        $reviewAttempts = array_values(array_filter(
            $reloaded['step_history'],
            static fn (array $entry): bool => $entry['step'] === 'review'
        ));
        $fixAttempts = array_values(array_filter(
            $reloaded['step_history'],
            static fn (array $entry): bool => $entry['step'] === 'fix'
        ));

        $this->assertCount(2, $reviewAttempts);
        $this->assertCount(1, $fixAttempts);
        $this->assertSame('transition_failed', $reviewAttempts[0]['status']);
        $this->assertSame('succeeded', $reviewAttempts[1]['status']);
        $this->assertSame('succeeded', $fixAttempts[0]['status']);
    }

    public function testQueueWorkerLoopProcessesQueuedTaskThenStopsAfterIdle(): void
    {
        $root = $this->createProjectRoot();
        [$paths, , $tasks, $runs, $engine] = $this->buildServices($root);

        $this->writeJson($paths->workflowDir() . '/packet-lifecycle.json', $this->successWorkflow());
        $sourceTask = $paths->exampleTaskDir() . '/catalog-product-list.json';
        $this->writeJson($sourceTask, $this->baseTask());
        $tasks->queueTask($sourceTask);
        $this->touchPlanningFiles($root, time() + 2);

        $worker = new QueueWorker($engine);
        $exitCode = $worker->loop(0, 1);

        $this->assertSame(0, $exitCode);

        $task = $tasks->loadTask('catalog-product-list');
        $this->assertSame('completed', $task['queue_status']);

        $runId = $task['last_run_id'];
        $this->assertNotSame('', $runId);
        $state = $runs->loadState($runId);
        $this->assertSame('completed', $state['status']);
    }

    private function createProjectRoot(): string
    {
        $root = sys_get_temp_dir() . '/migration-orchestrator-' . bin2hex(random_bytes(4));
        mkdir($root, 0777, true);

        foreach ([
            '/.migrate/schemas',
            '/.migrate/workflows',
            '/.migrate/packets',
            '/.migrate/examples/tasks',
            '/.migrate/tasks',
            '/.migrate/runs',
            '/.migrate/locks',
        ] as $directory) {
            mkdir($root . $directory, 0777, true);
        }

        $this->copyDirectory(__DIR__ . '/../.migrate/schemas', $root . '/.migrate/schemas');
        $this->copyDirectory(__DIR__ . '/../.migrate/packets', $root . '/.migrate/packets');

        file_put_contents($root . '/task_plan.md', "task plan\n");
        file_put_contents($root . '/findings.md', "findings\n");
        file_put_contents($root . '/progress.md', "progress\n");

        return $root;
    }

    private function buildServices(string $root): array
    {
        $paths = new ProjectPaths($root);
        $paths->ensureDirectories();
        $validator = new SchemaValidator($paths);
        $guard = new PlanningGuard($paths);
        $packets = new PacketRepository($paths, $validator);
        $tasks = new TaskRepository($paths, $validator, $packets);
        $runs = new RunRepository($paths, $validator);
        $engine = new RunEngine($paths, $validator, $guard, $tasks, $runs, $packets);

        return [$paths, $validator, $tasks, $runs, $engine];
    }

    private function successWorkflow(): array
    {
        $ok = ['php', '-r', 'fwrite(STDOUT, "ok\n");'];

        return [
            'name' => 'packet-lifecycle',
            'version' => '1.0.0',
            'initial_step' => 'semantic',
            'steps' => [
                ['name' => 'semantic', 'adapter' => ['type' => 'command', 'command' => $ok, 'cwd' => '.'], 'on_success' => 'generate', 'on_failure' => 'FAIL', 'timeout_sec' => 5, 'retry_policy' => ['max_attempts' => 1]],
                ['name' => 'generate', 'adapter' => ['type' => 'command', 'command' => $ok, 'cwd' => '.'], 'on_success' => 'implement', 'on_failure' => 'FAIL', 'timeout_sec' => 5, 'retry_policy' => ['max_attempts' => 1]],
                ['name' => 'implement', 'adapter' => ['type' => 'command', 'command' => $ok, 'cwd' => '.'], 'on_success' => 'review', 'on_failure' => 'FAIL', 'timeout_sec' => 5, 'retry_policy' => ['max_attempts' => 2]],
                ['name' => 'review', 'adapter' => ['type' => 'command', 'command' => $ok, 'cwd' => '.'], 'on_success' => 'COMPLETE', 'on_failure' => 'fix', 'timeout_sec' => 5, 'retry_policy' => ['max_attempts' => 3]],
                ['name' => 'fix', 'adapter' => ['type' => 'command', 'command' => $ok, 'cwd' => '.'], 'on_success' => 'review', 'on_failure' => 'FAIL', 'timeout_sec' => 5, 'retry_policy' => ['max_attempts' => 2]],
            ],
        ];
    }

    private function trackedWorkflowUsingRealExecutor(): array
    {
        $workflow = json_decode((string) file_get_contents(__DIR__ . '/../.migrate/workflows/packet-lifecycle.json'), true, 512, JSON_THROW_ON_ERROR);
        $orchestrator = realpath(__DIR__ . '/../bin/orchestrator');
        if ($orchestrator === false) {
            throw new RuntimeException('Failed to resolve orchestrator path.');
        }

        foreach ($workflow['steps'] as &$step) {
            $step['adapter']['command'] = ['php', $orchestrator, 'packet', 'run', $step['name']];
        }
        unset($step);

        return $workflow;
    }

    private function baseTask(): array
    {
        return [
            'id' => 'catalog-product-list',
            'title' => 'Catalog ProductList packet',
            'goal' => 'Implement the first ProductList packet.',
            'packet' => 'catalog-product-list',
            'workflow' => 'packet-lifecycle',
            'success_criteria' => [
                'ProductList resource test passes.',
            ],
            'priority' => 100,
        ];
    }

    private function trackedTask(
        string $id,
        string $title,
        string $goal,
        string $packetId,
        array $successCriteria,
        int $priority
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'goal' => $goal,
            'packet' => $packetId,
            'workflow' => 'packet-lifecycle',
            'success_criteria' => $successCriteria,
            'priority' => $priority,
        ];
    }

    private function writeJson(string $path, array $data): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        $entries = scandir($source);
        if ($entries === false) {
            throw new RuntimeException(sprintf('Failed to scan directory: %s', $source));
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $entry;
            $destinationPath = $destination . '/' . $entry;
            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destinationPath);
                continue;
            }

            copy($sourcePath, $destinationPath);
        }
    }

    private function touchPlanningFiles(string $root, int $timestamp): void
    {
        foreach (['task_plan.md', 'findings.md', 'progress.md'] as $file) {
            touch($root . '/' . $file, $timestamp);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $child = $path . '/' . $entry;
            if (is_dir($child)) {
                $this->removeDirectory($child);
                continue;
            }

            @unlink($child);
        }

        @rmdir($path);
    }
}
