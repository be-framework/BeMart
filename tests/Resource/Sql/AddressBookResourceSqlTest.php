<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\AbstractModule;

use function assert;
use function is_array;
use function is_string;

/**
 * SQL-backed hypermedia coverage for the customer address book —
 * mirror of {@see \MyVendor\BeMart\Tests\Resource\AddressBookResourceTest}
 * (Phase 2b, G-23 storage migration contract).
 *
 * Same four URIs, same body-shape assertions, same AUTHN / AUTHZ /
 * CSRF branches. The only difference is the storage binding (SQL via
 * the base class's sqlOverrideModule) and the session actor
 * (customer ids are the numeric `dtb_customer.id` values returned by
 * the SQL fixture helper, not the 32-char hex tokens the Fake test
 * hard-codes).
 *
 * Why mirror exactly: per G-23 the Resource-layer contract MUST stay
 * green for both Fake and SQL backings. If the SQL side passes but
 * the Fake side fails (or vice versa), the storage swap changed the
 * client-observable behavior — that's a contract change masquerading
 * as a storage change.
 */
final class AddressBookResourceSqlTest extends AbstractResourceSqlTestCase
{
    private string $aliceId;
    private string $bobId;

    /** @var non-empty-string|null */
    private string|null $currentCustomerId = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Two real customers, ids drawn from dtb_customer.id. Both
        // sessions in this suite cycle between these two — same shape
        // as the Fake-backed sibling, just numeric ids.
        $this->aliceId = (string) $this->insertCustomer(['email' => 'alice@example.com']);
        $this->bobId = (string) $this->insertCustomer(['email' => 'bob@example.com']);

        // mtb_pref is empty in the structure-only dump; seed the one
        // prefecture id our fixtures use so the FK from
        // dtb_customer_address.pref_id is satisfiable.
        $this->insertPref(13, 'Tokyo');

        $this->loginAs($this->aliceId);
    }

    protected function extraOverride(): AbstractModule|null
    {
        $customerId = $this->currentCustomerId;

        return new class ($customerId) extends AbstractModule {
            /** @param non-empty-string|null $customerId */
            public function __construct(private readonly string|null $customerId)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(SessionInterface::class)
                    ->toInstance(new FakeSession($this->customerId));
            }
        };
    }

    /** @param non-empty-string|null $customerId */
    private function loginAs(string|null $customerId): void
    {
        $this->currentCustomerId = $customerId;
        $this->resource = $this->buildResource();
    }

    /**
     * Seed a single address for the currently-bound session and return
     * its server-generated addressId. Mirrors the Fake-backed sibling's
     * helper exactly — POSTs through the resource layer so the full
     * Becoming chain (Input → Final → SqlAddressStorage) drives the
     * write.
     */
    private function seedAddress(): string
    {
        $ro = $this->resource->post('page://self/mypage/address-list', [
            'name01' => '山田',
            'name02' => 'アリス',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phoneNumber' => '0312345678',
            'kana01' => 'ヤマダ',
            'kana02' => 'アリス',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        assert($ro->code === Code::CREATED);
        assert(is_array($ro->body));
        $id = $ro->body['addressId'] ?? null;
        assert(is_string($id));

        return $id;
    }

    // ---- goCustomerAddressList (GET) ----

    public function testOnGetReturnsEmptyListInitially(): void
    {
        $ro = $this->resource->get('page://self/mypage/address-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($this->aliceId, $ro->body['customerId']);
        $this->assertSame(0, $ro->body['count']);
        $this->assertSame([], $ro->body['addresses']);
    }

    public function testOnGetReturnsSeededAddressesForCurrentCustomerOnly(): void
    {
        $aliceId1 = $this->seedAddress();
        $aliceId2 = $this->seedAddress();

        $this->loginAs($this->bobId);
        $this->seedAddress();

        $this->loginAs($this->aliceId);
        $ro = $this->resource->get('page://self/mypage/address-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
        $ids = [];
        foreach ($ro->body['addresses'] as $row) {
            $ids[] = $row['addressId'];
        }

        $this->assertContains($aliceId1, $ids);
        $this->assertContains($aliceId2, $ids);
    }

    public function testOnGetUnauthenticatedReturns401(): void
    {
        $this->loginAs(null);

        $ro = $this->resource->get('page://self/mypage/address-list');

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
    }

    // ---- doCreateCustomerAddress (POST) ----

    public function testOnPostCreatesAndReturns201(): void
    {
        $ro = $this->resource->post('page://self/mypage/address-list', [
            'name01' => '山田',
            'name02' => 'アリス',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame($this->aliceId, $ro->body['customerId']);
        $this->assertNotEmpty($ro->body['addressId']);
        $this->assertSame('神宮前1-1-1', $ro->body['addr02']);
        $this->assertNull($ro->body['kana01']);
        $this->assertNull($ro->body['companyName']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/mypage/address-list', [
            'name01' => '山田',
            'name02' => 'アリス',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phoneNumber' => '0312345678',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPostUnauthenticatedReturns401(): void
    {
        $this->loginAs(null);

        $ro = $this->resource->post('page://self/mypage/address-list', [
            'name01' => '山田',
            'name02' => 'アリス',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
    }

    public function testOnPostInvalidPostalCodeReturns400(): void
    {
        $ro = $this->resource->post('page://self/mypage/address-list', [
            'name01' => '山田',
            'name02' => 'アリス',
            'postalCode' => 'not-a-postcode',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phoneNumber' => '0312345678',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    // ---- doUpdateCustomerAddress (PUT) ----

    public function testOnPutMergesPartialUpdate(): void
    {
        $addressId = $this->seedAddress();

        $ro = $this->resource->put('page://self/mypage/address', [
            'addressId' => $addressId,
            'phoneNumber' => '0399998888',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($addressId, $ro->body['addressId']);
        $this->assertSame($this->aliceId, $ro->body['customerId']);
        $this->assertSame('0399998888', $ro->body['phoneNumber']);
        // Unrelated fields preserved through the merge.
        $this->assertSame('渋谷区', $ro->body['addr01']);
        $this->assertSame(13, $ro->body['pref']);
    }

    public function testOnPutUnknownIdReturns404(): void
    {
        $ro = $this->resource->put('page://self/mypage/address', [
            'addressId' => '99999999', // numeric but unused PK
            'phoneNumber' => '0399998888',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPutOtherCustomersAddressReturns403(): void
    {
        $aliceAddressId = $this->seedAddress();

        $this->loginAs($this->bobId);
        $ro = $this->resource->put('page://self/mypage/address', [
            'addressId' => $aliceAddressId,
            'phoneNumber' => '0399998888',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnPutMissingCsrfReturns403(): void
    {
        $addressId = $this->seedAddress();

        $ro = $this->resource->put('page://self/mypage/address', [
            'addressId' => $addressId,
            'phoneNumber' => '0399998888',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    // ---- doDeleteCustomerAddress (DELETE) ----

    public function testOnDeleteRemovesAndReturns200(): void
    {
        $addressId = $this->seedAddress();

        $ro = $this->resource->delete('page://self/mypage/address', [
            'addressId' => $addressId,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($addressId, $ro->body['addressId']);
        $this->assertSame($this->aliceId, $ro->body['customerId']);

        $list = $this->resource->get('page://self/mypage/address-list');
        $this->assertSame(0, $list->body['count']);
    }

    public function testOnDeleteUnknownIdReturns404(): void
    {
        $ro = $this->resource->delete('page://self/mypage/address', [
            'addressId' => '99999999',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnDeleteOtherCustomersAddressReturns403(): void
    {
        $aliceAddressId = $this->seedAddress();

        $this->loginAs($this->bobId);
        $ro = $this->resource->delete('page://self/mypage/address', [
            'addressId' => $aliceAddressId,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);

        $this->loginAs($this->aliceId);
        $list = $this->resource->get('page://self/mypage/address-list');
        $this->assertSame(1, $list->body['count']);
    }

    public function testOnDeleteMissingCsrfReturns403(): void
    {
        $addressId = $this->seedAddress();

        $ro = $this->resource->delete('page://self/mypage/address', [
            'addressId' => $addressId,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testOnDeleteUnauthenticatedReturns401(): void
    {
        $this->loginAs(null);

        $ro = $this->resource->delete('page://self/mypage/address', [
            'addressId' => '99999999',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
    }
}
