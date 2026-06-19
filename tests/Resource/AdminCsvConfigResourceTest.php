<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Csrf\CsrfTokenInterface;
use Ray\Csrf\Exception\MissingCsrfTokenException;
use Ray\Csrf\Http\CompositeRequestToken;
use Ray\Csrf\Http\RequestTokenInterface;
use MyVendor\BeMart\Form\AdminCsvConfigForm;
use MyVendor\BeMart\Module\TestModule;
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
                $this->bind(CsrfTokenInterface::class)->to(FakeCsrfToken::class);
                $this->bind(RequestTokenInterface::class)->to(CompositeRequestToken::class);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsCsvConfigForm(): void
    {
        $ro = $this->resource->get('page://self/admin/csv-config');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminCsvConfigForm::class, $ro->body['form']);
        $this->assertSame(3, $ro->body['csvType']);
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
            'csvType' => 1, // product
            'columns' => [
                ['columnName' => 'productCode', 'enabled' => true, 'sortNo' => 1],
                ['columnName' => 'productName', 'enabled' => true, 'sortNo' => 2],
                ['columnName' => 'note', 'enabled' => false, 'sortNo' => 3],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(1, $ro->body['csvType']);
        $this->assertSame(3, $ro->body['count']);

        // Persistence read-back belongs to the SQL suite. Fake context is
        // static Ray.FakeQuery fixtures and does not mutate query state.
    }

    public function testOnPostReplacesPreviousVectorAtomically(): void
    {
        // Mutation/re-read replacement semantics are covered by the SQL
        // suite. In fake context we only assert both command shapes.
        $first = $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 1,
            'columns' => [
                ['columnName' => 'orderNo', 'enabled' => true, 'sortNo' => 1],
                ['columnName' => 'total', 'enabled' => true, 'sortNo' => 2],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $first->code);
        $this->assertSame(2, $first->body['count']);

        $second = $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 1,
            'columns' => [
                ['columnName' => 'orderDate', 'enabled' => true, 'sortNo' => 1],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::OK, $second->code);
        $this->assertSame(1, $second->body['count']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $this->expectException(MissingCsrfTokenException::class);
        $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 1,
            'columns' => [
                ['columnName' => 'productCode', 'enabled' => true, 'sortNo' => 1],
            ],
        ]);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/csv-config', [
            'csvType' => 1,
            'columns' => [
                ['columnName' => 'productCode', 'enabled' => true, 'sortNo' => 1],
            ],
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
