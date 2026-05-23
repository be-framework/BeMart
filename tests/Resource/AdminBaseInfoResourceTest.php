<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeBaseInfoStorage;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Wave 8 — doUpdateBaseInfo resource coverage.
 *
 * Idempotent: replaying the same body is `changed=false`.
 */
final class AdminBaseInfoResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private Injector $injector;
    private FakeBaseInfoStorage $storage;

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

        $this->injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $this->injector->getInstance(ResourceInterface::class);
        $this->storage = $this->injector->getInstance(FakeBaseInfoStorage::class);
    }

    public function testOnPostHappyPathUpdatesBaseInfo(): void
    {
        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => '新ショップ',
            'shopKana' => 'シンショップ',
            'phoneNumber' => '03-1234-5678',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('新ショップ', $ro->body['shopName']);
        $this->assertSame('シンショップ', $ro->body['shopKana']);
        $this->assertSame('03-1234-5678', $ro->body['phoneNumber']);
        $this->assertTrue($ro->body['changed']);

        $persisted = $this->storage->get();
        $this->assertSame('新ショップ', $persisted->shopName);
        $this->assertSame(13, $persisted->pref);
    }

    public function testOnPostIdempotentReplayReturnsChangedFalse(): void
    {
        // Submit the exact seed values back.
        $seed = $this->storage->get();
        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => $seed->shopName,
            'shopKana' => $seed->shopKana,
            'shopNameEng' => $seed->shopNameEng,
            'companyName' => $seed->companyName,
            'postalCode' => $seed->postalCode,
            'pref' => $seed->pref,
            'addr01' => $seed->addr01,
            'addr02' => $seed->addr02,
            'phoneNumber' => $seed->phoneNumber,
            'businessHour' => $seed->businessHour,
            'shopEmail01' => $seed->shopEmail01,
            'shopMessage' => $seed->shopMessage,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertFalse($ro->body['changed']);
    }

    public function testOnPostEmptyShopNameReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => '   ',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostBadPhoneNumberReturns400(): void
    {
        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => '新ショップ',
            'phoneNumber' => 'not-digits',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => '新ショップ',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }

    public function testOnPostWithoutAdminSessionReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->post('page://self/admin/base-info', [
            'shopName' => '新ショップ',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
