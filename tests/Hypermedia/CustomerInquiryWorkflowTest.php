<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Injector;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

use function assert;
use function is_string;
use function strtolower;

class CustomerInquiryWorkflowTest extends TestCase
{
    protected ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->resource = $this->newResource();
    }

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    /**
     * flow-customer-inquiry: 顧客が問い合わせフォームを開き、送信し、完了画面からトップへ戻る。
     */
    #[Alps('Top')]
    public function testTop(): ResourceObject
    {
        $top = $this->resource->get('page://self/');
        $this->assertSame(Code::OK, $top->code);
        $this->assertSame('goTop', $this->bodyString($top, 'transitionId'));
        $this->assertSame('page://self/contact', $this->link($top, 'goContactForm'));

        return $top;
    }

    #[Alps('ContactForm')]
    #[Depends('testTop')]
    public function testContactForm(ResourceObject $response): ResourceObject
    {
        $contact = $this->follow($response, 'goContactForm');
        $this->assertSame('goContactForm', $this->bodyString($contact, 'transitionId'));
        $this->assertSame('page://self/contact', $this->submitTo($contact, 'POST'));

        return $contact;
    }

    #[Alps('doSubmitContact')]
    #[Depends('testContactForm')]
    public function testDoSubmitContact(ResourceObject $response): ResourceObject
    {
        $submitted = $this->resource->post($this->submitTo($response, 'POST'), [
            'contactName01' => '山田',
            'contactName02' => '太郎',
            'contactEmail' => 'yamada@example.com',
            'contactContents' => 'ハイパーメディア workflow pilot の問い合わせ本文です。',
            'csrfToken' => $this->bodyString($response, 'csrfToken'),
        ]);

        $this->assertSame(Code::OK, $submitted->code);
        $this->assertSame('/contact/complete', $this->header($submitted, 'Location'));
        $this->assertSame('yamada@example.com', $this->bodyString($submitted, 'contactEmail'));

        return $submitted;
    }

    #[Alps('ContactComplete')]
    #[Depends('testDoSubmitContact')]
    public function testContactComplete(ResourceObject $response): ResourceObject
    {
        $this->assertSame('/contact/complete', $this->header($response, 'Location'));
        $complete = $this->resource->get('page://self/contact/complete');
        $this->assertSame(Code::OK, $complete->code);
        $this->assertSame('goContactComplete', $this->bodyString($complete, 'transitionId'));
        $this->assertSame('page://self/', $this->link($complete, 'goTop'));

        return $complete;
    }

    #[Alps('goTop')]
    #[Depends('testContactComplete')]
    public function testGoTop(ResourceObject $response): void
    {
        $top = $this->follow($response, 'goTop');
        $this->assertSame('goTop', $this->bodyString($top, 'transitionId'));
    }

    private function follow(ResourceObject $response, string $rel): ResourceObject
    {
        $next = $this->resource->get($this->link($response, $rel));
        $this->assertSame(Code::OK, $next->code);

        return $next;
    }

    private function link(ResourceObject $response, string $rel): string
    {
        $body = $response->body;
        $this->assertIsArray($body);
        $links = $body['links'] ?? null;
        $this->assertIsArray($links);
        $href = $links[$rel] ?? null;
        $this->assertIsString($href);

        return $href;
    }

    private function submitTo(ResourceObject $response, string $method): string
    {
        $body = $response->body;
        $this->assertIsArray($body);
        $submitTo = $body['submitTo'] ?? null;
        $this->assertIsArray($submitTo);
        $this->assertSame($method, $submitTo['method'] ?? null);
        $href = $submitTo['href'] ?? null;
        $this->assertIsString($href);

        return $href;
    }

    private function bodyString(ResourceObject $response, string $key): string
    {
        $body = $response->body;
        $this->assertIsArray($body);
        $value = $body[$key] ?? null;
        $this->assertIsString($value);

        return $value;
    }

    private function header(ResourceObject $response, string $name): string|null
    {
        foreach ($response->headers as $header => $value) {
            if (! is_string($header) || ! is_string($value)) {
                continue;
            }

            if (strtolower($header) !== strtolower($name)) {
                continue;
            }

            return $value;
        }

        return null;
    }
}
