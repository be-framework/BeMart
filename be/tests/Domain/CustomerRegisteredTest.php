<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use MyVendor\BeMart\Be\Exception\EmailFormatException;
use MyVendor\BeMart\Be\Exception\Name01FormatException;
use MyVendor\BeMart\Be\Exception\PasswordFormatException;
use MyVendor\BeMart\Be\Final\CustomerRegistered;
use MyVendor\BeMart\Be\Input\RegisterCustomerInput;
use MyVendor\BeMart\Be\Reason\Query\FakeCustomerStorage;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;
use function password_verify;
use function property_exists;
use function assert;

final class CustomerRegisteredTest extends TestCase
{
    private BecomingInterface $becoming;
    private FakeCustomerStorage $storage;

    protected function setUp(): void
    {
        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->storage = $injector->getInstance(FakeCustomerStorage::class);
    }

    public function testHappyPathPersistsAndReturnsServerScalars(): void
    {
        $final = ($this->becoming)(new RegisterCustomerInput(
            email: 'new-user@example.com',
            password: 'correct-horse-battery-staple',
            name01: '田中',
            name02: '次郎',
        ));

        $this->assertInstanceOf(CustomerRegistered::class, $final);
        $this->assertSame('new-user@example.com', $final->email);
        $this->assertSame('田中', $final->name01);
        $this->assertSame('次郎', $final->name02);
        $this->assertSame(100, $final->initialPoint);
        $this->assertSame(2, $final->customerStatus);
        // CustomerIdGenerator produces a 32-char hex id.
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $final->customerId);
        // The storage now contains the new customer.
        $this->assertTrue($this->storage->existsByEmail('new-user@example.com'));
    }

    public function testOptionalFieldsAreCarriedThrough(): void
    {
        $final = ($this->becoming)(new RegisterCustomerInput(
            email: 'with-address@example.com',
            password: 'another-strong-passphrase',
            name01: '伊藤',
            name02: '三郎',
            kana01: 'イトウ',
            kana02: 'サブロウ',
            companyName: 'Example Co.',
            phoneNumber: '0312345678',
            postalCode: '1000001',
            pref: 13,
            addr01: '千代田区',
            addr02: '丸の内3-3-3',
            birth: '1992-08-08',
            sex: 1,
            job: 7,
        ));

        $this->assertInstanceOf(CustomerRegistered::class, $final);
        $this->assertTrue($this->storage->existsByEmail('with-address@example.com'));
    }

    public function testPasswordIsHashedAndNotExposed(): void
    {
        // The Final intentionally does not expose the hash; the persisted
        // CustomerEntity holds it inside the storage. We verify the hash
        // round-trips via password_verify (the same path the future login
        // flow will take). The Final class itself has no passwordHash
        // property — by construction the plaintext never leaves Being.
        $plain = 'verify-me-please-2026';
        ($this->becoming)(new RegisterCustomerInput(
            email: 'hash-check@example.com',
            password: $plain,
            name01: '高橋',
            name02: '四郎',
        ));

        $this->assertFalse(property_exists(CustomerRegistered::class, 'passwordHash'));
        $persisted = $this->storage->getByEmail('hash-check@example.com');
        assert($persisted !== null);
        $this->assertTrue(password_verify($plain, $persisted->passwordHash));
    }

    public function testDuplicateEmailIsRejected(): void
    {
        // alice@example.com is in the seed fixture.
        $this->expectException(EmailAlreadyRegisteredException::class);
        ($this->becoming)(new RegisterCustomerInput(
            email: 'alice@example.com',
            password: 'try-to-overwrite-2026',
            name01: '別人',
            name02: 'A',
        ));
    }

    public function testInvalidEmailRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new RegisterCustomerInput(
                email: 'not-an-email',
                password: 'whatever-2026',
                name01: '佐藤',
                name02: '五郎',
            ));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                EmailFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

    public function testEmptyPasswordRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new RegisterCustomerInput(
                email: 'pw-empty@example.com',
                password: '',
                name01: '佐藤',
                name02: '六郎',
            ));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                PasswordFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

    public function testEmptyName01Rejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new RegisterCustomerInput(
                email: 'name-empty@example.com',
                password: 'valid-passphrase-2026',
                name01: '',
                name02: '七郎',
            ));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                Name01FormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }

}
