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

final class ContactResourceTest extends TestCase
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

    public function testOnGetReturnsFormMetadata(): void
    {
        $ro = $this->resource->get('page://self/contact');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('goContactForm', $ro->body['transitionId']);
        $this->assertSame(
            ['contactName01', 'contactName02', 'contactEmail', 'contactContents', 'csrfToken'],
            $ro->body['fields'],
        );
        $this->assertSame('POST', $ro->body['submitTo']['method']);
        $this->assertSame('page://self/contact', $ro->body['submitTo']['href']);
        // `csrfToken` carries the trusted reference the HTML form must
        // echo back so the doSubmitContact POST passes CSRF validation.
        $this->assertSame(FakeCsrfToken::TOKEN, $ro->body['csrfToken']);
    }

    public function testOnPostSubmitsContactAndRedirectsToComplete(): void
    {
        $ro = $this->resource->post('page://self/contact', [
            'contactName01' => '山田',
            'contactName02' => '太郎',
            'contactEmail' => 'yamada@example.com',
            'contactContents' => 'お問い合わせの本文です。',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        // Post/Redirect/Get: a successful submit returns Code::OK + a
        // Location header to the completion page (the HTTP layer turns
        // that into a browser redirect).
        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('/contact/complete', $ro->headers['Location']);
        $this->assertSame('yamada@example.com', $ro->body['contactEmail']);

        $sent = $this->mailer->contactInquiries();
        $this->assertCount(1, $sent);
        $this->assertSame('お問い合わせの本文です。', $sent[0]->contactContents);
    }

    public function testOnPostInvalidEmailReturns400(): void
    {
        $ro = $this->resource->post('page://self/contact', [
            'contactName01' => '山田',
            'contactName02' => '太郎',
            'contactEmail' => 'not-an-email',
            'contactContents' => 'body text',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostEmptyContentsReturns400(): void
    {
        $ro = $this->resource->post('page://self/contact', [
            'contactName01' => '山田',
            'contactName02' => '太郎',
            'contactEmail' => 'yamada@example.com',
            'contactContents' => '',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
    }

    public function testOnPostMissingCsrfReturns403(): void
    {
        $ro = $this->resource->post('page://self/contact', [
            'contactName01' => '山田',
            'contactName02' => '太郎',
            'contactEmail' => 'yamada@example.com',
            'contactContents' => 'body text',
        ]);

        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }
}
