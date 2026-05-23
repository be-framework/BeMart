<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Form\AdminCustomerDeliveryForm;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for the admin お届け先編集 Customer Tier-2 page.
 *
 * The resource is a thin GET renderer for the EC-CUBE customer
 * address-book entry editor: BeMart does not yet model customer-address
 * writes as Be transitions, so the page exposes the empty edit-form body
 * shape only. The AUTHZ guard rejects anonymous admins with 403.
 */
final class AdminCustomerDeliveryEditResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const TEST_CUSTOMER_ID = 'cu000000000000000000000000000001';

    private ResourceInterface $resource;

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        });

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsAddressForm(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->get('page://self/admin/customer-delivery-edit');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminCustomerDeliveryForm::class, $ro->body['form']);
        $this->assertSame('', $ro->body['customerId']);
    }

    public function testOnGetReflectsRequestedCustomerId(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->get(
            'page://self/admin/customer-delivery-edit',
            ['customerId' => self::TEST_CUSTOMER_ID],
        );

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::TEST_CUSTOMER_ID, $ro->body['customerId']);
    }

    public function testOnGetAnonymousAdminReturns403(): void
    {
        $this->rebindAdminSession(null);

        $ro = $this->resource->get('page://self/admin/customer-delivery-edit');

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('管理者', $ro->body['message']);
    }
}
