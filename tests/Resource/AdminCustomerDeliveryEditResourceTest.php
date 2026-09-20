<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAddressAccessException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Form\AdminCustomerDeliveryForm;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for the admin お届け先編集 Customer Tier-2 page.
 *
 * The resource renders (GET) and persists (POST create/update, DELETE) the
 * EC-CUBE customer address-book entry editor. Unlike the storefront Mypage
 * flow, the admin acts on a customer keyed by the route-param customerId,
 * so the write transitions land on admin-specific Be Inputs/Finals guarded
 * by the AdminSession (403 first) and an ownership check on the target
 * address. The AUTHZ guard rejects anonymous admins with 403.
 */
final class AdminCustomerDeliveryEditResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const TEST_CUSTOMER_ID = 'cu000000000000000000000000000001';

    /** Seeded fixture customer (be/var/fake/query/customer_find_by_id.jsonl). */
    private const SEEDED_CUSTOMER_ID = '0123456789abcdef0123456789abcdef';

    /** Seeded fixture address owned by SEEDED_CUSTOMER_ID (address_get.jsonl). */
    private const SEEDED_ADDRESS_ID = 'addr00000000000000000000000000a1';

    /** A different seeded fixture customer — not the owner of SEEDED_ADDRESS_ID. */
    private const OTHER_CUSTOMER_ID = 'fedcba9876543210fedcba9876543210';

    private ResourceInterface $resource;

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
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

    public function testCreateAddressReturns201(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->post('page://self/admin/customer-delivery-edit', [
            'customerId' => self::SEEDED_CUSTOMER_ID,
            'name01' => '山田',
            'name02' => '太郎',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame(self::SEEDED_CUSTOMER_ID, $ro->body['customerId']);
        $this->assertNotSame('', $ro->body['addressId']);
        $this->assertSame('山田', $ro->body['name01']);
    }

    public function testUpdateAddressReturns200(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->post('page://self/admin/customer-delivery-edit', [
            'customerId' => self::SEEDED_CUSTOMER_ID,
            'addressId' => self::SEEDED_ADDRESS_ID,
            'name01' => '改名',
            'name02' => '太郎',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前9-9-9',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::SEEDED_ADDRESS_ID, $ro->body['addressId']);
        $this->assertSame(self::SEEDED_CUSTOMER_ID, $ro->body['customerId']);
        $this->assertSame('改名', $ro->body['name01']);
        $this->assertSame('神宮前9-9-9', $ro->body['addr02']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/customer-delivery-edit', [
            'customerId' => self::SEEDED_CUSTOMER_ID,
            'name01' => '山田',
            'name02' => '太郎',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testUpdateForeignAddressReturns403(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $this->expectException(UnauthorizedAddressAccessException::class);

        // SEEDED_ADDRESS_ID is owned by SEEDED_CUSTOMER_ID, not OTHER_CUSTOMER_ID.
        $this->resource->post('page://self/admin/customer-delivery-edit', [
            'customerId' => self::OTHER_CUSTOMER_ID,
            'addressId' => self::SEEDED_ADDRESS_ID,
            'name01' => '乗っ取り',
            'name02' => '太郎',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testDeleteAddressReturns200(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);

        $ro = $this->resource->delete('page://self/admin/customer-delivery-edit', [
            'customerId' => self::SEEDED_CUSTOMER_ID,
            'addressId' => self::SEEDED_ADDRESS_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::SEEDED_ADDRESS_ID, $ro->body['addressId']);
        $this->assertSame(self::SEEDED_CUSTOMER_ID, $ro->body['customerId']);
    }

    public function testDeleteRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(UnauthorizedAdminAccessException::class);

        $this->resource->delete('page://self/admin/customer-delivery-edit', [
            'customerId' => self::SEEDED_CUSTOMER_ID,
            'addressId' => self::SEEDED_ADDRESS_ID,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
