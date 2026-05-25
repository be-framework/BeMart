<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\FavoriteListFetched;
use MyVendor\BeMart\Be\Input\GetFavoriteListInput;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Pilot (goFavoriteList) — Direct safe-read of the logged-in
 * customer's full favorites list. Pair to Pilot 13 doAddFavorite +
 * doRemoveFavorite.
 *
 * FavoriteStorage is singleton-bound by AppModule, so seeding via the
 * injector's instance lets the Final see those rows within the same
 * test case.
 */
final class FavoriteListFetchedTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const FAVORITE_LIST_CUSTOMER_ID = 'favorite-list-customer';

    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $this->rebindSession(self::ALICE_ID);
    }

    private function rebindSession(string|null $customerId): void
    {
        $session = new FakeSession($customerId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testEmptyListHappyPath(): void
    {
        $final = ($this->becoming)(new GetFavoriteListInput());

        $this->assertInstanceOf(FavoriteListFetched::class, $final);
        $this->assertSame(self::ALICE_ID, $final->customerId);
        $this->assertSame([], $final->favorites);
        $this->assertSame(0, $final->favoriteCount);
    }

    public function testSeededFavoritesAreReturned(): void
    {
        $this->rebindSession(self::FAVORITE_LIST_CUSTOMER_ID);

        $final = ($this->becoming)(new GetFavoriteListInput());

        $this->assertInstanceOf(FavoriteListFetched::class, $final);
        $this->assertSame(self::FAVORITE_LIST_CUSTOMER_ID, $final->customerId);
        $this->assertSame(2, $final->favoriteCount);

        $byCode = [];
        foreach ($final->favorites as $row) {
            $byCode[$row['productCode']] = $row;
        }

        $this->assertArrayHasKey('sample-001', $byCode);
        $this->assertSame('サンプル商品 A', $byCode['sample-001']['productName']);
        $this->assertSame(1200, $byCode['sample-001']['unitPrice']);

        $this->assertArrayHasKey('sample-002', $byCode);
        $this->assertSame('サンプル商品 B', $byCode['sample-002']['productName']);
        $this->assertSame(9800, $byCode['sample-002']['unitPrice']);

        // Bob's favorite stays scoped to bob.
        $this->assertArrayNotHasKey('sample-003', $byCode);
    }

    public function testNoSessionRaisesUnauthenticated(): void
    {
        $this->rebindSession(null);

        $this->expectException(UnauthenticatedException::class);
        ($this->becoming)(new GetFavoriteListInput());
    }
}
