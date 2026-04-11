<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class ProjectPaths
{
    public function __construct(private readonly string $root)
    {
    }

    public function root(): string
    {
        return $this->root;
    }

    public function migrateDir(): string
    {
        return $this->root . '/.migrate';
    }

    public function schemaDir(): string
    {
        return $this->migrateDir() . '/schemas';
    }

    public function workflowDir(): string
    {
        return $this->migrateDir() . '/workflows';
    }

    public function packetDir(): string
    {
        return $this->migrateDir() . '/packets';
    }

    public function exampleTaskDir(): string
    {
        return $this->migrateDir() . '/examples/tasks';
    }

    public function taskQueueDir(): string
    {
        return $this->migrateDir() . '/tasks';
    }

    public function runDir(): string
    {
        return $this->migrateDir() . '/runs';
    }

    public function runPath(string $runId): string
    {
        return $this->runDir() . '/' . $runId;
    }

    public function lockDir(): string
    {
        return $this->migrateDir() . '/locks';
    }

    public function planningFiles(): array
    {
        return [
            'task_plan.md' => $this->root . '/task_plan.md',
            'findings.md' => $this->root . '/findings.md',
            'progress.md' => $this->root . '/progress.md',
        ];
    }

    public function ensureDirectories(): void
    {
        $directories = [
            $this->migrateDir(),
            $this->schemaDir(),
            $this->workflowDir(),
            $this->packetDir(),
            $this->exampleTaskDir(),
            $this->taskQueueDir(),
            $this->runDir(),
            $this->lockDir(),
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new \RuntimeException(sprintf('Failed to create directory: %s', $directory));
            }
        }
    }
}
