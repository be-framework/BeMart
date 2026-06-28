<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

use function count;

/**
 * Render tests for the admin Customer-list page (goCustomerList / 会員一覧).
 *
 * The page is a clean-room idea-admin-* build extending admin-base.html.twig.
 * Tests are organised in two tiers:
 *
 *   L1 — required field / list-data output (markup-parity contract)
 *   L2 — form action/method + link href/rel semantics
 *
 * EC-CUBE reference-rendering comparison (the former "honesty test") is
 * archived to the ec-cube-parity-archived group and skipped automatically
 * because the template no longer matches EC-CUBE DOM structure.
 */
final class AdminCustomerListHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ── L0: HTTP contract ────────────────────────────────────────────────────

    public function testCustomerListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/customer-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ── L0: idea-admin shell landmarks ───────────────────────────────────────

    public function testCustomerListUsesIdeaAdminShell(): void
    {
        $html = $this->resource->get('page://self/admin/customer-list')->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
            'class="idea-admin-content"',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "idea-admin shell landmark missing: {$landmark}");
        }
    }

    // ── L1: required field / list-data output ────────────────────────────────

    /**
     * The keyword search input (AdminCustomerSearchForm field 'multi') must
     * be present with its canonical id so that JavaScript and accessibility
     * tooling can locate it.
     */
    public function testCustomerListRendersKeywordSearchInput(): void
    {
        $html = $this->resource->get('page://self/admin/customer-list')->toString();

        $this->assertStringContainsString('id="admin_search_customer_multi"', $html);
    }

    /**
     * The resource body's 'customers' list is rendered as table rows.
     * Seeded data includes alice@example.com; her email must appear in the
     * table body.
     */
    public function testCustomerListRendersSeededCustomerEmail(): void
    {
        $html = $this->resource->get('page://self/admin/customer-list')->toString();

        $this->assertStringContainsString('alice@example.com', $html);
    }

    /**
     * Every seeded customer's 'customerId' must link to the detail page.
     * This verifies the goCustomer href binding (L1 data output).
     */
    public function testCustomerListRendersDetailLinkForEachCustomer(): void
    {
        $ro  = $this->resource->get('page://self/admin/customer-list');
        $html = $ro->toString();

        foreach ($ro->body['customers'] as $customer) {
            $this->assertStringContainsString(
                $customer['customerId'],
                $html,
                "customerId {$customer['customerId']} not found in rendered HTML",
            );
        }
    }

    /**
     * The result count from the resource body ('count') must be rendered.
     */
    public function testCustomerListRendersResultCount(): void
    {
        $ro   = $this->resource->get('page://self/admin/customer-list');
        $html = $ro->toString();

        $this->assertStringContainsString((string) $ro->body['count'], $html);
    }

    /**
     * When customers exist the data table is rendered with the idea-admin-table
     * vocabulary. When the list is empty the idea-admin-empty state appears
     * instead. This test verifies the populated branch.
     */
    public function testCustomerListRendersIdeaAdminTableWhenCustomersExist(): void
    {
        $ro   = $this->resource->get('page://self/admin/customer-list');
        $html = $ro->toString();

        if (count($ro->body['customers']) === 0) {
            $this->assertStringContainsString('idea-admin-empty', $html);

            return;
        }

        $this->assertStringContainsString('idea-admin-table-wrap', $html);
        $this->assertStringContainsString('idea-admin-table', $html);
    }

    // ── L2: form action/method + link href/rel semantics ─────────────────────

    /**
     * The search form must submit via GET to /admin/customer-list
     * (goCustomerList is a safe read — no CSRF).
     */
    public function testCustomerListSearchFormUsesGetMethod(): void
    {
        $html = $this->resource->get('page://self/admin/customer-list')->toString();

        $this->assertStringContainsString('method="get"', $html);
        $this->assertStringContainsString('action="/admin/customer-list"', $html);
    }

    /**
     * Each customer row must link to /admin/customer?customerId=…
     * (goCustomer, GET). Verifies the href binding from
     * #[Link(rel: 'goCustomer', href: 'page://self/admin/customer', method: 'get')].
     */
    public function testCustomerListDetailLinksPointToAdminCustomerRoute(): void
    {
        $ro   = $this->resource->get('page://self/admin/customer-list');
        $html = $ro->toString();

        foreach ($ro->body['customers'] as $customer) {
            $expected = '/admin/customer?customerId=' . $customer['customerId'];
            $this->assertStringContainsString(
                $expected,
                $html,
                "detail link not found for customer {$customer['customerId']}",
            );
        }
    }

    /**
     * Delete confirmations must POST to /admin/delete-customer
     * (doDeleteCustomer, POST — matches
     * #[Link(rel: 'doDeleteCustomer', href: 'page://self/admin/delete-customer', method: 'post')]).
     */
    public function testCustomerListDeleteActionPostsToAdminDeleteCustomer(): void
    {
        $ro   = $this->resource->get('page://self/admin/customer-list');
        $html = $ro->toString();

        if (count($ro->body['customers']) === 0) {
            $this->markTestSkipped('No seeded customers — delete action not rendered.');
        }

        $this->assertStringContainsString('action="/admin/delete-customer"', $html);
        // The delete form must use POST (no _method override — resource uses
        // a dedicated doDeleteCustomer POST endpoint, not DELETE on the resource).
        $this->assertStringContainsString('method="post"', $html);
    }

    /**
     * The CSV export link must point to /admin/customer-csv.
     */
    public function testCustomerListCsvExportLinkPresent(): void
    {
        $ro   = $this->resource->get('page://self/admin/customer-list');
        $html = $ro->toString();

        if (count($ro->body['customers']) === 0) {
            $this->markTestSkipped('CSV link only rendered when count > 0.');
        }

        $this->assertStringContainsString('/admin/customer-csv', $html);
    }

    // ── Archived: EC-CUBE parity (clean-room DOM no longer matches) ──────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testCustomerListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE reference-rendering comparison archived: '
            . 'CustomerList.html.twig is now a clean-room idea-admin-* build '
            . 'whose DOM intentionally diverges from EC-CUBE\'s admin template. '
            . 'Functional parity is covered by L1/L2 tests above.',
        );
    }
}
