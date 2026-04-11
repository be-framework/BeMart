<?php

declare(strict_types=1);

namespace MigrationOrchestrator;

final class QueueWorker
{
    public function __construct(private readonly RunEngine $engine)
    {
    }

    public function runOnce(): ?array
    {
        try {
            return $this->engine->runNext();
        } catch (\RuntimeException $runtimeException) {
            if ($runtimeException->getMessage() === 'No queued tasks found.') {
                return null;
            }

            throw $runtimeException;
        }
    }

    public function loop(int $sleepSeconds = 5, int $maxIdleCycles = 0): int
    {
        if ($sleepSeconds < 0) {
            throw new \RuntimeException('sleepSeconds must be >= 0.');
        }

        if ($maxIdleCycles < 0) {
            throw new \RuntimeException('maxIdleCycles must be >= 0.');
        }

        $idleCycles = 0;

        while (true) {
            $result = $this->runOnce();
            if ($result === null) {
                $idleCycles++;
                if ($maxIdleCycles > 0 && $idleCycles >= $maxIdleCycles) {
                    return 0;
                }

                if ($sleepSeconds > 0) {
                    sleep($sleepSeconds);
                }
                continue;
            }

            $idleCycles = 0;
        }
    }
}
