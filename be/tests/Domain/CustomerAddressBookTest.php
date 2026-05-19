<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\AddressNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAddressAccessException;
use MyVendor\BeMart\Be\Final\CustomerAddressCreated;
use MyVendor\BeMart\Be\Final\CustomerAddressDeleted;
use MyVendor\BeMart\Be\Final\CustomerAddressListFetched;
use MyVendor\BeMart\Be\Final\CustomerAddressUpdated;
use MyVendor\BeMart\Be\Input\CreateCustomerAddressInput;
use MyVendor\BeMart\Be\Input\DeleteCustomerAddressInput;
use MyVendor\BeMart\Be\Input\GetCustomerAddressListInput;
use MyVendor\BeMart\Be\Input\UpdateCustomerAddressInput;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\FakeAddressStorage;
use MyVendor\BeMart\Be\Reason\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function assert;
use function dirname;

/**
 * Pilot 16 — domain tests for the customer address book.
 *
 * Covers the four Direct flows (list / create / update / delete) at
 * the Be Final layer. The Resource tests cover HTTP semantics; these
 * focus on AUTHN / AUTHZ branching and partial-update merging.
 */
final class CustomerAddressBookTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const BOB_ID = 'fedcba9876543210fedcba9876543210';

    private BecomingInterface $becoming;
    private FakeAddressStorage $storage;

    protected function setUp(): void
    {
        // Fresh per-test storage shared across `bindAs` rebinds so a
        // row Alice creates remains visible when we rebind to Bob —
        // that is what lets the AUTHZ tests exercise foreign-owner
        // detection across two separate injectors.
        $this->storage = new FakeAddressStorage();
    }

    private function bindAs(string|null $sessionCustomerId): void
    {
        $session = new FakeSession($sessionCustomerId);
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
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    /**
     * Build a CreateInput with sensible defaults — keeps the test
     * cases focused on the field they actually exercise.
     */
    private function buildCreateInput(): CreateCustomerAddressInput
    {
        return new CreateCustomerAddressInput(
            name01: '山田',
            name02: 'アリス',
            postalCode: '1500001',
            pref: 13,
            addr01: '渋谷区',
            addr02: '神宮前1-1-1',
            phoneNumber: '0312345678',
            kana01: 'ヤマダ',
            kana02: 'アリス',
        );
    }

    // ---- Create ----

    public function testCreateGeneratesIdAndPullsCustomerFromSession(): void
    {
        $this->bindAs(self::ALICE_ID);

        $final = ($this->becoming)($this->buildCreateInput());

        $this->assertInstanceOf(CustomerAddressCreated::class, $final);
        $this->assertSame(self::ALICE_ID, $final->customerId);
        $this->assertNotEmpty($final->addressId);
        $this->assertSame('山田', $final->name01);
        $this->assertSame('神宮前1-1-1', $final->addr02);

        $persisted = $this->storage->getById($final->addressId);
        assert($persisted !== null);
        $this->assertSame(self::ALICE_ID, $persisted->customerId);
    }

    public function testCreateUnauthenticatedRaises(): void
    {
        $this->bindAs(null);

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)($this->buildCreateInput());
    }

    // ---- List ----

    public function testListReturnsOnlyOwnedRows(): void
    {
        $this->bindAs(self::ALICE_ID);
        $aliceFinal1 = ($this->becoming)($this->buildCreateInput());
        $aliceFinal2 = ($this->becoming)($this->buildCreateInput());
        assert($aliceFinal1 instanceof CustomerAddressCreated);
        assert($aliceFinal2 instanceof CustomerAddressCreated);

        $this->bindAs(self::BOB_ID);
        ($this->becoming)($this->buildCreateInput());

        $this->bindAs(self::ALICE_ID);
        $listFinal = ($this->becoming)(new GetCustomerAddressListInput());

        $this->assertInstanceOf(CustomerAddressListFetched::class, $listFinal);
        $this->assertSame(2, $listFinal->count);
        $ids = [];
        foreach ($listFinal->addresses as $row) {
            $ids[] = $row['addressId'];
        }

        $this->assertContains($aliceFinal1->addressId, $ids);
        $this->assertContains($aliceFinal2->addressId, $ids);
    }

    public function testListUnauthenticatedRaises(): void
    {
        $this->bindAs(null);

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new GetCustomerAddressListInput());
    }

    // ---- Update ----

    public function testUpdateMergesNullsOntoCurrent(): void
    {
        $this->bindAs(self::ALICE_ID);
        $created = ($this->becoming)($this->buildCreateInput());
        assert($created instanceof CustomerAddressCreated);

        $updated = ($this->becoming)(new UpdateCustomerAddressInput(
            addressId: $created->addressId,
            phoneNumber: '0399998888',
        ));

        $this->assertInstanceOf(CustomerAddressUpdated::class, $updated);
        $this->assertSame('0399998888', $updated->phoneNumber);
        $this->assertSame('山田', $updated->name01);
        $this->assertSame('渋谷区', $updated->addr01);
    }

    public function testUpdateUnknownIdRaisesNotFound(): void
    {
        $this->bindAs(self::ALICE_ID);

        $this->expectException(AddressNotFoundException::class);
        ($this->becoming)(new UpdateCustomerAddressInput(
            addressId: 'deadbeefdeadbeefdeadbeefdeadbeef',
            phoneNumber: '0399998888',
        ));
    }

    public function testUpdateForeignAddressRaisesUnauthorized(): void
    {
        // Alice creates.
        $this->bindAs(self::ALICE_ID);
        $created = ($this->becoming)($this->buildCreateInput());
        assert($created instanceof CustomerAddressCreated);

        // Bob attempts to update.
        $this->bindAs(self::BOB_ID);
        $this->expectException(UnauthorizedAddressAccessException::class);
        ($this->becoming)(new UpdateCustomerAddressInput(
            addressId: $created->addressId,
            phoneNumber: '0399998888',
        ));
    }

    // ---- Delete ----

    public function testDeleteRemovesRow(): void
    {
        $this->bindAs(self::ALICE_ID);
        $created = ($this->becoming)($this->buildCreateInput());
        assert($created instanceof CustomerAddressCreated);

        $deleted = ($this->becoming)(new DeleteCustomerAddressInput(addressId: $created->addressId));

        $this->assertInstanceOf(CustomerAddressDeleted::class, $deleted);
        $this->assertSame($created->addressId, $deleted->addressId);
        $this->assertNull($this->storage->getById($created->addressId));
    }

    public function testDeleteUnknownIdRaisesNotFound(): void
    {
        $this->bindAs(self::ALICE_ID);

        $this->expectException(AddressNotFoundException::class);
        ($this->becoming)(new DeleteCustomerAddressInput(addressId: 'deadbeefdeadbeefdeadbeefdeadbeef'));
    }

    public function testDeleteForeignAddressRaisesUnauthorized(): void
    {
        $this->bindAs(self::ALICE_ID);
        $created = ($this->becoming)($this->buildCreateInput());
        assert($created instanceof CustomerAddressCreated);

        $this->bindAs(self::BOB_ID);
        $this->expectException(UnauthorizedAddressAccessException::class);
        ($this->becoming)(new DeleteCustomerAddressInput(addressId: $created->addressId));
    }
}
