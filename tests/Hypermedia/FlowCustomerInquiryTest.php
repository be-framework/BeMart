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

/**
 * Semantic workflow identified by {@see self::FLOW_ID}.
 *
 * 顧客が問い合わせフォームを開き、送信し、完了画面から index へ戻る。
 */
class FlowCustomerInquiryTest extends AbstractWorkflowTest
{
    public const FLOW_ID = 'flow-customer-inquiry';

    private const CONTACT_EMAIL = 'yamada@example.com';

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return $resource;
    }

    #[Alps('Top')]
    public function testIndex(): ResourceObject
    {
        $index = $this->resource->get('page://self/');
        $this->assertSame(Code::OK, $index->code);

        return $index;
    }

    #[Alps('ContactForm')]
    #[Depends('testIndex')]
    public function testContactForm(ResourceObject $response): ResourceObject
    {
        return $this->follow($response, 'goContactForm');
    }

    #[Alps('doSubmitContact')]
    #[Depends('testContactForm')]
    public function testDoSubmitContact(ResourceObject $response): ResourceObject
    {
        $submitted = $this->follow($response, 'doSubmitContact', [
            'contactName01' => '山田',
            'contactName02' => '太郎',
            'contactEmail' => self::CONTACT_EMAIL,
            'contactContents' => 'ハイパーメディア workflow pilot の問い合わせ本文です。',
            'csrfToken' => $this->bodyValue($response, 'csrfToken'),
        ]);

        $this->assertSame(Code::OK, $submitted->code);
        $this->assertSame('/contact/complete', $this->header($submitted, 'Location'));
        $this->assertSame(self::CONTACT_EMAIL, $this->bodyValue($submitted, 'contactEmail'));

        return $submitted;
    }

    #[Alps('ContactComplete')]
    #[Depends('testDoSubmitContact')]
    public function testContactComplete(ResourceObject $response): ResourceObject
    {
        $this->assertSame('/contact/complete', $this->header($response, 'Location'));
        $complete = $this->resource->get('page://self/contact/complete');
        $this->assertSame(Code::OK, $complete->code);

        return $complete;
    }

    #[Alps('goTop')]
    #[Depends('testContactComplete')]
    public function testReturnsToIndex(ResourceObject $response): void
    {
        $this->follow($response, 'goTop');
    }
}
