<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Error;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Sunday\Extension\Error\ErrorInterface;
use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use BEAR\Sunday\Extension\Router\RouterMatch as Request;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use Be\Framework\Exception\SemanticVariableException;
use Be\Framework\SemanticVariable\ValidationMessageHandler;
use ErrorException;
use Exception;
use Override;
use Throwable;

use const E_ERROR;

final class AppThrowableHandler implements ThrowableHandlerInterface
{
    private const HTTP_CONFLICT = 409;
    private const HTTP_UNPROCESSABLE_ENTITY = 422;

    /** @var list<class-string<Throwable>> */
    private const BAD_REQUEST = [
        \MyVendor\BeMart\Be\Exception\InvalidCurrentPasswordException::class,
        \MyVendor\BeMart\Be\Exception\MasterOperationNotSupportedException::class,
        \MyVendor\BeMart\Be\Exception\MasterTypeFormatException::class,
        \MyVendor\BeMart\Be\Exception\PasswordConfirmationMismatchException::class,
        \MyVendor\BeMart\Be\Exception\PasswordPolicyViolationException::class,
        \MyVendor\BeMart\Be\Exception\ResetKeyInvalidException::class,
        \MyVendor\BeMart\Be\Exception\TwoFactorAuthFailedException::class,
    ];

    /** @var list<class-string<Throwable>> */
    private const UNAUTHORIZED = [
        \MyVendor\BeMart\Be\Exception\AdminLoginFailedException::class,
        \MyVendor\BeMart\Be\Exception\LoginFailedException::class,
        \MyVendor\BeMart\Be\Exception\UnauthenticatedException::class,
    ];

    /** @var list<class-string<Throwable>> */
    private const FORBIDDEN = [
        \MyVendor\BeMart\Be\Exception\InsufficientAuthorityException::class,
        \MyVendor\BeMart\Be\Exception\UnauthorizedAddressAccessException::class,
        \MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class,
        \MyVendor\BeMart\Be\Exception\UnauthorizedOrderAccessException::class,
        \MyVendor\BeMart\Be\Exception\UnauthorizedPreOrderAccessException::class,
    ];

    /** @var list<class-string<Throwable>> */
    private const NOT_FOUND = [
        \MyVendor\BeMart\Be\Exception\AddressNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\AdminNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\BlockNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\CartItemNotInCartException::class,
        \MyVendor\BeMart\Be\Exception\CategoryNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\ClassCategoryNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\ClassNameNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\CustomerNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\DeliveryNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\LayoutNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\MasterRowNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\NewsNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\OrderNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\PageNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\PaymentMethodAdminNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\PluginNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\PreOrderNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\ProductClassNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\ProductNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\SecretKeyNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\TagNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\TaxRuleNotFoundException::class,
        \MyVendor\BeMart\Be\Exception\TemplateNotFoundException::class,
    ];

    /** @var list<class-string<Throwable>> */
    private const CONFLICT = [
        \MyVendor\BeMart\Be\Exception\CustomerAlreadyActivatedException::class,
        \MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException::class,
        \MyVendor\BeMart\Be\Exception\LoginIdAlreadyTakenException::class,
        \MyVendor\BeMart\Be\Exception\OutOfStockException::class,
        \MyVendor\BeMart\Be\Exception\PluginNotInstalledException::class,
        \MyVendor\BeMart\Be\Exception\ProductCodeAlreadyInUseException::class,
    ];

    /** @var list<class-string<Throwable>> */
    private const UNPROCESSABLE = [
        \MyVendor\BeMart\Be\Exception\InsufficientStockException::class,
        \MyVendor\BeMart\Be\Exception\PaymentDeclinedException::class,
    ];

    private AppErrorPage|null $errorPage = null;
    private bool $delegated = false;

    public function __construct(
        private readonly TransferInterface $responder,
        private readonly ErrorInterface $fallback,
    ) {
    }

    #[Override]
    public function handle(Throwable $e, Request $request): self
    {
        $status = $this->status($e);
        if ($status === null) {
            $this->delegated = true;
            $this->fallback->handle($this->asException($e), $request);

            return $this;
        }

        $this->delegated = false;
        $this->errorPage = new AppErrorPage($status, ['message' => $this->message($e, $status)]);

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
            'message' => $this->statusText(Code::ERROR),
        ]), []);
    }

    private function status(Throwable $e): int|null
    {
        if ($e instanceof SemanticVariableException) {
            return Code::BAD_REQUEST;
        }

        if ($e instanceof JsonSchemaException) {
            return Code::BAD_REQUEST;
        }

        if ($e instanceof BadRequestException) {
            $code = $e->getCode();

            return $code >= 400 && $code < 600 ? $code : Code::BAD_REQUEST;
        }

        foreach ([
            Code::BAD_REQUEST => self::BAD_REQUEST,
            Code::UNAUTHORIZED => self::UNAUTHORIZED,
            Code::FORBIDDEN => self::FORBIDDEN,
            Code::NOT_FOUND => self::NOT_FOUND,
            self::HTTP_CONFLICT => self::CONFLICT,
            self::HTTP_UNPROCESSABLE_ENTITY => self::UNPROCESSABLE,
        ] as $status => $classes) {
            foreach ($classes as $class) {
                if ($e instanceof $class) {
                    return $status;
                }
            }
        }

        return null;
    }

    private function message(Throwable $e, int $status): string
    {
        if ($e instanceof SemanticVariableException) {
            return $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.';
        }

        if ($e instanceof JsonSchemaException) {
            $message = preg_replace('/; by .+$/', '', $e->getMessage());

            return 'Invalid input. ' . ($message ?: 'Request parameters do not match the schema.');
        }

        if ($e instanceof BadRequestException && $e->getMessage() !== '') {
            return $e->getMessage();
        }

        $message = (new ValidationMessageHandler())->getMessage($e, 'ja');
        if ($message !== '' && $message !== 'Validation error') {
            return $message;
        }

        if ($e->getMessage() !== '') {
            return $e->getMessage();
        }

        return $this->statusText($status);
    }

    private function statusText(int $status): string
    {
        return (new Code())->statusText[$status] ?? 'Error';
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
