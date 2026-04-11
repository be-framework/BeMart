<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class RunEngine
{
    public function __construct(
        private readonly ProjectPaths $paths,
        private readonly SchemaValidator $validator,
        private readonly PlanningGuard $planningGuard,
        private readonly TaskRepository $tasks,
        private readonly RunRepository $runs
    ) {
    }

    public function runNext(): array
    {
        return FileLock::withExclusiveLock($this->paths->lockDir() . '/queue.lock', function (): array {
            $task = $this->tasks->nextQueuedTask();
            if ($task === null) {
                throw new \RuntimeException('No queued tasks found.');
            }

            $workflow = $this->loadWorkflow($task);
            $planningSnapshot = $this->planningGuard->assertReadyForStart((string) $task['queued_at']);
            $state = $this->runs->createRun($task, $workflow, $planningSnapshot);

            $task['queue_status'] = 'running';
            $task['last_run_id'] = $state['run_id'];
            $this->tasks->saveTask($task);

            return $this->executeLoop($task, $workflow, $state, false);
        });
    }

    public function resumeRun(string $runId): array
    {
        return FileLock::withExclusiveLock($this->paths->lockDir() . '/run-' . $runId . '.lock', function () use ($runId): array {
            $state = $this->runs->loadState($runId);
            $task = $this->tasks->loadTask((string) $state['task_id']);
            $workflow = $this->loadWorkflow($task);

            if (($state['status'] ?? '') === 'completed') {
                throw new \RuntimeException(sprintf('Run %s is already completed.', $runId));
            }

            if (isset($state['plan_sync_required_since'])) {
                $state['planning_snapshot'] = $this->planningGuard->assertReadyForResume((string) $state['plan_sync_required_since']);
                unset($state['plan_sync_required_since']);
            }

            if (($state['status'] ?? '') === 'running' && isset($state['active_step']) && is_array($state['active_step'])) {
                $this->abandonInterruptedStep($state);
            }

            $state['status'] = 'running';
            $this->runs->saveState($state);

            $task['queue_status'] = 'running';
            $this->tasks->saveTask($task);

            return $this->executeLoop($task, $workflow, $state, true);
        });
    }

    public function status(?string $runId = null): array
    {
        $targetRunId = $runId ?: $this->runs->latestRunId();
        if ($targetRunId === null) {
            throw new \RuntimeException('No runs found.');
        }

        $state = $this->runs->loadState($targetRunId);
        return [
            'summary' => $this->summarizeState($state),
            'state' => $state,
        ];
    }

    public function failRun(string $runId, string $reason): array
    {
        return FileLock::withExclusiveLock($this->paths->lockDir() . '/run-' . $runId . '.lock', function () use ($runId, $reason): array {
            $state = $this->runs->loadState($runId);
            if (($state['status'] ?? '') === 'completed') {
                throw new \RuntimeException(sprintf('Run %s is already completed.', $runId));
            }

            $finishedAt = $this->now();
            if (isset($state['active_step']) && is_array($state['active_step'])) {
                $this->updateLastHistoryEntry($state, [
                    'status' => 'manual_failed',
                    'finished_at' => $finishedAt,
                    'message' => $reason,
                    'exit_code' => 99,
                    'artifact_ref' => '',
                ]);
                unset($state['active_step']);
            }

            $state['status'] = 'failed';
            $state['plan_sync_required_since'] = $finishedAt;
            $state['last_error'] = $reason;
            $this->runs->saveState($state);
            $this->runs->appendEvent($runId, [
                'timestamp' => $finishedAt,
                'run_id' => $runId,
                'step' => (string) ($state['current_step'] ?? ''),
                'event_type' => 'run_failed',
                'status' => 'failed',
                'message' => $reason,
                'artifact_ref' => '',
            ]);

            $task = $this->tasks->loadTask((string) $state['task_id']);
            $task['queue_status'] = 'failed';
            $task['last_run_id'] = $runId;
            $this->tasks->saveTask($task);

            return $state;
        });
    }

    private function executeLoop(array $task, array $workflow, array $state, bool $resume): array
    {
        $steps = $this->stepsByName($workflow);
        $currentStep = (string) ($state['current_step'] ?? $workflow['initial_step']);

        while (true) {
            if (!isset($steps[$currentStep])) {
                throw new \RuntimeException(sprintf('Unknown step %s in workflow %s', $currentStep, $workflow['name']));
            }

            $step = $steps[$currentStep];
            $attempt = $this->nextAttempt($state, $currentStep);
            $maxAttempts = (int) ($step['retry_policy']['max_attempts'] ?? 1);
            if ($attempt > $maxAttempts) {
                return $this->markFailed($task, $state, $currentStep, sprintf('Maximum attempts exceeded for %s', $currentStep), 98, []);
            }

            $startedAt = $this->now();
            $historyEntry = [
                'step' => $currentStep,
                'attempt' => $attempt,
                'status' => 'running',
                'started_at' => $startedAt,
            ];

            $state['status'] = 'running';
            $state['current_step'] = $currentStep;
            $state['active_step'] = [
                'name' => $currentStep,
                'attempt' => $attempt,
                'started_at' => $startedAt,
            ];
            $state['step_history'][] = $historyEntry;
            $this->runs->saveState($state);
            $this->runs->appendEvent((string) $state['run_id'], [
                'timestamp' => $startedAt,
                'run_id' => $state['run_id'],
                'step' => $currentStep,
                'event_type' => 'step_started',
                'status' => 'running',
                'message' => sprintf('Step %s attempt %d started', $currentStep, $attempt),
                'artifact_ref' => '',
            ]);

            $commandResult = $this->executeCommand($step['adapter'], (int) $step['timeout_sec'], $state, $task, $workflow);
            $finishedAt = $this->now();

            if ($commandResult['exit_code'] === 0) {
                $artifactRef = $this->runs->storeStepResult((string) $state['run_id'], [
                    'step' => $currentStep,
                    'attempt' => $attempt,
                    'status' => 'succeeded',
                    'exit_code' => 0,
                    'command' => $step['adapter']['command'],
                    'cwd' => $commandResult['cwd'],
                    'started_at' => $startedAt,
                    'finished_at' => $finishedAt,
                    'stdout' => $commandResult['stdout'],
                    'stderr' => $commandResult['stderr'],
                    'message' => 'Step completed successfully.',
                ]);

                $this->updateLastHistoryEntry($state, [
                    'status' => 'succeeded',
                    'finished_at' => $finishedAt,
                    'exit_code' => 0,
                    'artifact_ref' => $artifactRef,
                    'message' => 'Step completed successfully.',
                ]);
                unset($state['active_step']);
                $nextStep = (string) $step['on_success'];

                $this->runs->appendEvent((string) $state['run_id'], [
                    'timestamp' => $finishedAt,
                    'run_id' => $state['run_id'],
                    'step' => $currentStep,
                    'event_type' => 'step_completed',
                    'status' => 'succeeded',
                    'message' => sprintf('Step %s succeeded', $currentStep),
                    'artifact_ref' => $artifactRef,
                ]);

                if ($nextStep === 'COMPLETE') {
                    $state['status'] = 'completed';
                    $state['current_step'] = 'COMPLETE';
                    unset($state['last_error']);
                    $this->runs->saveState($state);
                    $this->runs->appendEvent((string) $state['run_id'], [
                        'timestamp' => $finishedAt,
                        'run_id' => $state['run_id'],
                        'step' => $currentStep,
                        'event_type' => 'run_completed',
                        'status' => 'completed',
                        'message' => sprintf('Run %s completed', $state['run_id']),
                        'artifact_ref' => '',
                    ]);

                    $task['queue_status'] = 'completed';
                    $task['last_run_id'] = $state['run_id'];
                    $this->tasks->saveTask($task);
                    return $state;
                }

                $state['current_step'] = $nextStep;
                $this->runs->saveState($state);
                $currentStep = $nextStep;
                continue;
            }

            if ($commandResult['exit_code'] === 10) {
                $artifactRef = $this->runs->storeStepResult((string) $state['run_id'], [
                    'step' => $currentStep,
                    'attempt' => $attempt,
                    'status' => 'transition_failed',
                    'exit_code' => 10,
                    'command' => $step['adapter']['command'],
                    'cwd' => $commandResult['cwd'],
                    'started_at' => $startedAt,
                    'finished_at' => $finishedAt,
                    'stdout' => $commandResult['stdout'],
                    'stderr' => $commandResult['stderr'],
                    'message' => 'Step requested failure transition.',
                ]);

                $this->updateLastHistoryEntry($state, [
                    'status' => 'transition_failed',
                    'finished_at' => $finishedAt,
                    'exit_code' => 10,
                    'artifact_ref' => $artifactRef,
                    'message' => 'Step requested failure transition.',
                ]);
                unset($state['active_step']);

                $nextStep = (string) $step['on_failure'];
                if ($nextStep === 'FAIL') {
                    return $this->markFailed($task, $state, $currentStep, 'Step requested terminal failure.', 10, $step['adapter']['command']);
                }

                $state['current_step'] = $nextStep;
                $this->runs->saveState($state);
                $this->runs->appendEvent((string) $state['run_id'], [
                    'timestamp' => $finishedAt,
                    'run_id' => $state['run_id'],
                    'step' => $currentStep,
                    'event_type' => 'step_transitioned',
                    'status' => 'transition_failed',
                    'message' => sprintf('Step %s moved to %s', $currentStep, $nextStep),
                    'artifact_ref' => $artifactRef,
                ]);
                $currentStep = $nextStep;
                continue;
            }

            return $this->markFailed(
                $task,
                $state,
                $currentStep,
                trim($commandResult['stderr']) !== '' ? trim($commandResult['stderr']) : sprintf('Command exited with %d', $commandResult['exit_code']),
                $commandResult['exit_code'],
                $step['adapter']['command'],
                $startedAt,
                $finishedAt,
                $commandResult['stdout'],
                $commandResult['stderr']
            );
        }
    }

    private function markFailed(
        array $task,
        array $state,
        string $currentStep,
        string $message,
        int $exitCode,
        array $command,
        ?string $startedAt = null,
        ?string $finishedAt = null,
        string $stdout = '',
        string $stderr = ''
    ): array {
        $startedAt ??= $this->now();
        $finishedAt ??= $this->now();

        $artifactRef = $this->runs->storeStepResult((string) $state['run_id'], [
            'step' => $currentStep,
            'attempt' => $this->currentAttempt($state, $currentStep),
            'status' => 'failed',
            'exit_code' => $exitCode,
            'command' => $command,
            'cwd' => $this->paths->root(),
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'stdout' => $stdout,
            'stderr' => $stderr,
            'message' => $message,
        ]);

        $this->updateLastHistoryEntry($state, [
            'status' => 'failed',
            'finished_at' => $finishedAt,
            'exit_code' => $exitCode,
            'artifact_ref' => $artifactRef,
            'message' => $message,
        ]);
        unset($state['active_step']);

        $state['status'] = 'failed';
        $state['current_step'] = $currentStep;
        $state['plan_sync_required_since'] = $finishedAt;
        $state['last_error'] = $message;
        $this->runs->saveState($state);
        $this->runs->appendEvent((string) $state['run_id'], [
            'timestamp' => $finishedAt,
            'run_id' => $state['run_id'],
            'step' => $currentStep,
            'event_type' => 'run_failed',
            'status' => 'failed',
            'message' => $message,
            'artifact_ref' => $artifactRef,
        ]);

        $task['queue_status'] = 'failed';
        $task['last_run_id'] = $state['run_id'];
        $this->tasks->saveTask($task);
        return $state;
    }

    private function executeCommand(array $adapter, int $timeoutSec, array $state, array $task, array $workflow): array
    {
        if (($adapter['type'] ?? '') !== 'command') {
            throw new \RuntimeException('Only command adapters are supported in v1.');
        }

        $cwd = isset($adapter['cwd']) ? $this->paths->root() . '/' . $adapter['cwd'] : $this->paths->root();
        if (!is_dir($cwd)) {
            throw new \RuntimeException(sprintf('Adapter cwd does not exist: %s', $cwd));
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            $adapter['command'],
            $descriptorSpec,
            $pipes,
            $cwd,
            $this->buildAdapterEnvironment($state, $task, $workflow, $cwd)
        );
        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start command adapter.');
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $startedAt = microtime(true);
        $exitCode = 1;

        try {
            while (true) {
                $status = proc_get_status($process);
                $stdout .= stream_get_contents($pipes[1]) ?: '';
                $stderr .= stream_get_contents($pipes[2]) ?: '';

                if (!$status['running']) {
                    $exitCode = (int) $status['exitcode'];
                    break;
                }

                if ((microtime(true) - $startedAt) > $timeoutSec) {
                    proc_terminate($process);
                    $stderr .= ($stderr === '' ? '' : PHP_EOL) . sprintf('Command timed out after %d seconds.', $timeoutSec);
                    $exitCode = 124;
                    break;
                }

                usleep(10_000);
            }
        } finally {
            $stdout .= stream_get_contents($pipes[1]) ?: '';
            $stderr .= stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }

        return [
            'exit_code' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
            'cwd' => $cwd,
        ];
    }

    private function loadWorkflow(array $task): array
    {
        $workflowPath = $this->tasks->workflowPathForTask($task);
        $workflow = JsonFile::decodeFile($workflowPath);
        $this->validator->assertValid('workflow', $workflow);
        $this->assertWorkflowTopology($workflow);
        return $workflow;
    }

    private function assertWorkflowTopology(array $workflow): void
    {
        $steps = $this->stepsByName($workflow);
        $initialStep = (string) $workflow['initial_step'];
        if (!isset($steps[$initialStep])) {
            throw new \RuntimeException(sprintf('Workflow initial_step %s is not defined.', $initialStep));
        }

        foreach ($workflow['steps'] as $step) {
            foreach (['on_success', 'on_failure'] as $transition) {
                $target = (string) $step[$transition];
                if (in_array($target, ['COMPLETE', 'FAIL'], true)) {
                    continue;
                }

                if (!isset($steps[$target])) {
                    throw new \RuntimeException(sprintf(
                        'Workflow step %s references unknown transition target %s',
                        $step['name'],
                        $target
                    ));
                }
            }
        }
    }

    private function stepsByName(array $workflow): array
    {
        $steps = [];
        foreach ($workflow['steps'] as $step) {
            $steps[(string) $step['name']] = $step;
        }

        return $steps;
    }

    private function nextAttempt(array $state, string $step): int
    {
        $maxAttempt = 0;
        foreach ($state['step_history'] as $entry) {
            if (($entry['step'] ?? '') === $step) {
                $maxAttempt = max($maxAttempt, (int) $entry['attempt']);
            }
        }

        return $maxAttempt + 1;
    }

    private function currentAttempt(array $state, string $step): int
    {
        $maxAttempt = 1;
        foreach ($state['step_history'] as $entry) {
            if (($entry['step'] ?? '') === $step) {
                $maxAttempt = max($maxAttempt, (int) $entry['attempt']);
            }
        }

        return $maxAttempt;
    }

    private function updateLastHistoryEntry(array &$state, array $patch): void
    {
        $lastIndex = count($state['step_history']) - 1;
        if ($lastIndex < 0) {
            throw new \RuntimeException('No step history entry available to update.');
        }

        foreach ($patch as $key => $value) {
            $state['step_history'][$lastIndex][$key] = $value;
        }
    }

    private function abandonInterruptedStep(array &$state): void
    {
        $finishedAt = $this->now();
        $this->updateLastHistoryEntry($state, [
            'status' => 'abandoned',
            'finished_at' => $finishedAt,
            'message' => 'Interrupted execution abandoned on resume.',
        ]);
        unset($state['active_step']);
        $state['status'] = 'failed';
        $state['plan_sync_required_since'] = $finishedAt;
        $state['last_error'] = 'Interrupted execution detected.';
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format(DATE_ATOM);
    }

    private function buildAdapterEnvironment(array $state, array $task, array $workflow, string $cwd): array
    {
        $environment = getenv();
        if (!is_array($environment)) {
            $environment = [];
        }

        return array_merge($environment, [
            'ORCH_PROJECT_ROOT' => $this->paths->root(),
            'ORCH_CWD' => $cwd,
            'ORCH_RUN_ID' => (string) $state['run_id'],
            'ORCH_RUN_DIR' => $this->paths->runPath((string) $state['run_id']),
            'ORCH_RUN_STATE_PATH' => $this->paths->runPath((string) $state['run_id']) . '/state.json',
            'ORCH_TASK_ID' => (string) $task['id'],
            'ORCH_TASK_FILE' => $this->tasks->taskPath((string) $task['id']),
            'ORCH_WORKFLOW' => (string) $workflow['name'],
            'ORCH_CURRENT_STEP' => (string) $state['current_step'],
            'ORCH_STEP_ATTEMPT' => (string) (($state['active_step']['attempt'] ?? null) ?: $this->currentAttempt($state, (string) $state['current_step'])),
            'ORCH_PACKET_TYPE' => (string) ($task['packet_type'] ?? ''),
            'ORCH_TASK_INPUTS_JSON' => json_encode($task['inputs'] ?? [], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'ORCH_SUCCESS_CRITERIA_JSON' => json_encode($task['success_criteria'] ?? [], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);
    }

    private function summarizeState(array $state): array
    {
        $lastEntry = $state['step_history'] !== [] ? $state['step_history'][count($state['step_history']) - 1] : null;

        return [
            'run_id' => $state['run_id'],
            'task_id' => $state['task_id'],
            'workflow' => $state['workflow'],
            'status' => $state['status'],
            'current_step' => $state['current_step'],
            'requires_plan_sync' => isset($state['plan_sync_required_since']),
            'last_error' => $state['last_error'] ?? '',
            'last_step' => $lastEntry,
        ];
    }
}
