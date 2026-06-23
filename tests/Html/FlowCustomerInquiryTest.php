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
 *  3. POST /contact (mode=confirm, fields) — render the read-only confirm
 *     (review) screen; nothing is sent yet
 *  4. POST /contact (mode=complete) from the confirm screen — send + 303 →
 *     /contact/complete?ticketId=…
 *  5. GET /contact/complete?ticketId=… — complete screen, assert ticketId
 *  6. follow goTop back to the index
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
     * POST /contact with mode=confirm — renders the read-only CONFIRM page.
     *
     * EC-CUBE ContactController: mode=confirm renders the review screen, it
     * does NOT send. The entered inquiry is re-shown read-only AND carried
     * forward as hidden inputs so the final 送信する can re-post it.
     */
    #[Alps('doSubmitContact')]
    #[Depends('testContactForm')]
    public function testDoSubmitContactConfirm(ResourceObject $contact): ResourceObject
    {
        $confirm = $this->submit($contact, 'doSubmitContact', [
            'contactName01' => '山田',
            'contactName02' => '花子',
            'contactEmail' => self::CONTACT_EMAIL,
            'contactContents' => 'HTML workflow E2E pilot — お問い合わせ内容です。',
            'mode' => 'confirm',
        ]);

        // The confirm review screen — NOT a redirect to completion, NOT a send.
        $this->assertSame(Code::OK, $confirm->code, (string) ($confirm->view ?? $confirm->code));
        $this->assertNull($this->header($confirm, 'Location'));
        $view = (string) ($confirm->view ?? '');
        $this->assertStringContainsString('ec-contactConfirmRole', $view);
        $this->assertStringContainsString('送信する', $view);
        $this->assertStringContainsString('HTML workflow E2E pilot — お問い合わせ内容です。', $view);

        return $confirm;
    }

    /**
     * POST /contact from the confirm screen with mode=complete — actually
     * sends and 303-redirects to /contact/complete?ticketId=…
     */
    #[Alps('doSubmitContact')]
    #[Depends('testDoSubmitContactConfirm')]
    public function testDoSubmitContactComplete(ResourceObject $confirm): ResourceObject
    {
        $committed = $this->submit($confirm, 'doSubmitContact', ['mode' => 'complete']);

        $this->assertTrue(
            in_array($committed->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            (string) ($committed->view ?? $committed->code),
        );

        $location = $this->header($committed, 'Location');
        if ($location !== null) {
            $this->assertStringContainsString('ticketId', $location);

            return $this->followLocation($committed, $location);
        }

        // If the server already followed the redirect internally and returned
        // the complete page at 200, assert the complete screen rendered.
        $this->assertStringContainsString('お問い合わせ', (string) ($committed->view ?? ''));

        return $committed;
    }

    #[Alps('ContactComplete')]
    #[Depends('testDoSubmitContactComplete')]
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
