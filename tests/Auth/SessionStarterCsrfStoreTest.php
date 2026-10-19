<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use BEAR\Csrf\CsrfSessionKey;
use MyVendor\BeMart\Auth\NullSessionStarter;
use MyVendor\BeMart\Auth\SessionStarterCsrfStore;
use MyVendor\BeMart\Auth\SessionStarterInterface;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SessionStarterCsrfStoreTest extends TestCase
{
    private const KEY = CsrfSessionKey::DEFAULT_NAME;

    private SessionStarterCsrfStore $store;

    protected function setUp(): void
    {
        unset($_SESSION[self::KEY]);
        $this->store = new SessionStarterCsrfStore(new NullSessionStarter());
    }

    protected function tearDown(): void
    {
        unset($_SESSION[self::KEY], $_SESSION['other_slot']);
    }

    public function testGetReturnsNullWhenNothingIsStored(): void
    {
        $this->assertNull($this->store->get());
    }

    /** @return iterable<string, array{mixed}> */
    public static function unusableValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'integer' => [123];
        yield 'null' => [null];
    }

    #[DataProvider('unusableValues')]
    public function testGetTreatsAnUnusableValueAsAbsent(mixed $stored): void
    {
        $_SESSION[self::KEY] = $stored;

        $this->assertNull($this->store->get());
    }

    public function testSetThenGetRoundTrips(): void
    {
        $this->store->set('issued-token');

        $this->assertSame('issued-token', $this->store->get());
        $this->assertSame('issued-token', $_SESSION[self::KEY]);
    }

    public function testRemoveEmptiesTheSlot(): void
    {
        $this->store->set('issued-token');

        $this->store->remove();

        $this->assertNull($this->store->get());
        $this->assertArrayNotHasKey(self::KEY, $_SESSION);
    }

    public function testEveryAccessOpensTheSessionThroughThePort(): void
    {
        $starter = new class implements SessionStarterInterface {
            public int $started = 0;

            #[Override]
            public function ensureStarted(): void
            {
                $this->started++;
            }
        };
        $store = new SessionStarterCsrfStore($starter);

        $store->get();
        $store->set('issued-token');
        $store->remove();

        $this->assertSame(3, $starter->started);
    }

    public function testWritesToTheConfiguredSessionKey(): void
    {
        $store = new SessionStarterCsrfStore(new NullSessionStarter(), new CsrfSessionKey('other_slot'));

        $store->set('issued-token');

        $this->assertSame('issued-token', $_SESSION['other_slot']);
        $this->assertArrayNotHasKey(self::KEY, $_SESSION);
    }
}
