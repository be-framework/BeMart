<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class PlanningGuard
{
    public function __construct(private readonly ProjectPaths $paths)
    {
    }

    public function assertReadyForStart(string $queuedAt): array
    {
        return $this->assertFilesUpdatedSince($queuedAt, 'task start');
    }

    public function assertReadyForResume(string $since): array
    {
        return $this->assertFilesUpdatedSince($since, 'resume');
    }

    private function assertFilesUpdatedSince(string $since, string $action): array
    {
        $threshold = new \DateTimeImmutable($since);
        $snapshot = [];

        foreach ($this->paths->planningFiles() as $name => $path) {
            if (!is_file($path)) {
                throw new \RuntimeException(sprintf('Planning guard failed for %s: missing %s', $action, $name));
            }

            $modifiedAtUnix = filemtime($path);
            if ($modifiedAtUnix === false) {
                throw new \RuntimeException(sprintf('Planning guard failed for %s: unreadable mtime for %s', $action, $name));
            }

            $modifiedAt = (new \DateTimeImmutable())->setTimestamp($modifiedAtUnix);
            if ($modifiedAt < $threshold) {
                throw new \RuntimeException(sprintf(
                    'Planning guard failed for %s: %s has not been updated since %s',
                    $action,
                    $name,
                    $threshold->format(DATE_ATOM)
                ));
            }

            $snapshot[$name] = [
                'path' => $path,
                'modified_at' => $modifiedAt->format(DATE_ATOM),
            ];
        }

        return $snapshot;
    }
}

