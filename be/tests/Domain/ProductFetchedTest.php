<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ProductCodeFormatException;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Final\ProductFetched;
use MyVendor\BeMart\Be\Input\GetProductInput;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

final class ProductFetchedTest extends TestCase
{
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testGetProductBecomesProductFetched(): void
    {
        $final = ($this->becoming)(new GetProductInput('sample-001'));

        $this->assertInstanceOf(ProductFetched::class, $final);
        $this->assertSame('sample-001', $final->productCode);
        $this->assertSame('サンプル商品 A', $final->productName);
        $this->assertSame(1200, $final->price02);
        $this->assertSame(50, $final->stock);
    }

    public function testStockUnlimitedYieldsNull(): void
    {
        $final = ($this->becoming)(new GetProductInput('sample-002'));
        $this->assertInstanceOf(ProductFetched::class, $final);
        $this->assertNull($final->stock);
    }

    public function testInvalidCodeRejectedBySemanticValidator(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new GetProductInput(''));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                ProductCodeFormatException::class,
                $e->getErrors()->exceptions[0],
            );
            $messages = $e->getErrors()->getMessages('ja');
            $this->assertSame('商品コードの形式が不正です。', $messages[0]);

            throw $e;
        }
    }

    public function testInvalidCharsRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        ($this->becoming)(new GetProductInput('bad code!'));
    }

    public function testMissingProductTriggersDomainException(): void
    {
        $this->expectException(ProductNotFoundException::class);
        ($this->becoming)(new GetProductInput('does-not-exist'));
    }
}
