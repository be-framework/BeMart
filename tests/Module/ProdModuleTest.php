<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use Be\Framework\Becoming;
use Be\Framework\BecomingInterface;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use MyVendor\BeMart\Module\AppModule;
use MyVendor\BeMart\Module\ProdModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;
use function file_exists;
use function filemtime;
use function touch;
use function unlink;

final class ProdModuleTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = dirname(__DIR__, 2) . '/var/log/bemart.json';
    }

    public function testProdContextBindsBecomingInterfaceToPlainBecoming(): void
    {
        $injector = new Injector(
            new ProdModule(new Meta('MyVendor\\BeMart', 'prod')),
            dirname(__DIR__, 2) . '/var/tmp/prod',
        );

        $becoming = $injector->getInstance(BecomingInterface::class);

        $this->assertInstanceOf(Becoming::class, $becoming);
    }

    public function testProdContextBindsSemanticLoggerInterfaceToPlainSemanticLogger(): void
    {
        $injector = new Injector(
            new ProdModule(new Meta('MyVendor\\BeMart', 'prod')),
            dirname(__DIR__, 2) . '/var/tmp/prod',
        );

        $logger = $injector->getInstance(SemanticLoggerInterface::class);

        $this->assertInstanceOf(SemanticLogger::class, $logger);
    }

    public function testProdContextDoesNotWriteLogFileOnBecoming(): void
    {
        // Establish a known "before" timestamp by touching the file (or
        // confirming it doesn't exist). DevBecoming would overwrite this.
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        // Slice 7: ProdModule binds SessionInterface to SymfonySessionAdapter,
        // which reads $_SESSION['customer_id']. The `aaaa…` pre-order belongs
        // to customer-001; we mirror that into $_SESSION here to satisfy
        // CheckoutPrepared's AUTHZ check. This is the same mirror an EC-CUBE
        // EventListener would set after a successful login.
        $_SESSION['customer_id'] = 'customer-001';

        $injector = new Injector(
            new ProdModule(new Meta('MyVendor\\BeMart', 'prod')),
            dirname(__DIR__, 2) . '/var/tmp/prod',
        );

        $resource = $injector->getInstance(ResourceInterface::class);
        $ro = $resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertFileDoesNotExist(
            $this->logFile,
            'ProdModule must NOT write var/log/bemart.json (PII leak prevention)',
        );
    }

    public function testDevContextDoesWriteLogFileOnBecoming(): void
    {
        // Negative control: confirm AppModule (dev default) still writes
        // the log. If this stops being true the test above becomes vacuous.
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );

        $resource = $injector->getInstance(ResourceInterface::class);
        $resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
        ]);

        $this->assertFileExists($this->logFile);
    }

    protected function tearDown(): void
    {
        // Clean up so the next test run starts from a known state.
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        // Slice 7: scrub any session value we set so unrelated tests
        // (especially anonymous-session AUTHZ tests) start clean.
        unset($_SESSION['customer_id']);
    }
}
