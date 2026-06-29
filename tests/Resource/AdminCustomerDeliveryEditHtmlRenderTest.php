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
 * HTML render checks for the admin お届け先編集 page.
 *
 * L1 — required data / form fields are present in the output.
 * L2 — action/method and navigation links are correct.
 * Frame — landmark structure matches idea-admin-shell / idea-admin-content.
 */
final class AdminCustomerDeliveryEditHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const TEST_CUSTOMER_ID = 'customer-001';

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

    /** Resource resolves to HTTP 200. */
    public function testResourceReturnsOk(): void
    {
        $ro = $this->resource->get('page://self/admin/customer-delivery-edit');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * Frame landmark: idea-admin-shell wraps the page; idea-admin-content
     * is present as the content landmark.
     *
     * @group frame
     */
    public function testIdeaAdminShellLandmarkIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/customer-delivery-edit')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html, 'idea-admin-shell wrapper missing');
        $this->assertStringContainsString('class="idea-admin-content"', $html, 'idea-admin-content landmark missing');
    }

    /**
     * L1 — required form fields render in the output.
     *
     * All nine fields defined in AdminCustomerDeliveryForm must be present
     * by their form-control id.
     *
     * @group l1
     */
    public function testRequiredFormFieldsArePresent(): void
    {
        $html = $this->resource->get('page://self/admin/customer-delivery-edit')->toString();

        $requiredIds = [
            'admin_customer_address_name_name01',
            'admin_customer_address_name_name02',
            'admin_customer_address_kana_kana01',
            'admin_customer_address_kana_kana02',
            'admin_customer_address_company_name',
            'admin_customer_address_postal_code',
            'admin_customer_address_address_pref',
            'admin_customer_address_address_addr01',
            'admin_customer_address_address_addr02',
            'admin_customer_address_phone_number',
        ];

        foreach ($requiredIds as $id) {
            $this->assertStringContainsString(
                'id="' . $id . '"',
                $html,
                "Form field id=\"{$id}\" missing from rendered output",
            );
        }
    }

    /**
     * L1 — form element is present with method=post.
     *
     * @group l2
     */
    public function testFormMethodIsPost(): void
    {
        $html = $this->resource->get('page://self/admin/customer-delivery-edit')->toString();

        $this->assertMatchesRegularExpression(
            '/<form[^>]+method=["\']post["\']/i',
            $html,
            'form method="post" not found',
        );
    }

    /**
     * L2 — customer-list back-link (goCustomerList relation) is present.
     *
     * @group l2
     */
    public function testCustomerListBackLinkIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/customer-delivery-edit')->toString();

        $this->assertStringContainsString('/admin/customer-list', $html, 'Back-link to customer-list missing');
    }

    /**
     * L2 — with a customerId, the form action and back-link contain the id.
     *
     * @group l2
     */
    public function testFormActionContainsCustomerId(): void
    {
        $html = $this->resource->get(
            'page://self/admin/customer-delivery-edit',
            ['customerId' => self::TEST_CUSTOMER_ID],
        )->toString();

        $this->assertStringContainsString(
            self::TEST_CUSTOMER_ID,
            $html,
            'customerId not propagated into rendered output',
        );
    }

    /**
     * EC-CUBE markup parity checks are archived.
     *
     * These asserted Bootstrap / c-* class presence from the pre-BeMart
     * EC-CUBE template; they have no meaning in the idea-admin vocabulary.
     *
     * @group ec-cube-parity-archived
     */
    public function testEcCubeMarkupParityArchived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE c-* / Bootstrap markup parity archived — idea-admin vocabulary does not use those classes.',
        );
    }
}
