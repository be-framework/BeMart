<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Exception\BadRequestException;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use BEAR\Sunday\Extension\Router\RouterMatch;
use MyVendor\BeMart\Provide\Error\ExceptionStatusMapper;
use MyVendor\BeMart\Provide\Error\HtmlThrowableHandler;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use MyVendor\BeMart\Tests\Support\RecordingErrorHandler;
use MyVendor\BeMart\Tests\Support\RecordingResponder;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Twig\Environment;

/**
 * The html context must render exceptions as HTML, not the JSON body that
 * {@see \MyVendor\BeMart\Provide\Error\AppThrowableHandler} emits for
 * API/HAL contexts. Covers the HtmlModule override wiring, the rendered
 * error page for a mapped status, and delegation of unexpected throwables.
 */
final class HtmlErrorRenderTest extends TestCase
{
    private HtmlThrowableHandler $handler;
    private RecordingResponder $responder;
    private RecordingErrorHandler $fallback;

    protected function setUp(): void
    {
        $twig = HtmlTestInjector::getInstance()->getInstance(Environment::class);
        $this->responder = new RecordingResponder();
        $this->fallback = new RecordingErrorHandler();
        $this->handler = new HtmlThrowableHandler(
            $this->responder,
            $this->fallback,
            new ExceptionStatusMapper(),
            $twig,
        );
    }

    public function testHtmlContextOverridesThrowableHandlerWithHtmlVariant(): void
    {
        $bound = HtmlTestInjector::getInstance()->getInstance(ThrowableHandlerInterface::class);

        $this->assertInstanceOf(HtmlThrowableHandler::class, $bound);
    }

    public function testRendersHtmlErrorPageForMappedException(): void
    {
        $this->handler->handle(new BadRequestException('入力が不正です。', 400), new RouterMatch());
        $this->handler->transfer();

        $ro = $this->responder->ro;
        $this->assertInstanceOf(ResourceObject::class, $ro);
        $this->assertSame(400, $ro->code);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);

        $html = (string) $ro->view;
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('入力が不正です。', $html);
        $this->assertFalse($this->fallback->transferred);
    }

    public function testDelegatesUnmappedExceptionToFrameworkHandler(): void
    {
        $this->handler->handle(new RuntimeException('boom'), new RouterMatch());
        $this->handler->transfer();

        $this->assertTrue($this->fallback->handled);
        $this->assertTrue($this->fallback->transferred);
        $this->assertNull($this->responder->ro);
    }
}
