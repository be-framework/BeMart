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
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use Override;
use Throwable;
use Twig\Environment;

use const E_ERROR;

/**
 * html-context throwable handler.
 *
 * Known domain / framework exceptions (mapped by {@see ExceptionStatusMapper})
 * render an HTML error page from {@see self::TEMPLATE} so browser users see
 * HTML rather than the JSON body {@see AppThrowableHandler} emits. Unexpected
 * throwables (unmapped → 500) are delegated to the framework
 * {@see ErrorInterface}, preserving the dev handler's HTML stack trace and
 * the prod handler's generic page.
 */
final class HtmlThrowableHandler implements ThrowableHandlerInterface
{
    private const TEMPLATE = 'Page/Error.html.twig';
    private const LOGIN_PATH = '/login';

    private bool $delegated = false;
    private bool $redirect = false;
    private string $location = '';
    private int $status = Code::ERROR;
    private string $html = '';

    public function __construct(
        private readonly TransferInterface $responder,
        private readonly ErrorInterface $fallback,
        private readonly ExceptionStatusMapper $mapper,
        private readonly Environment $twig,
    ) {
    }

    #[Override]
    public function handle(Throwable $e, Request $request): self
    {
        // EC-CUBE firewalls customer pages: an anonymous visitor of an
        // auth-only page (e.g. /mypage) is redirected to the login form,
        // not shown a dead-end 401 error page. Only the browser context
        // recovers this way; the JSON/HAL handler keeps the 401 contract.
        if ($e instanceof UnauthenticatedException) {
            $this->delegated = false;
            $this->redirect = true;
            $this->location = self::LOGIN_PATH;

            return $this;
        }

        $status = $this->mapper->status($e);
        if ($status === null) {
            $this->delegated = true;
            $this->fallback->handle($this->asException($e), $request);

            return $this;
        }

        $this->delegated = false;
        $this->redirect = false;
        $this->status = $status;
        $this->html = $this->twig->render(self::TEMPLATE, [
            'code' => $status,
            'statusText' => $this->mapper->statusText($status),
            'message' => $this->mapper->message($e, $status),
            'errors' => $this->mapper->errors($e),
        ]);

        return $this;
    }

    #[Override]
    public function transfer(): void
    {
        if ($this->delegated) {
            $this->fallback->transfer();

            return;
        }

        if ($this->redirect) {
            ($this->responder)(new HtmlRedirect($this->location), []);

            return;
        }

        ($this->responder)(new HtmlErrorPage($this->status, $this->html), []);
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
