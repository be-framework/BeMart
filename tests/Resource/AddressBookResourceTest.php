<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Fake\Query\FakeAddressStorage;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;
use function is_array;
use function is_string;

/**
 * Pilot 16 — customer address book resource tests.
 *
 * Covers all four transitions end-to-end through the BEAR.Sunday
 * resource layer:
 *
 *   - goCustomerAddressList     (GET  /mypage/address-list)
 *   - doCreateCustomerAddress   (POST /mypage/address-list)
 *   - doUpdateCustomerAddress   (PUT  /mypage/address)
 *   - doDeleteCustomerAddress   (DELETE /mypage/address)
 *
 * Tests share FakeAddressStorage (Singleton) so a POST seeds rows that
 * subsequent GET / PUT / DELETE calls see. Each test uses
 * `rebindSession` to drive the AUTHN / AUTHZ branches with two
 * fixture customers (alice + bob — same ids as the customer fixture).
 */
final class AddressBookResourceTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const BOB_ID = 'fedcba9876543210fedcba9876543210';

    private ResourceInterface $resource;
    private FakeAddressStorage $storage;

    protected function setUp(): void
    {
        // Fresh per-test storage shared across session rebinds. Each
        // rebindSession() builds a fresh injector but reuses this same
        // storage instance via toInstance(), so a row Alice creates
        // remains visible when Bob's injector is built next — that's
        // the only way to exercise cross-customer AUTHZ at the
        // Resource layer with separate sessions.
        $this->storage = new FakeAddressStorage();
        $this->rebindSession(self::ALICE_ID);
    }

    private function rebindSession(string|null $customerId): void
    {
        $session = new FakeSession($customerId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $this->storage) extends AbstractModule {
            public function __construct(
                private readonly FakeSession $session,
                private readonly FakeAddressStorage $storage,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(SessionInterface::class)->toInstance($this->session);
                $this->bind(AddressStorageInterface::class)->toInstance($this->storage);
                $this->bind(FakeAddressStorage::class)->toInstance($this->storage);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /**
     * Seed a single address for the currently-bound session and return
     * its server-generated addressId. Helper to keep the rest of the
     * tests focused on the transition under test.
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
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame(0, $ro->body['count']);
        $this->assertSame([], $ro->body['addresses']);
    }

    public function testOnGetReturnsSeededAddressesForCurrentCustomerOnly(): void
    {
        // Seed two for alice.
        $aliceId1 = $this->seedAddress();
        $aliceId2 = $this->seedAddress();

        // Switch to bob and seed one — should not appear in alice's list.
        $this->rebindSession(self::BOB_ID);
        $this->seedAddress();

        // Switch back to alice.
        $this->rebindSession(self::ALICE_ID);
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
        $this->rebindSession(null);

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
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertNotEmpty($ro->body['addressId']);
        $this->assertSame('神宮前1-1-1', $ro->body['addr02']);
        // Optional fields default to null when omitted.
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
        $this->rebindSession(null);

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
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame('0399998888', $ro->body['phoneNumber']);
        // Unrelated fields preserved.
        $this->assertSame('渋谷区', $ro->body['addr01']);
        $this->assertSame(13, $ro->body['pref']);
    }

    public function testOnPutUnknownIdReturns404(): void
    {
        $ro = $this->resource->put('page://self/mypage/address', [
            'addressId' => 'deadbeefdeadbeefdeadbeefdeadbeef',
            'phoneNumber' => '0399998888',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnPutOtherCustomersAddressReturns403(): void
    {
        // Alice creates an address.
        $aliceAddressId = $this->seedAddress();

        // Bob logs in and tries to update alice's address.
        $this->rebindSession(self::BOB_ID);
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
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);

        // Verify removal — subsequent list call sees zero rows.
        $list = $this->resource->get('page://self/mypage/address-list');
        $this->assertSame(0, $list->body['count']);
    }

    public function testOnDeleteUnknownIdReturns404(): void
    {
        $ro = $this->resource->delete('page://self/mypage/address', [
            'addressId' => 'deadbeefdeadbeefdeadbeefdeadbeef',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnDeleteOtherCustomersAddressReturns403(): void
    {
        // Alice creates an address.
        $aliceAddressId = $this->seedAddress();

        // Bob logs in and tries to delete alice's address.
        $this->rebindSession(self::BOB_ID);
        $ro = $this->resource->delete('page://self/mypage/address', [
            'addressId' => $aliceAddressId,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);

        // Alice's address is still there.
        $this->rebindSession(self::ALICE_ID);
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
        $this->rebindSession(null);

        $ro = $this->resource->delete('page://self/mypage/address', [
            'addressId' => 'deadbeefdeadbeefdeadbeefdeadbeef',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
    }
}
