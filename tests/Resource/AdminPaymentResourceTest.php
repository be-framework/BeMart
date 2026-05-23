<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakePaymentMethodAdminStorage;
use MyVendor\BeMart\Be\Reason\Query\PaymentMethodAdminStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\AdminPaymentForm;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;
use function is_string;
use function str_contains;

/**
 * Wave 9θ — admin Payment master CRUD resource coverage.
 */
final class AdminPaymentResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;
    private FakePaymentMethodAdminStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new FakePaymentMethodAdminStorage();
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly FakePaymentMethodAdminStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
                $this->bind(PaymentMethodAdminStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakePaymentMethodAdminStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function seed(string $name, int $charge = 0): string
    {
        $ro = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => $name,
            'charge' => $charge,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $id = $ro->body['paymentId'];
        assert(is_string($id));

        return $id;
    }

    public function testCreateReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => 'クレジットカード',
            'charge' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('クレジットカード', $ro->body['paymentMethodName']);
        $this->assertTrue($ro->body['visible']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => 'クレジットカード',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateRejectsMissingCsrf(): void
    {
        $ro = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => 'クレジットカード',
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }

    public function testListReturnsRows(): void
    {
        $this->seed('代金引換', 300);
        $this->seed('クレジットカード');

        $ro = $this->resource->get('page://self/admin/payment/payment-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/payment/payment-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testPutEditsMaster(): void
    {
        $id = $this->seed('クレジットカード');

        $ro = $this->resource->put('page://self/admin/payment/payment', [
            'paymentId' => $id,
            'paymentMethodName' => 'クレジット',
            'charge' => 200,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('クレジット', $ro->body['paymentMethodName']);
        $this->assertSame(200, $ro->body['charge']);
    }

    public function testPutUnknownIdReturns404(): void
    {
        $ro = $this->resource->put('page://self/admin/payment/payment', [
            'paymentId' => 'nonexistent-zzz',
            'paymentMethodName' => 'X',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed('代金引換');

        $ro = $this->resource->delete('page://self/admin/payment/payment', [
            'paymentId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($id, $ro->body['paymentId']);
    }

    public function testDeleteRejectsMissingCsrf(): void
    {
        $id = $this->seed('代金引換');
        $ro = $this->resource->delete('page://self/admin/payment/payment', [
            'paymentId' => $id,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }

    public function testOnGetNewReturnsBlankForm(): void
    {
        $ro = $this->resource->get('page://self/admin/payment/payment');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminPaymentForm::class, $ro->body['form']);
        $this->assertSame('', $ro->body['paymentId']);
        $this->assertNull($ro->body['payment']);
    }

    public function testOnGetReturnsPaymentForm(): void
    {
        $id = $this->seed('クレジットカード', 200);

        $ro = $this->resource->get('page://self/admin/payment/payment', ['paymentId' => $id]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminPaymentForm::class, $ro->body['form']);
        $this->assertSame($id, $ro->body['paymentId']);
        $this->assertSame('クレジットカード', $ro->body['payment']['paymentMethodName']);
        $this->assertSame(200, $ro->body['payment']['charge']);
    }

    public function testOnGetUnknownIdReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/payment/payment', ['paymentId' => 'nonexistent-zzz']);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnGetRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/payment/payment');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
