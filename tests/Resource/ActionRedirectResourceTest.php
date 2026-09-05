<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * `returnTo` is attacker-supplied and lands in the `Location` header, so
 * every resource that honours it — the two ActionRedirect resources and
 * the two UnsupportedRoute placeholders — must accept only a same-origin
 * absolute path.
 */
final class ActionRedirectResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $base->override(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });

        $this->resource = (new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test'))
            ->getInstance(ResourceInterface::class);
    }

    /** @return array<string, array{string, string}> GET uri → expected fallback */
    public static function guards(): array
    {
        return [
            'storefront' => ['page://self/action-redirect', '/'],
            'admin' => ['page://self/admin/action-redirect', '/admin'],
        ];
    }

    /** @dataProvider guards */
    public function testBackslashAuthorityIsRejected(string $uri, string $fallback): void
    {
        $ro = $this->resource->get($uri, ['returnTo' => '/\evil.example']);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($fallback, $ro->headers['Location']);
    }

    /** @dataProvider guards */
    public function testProtocolRelativeUrlIsRejected(string $uri, string $fallback): void
    {
        $ro = $this->resource->get($uri, ['returnTo' => '//evil.example']);

        $this->assertSame($fallback, $ro->headers['Location']);
    }

    /** @dataProvider guards */
    public function testCarriageReturnIsRejected(string $uri, string $fallback): void
    {
        $ro = $this->resource->get($uri, ['returnTo' => "/admin\r\nSet-Cookie: a=b"]);

        $this->assertSame($fallback, $ro->headers['Location']);
    }

    /** @dataProvider guards */
    public function testSameOriginPathIsHonoured(string $uri, string $fallback): void
    {
        $ro = $this->resource->get($uri, ['returnTo' => '/admin/product-list?page=2']);

        $this->assertNotSame($fallback, $ro->headers['Location']);
        $this->assertSame('/admin/product-list?page=2', $ro->headers['Location']);
    }

    /**
     * UnsupportedRoute takes `returnTo` on POST only, so the same guard
     * has to hold for the POST sinks.
     *
     * @return array<string, array{string, string}> POST uri → expected fallback
     */
    public static function postGuards(): array
    {
        return [
            'storefront action-redirect' => ['page://self/action-redirect', '/'],
            'admin action-redirect' => ['page://self/admin/action-redirect', '/admin'],
            'storefront unsupported-route' => ['page://self/unsupported-route', '/'],
            'admin unsupported-route' => ['page://self/admin/unsupported-route', '/admin'],
        ];
    }

    /** @dataProvider postGuards */
    public function testBackslashAuthorityIsRejectedOnPost(string $uri, string $fallback): void
    {
        $ro = $this->resource->post($uri, ['returnTo' => '/\evil.example']);

        $this->assertSame($fallback, $ro->headers['Location']);
    }

    /** @dataProvider postGuards */
    public function testSameOriginPathIsHonouredOnPost(string $uri, string $fallback): void
    {
        $ro = $this->resource->post($uri, ['returnTo' => '/mypage']);

        $this->assertNotSame($fallback, $ro->headers['Location']);
        $this->assertSame('/mypage', $ro->headers['Location']);
    }
}
