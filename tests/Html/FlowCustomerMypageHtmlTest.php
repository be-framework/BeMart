<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;

use function assert;
use function dirname;

/**
 * HTML regression walk for the logged-in customer journey — the runtime bugs
 * this only an end-to-end (real HTTP + real MySQL + rendered HTML + session)
 * walk can catch, that the static contract / render tests cannot:
 *
 *  - mypage rendered an "Invalid input" JSON-Schema rejection because order
 *    items carried an empty productCode (recentOrders[0].items[0].productCode).
 *  - the storefront header showed the anonymous ログイン link while a customer
 *    was logged in (is_logged_in() / Block/login toggle).
 *
 * Customer id 3 is a seeded customer with an order, logged in via the test
 * session header (the harness's customer auth), so the order-backed mypage path
 * is actually exercised. This is an independent walk (path C): it does not share
 * the JSON workflow scenario — the storefront flow legitimately differs.
 */
final class FlowCustomerMypageHtmlTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-customer-mypage-html';

    private const CUSTOMER_ID = '3';
    private const CSRF_TOKEN = 'flow-customer-mypage-csrf-token';

    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$dbSession = WorkflowDbSession::startWithCsrfToken(self::CSRF_TOKEN);
        self::$dbSession->session()->setCustomerId(self::CUSTOMER_ID);
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
            '127.0.0.1:8113',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('goMypage')]
    public function testMypageRendersWithoutSchemaRejection(): void
    {
        $mypage = $this->resource->get('page://self/mypage');

        // The empty-productCode bug surfaced as a JSON-Schema "Invalid input"
        // rejection (HTTP 400) on the order-backed mypage; a real render proves
        // the seeded order items now satisfy the schema.
        $this->assertSame(Code::OK, $mypage->code, (string) ($mypage->view ?? ''));
        $this->assertStringNotContainsString('Invalid input', (string) ($mypage->view ?? ''));
        $this->assertStringContainsString('マイページ', (string) ($mypage->view ?? ''));
    }

    #[Alps('goHome')]
    public function testStorefrontHeaderReflectsLoggedInState(): void
    {
        $home = $this->resource->get('page://self/');

        // While logged in the header must offer ログアウト (a POST /logout form),
        // not the anonymous ログイン link — the Block/login is_logged_in() toggle.
        $this->assertSame(Code::OK, $home->code, (string) ($home->view ?? ''));
        $this->assertStringContainsString('action="/logout"', (string) ($home->view ?? ''));
    }
}
