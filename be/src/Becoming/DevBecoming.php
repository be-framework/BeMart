<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Becoming;

use Be\Framework\Becoming;
use Be\Framework\BecomingInterface;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use Override;

use function dirname;
use function file_put_contents;
use function is_dir;
use function json_encode;
use function mkdir;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class DevBecoming implements BecomingInterface
{
    public function __construct(
        private readonly Becoming $becoming,
        private readonly SemanticLoggerInterface $logger,
        private readonly string $logFile,
    ) {
    }

    #[Override]
    public function __invoke(object $input): object
    {
        try {
            return ($this->becoming)($input);
        } finally {
            $dir = dirname($this->logFile);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents(
                $this->logFile,
                json_encode($this->logger->flush()->toTreeArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
            );
        }
    }
}
