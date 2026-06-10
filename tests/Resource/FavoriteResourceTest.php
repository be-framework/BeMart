<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class FavoriteResourceTest extends TestCase
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
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnPostAddsFavoriteAndReturns201(): void
    {
        $ro = $this->resource->post('page://self/mypage/favorite', [
            'productCode' => 'sample-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('sample-001', $ro->body['productCode']);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertFalse($ro->body['alreadyExisted']);
    }

    public function testOnPostExistingFavoriteReturns200WithAlreadyExisted(): void
    {
        // Fake context is static-fixture based; duplicate-after-mutation is
        // covered by the SQL suite. This customer already has the favorite.
        $this->rebindSession('favorite-html-customer');

        $ro = $this->resource->post('page://self/mypage/favorite', [
            'productCode' => 'sample-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertTrue($ro->body['alreadyExisted']);
    }

    public function testOnPostUnknownProductReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\ProductNotFoundException::class);

        $this->resource->post('page://self/mypage/favorite', [
            'productCode' => 'missing-zzz',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostUnauthenticatedReturns401(): void
    {
        $this->rebindSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthenticatedException::class);

        $this->resource->post('page://self/mypage/favorite', [
            'productCode' => 'sample-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnDeleteExistingFavoriteReturns200(): void
    {
        // Fake context is static-fixture based; add-then-delete is covered
        // by the SQL suite. This customer already has the favorite.
        $this->rebindSession('favorite-html-customer');

        $ro = $this->resource->delete('page://self/mypage/favorite', [
            'productCode' => 'sample-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('sample-001', $ro->body['productCode']);
        $this->assertSame('favorite-html-customer', $ro->body['customerId']);
        $this->assertFalse($ro->body['alreadyAbsent']);
    }

    public function testOnDeleteAbsentIsIdempotent(): void
    {
        // Delete without prior add — still 200, alreadyAbsent=true.
        $ro = $this->resource->delete('page://self/mypage/favorite', [
            'productCode' => 'sample-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('sample-001', $ro->body['productCode']);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertTrue($ro->body['alreadyAbsent']);
    }

    public function testOnDeleteUnauthenticatedReturns401(): void
    {
        $this->rebindSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthenticatedException::class);

        $this->resource->delete('page://self/mypage/favorite', [
            'productCode' => 'sample-001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

}
