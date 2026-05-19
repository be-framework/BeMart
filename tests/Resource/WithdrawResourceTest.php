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

final class WithdrawResourceTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const ALICE_DUMMY_EMAIL = 'withdrawn-0123456789abcdef0123456789abcdef@example.invalid';

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

    public function testOnPostHappyPathReturns200(): void
    {
        $ro = $this->resource->post('page://self/mypage/withdraw', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame(self::ALICE_DUMMY_EMAIL, $ro->body['dummyEmail']);
        $this->assertTrue($ro->body['cleared']);
        $this->assertStringContainsString('退会', $ro->body['message']);
    }

    public function testOnPostWithoutSessionReturns401(): void
    {
        $this->rebindSession(null);

        $ro = $this->resource->post('page://self/mypage/withdraw', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::UNAUTHORIZED, $ro->code);
        $this->assertStringContainsString('ログイン', $ro->body['message']);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/mypage/withdraw', []);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
