<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Exception\BadRequestException;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Error\ErrorInterface;
use BEAR\Sunday\Extension\Router\RouterMatch;
use BEAR\Sunday\Extension\Transfer\TransferInterface;
use Exception;
use MyVendor\BeMart\Provide\Error\AppThrowableHandler;
use PHPUnit\Framework\TestCase;

use function putenv;

final class AppThrowableHandlerTest extends TestCase
{
    public function testHtmlContextRendersErrorAsHtml(): void
    {
        putenv('APP_CONTEXT=html-test-hal-api-app');
        $transfer = new CapturingTransfer();
        $handler = new AppThrowableHandler($transfer, new NullError());

        $handler->handle(
            new BadRequestException('page://self/admin/payment/payment-lista', 404),
            new RouterMatch('get', '/admin/payment/payment-lista'),
        )->transfer();

        $this->assertSame(404, $transfer->resource?->code);
        $this->assertSame('text/html; charset=utf-8', $transfer->resource?->headers['Content-Type'] ?? null);
        $this->assertStringContainsString('<!doctype html>', $transfer->resource?->view ?? '');
        $this->assertStringContainsString('ページが見つかりません', $transfer->resource?->view ?? '');
        $this->assertStringNotContainsString('{"code":404', $transfer->resource?->view ?? '');
    }

    public function testApiContextKeepsJsonError(): void
    {
        putenv('APP_CONTEXT=prod-hal-api-app');
        $transfer = new CapturingTransfer();
        $handler = new AppThrowableHandler($transfer, new NullError());

        $handler->handle(
            new BadRequestException('page://self/admin/payment/payment-lista', 404),
            new RouterMatch('get', '/admin/payment/payment-lista'),
        )->transfer();

        $this->assertSame(404, $transfer->resource?->code);
        $this->assertSame('application/json; charset=utf-8', $transfer->resource?->headers['Content-Type'] ?? null);
        $this->assertNull($transfer->resource?->view);
    }

    protected function tearDown(): void
    {
        putenv('APP_CONTEXT');
    }
}

final class CapturingTransfer implements TransferInterface
{
    public ResourceObject|null $resource = null;

    /** @param array<string, mixed> $server */
    public function __invoke(ResourceObject $ro, array $server): void
    {
        $this->resource = $ro;
    }
}

final class NullError implements ErrorInterface
{
    public function handle(Exception $e, RouterMatch $request): self
    {
        return $this;
    }

    public function transfer(): void
    {
    }
}
