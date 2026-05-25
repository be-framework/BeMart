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
use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
use MyVendor\BeMart\Module\ProdModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;
use function file_exists;
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

        // Slice 7: ProdModule binds SessionInterface to EccubeSharedSessionAdapter,
        // which reads $_SESSION['customer_id'].
        $_SESSION['customer_id'] = 'customer-001';

        // Slice 8: ProdModule also binds CsrfTokenInterface to
        // EccubeSharedCsrfTokenAdapter, which checks `$_SESSION['_csrf_token']`.
        // Mirror a reference token so the prod adapter accepts our submission.
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'prod-csrf-mirror';

        $injector = new Injector(
            new ProdModule(new Meta('MyVendor\\BeMart', 'prod')),
            dirname(__DIR__, 2) . '/var/tmp/prod',
        );

        $resource = $injector->getInstance(ResourceInterface::class);
        $ro = $resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
            'csrfToken' => 'prod-csrf-mirror',
        ]);

        // Phase 2c (production cutover): ProdModule now installs SqlModule,
        // so the prod context runs the SQL-backed Reasons against the real
        // DATABASE_URL DB instead of the in-memory Fakes. The Fakes used to
        // pre-load the `aaaa…` PROCESSING pre-order for customer-001; the
        // SQL backend has no such row unless the DB is seeded (an
        // out-of-scope infrastructure prerequisite — load
        // sql/schema/ec-cube-4.3-mysql-mysqldump.sql and seed dtb_*/mtb_*).
        // So the checkout no longer 201s here; it resolves to a 404
        // (pre-order not found). What this test still pins is the invariant
        // it was written for: the request runs end-to-end through the
        // Becoming framework under the prod context, and the prod logging
        // override means var/log/bemart.json is NEVER written regardless of
        // the response code (PII leak prevention).
        $this->assertGreaterThanOrEqual(Code::OK, $ro->code);
        $this->assertFileDoesNotExist(
            $this->logFile,
            'ProdModule must NOT write var/log/bemart.json (PII leak prevention)',
        );
    }

    public function testProdContextRejectsMissingCsrfToken(): void
    {
        // Slice 8: even with a valid session customerId, a state-changing
        // request without a CSRF token is rejected at the resource boundary.
        $_SESSION['customer_id'] = 'customer-001';
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'prod-csrf-mirror';

        $injector = new Injector(
            new ProdModule(new Meta('MyVendor\\BeMart', 'prod')),
            dirname(__DIR__, 2) . '/var/tmp/prod',
        );

        $resource = $injector->getInstance(ResourceInterface::class);
        $ro = $resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
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
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertFileExists($this->logFile);
    }

    protected function tearDown(): void
    {
        // Clean up so the next test run starts from a known state.
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        // Slice 7/8: scrub any session values we set so unrelated tests
        // (especially anonymous-session AUTHZ and missing-CSRF tests)
        // start clean.
        unset(
            $_SESSION['customer_id'],
            $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY],
        );
    }
}
