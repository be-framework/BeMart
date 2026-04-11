<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class RunRepository
{
    public function __construct(
        private readonly ProjectPaths $paths,
        private readonly SchemaValidator $validator
    ) {
    }

    public function createRun(array $task, array $workflow, array $planningSnapshot): array
    {
        $runId = $this->nextRunId((string) $task['id']);
        $runPath = $this->paths->runPath($runId);

        foreach ([$runPath, $runPath . '/artifacts'] as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Failed to create run directory: %s', $directory));
            }
        }

        $now = $this->now();
        $state = [
            'schema_version' => '1.0.0',
            'run_id' => $runId,
            'task_id' => $task['id'],
            'workflow' => $workflow['name'],
            'status' => 'running',
            'current_step' => $workflow['initial_step'],
            'created_at' => $now,
            'updated_at' => $now,
            'planning_snapshot' => $planningSnapshot,
            'step_history' => [],
        ];

        $this->saveState($state);
        $this->appendEvent($runId, [
            'timestamp' => $now,
            'run_id' => $runId,
            'step' => '',
            'event_type' => 'run_created',
            'status' => 'running',
            'message' => sprintf('Run created for task %s', $task['id']),
            'artifact_ref' => '',
        ]);

        return $state;
    }

    public function loadState(string $runId): array
    {
        $state = JsonFile::decodeFile($this->statePath($runId));
        $this->validator->assertValid('run-state', $state);
        return $state;
    }

    public function saveState(array $state): void
    {
        $state['updated_at'] = $this->now();
        $this->validator->assertValid('run-state', $state);
        JsonFile::encodeFile($this->statePath((string) $state['run_id']), $state);
    }

    public function appendEvent(string $runId, array $event): void
    {
        JsonFile::appendNdjson($this->eventPath($runId), $event);
    }

    public function storeStepResult(string $runId, array $result): string
    {
        $artifactsDir = $this->paths->runPath($runId) . '/artifacts';
        $prefix = sprintf('%s-attempt-%d', $result['step'], $result['attempt']);
        $stdoutPath = $artifactsDir . '/' . $prefix . '.stdout.log';
        $stderrPath = $artifactsDir . '/' . $prefix . '.stderr.log';
        file_put_contents($stdoutPath, $result['stdout']);
        file_put_contents($stderrPath, $result['stderr']);

        $payload = [
            'schema_version' => '1.0.0',
            'run_id' => $runId,
            'step' => $result['step'],
            'attempt' => $result['attempt'],
            'status' => $result['status'],
            'exit_code' => $result['exit_code'],
            'command' => $result['command'],
            'cwd' => $result['cwd'],
            'started_at' => $result['started_at'],
            'finished_at' => $result['finished_at'],
            'stdout_path' => str_replace($this->paths->runPath($runId) . '/', '', $stdoutPath),
            'stderr_path' => str_replace($this->paths->runPath($runId) . '/', '', $stderrPath),
            'message' => $result['message'],
        ];

        $this->validator->assertValid('step-result', $payload);
        $resultPath = $artifactsDir . '/' . $prefix . '.step-result.json';
        JsonFile::encodeFile($resultPath, $payload);

        return str_replace($this->paths->runPath($runId) . '/', '', $resultPath);
    }

    public function latestRunId(): ?string
    {
        $directories = array_filter(glob($this->paths->runDir() . '/*') ?: [], 'is_dir');
        if ($directories === []) {
            return null;
        }

        usort($directories, static fn (string $left, string $right): int => filemtime($right) <=> filemtime($left));
        return basename($directories[0]);
    }

    private function nextRunId(string $taskId): string
    {
        $slug = preg_replace('/[^A-Za-z0-9._-]+/', '-', $taskId) ?: 'task';
        $candidate = sprintf('%s-%s', (new \DateTimeImmutable())->format('YmdHis'), $slug);
        $index = 1;

        while (is_dir($this->paths->runPath($candidate))) {
            $candidate = sprintf('%s-%s-%d', (new \DateTimeImmutable())->format('YmdHis'), $slug, $index);
            $index++;
        }

        return $candidate;
    }

    private function statePath(string $runId): string
    {
        return $this->paths->runPath($runId) . '/state.json';
    }

    private function eventPath(string $runId): string
    {
        return $this->paths->runPath($runId) . '/events.ndjson';
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format(DATE_ATOM);
    }
}

