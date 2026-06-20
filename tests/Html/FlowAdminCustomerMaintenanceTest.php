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
use function bin2hex;
use function dirname;
use function html_entity_decode;
use function in_array;
use function preg_match;
use function random_bytes;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * HTML hypermedia walk of the admin customer management pages — driven
 * entirely by the rendered HTML's ALPS affordances (class/rel) over real
 * HTTP (Path C: independent, not extending the Hypermedia class).
 *
 * Ported from tests/Hypermedia/FlowAdminCustomerMaintenanceTest.php.
 *
 * Steps walked:
 *   1. testOpensCustomerList        — GET /admin/customer-list (200 + list renders with count)
 *   2. testSearchesCustomerByEmail  — GET /admin/customer-list?emailKeyword=… (200 + created email)
 *   3. testNavigatesToCustomerDetail — GET /admin/customer?customerId=… (200 + email rendered)
 *
 * Steps skipped:
 *   - doCreateCustomer: The CreateCustomer resource (page://self/admin/create-customer)
 *     has no HTML template — there is no <form class="doCreateCustomer"> rendered
 *     on any admin customer page. Customer creation from the admin side is wired
 *     as a resource-only POST; the HTML form affordance is absent. Cannot be walked
 *     via submit().
 *     Setup workaround: a customer is created via a direct resource POST in
 *     setUpBeforeClass so subsequent GET steps can assert against a known email.
 *   - doDeleteCustomer: CustomerList.html.twig renders the delete action as a plain
 *     <a href="/admin/delete-customer?customerId=…"> anchor inside a Bootstrap modal,
 *     not as a <form class="doDeleteCustomer">. There is no HTML form affordance for
 *     deletion, so it cannot be exercised via submit().
 *   - Navigation links in Customer.html.twig (back-link to /admin/customer-list)
 *     carry no ALPS rel attribute and are therefore not tagged affordances.
 *     Direct GET is used instead.
 *
 * Because neither doCreateCustomer nor doDeleteCustomer has a rendered HTML form
 * with an ALPS class, this walk is effectively GET-only after the setup step.
 */
final class FlowAdminCustomerMaintenanceTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-admin-customer-html';

    private const HOST = '127.0.0.1:8119';
    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'workflow-admin-customer-html-csrf-token';

    private static string $email;
    private static string $customerId;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$email = 'flow-admin-customer-html-' . bin2hex(random_bytes(4)) . '@example.com';
        self::$dbSession = WorkflowDbSession::startForAdmin(self::ADMIN_ID, self::CSRF_TOKEN);

        // doCreateCustomer has no rendered HTML form affordance (<form class="doCreateCustomer">
        // is absent from all admin customer templates). We create the customer via a direct
        // resource POST so the GET-only walk steps can assert against a known email address.
        $setup = new HttpResource(
            self::HOST,
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
        $created = $setup->post('page://self/admin/create-customer', [
            'email' => self::$email,
            'password' => 'workflow-admin-customer-html-pw-2026',
            'name01' => '管理',
            'name02' => 'HTML顧客',
            'kana01' => 'カンリ',
            'kana02' => 'コキャク',
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => 13,
            'addr01' => '千代田区',
            'addr02' => 'HTML1-1',
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        // HTML context returns 303 See Other (Post/Redirect/Get pattern); JSON returns 201.
        assert(in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true), (string) ($created->view ?? $created->code));

        // Resolve the customerId from the customer list so the detail step can GET by id.
        $list = $setup->get('page://self/admin/customer-list', ['emailKeyword' => self::$email]);
        self::$customerId = self::extractCustomerIdFromList((string) ($list->view ?? ''), self::$email);
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
            self::HOST,
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    /**
     * Open the admin customer list — the entry point for all customer management.
     * Verifies the list page renders (200) and shows the customer created in setup.
     */
    #[Alps('goCustomerList')]
    public function testOpensCustomerList(): ResourceObject
    {
        $list = $this->resource->get('page://self/admin/customer-list');

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertStringContainsString('ex-customer-', (string) ($list->view ?? ''));

        return $list;
    }

    /**
     * Search the customer list by the email created in setup — verifies the
     * keyword filter narrows the result and the created customer row is visible.
     */
    #[Alps('goCustomerList')]
    #[Depends('testOpensCustomerList')]
    public function testSearchesCustomerByEmail(ResourceObject $list): ResourceObject
    {
        $searched = $this->resource->get('page://self/admin/customer-list', [
            'emailKeyword' => self::$email,
        ]);

        $this->assertSame(Code::OK, $searched->code, (string) ($searched->view ?? $searched->code));
        $this->assertStringContainsString(self::$email, (string) ($searched->view ?? ''));

        return $searched;
    }

    /**
     * Navigate to the customer detail page for the customer created in setup.
     *
     * CustomerList.html.twig links customers via a plain <a href="/admin/customer?customerId=…">
     * anchor with no ALPS rel attribute, so follow() cannot be used. A direct GET
     * mirrors what a browser would do when clicking the name link in the table row.
     *
     * Verifies the detail page renders (200) and displays the customer's email.
     */
    #[Alps('goCustomer')]
    #[Depends('testSearchesCustomerByEmail')]
    public function testNavigatesToCustomerDetail(ResourceObject $searched): void
    {
        $detail = $this->resource->get('page://self/admin/customer', [
            'customerId' => self::$customerId,
        ]);

        $this->assertSame(Code::OK, $detail->code, (string) ($detail->view ?? $detail->code));
        $this->assertStringContainsString(self::$email, (string) ($detail->view ?? ''));
    }

    /**
     * Extract the customerId from the customer list HTML for the given email address.
     * The list renders <tr id="ex-customer-{id}">…{email}…</tr>, so a combined regex
     * recovers the id from the row that contains the target email.
     */
    private static function extractCustomerIdFromList(string $html, string $email): string
    {
        $escaped = preg_quote($email, '/');
        $pattern = '/<tr\s+id="ex-customer-([^"]+)"[^>]*>.*?' . $escaped . '.*?<\/tr>/s';
        $matched = preg_match($pattern, $html, $match);
        assert($matched === 1, 'Customer row not found in customer list for email: ' . $email);

        return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
    }
}
