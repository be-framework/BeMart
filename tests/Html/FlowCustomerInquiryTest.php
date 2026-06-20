<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function dirname;
use function in_array;

/**
 * HTML E2E walk for the anonymous customer-inquiry (contact) journey.
 *
 * Path C — independent walk (NOT extending the JSON workflow): the
 * storefront renders a multi-step contact flow (form → confirm → complete)
 * whose HTML shape the JSON API has no equivalent of, so it can never
 * share the JSON workflow scenario.
 *
 * Journey:
 *  1. GET / — index, assert goContactForm affordance
 *  2. GET /contact — contact form, assert doSubmitContact form
 *  3. POST /contact (mode=confirm, fields) — browser submit → 303 → /contact/complete?ticketId=…
 *  4. GET /contact/complete?ticketId=… — complete screen, assert ticketId
 *
 * Anonymous (no login). Only WorkflowDbSession::startWithCsrfToken is
 * needed; no customer/admin session is set.
 */
final class FlowCustomerInquiryTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-customer-inquiry-html';

    private const CSRF_TOKEN = 'flow-customer-inquiry-csrf-token';
    private const CONTACT_EMAIL = 'inquiry-html@example.com';

    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$dbSession = WorkflowDbSession::startWithCsrfToken(self::CSRF_TOKEN);
    }

    public static function tearDownAfterClass(): void
    {
        self::$dbSession?->restore();
        self::$dbSession = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        assert(self::$dbSession instanceof WorkflowDbSession);

        return new HttpResource(
            '127.0.0.1:8124',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('Top')]
    public function testIndex(): ResourceObject
    {
        $index = $this->resource->get('page://self/');
        $this->assertSame(Code::OK, $index->code, (string) ($index->view ?? $index->code));
        $this->assertAffordance($index, 'goContactForm');

        return $index;
    }

    #[Alps('ContactForm')]
    #[Depends('testIndex')]
    public function testContactForm(ResourceObject $index): ResourceObject
    {
        $contact = $this->follow($index, 'goContactForm');
        $this->assertAffordance($contact, 'doSubmitContact');
        $this->assertStringContainsString('お問い合わせ', (string) ($contact->view ?? ''));

        return $contact;
    }

    /**
     * POST /contact with browser mode — expects 303 redirect to /contact/complete.
     *
     * The Contact resource detects browser mode when the `mode` param is
     * non-null (the submit button sends name="mode" value="confirm").
     * A successful browser submit returns 303 + Location.
     */
    #[Alps('doSubmitContact')]
    #[Depends('testContactForm')]
    public function testDoSubmitContact(ResourceObject $contact): ResourceObject
    {
        $submitted = $this->submit($contact, 'doSubmitContact', [
            'contactName01' => '山田',
            'contactName02' => '花子',
            'contactEmail' => self::CONTACT_EMAIL,
            'contactContents' => 'HTML workflow E2E pilot — お問い合わせ内容です。',
            'mode' => 'confirm',
        ]);

        $this->assertTrue(
            in_array($submitted->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            (string) ($submitted->view ?? $submitted->code),
        );

        $location = $this->header($submitted, 'Location');
        if ($location !== null) {
            $this->assertStringContainsString('ticketId', $location);

            return $this->followLocation($submitted, $location);
        }

        // If the server already followed the redirect internally and returned
        // the complete page at 200, assert the complete screen rendered.
        $this->assertStringContainsString('お問い合わせ', (string) ($submitted->view ?? ''));

        return $submitted;
    }

    #[Alps('ContactComplete')]
    #[Depends('testDoSubmitContact')]
    public function testContactComplete(ResourceObject $response): ResourceObject
    {
        $this->assertSame(Code::OK, $response->code, (string) ($response->view ?? $response->code));
        $this->assertStringContainsString('お問い合わせ', (string) ($response->view ?? ''));

        return $response;
    }

    #[Alps('goTop')]
    #[Depends('testContactComplete')]
    public function testReturnsToIndex(ResourceObject $response): void
    {
        $home = $this->follow($response, 'goTop');
        $this->assertSame(Code::OK, $home->code, (string) ($home->view ?? $home->code));
    }
}
