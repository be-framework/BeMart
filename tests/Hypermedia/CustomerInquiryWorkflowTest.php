<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;
use PHPUnit\Framework\Attributes\Depends;

use function assert;

class CustomerInquiryWorkflowTest extends AbstractWorkflowTest
{
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
        $this->assertSame('goTop', $this->transitionId($top));
        $this->assertSame('page://self/contact', $this->link($top, 'goContactForm'));

        return $top;
    }

    #[Alps('ContactForm')]
    #[Depends('testTop')]
    public function testContactForm(ResourceObject $response): ResourceObject
    {
        $contact = $this->follow($response, 'goContactForm');
        $this->assertSame('goContactForm', $this->transitionId($contact));
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
        $this->assertSame('goContactComplete', $this->transitionId($complete));
        $this->assertSame('page://self/', $this->link($complete, 'goTop'));

        return $complete;
    }

    #[Alps('goTop')]
    #[Depends('testContactComplete')]
    public function testGoTop(ResourceObject $response): void
    {
        $top = $this->follow($response, 'goTop');
        $this->assertSame('goTop', $this->transitionId($top));
    }
}
