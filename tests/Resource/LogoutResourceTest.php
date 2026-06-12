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

final class LogoutResourceTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindSession(self::ALICE_ID);
    }

    /** Build a fresh resource client with the given session customerId (null = anonymous). */
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

    public function testOnPostLogsOutLoggedInUser(): void
    {
        $ro = $this->resource->post('page://self/logout', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertTrue($ro->body['wasLoggedIn']);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertStringContainsString('ログアウト', $ro->body['message']);
    }

    public function testOnPostIsIdempotentForAnonymous(): void
    {
        // ALPS type=idempotent: logging out an anonymous client is a no-op
        // success, NOT a 401. The body simply reports wasLoggedIn=false.
        $this->rebindSession(null);

        $ro = $this->resource->post('page://self/logout', [
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertFalse($ro->body['wasLoggedIn']);
        $this->assertNull($ro->body['customerId']);
    }

}
