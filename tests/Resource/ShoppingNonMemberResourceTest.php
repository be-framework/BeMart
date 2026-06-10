<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class ShoppingNonMemberResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnGetReturnsFormMetadata(): void
    {
        $ro = $this->resource->get('page://self/shopping/non-member');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goShoppingNonMember', $ro->body['transitionId']);
        $this->assertSame(
            [
                'name01',
                'name02',
                'kana01',
                'kana02',
                'email',
                'phoneNumber',
                'postalCode',
                'pref',
                'addr01',
                'addr02',
                'csrfToken',
            ],
            $ro->body['fields'],
        );
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/shopping/non-member', $ro->body['submitTo']['href']);
        $this->assertSame(FakeCsrfToken::TOKEN, $ro->body['csrfToken']);
    }

    public function testOnPostValidatesAndReturnsPreOrderId(): void
    {
        $ro = $this->resource->post('page://self/shopping/non-member', [
            'name01' => '田中',
            'name02' => '太郎',
            'kana01' => 'タナカ',
            'kana02' => 'タロウ',
            'email' => 'guest@example.com',
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-2-3',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{40}\z/', $ro->body['preOrderId']);
        $this->assertSame('田中', $ro->body['name01']);
        $this->assertSame('太郎', $ro->body['name02']);
        $this->assertSame('guest@example.com', $ro->body['email']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/shopping/non-member', [
            'name01' => '田中',
            'name02' => '太郎',
            'kana01' => 'タナカ',
            'kana02' => 'タロウ',
            'email' => 'not-an-email',
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-2-3',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

}
