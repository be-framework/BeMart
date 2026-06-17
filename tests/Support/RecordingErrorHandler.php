<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support;

use BEAR\Sunday\Extension\Error\ErrorInterface;
use BEAR\Sunday\Extension\Router\RouterMatch;
use Exception;
use Override;

/** Test double for the framework error handler a throwable handler delegates to. */
final class RecordingErrorHandler implements ErrorInterface
{
    public bool $handled = false;
    public bool $transferred = false;
    public Exception|null $exception = null;

    #[Override]
    public function handle(Exception $e, RouterMatch $request)
    {
        $this->handled = true;
        $this->exception = $e;

        return $this;
    }

    #[Override]
    public function transfer()
    {
        $this->transferred = true;
    }
}
