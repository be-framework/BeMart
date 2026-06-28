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

/**
 * Phase 3 — idea-admin clean-room build for the admin Customer-edit page.
 *
 * The template (Customer.html.twig) extends admin-base.html.twig and uses
 * the idea-admin-* design vocabulary exclusively. Assertions are split into
 * three tiers:
 *
 *   L0 — HTML document shell (<!doctype>, <html lang="ja">, idea-admin-shell)
 *   L1 — Required fields rendered / customer data output
 *   L2 — Form action/method, back-link href
 *
 * The EC-CUBE markup-parity test is archived as
 * {@see testCustomerEditHtmlMatchesEcCubeRenderingArchived} (group
 * ec-cube-parity-archived) because the template no longer derives from the
 * EC-CUBE admin DOM.
 */
final class AdminCustomerHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    /** The happy-path customer pre-seeded in be/var/fake/customers.json. */
    private const SEED_CUSTOMER_EMAIL = 'alice@example.com';

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

    // ──────────────────────────────────────────────────────────────────────────
    // L0 — Document shell
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * The page renders as a valid HTML document served with the HTML
     * content-type and rooted in the idea-admin-shell frame.
     */
    public function testCustomerEditRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ]);

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * The page uses the idea-admin-* shell landmarks from admin-base.html.twig.
     * No EC-CUBE / Bootstrap / c-* landmarks are expected.
     */
    public function testCustomerEditUsesIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ])->toString();

        foreach ([
            '<div class="idea-admin-shell">',
            '<header class="idea-admin-topbar">',
            '<aside class="idea-admin-sidebar">',
            '<main class="idea-admin-content">',
            'idea-admin-page-header',
            'idea-admin-page-title',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "shell landmark missing: {$needle}");
        }

        // Confirm no EC-CUBE / Bootstrap / c-* landmarks leaked through.
        foreach ([
            'c-container',
            'c-headerBar',
            'c-mainNavArea',
            'c-contentsArea',
            'c-conversionArea',
            'btn-ec-conversion',
            'ec-cardCollapse',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html, "EC-CUBE class must not appear: {$forbidden}");
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // L1 — Required fields / customer data output
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * All required form fields are rendered with the correct form-field ids
     * from AdminCustomerForm and the pre-filled profile values from the
     * resource body.
     *
     * Field ids are the AdminCustomerForm::init() attrib values — they are
     * the agreed reference (a port of EC-CUBE's CustomerType block prefix
     * `admin_customer`).
     */
    public function testCustomerEditRendersRequiredFormFields(): void
    {
        $html = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ])->toString();

        // Required fields (name01, name02, email) must be present.
        $this->assertStringContainsString('id="admin_customer_name_name01"', $html);
        $this->assertStringContainsString('id="admin_customer_name_name02"', $html);
        $this->assertStringContainsString('id="admin_customer_email"', $html);
    }

    /**
     * The form is pre-filled: the seed customer's profile values appear in the
     * rendered input values.
     */
    public function testCustomerEditPreFillsProfileFromResourceBody(): void
    {
        $html = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ])->toString();

        // Seed customer 姓 = 山田, メール = alice@example.com.
        $this->assertStringContainsString('value="山田"', $html);
        $this->assertStringContainsString('value="alice@example.com"', $html);
    }

    /**
     * All form fields declared by AdminCustomerForm are rendered via the form
     * object, including optional fields and the note textarea.
     */
    public function testCustomerEditRendersAllFormFields(): void
    {
        $html = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ])->toString();

        // Optional profile fields.
        $this->assertStringContainsString('id="admin_customer_kana_kana01"', $html);
        $this->assertStringContainsString('id="admin_customer_kana_kana02"', $html);
        $this->assertStringContainsString('id="admin_customer_company_name"', $html);
        $this->assertStringContainsString('id="admin_customer_postal_code"', $html);
        $this->assertStringContainsString('id="admin_customer_address_addr01"', $html);
        $this->assertStringContainsString('id="admin_customer_address_addr02"', $html);
        $this->assertStringContainsString('id="admin_customer_phone_number"', $html);
        $this->assertStringContainsString('id="admin_customer_plain_password_first"', $html);
        $this->assertStringContainsString('id="admin_customer_plain_password_second"', $html);
        $this->assertStringContainsString('id="admin_customer_birth"', $html);
        $this->assertStringContainsString('id="admin_customer_point"', $html);
        $this->assertStringContainsString('<textarea id="admin_customer_note"', $html);
    }

    /**
     * The customer ID is rendered in the KPI summary strip. This verifies the
     * read-only body value reaches the template.
     */
    public function testCustomerEditRendersCustomerIdInKpiStrip(): void
    {
        $ro = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ]);
        $html = $ro->toString();

        $customerId = (string) ($ro->body['customerId'] ?? '');
        $this->assertNotEmpty($customerId);
        $this->assertStringContainsString($customerId, $html);
    }

    /**
     * The empty-state blocks for order history and address book are rendered
     * when those lists are empty (Wave 5 seed carries empty lists).
     */
    public function testCustomerEditRendersEmptyStateSectionsForOrdersAndAddresses(): void
    {
        $html = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ])->toString();

        $this->assertStringContainsString('id="history_box"', $html);
        $this->assertStringContainsString('id="address_box"', $html);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // L2 — Form action/method and navigation links
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * The edit form posts to /admin/customer?customerId=<id> — the action
     * and method derived from the resource method routing.
     */
    public function testCustomerEditFormActionAndMethod(): void
    {
        $ro = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ]);
        $html = $ro->toString();

        $customerId = (string) ($ro->body['customerId'] ?? '');
        $this->assertStringContainsString(
            'action="/admin/customer?customerId=' . $customerId . '"',
            $html,
        );
        $this->assertStringContainsString('method="post"', $html);
    }

    /**
     * The back-navigation link points to /admin/customer-list — the
     * #[Link(rel: 'goCustomerList')] href from the Customer resource.
     */
    public function testCustomerEditBackLinkPointsToCustomerList(): void
    {
        $html = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ])->toString();

        $this->assertStringContainsString('href="/admin/customer-list"', $html);
    }

    /**
     * The delivery-edit link carries the customerId query parameter.
     */
    public function testCustomerEditDeliveryLinkCarriesCustomerId(): void
    {
        $ro = $this->resource->get('page://self/admin/customer', [
            'email' => self::SEED_CUSTOMER_EMAIL,
        ]);
        $html = $ro->toString();

        $customerId = (string) ($ro->body['customerId'] ?? '');
        $this->assertStringContainsString(
            'href="/admin/customer-delivery-edit?customerId=' . $customerId . '"',
            $html,
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Archived — EC-CUBE markup parity (clean-room rebuild invalidates this)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * The EC-CUBE markup-parity diff test is archived because the template is
     * now a clean-room idea-admin build; its DOM no longer derives from the
     * EC-CUBE admin theme and a line-for-line diff against the EC-CUBE
     * rendering is not meaningful.
     *
     * @group ec-cube-parity-archived
     */
    public function testCustomerEditHtmlMatchesEcCubeRenderingArchived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup-parity test archived: Customer.html.twig is a clean-room '
            . 'idea-admin build. Functional/semantic coverage is provided by the L1/L2 '
            . 'tests in this class.',
        );
    }
}
