<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-level coverage for goFavoriteList (the read pair for
 * Pilot 13 doAddFavorite + doRemoveFavorite). FavoriteStorage is
 * singleton-bound by AppModule, so a POST to /mypage/favorite within
 * the same injector is visible to a subsequent GET to
 * /mypage/favorite-list.
 */
final class FavoriteListResourceTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    private ResourceInterface $resource;

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
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetEmptyListReturns200(): void
    {
        $ro = $this->resource->get('page://self/mypage/favorite-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame([], $ro->body['favorites']);
        $this->assertSame(0, $ro->body['favoriteCount']);
    }

    public function testOnGetAfterAddReturnsTheFavorite(): void
    {
        $this->resource->post('page://self/mypage/favorite', [
            'productCode' => 'sample-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $ro = $this->resource->get('page://self/mypage/favorite-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame(1, $ro->body['favoriteCount']);
        $this->assertCount(1, $ro->body['favorites']);
        $this->assertSame('sample-001', $ro->body['favorites'][0]['productCode']);
    }

    public function testOnGetUnauthenticatedReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->get('page://self/mypage/favorite-list');

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
    }
}
