<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\DeliveryStorageInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeDeliveryStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\AdminDeliveryForm;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;
use function is_string;
use function str_contains;

/**
 * Wave 9θ — admin Delivery master CRUD resource coverage.
 */
final class AdminDeliveryResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private FakeDeliveryStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new FakeDeliveryStorage();
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakeDeliveryStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(DeliveryStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakeDeliveryStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function seed(string $name): string
    {
        $ro = $this->resource->post('page://self/admin/delivery/delivery-list', [
            'deliveryName' => $name,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['deliveryId'];
        assert(is_string($id));

        return $id;
    }

    public function testCreateReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/delivery/delivery-list', [
            'deliveryName' => 'ヤマト宅急便',
            'visible' => true,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('ヤマト宅急便', $ro->body['deliveryName']);
        $this->assertTrue($ro->body['visible']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/delivery/delivery-list', [
            'deliveryName' => 'ヤマト',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateRejectsMissingCsrf(): void
    {
        $ro = $this->resource->post('page://self/admin/delivery/delivery-list', [
            'deliveryName' => 'ヤマト',
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }

    public function testListReturnsRows(): void
    {
        $this->seed('ヤマト宅急便');
        $this->seed('ゆうパック');

        $ro = $this->resource->get('page://self/admin/delivery/delivery-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/delivery/delivery-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testPutEditsMaster(): void
    {
        $id = $this->seed('ヤマト');

        $ro = $this->resource->put('page://self/admin/delivery/delivery', [
            'deliveryId' => $id,
            'deliveryName' => 'ヤマト宅急便',
            'visible' => false,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('ヤマト宅急便', $ro->body['deliveryName']);
        $this->assertFalse($ro->body['visible']);
    }

    public function testPutUnknownIdReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/delivery/delivery', [
            'deliveryId' => 'nonexistent-zzz',
            'deliveryName' => 'X',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed('ヤマト宅急便');

        $ro = $this->resource->delete('page://self/admin/delivery/delivery', [
            'deliveryId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($id, $ro->body['deliveryId']);
    }

    public function testDeleteRejectsMissingCsrf(): void
    {
        $id = $this->seed('ヤマト宅急便');
        $ro = $this->resource->delete('page://self/admin/delivery/delivery', [
            'deliveryId' => $id,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }

    public function testOnGetNewReturnsBlankForm(): void
    {
        $ro = $this->resource->get('page://self/admin/delivery/delivery');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminDeliveryForm::class, $ro->body['form']);
        $this->assertSame('', $ro->body['deliveryId']);
        $this->assertNull($ro->body['delivery']);
    }

    public function testOnGetReturnsDeliveryForm(): void
    {
        $id = $this->seed('ヤマト宅急便');

        $ro = $this->resource->get('page://self/admin/delivery/delivery', ['deliveryId' => $id]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminDeliveryForm::class, $ro->body['form']);
        $this->assertSame($id, $ro->body['deliveryId']);
        $this->assertSame('ヤマト宅急便', $ro->body['delivery']['deliveryName']);
    }

    public function testOnGetUnknownIdReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/delivery/delivery', ['deliveryId' => 'nonexistent-zzz']);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnGetRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/delivery/delivery');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
