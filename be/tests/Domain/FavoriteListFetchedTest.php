<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\FavoriteListFetched;
use MyVendor\BeMart\Be\Input\GetFavoriteListInput;
use MyVendor\BeMart\Be\Reason\Entity\FavoriteEntity;
use MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\AppModule;
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

    private BecomingInterface $becoming;
    private FavoriteStorageInterface $favorites;

    protected function setUp(): void
    {
        $this->rebindSession(self::ALICE_ID);
    }

    private function rebindSession(string|null $customerId): void
    {
        $session = new FakeSession($customerId);
        $base = new AppModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(SessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->becoming = $injector->getInstance(BecomingInterface::class);
        $this->favorites = $injector->getInstance(FavoriteStorageInterface::class);
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
        $this->favorites->add(new FavoriteEntity(
            customerId: self::ALICE_ID,
            productCode: 'sample-001',
            productName: 'サンプル商品 A',
            unitPrice: 1200,
        ));
        $this->favorites->add(new FavoriteEntity(
            customerId: self::ALICE_ID,
            productCode: 'sample-002',
            productName: 'サンプル商品 B',
            unitPrice: 9800,
        ));
        // Another customer's favorite must not leak.
        $this->favorites->add(new FavoriteEntity(
            customerId: 'fedcba9876543210fedcba9876543210',
            productCode: 'sample-003',
            productName: 'サンプル商品 C',
            unitPrice: 500,
        ));

        $final = ($this->becoming)(new GetFavoriteListInput());

        $this->assertInstanceOf(FavoriteListFetched::class, $final);
        $this->assertSame(self::ALICE_ID, $final->customerId);
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
