<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 9ι — goExportCustomer resource coverage. Mirrors Wave 8α
 * AdminProductCsvResourceTest + Wave 8β AdminCategoryResource's
 * testExportCsvDumpsRows.
 */
final class AdminCustomerCsvResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsCsvAndAttachmentHeader(): void
    {
        $ro = $this->resource->get('page://self/admin/customer-csv');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertGreaterThanOrEqual(1, $ro->body['rowCount']);
        // Header row.
        $this->assertStringContainsString('customerId,email,name01', $ro->body['csv']);
        // Headers set for download.
        $this->assertSame('text/csv; charset=UTF-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('customers.csv', $ro->headers['Content-Disposition']);
        // passwordHash MUST NOT leak.
        $this->assertStringNotContainsString('passwordHash', $ro->body['csv']);
    }

    public function testOnGetWithoutAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->get('page://self/admin/customer-csv');
    }
}
