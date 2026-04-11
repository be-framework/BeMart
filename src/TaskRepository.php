<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class TaskRepository
{
    public function __construct(
        private readonly ProjectPaths $paths,
        private readonly SchemaValidator $validator
    ) {
    }

    public function queueTask(string $sourcePath): array
    {
        $task = JsonFile::decodeFile($sourcePath);
        $this->validator->assertValid('task', $task);

        $task['queued_at'] = $this->now();
        $task['queue_status'] = 'queued';
        $task['source_path'] = realpath($sourcePath) ?: $sourcePath;
        $task['last_run_id'] = $task['last_run_id'] ?? '';

        $destination = $this->taskPathInternal((string) $task['id']);
        if (is_file($destination)) {
            throw new \RuntimeException(sprintf('Queued task already exists: %s', $task['id']));
        }

        JsonFile::encodeFile($destination, $task);
        return $task;
    }

    public function nextQueuedTask(): ?array
    {
        $tasks = array_filter(
            $this->allTasks(),
            static fn (array $task): bool => ($task['queue_status'] ?? 'queued') === 'queued'
        );

        if ($tasks === []) {
            return null;
        }

        usort($tasks, static function (array $left, array $right): int {
            $priorityOrder = ($right['priority'] <=> $left['priority']);
            if ($priorityOrder !== 0) {
                return $priorityOrder;
            }

            return strcmp((string) ($left['queued_at'] ?? ''), (string) ($right['queued_at'] ?? ''));
        });

        return $tasks[0];
    }

    public function loadTask(string $taskId): array
    {
        $path = $this->taskPathInternal($taskId);
        $task = JsonFile::decodeFile($path);
        $this->validator->assertValid('task', $task);
        return $task;
    }

    public function saveTask(array $task): void
    {
        $this->validator->assertValid('task', $task);
        JsonFile::encodeFile($this->taskPathInternal((string) $task['id']), $task);
    }

    public function workflowPathForTask(array $task): string
    {
        return $this->paths->workflowDir() . '/' . $task['workflow'] . '.json';
    }

    public function taskPath(string $taskId): string
    {
        return $this->taskPathInternal($taskId);
    }

    private function allTasks(): array
    {
        $tasks = [];
        $pattern = $this->paths->taskQueueDir() . '/*.json';
        foreach (glob($pattern) ?: [] as $path) {
            $task = JsonFile::decodeFile($path);
            $this->validator->assertValid('task', $task);
            $tasks[] = $task;
        }

        return $tasks;
    }

    private function taskPathInternal(string $taskId): string
    {
        return $this->paths->taskQueueDir() . '/' . $taskId . '.json';
    }

    private function now(): string
    {
        return (new \DateTimeImmutable())->format(DATE_ATOM);
    }
}
