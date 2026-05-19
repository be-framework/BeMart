<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\FakeMailer;
use MyVendor\BeMart\Module\AppModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class ForgotPasswordResourceTest extends TestCase
{
    private ResourceInterface $resource;
    private FakeMailer $mailer;

    protected function setUp(): void
    {
        $injector = new Injector(
            new AppModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->mailer = $injector->getInstance(FakeMailer::class);
    }

    public function testOnPostKnownEmailMailsResetLink(): void
    {
        $ro = $this->resource->post('page://self/forgot-password', [
            'email' => 'alice@example.com',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertStringContainsString('メール', $ro->body['message']);

        $sent = $this->mailer->passwordResets();
        $this->assertCount(1, $sent);
        $this->assertSame('alice@example.com', $sent[0]['email']);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $sent[0]['resetKey']);
    }

    public function testOnPostUnknownEmailReturnsSameShapeNoMail(): void
    {
        $ro = $this->resource->post('page://self/forgot-password', [
            'email' => 'nobody@example.com',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        // Status and body are IDENTICAL to the registered-email case.
        $this->assertSame(Code::OK, $ro->code);
        $this->assertStringContainsString('メール', $ro->body['message']);

        // But no mail dispatched.
        $this->assertCount(0, $this->mailer->passwordResets());
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $ro = $this->resource->post('page://self/forgot-password', [
            'email' => 'not-an-email',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/forgot-password', [
            'email' => 'alice@example.com',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
