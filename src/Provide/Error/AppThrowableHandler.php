<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Error;

use BEAR\Resource\Code;
use BEAR\Sunday\Extension\Error\ErrorInterface;
use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use BEAR\Sunday\Extension\Router\RouterMatch as Request;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use ErrorException;
use Exception;
use Override;
use Throwable;

use const E_ERROR;

/**
 * Default (JSON) throwable handler. Maps known domain / framework
 * exceptions to an HTTP status via {@see ExceptionStatusMapper} and
 * emits an {@see AppErrorPage} JSON body; unexpected throwables are
 * delegated to the framework {@see ErrorInterface}.
 *
 * The html context overrides this with {@see HtmlThrowableHandler}, which
 * shares the same {@see ExceptionStatusMapper} but renders HTML.
 */
final class AppThrowableHandler implements ThrowableHandlerInterface
{
    private AppErrorPage|null $errorPage = null;
    private bool $delegated = false;

    public function __construct(
        private readonly TransferInterface $responder,
        private readonly ErrorInterface $fallback,
        private readonly ExceptionStatusMapper $mapper,
    ) {
    }

    #[Override]
    public function handle(Throwable $e, Request $request): self
    {
        $status = $this->mapper->status($e);
        if ($status === null) {
            $this->delegated = true;
            $this->fallback->handle($this->asException($e), $request);

            return $this;
        }

        $this->delegated = false;
        $this->errorPage = new AppErrorPage($status, ['message' => $this->mapper->message($e, $status)]);

        return $this;
    }

    #[Override]
    public function transfer(): void
    {
        if ($this->delegated) {
            $this->fallback->transfer();

            return;
        }

        ($this->responder)($this->errorPage ?? new AppErrorPage(Code::ERROR, [
            'message' => $this->mapper->statusText(Code::ERROR),
        ]), []);
    }

    private function asException(Throwable $e): Exception
    {
        if ($e instanceof Exception) {
            return $e;
        }

        return new ErrorException(
            $e->getMessage(),
            (int) $e->getCode(),
            E_ERROR,
            $e->getFile(),
            $e->getLine(),
            $e,
        );
    }
}
