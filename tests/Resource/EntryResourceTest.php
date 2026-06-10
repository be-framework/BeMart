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

final class EntryResourceTest extends TestCase
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
        $ro = $this->resource->get('page://self/entry');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goCustomerRegistration', $ro->body['transitionId']);
        // 4 required + 11 optional + csrfToken
        $this->assertContains('email', $ro->body['fields']);
        $this->assertContains('password', $ro->body['fields']);
        $this->assertContains('name01', $ro->body['fields']);
        $this->assertContains('name02', $ro->body['fields']);
        $this->assertContains('phoneNumber', $ro->body['fields']);
        $this->assertContains('csrfToken', $ro->body['fields']);
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/entry', $ro->body['submitTo']['href']);
        $this->assertSame(FakeCsrfToken::TOKEN, $ro->body['csrfToken']);
    }

    public function testOnPostRegistersAndReturns201(): void
    {
        $ro = $this->resource->post('page://self/entry', [
            'email' => 'first@example.com',
            'password' => 'first-passphrase-2026',
            'name01' => '一郎',
            'name02' => '田中',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('first@example.com', $ro->body['email']);
        $this->assertSame('一郎', $ro->body['name01']);
        $this->assertSame(100, $ro->body['initialPoint']);
        $this->assertSame(2, $ro->body['customerStatus']);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $ro->body['customerId']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testOnPostCarriesOptionalFields(): void
    {
        $ro = $this->resource->post('page://self/entry', [
            'email' => 'second@example.com',
            'password' => 'second-passphrase-2026',
            'name01' => '二郎',
            'name02' => '田中',
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            'pref' => 13,
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-2-3',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
    }

    public function testOnPostDuplicateEmailReturns409(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException::class);

        $this->resource->post('page://self/entry', [
            'email' => 'alice@example.com',
            'password' => 'try-to-overwrite-2026',
            'name01' => '別人',
            'name02' => 'A',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/entry', [
            'email' => 'not-an-email',
            'password' => 'whatever-2026',
            'name01' => '佐藤',
            'name02' => '五郎',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostEmptyPasswordReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/entry', [
            'email' => 'pw-empty@example.com',
            'password' => '',
            'name01' => '佐藤',
            'name02' => '六郎',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

}
