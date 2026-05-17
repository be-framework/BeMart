<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Be\Framework\Becoming;
use Be\Framework\BecomingInterface;
use BEAR\AppMeta\AbstractAppMeta;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use MyVendor\BeMart\Be\Becoming\DevBecoming;
use Ray\Di\ProviderInterface;

/** @implements ProviderInterface<BecomingInterface> */
final class DevBecomingProvider implements ProviderInterface
{
    public function __construct(
        private readonly Becoming $becoming,
        private readonly SemanticLoggerInterface $logger,
        private readonly AbstractAppMeta $appMeta,
    ) {
    }

    public function get(): BecomingInterface
    {
        return new DevBecoming(
            $this->becoming,
            $this->logger,
            $this->appMeta->appDir . '/var/log/bemart.json',
        );
    }
}
