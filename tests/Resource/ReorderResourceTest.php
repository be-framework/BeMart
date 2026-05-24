<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\SessionInterface;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

/**
 * Resource-layer coverage for Pilot 12 (doReorder).
 *
 * Mirrors CheckoutResourceTest's rebindSession + CSRF pattern.
 */
final class ReorderResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        // SEED_ORDER_NO belongs to customer-001 — match the session.
        $this->rebindSession('customer-001');
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
                $this->bind(SessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnPostHappyPathReturns201(): void
    {
        $ro = $this->resource->post('page://self/mypage/reorder', [
            'orderNo' => 'past0000000000000000000000000001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('customer-001', $ro->body['customerId']);
        $this->assertSame('past0000000000000000000000000001', $ro->body['orderNo']);
        $this->assertSame(2, $ro->body['addedCount']);
        $this->assertSame(0, $ro->body['skippedCount']);
        $this->assertSame([], $ro->body['skippedProductCodes']);
        $this->assertSame(['session-prefix-1_1'], $ro->body['cartKeys']);
        $this->assertArrayHasKey('Location', $ro->headers);
        $this->assertSame('/cart', $ro->headers['Location']);
    }

    public function testOnPostWrongOwnerReturns403(): void
    {
        $this->rebindSession('customer-999');

        $ro = $this->resource->post('page://self/mypage/reorder', [
            'orderNo' => 'past0000000000000000000000000001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertSame('past0000000000000000000000000001', $ro->body['orderNo']);
    }

    public function testOnPostUnknownOrderReturns404(): void
    {
        $ro = $this->resource->post('page://self/mypage/reorder', [
            'orderNo' => 'never00000000000000000000000000z',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
        $this->assertSame('never00000000000000000000000000z', $ro->body['orderNo']);
    }

    public function testOnPostAnonymousReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->post('page://self/mypage/reorder', [
            'orderNo' => 'past0000000000000000000000000001',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/mypage/reorder', [
            'orderNo' => 'past0000000000000000000000000001',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF', $ro->body['message']);
    }
}
