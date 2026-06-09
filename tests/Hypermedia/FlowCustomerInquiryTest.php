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
use function rawurlencode;

/**
 * Semantic workflow identified by {@see self::FLOW_ID}.
 *
 * 顧客が問い合わせフォームを開き、送信し、完了画面から index へ戻る。
 * 完了状態では public receipt として ticketId が発行される。
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
        $submitted = $this->resource->post('page://self/contact', [
            'contactName01' => '山田',
            'contactName02' => '太郎',
            'contactEmail' => self::CONTACT_EMAIL,
            'contactContents' => 'ハイパーメディア workflow pilot の問い合わせ本文です。',
            'csrfToken' => $this->bodyValue($response, 'csrfToken'),
        ]);

        $this->assertSame(Code::OK, $submitted->code);
        $ticketId = $this->bodyValue($submitted, 'ticketId');
        $this->assertIsString($ticketId);
        $this->assertNotSame('', $ticketId);
        $this->assertSame('/contact/complete?ticketId=' . rawurlencode($ticketId), $this->header($submitted, 'Location'));
        $this->assertSame(self::CONTACT_EMAIL, $this->bodyValue($submitted, 'contactEmail'));

        return $submitted;
    }

    #[Alps('ContactComplete')]
    #[Depends('testDoSubmitContact')]
    public function testContactComplete(ResourceObject $response): ResourceObject
    {
        $location = $this->header($response, 'Location');
        $this->assertIsString($location);

        return $this->followLocation($response, $location);
    }

    #[Alps('ticketId')]
    #[Depends('testContactComplete')]
    public function testIssuesTicket(ResourceObject $response): ResourceObject
    {
        $ticketId = $this->bodyValue($response, 'ticketId');
        $this->assertIsString($ticketId);
        $this->assertNotSame('', $ticketId);

        return $response;
    }

    #[Alps('goTop')]
    #[Depends('testIssuesTicket')]
    public function testReturnsToIndex(ResourceObject $response): void
    {
        $this->follow($response, 'goTop');
    }
}
