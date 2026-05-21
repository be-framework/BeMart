<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\CsvColumnConfigStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\AdminCsvConfigForm;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 9ι — doUpdateCsv resource coverage.
 *
 * Per-type column vector replace. Wave 9 first iteration: the storage
 * holds the configuration but the export Finals still emit a hardcoded
 * column list — only the round-trip is exercised here.
 */
final class AdminCsvConfigResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private CsvColumnConfigStorageInterface $storage;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->storage = $injector->getInstance(CsvColumnConfigStorageInterface::class);
    }

    public function testOnGetReturnsCsvConfigForm(): void
    {
        $ro = $this->resource->get('page://self/admin/csv-config');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminCsvConfigForm::class, $ro->body['form']);
        $this->assertSame(1, $ro->body['id']);
        $this->assertArrayHasKey('orderNo', $ro->body['outputColumns']);
        $this->assertArrayHasKey('paymentMethod', $ro->body['notOutputColumns']);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/csv-config');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }

    public function testOnPostHappyPathPersistsColumnVector(): void
    {
        $ro = $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 3, // product
            'columns' => [
                ['columnName' => 'productCode', 'enabled' => true, 'sortNo' => 1],
                ['columnName' => 'productName', 'enabled' => true, 'sortNo' => 2],
                ['columnName' => 'note', 'enabled' => false, 'sortNo' => 3],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(3, $ro->body['csvType']);
        $this->assertSame(3, $ro->body['count']);

        $persisted = $this->storage->listByType(3);
        $this->assertCount(3, $persisted);
        $this->assertSame('productCode', $persisted[0]->columnName);
        $this->assertTrue($persisted[0]->enabled);
        $this->assertSame('note', $persisted[2]->columnName);
        $this->assertFalse($persisted[2]->enabled);
    }

    public function testOnPostReplacesPreviousVectorAtomically(): void
    {
        // First write: 2 columns.
        $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 1,
            'columns' => [
                ['columnName' => 'orderNo', 'enabled' => true, 'sortNo' => 1],
                ['columnName' => 'total', 'enabled' => true, 'sortNo' => 2],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertCount(2, $this->storage->listByType(1));

        // Second write: 1 column — must replace, not merge.
        $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 1,
            'columns' => [
                ['columnName' => 'orderDate', 'enabled' => true, 'sortNo' => 1],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $rows = $this->storage->listByType(1);
        $this->assertCount(1, $rows);
        $this->assertSame('orderDate', $rows[0]->columnName);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 3,
            'columns' => [
                ['columnName' => 'productCode', 'enabled' => true, 'sortNo' => 1],
            ],
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 3,
            'columns' => [
                ['columnName' => 'productCode', 'enabled' => true, 'sortNo' => 1],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
