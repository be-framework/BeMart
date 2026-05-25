<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource\Sql;

use BEAR\Resource\Code;
use MyVendor\BeMart\Be\Reason\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use Ray\Di\AbstractModule;

/**
 * SQL-backed hypermedia coverage for goFavoriteList (Phase 2a Step 5).
 *
 * Mirrors {@see \MyVendor\BeMart\Tests\Resource\FavoriteListResourceTest}
 * but drives {@see \MyVendor\BeMart\Be\Reason\Query\SqlFavoriteStorage}
 * via `ResourceInterface::get('page://self/mypage/favorite-list')` after
 * seeding rows through SQL fixture helpers.
 *
 * customerId comes from {@see SessionInterface} (Pilot 5 F-2 lesson — the
 * actor is read from the session, never from the request body). The
 * test rebinds SessionInterface to a {@see FakeSession} carrying the
 * inserted customer's id (which must be numeric — dtb_customer.id is
 * `int unsigned` and the SQL impls reject non-numeric ids).
 */
final class FavoriteListResourceSqlTest extends AbstractResourceSqlTestCase
{
    /** @var non-empty-string|null */
    private string|null $currentCustomerId = null;

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

    /**
     * @param non-empty-string|null $customerId
     */
    private function loginAs(string|null $customerId): void
    {
        $this->currentCustomerId = $customerId;
        $this->resource = $this->buildResource();
    }

    public function testOnGetEmptyListReturns200(): void
    {
        $customerId = $this->insertCustomer(['email' => 'fav-empty@example.com']);
        $this->loginAs((string) $customerId);

        $ro = $this->resource->get('page://self/mypage/favorite-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame((string) $customerId, $ro->body['customerId']);
        $this->assertSame([], $ro->body['favorites']);
        $this->assertSame(0, $ro->body['favoriteCount']);
    }

    public function testOnGetReturnsTwoFavoritesForCustomer(): void
    {
        $customerId = $this->insertCustomer(['email' => 'fav-two@example.com']);
        $productA = $this->insertProduct([
            'name' => 'Cherry',
            'product_code' => 'SQL-FL-A',
            'price02' => 450,
        ]);
        $productB = $this->insertProduct([
            'name' => 'Durian',
            'product_code' => 'SQL-FL-B',
            'price02' => 1200,
        ]);
        $this->insertFavorite($customerId, $productA);
        $this->insertFavorite($customerId, $productB);

        // Seed an unrelated customer's favorite to prove the customerId
        // filter is doing real work.
        $otherCustomerId = $this->insertCustomer(['email' => 'fav-other@example.com']);
        $productC = $this->insertProduct(['product_code' => 'SQL-FL-C']);
        $this->insertFavorite($otherCustomerId, $productC);

        $this->loginAs((string) $customerId);

        $ro = $this->resource->get('page://self/mypage/favorite-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame((string) $customerId, $ro->body['customerId']);
        $this->assertSame(2, $ro->body['favoriteCount']);
        $this->assertCount(2, $ro->body['favorites']);

        $codes = array_map(
            static fn (array $r): string => $r['productCode'],
            $ro->body['favorites'],
        );
        sort($codes);
        $this->assertSame(['SQL-FL-A', 'SQL-FL-B'], $codes);

        // unitPrice snapshot comes from dtb_product_class.price02 — the
        // SQL impl carries it on the projection (Grade B mapping uses
        // a 3-way JOIN; coverage here is end-to-end).
        $byCode = [];
        foreach ($ro->body['favorites'] as $fav) {
            $byCode[$fav['productCode']] = $fav['unitPrice'];
        }
        $this->assertSame(450, $byCode['SQL-FL-A']);
        $this->assertSame(1200, $byCode['SQL-FL-B']);
    }
}
