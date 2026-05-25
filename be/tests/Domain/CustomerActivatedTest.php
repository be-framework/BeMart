<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\SecretKeyFormatException;
use MyVendor\BeMart\Be\Exception\SecretKeyNotFoundException;
use MyVendor\BeMart\Be\Final\CustomerActivated;
use MyVendor\BeMart\Be\Input\ActivateCustomerInput;
use MyVendor\BeMart\Be\Reason\Query\FakeCustomerStorage;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function assert;
use function dirname;

final class CustomerActivatedTest extends TestCase
{
    private const PROVISIONAL_KEY = 'pending-secret-key-pilot7-2026abcd';
    private const PROVISIONAL_ID = '20000000dddd2222eeee3333ffff4444';

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

    public function testHappyPathFlipsStatusAndClearsKey(): void
    {
        $final = ($this->becoming)(new ActivateCustomerInput(secretKey: self::PROVISIONAL_KEY));

        $this->assertInstanceOf(CustomerActivated::class, $final);
        $this->assertSame(self::PROVISIONAL_ID, $final->customerId);
        $this->assertSame('provisional@example.com', $final->email);
        $this->assertSame(2, $final->customerStatus);

        $persisted = $this->storage->getByEmail('provisional@example.com');
        assert($persisted !== null);
        $this->assertSame(2, $persisted->customerStatus);
        $this->assertNull($persisted->secretKey);
    }

    public function testActivateIsIdempotent(): void
    {
        // First activation flips status; the key is cleared.
        ($this->becoming)(new ActivateCustomerInput(secretKey: self::PROVISIONAL_KEY));

        // A re-attempt with the same key now misses (the key was cleared
        // by the first activation). The merged "wrong key / expired /
        // already used" error path applies.
        $this->expectException(SecretKeyNotFoundException::class);
        ($this->becoming)(new ActivateCustomerInput(secretKey: self::PROVISIONAL_KEY));
    }

    public function testUnknownKeyRaisesNotFound(): void
    {
        $this->expectException(SecretKeyNotFoundException::class);
        ($this->becoming)(new ActivateCustomerInput(
            secretKey: 'unknown-key-not-in-fixture-2026',
        ));
    }

    public function testTooShortKeyRejectedBySemantic(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new ActivateCustomerInput(secretKey: 'short'));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(SecretKeyFormatException::class, $e->getErrors()->exceptions[0]);

            throw $e;
        }
    }
}
