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

final class ChangeResourceTest extends TestCase
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

    public function testOnGetReturnsFormPrePopulated(): void
    {
        $ro = $this->resource->get('page://self/mypage/change');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame('alice@example.com', $ro->body['email']);
        $this->assertSame('山田', $ro->body['name01']);
        $this->assertSame('アリス', $ro->body['name02']);
        $this->assertSame('ヤマダ', $ro->body['kana01']);
        $this->assertSame('0312345678', $ro->body['phoneNumber']);
        $this->assertSame('1500001', $ro->body['postalCode']);
        $this->assertSame(13, $ro->body['pref']);
        $this->assertSame('渋谷区', $ro->body['addr01']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/mypage/change', $ro->body['submitTo']['href']);
    }

    public function testOnGetUnauthenticatedReturns401(): void
    {
        $this->rebindSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthenticatedException::class);

        $this->resource->get('page://self/mypage/change');
    }

    public function testOnPostPatchesAndReturns200(): void
    {
        $ro = $this->resource->post('page://self/mypage/change', [
            'email' => 'alice@example.com',
            'phoneNumber' => '0309998888',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        $this->assertSame('alice@example.com', $ro->body['email']);
    }

    public function testOnPostEmailCollisionReturns409(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException::class);

        $this->resource->post('page://self/mypage/change', [
            'email' => 'bob@example.com',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostWithoutSessionReturns401(): void
    {
        $this->rebindSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthenticatedException::class);

        $this->resource->post('page://self/mypage/change', [
            'email' => 'alice@example.com',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/mypage/change', [
            'email' => 'not-an-email',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }
}
