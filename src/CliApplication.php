<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class CliApplication
{
    public function run(array $argv, string $workingDirectory): int
    {
        $paths = new ProjectPaths($workingDirectory);
        $paths->ensureDirectories();

        $validator = new SchemaValidator($paths);
        $planningGuard = new PlanningGuard($paths);
        $packets = new PacketRepository($paths, $validator);
        $tasks = new TaskRepository($paths, $validator, $packets);
        $runs = new RunRepository($paths, $validator);
        $engine = new RunEngine($paths, $validator, $planningGuard, $tasks, $runs, $packets);
        $worker = new QueueWorker($engine);
        $packetExecutor = new PacketExecutor($paths, $validator, $packets);

        try {
            $command = $argv[1] ?? '';
            return match ($command) {
                'validate' => $this->validate($validator, $argv),
                'packet' => $this->packetCommand($packetExecutor, $argv),
                'task' => $this->task($tasks, $argv),
                'run' => $this->runCommand($engine, $argv),
                'worker' => $this->workerCommand($worker, $argv),
                default => $this->usage(),
            };
        } catch (\Throwable $throwable) {
            fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
            return 1;
        }
    }

    private function validate(SchemaValidator $validator, array $argv): int
    {
        $path = $argv[2] ?? '';
        if ($path === '') {
            return $this->usage();
        }

        $kind = $argv[3] ?? null;
        $errors = $validator->validateFile($path, $kind);
        if ($errors !== []) {
            $this->printJson([
                'valid' => false,
                'kind' => $kind ?: $validator->detectKind($path),
                'path' => $path,
                'errors' => $errors,
            ]);
            return 1;
        }

        $this->printJson([
            'valid' => true,
            'kind' => $kind ?: $validator->detectKind($path),
            'path' => $path,
        ]);
        return 0;
    }

    private function task(TaskRepository $tasks, array $argv): int
    {
        $subcommand = $argv[2] ?? '';
        if ($subcommand !== 'add') {
            return $this->usage();
        }

        $path = $argv[3] ?? '';
        if ($path === '') {
            return $this->usage();
        }

        $queued = $tasks->queueTask($path);
        $this->printJson([
            'queued' => true,
            'task_id' => $queued['id'],
            'workflow' => $queued['workflow'],
            'queued_at' => $queued['queued_at'],
        ]);
        return 0;
    }

    private function packetCommand(PacketExecutor $packetExecutor, array $argv): int
    {
        $subcommand = $argv[2] ?? '';
        if ($subcommand !== 'run') {
            return $this->usage();
        }

        $step = (string) ($argv[3] ?? '');
        if ($step === '') {
            return $this->usage();
        }

        return $packetExecutor->run($step);
    }

    private function runCommand(RunEngine $engine, array $argv): int
    {
        $subcommand = $argv[2] ?? '';
        $result = match ($subcommand) {
            'next' => $engine->runNext(),
            'resume' => $engine->resumeRun((string) ($argv[3] ?? '')),
            'status' => $engine->status($argv[3] ?? null),
            'fail' => $engine->failRun((string) ($argv[3] ?? ''), (string) ($argv[4] ?? 'Run failed manually.')),
            default => null,
        };

        if ($result === null) {
            return $this->usage();
        }

        $this->printJson($result);
        return 0;
    }

    private function workerCommand(QueueWorker $worker, array $argv): int
    {
        $subcommand = $argv[2] ?? '';

        if ($subcommand === 'once') {
            $result = $worker->runOnce();
            $this->printJson([
                'ran' => $result !== null,
                'result' => $result,
            ]);
            return 0;
        }

        if ($subcommand === 'loop') {
            $sleepSeconds = 5;
            $maxIdleCycles = 0;

            foreach (array_slice($argv, 3) as $argument) {
                if (str_starts_with($argument, '--sleep=')) {
                    $sleepSeconds = (int) substr($argument, strlen('--sleep='));
                    continue;
                }

                if (str_starts_with($argument, '--max-idle=')) {
                    $maxIdleCycles = (int) substr($argument, strlen('--max-idle='));
                    continue;
                }

                return $this->usage();
            }

            return $worker->loop($sleepSeconds, $maxIdleCycles);
        }

        return $this->usage();
    }

    private function usage(): int
    {
        fwrite(STDERR, "Usage:\n");
        fwrite(STDERR, "  bin/orchestrator validate <file> [kind]\n");
        fwrite(STDERR, "  bin/orchestrator packet run <semantic|generate|implement|review|fix>\n");
        fwrite(STDERR, "  bin/orchestrator task add <task.json>\n");
        fwrite(STDERR, "  bin/orchestrator run next\n");
        fwrite(STDERR, "  bin/orchestrator run resume <run-id>\n");
        fwrite(STDERR, "  bin/orchestrator run status [run-id]\n");
        fwrite(STDERR, "  bin/orchestrator run fail <run-id> [reason]\n");
        fwrite(STDERR, "  bin/orchestrator worker once\n");
        fwrite(STDERR, "  bin/orchestrator worker loop [--sleep=5] [--max-idle=0]\n");
        return 1;
    }

    private function printJson(array $payload): void
    {
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    }
}
