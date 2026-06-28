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
 * Phase 3 — HTML render check for the admin 配送先変更 (Order ShippingAddress) page.
 *
 * Frame landmarks use the idea-admin design language:
 *   idea-admin-shell   — app shell (topbar + sidebar + content)
 *   idea-admin-content — main content region
 *
 * L1 — required field presence (form field ids from AdminOrderShippingForm::init()).
 * L2 — PUT form action / method and back-nav link href / rel derived from
 *        #[Link] on ShippingAddress resource.
 *        POST address-picker form (doSelectShippingAddress) is also verified.
 */
final class AdminOrderShippingHtmlRenderTest extends TestCase
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

    /** Smoke: page returns 200 and renders a complete HTML document. */
    public function testOrderShippingRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/order/shipping-address');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * Frame landmark: idea-admin shell and content regions are present.
     *
     * Verifies the page extends admin-base.html.twig correctly.
     */
    public function testOrderShippingRendersIdeaAdminShellLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/order/shipping-address')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html);
        $this->assertStringContainsString('class="idea-admin-content"', $html);
    }

    /**
     * L1 — all seven address fields from AdminOrderShippingForm are present.
     *
     * Field ids are set in AdminOrderShippingForm::init() via setAttribs():
     *   shipping_name_name01, shipping_name_name02, shipping_postal_code,
     *   shipping_address_pref, shipping_address_addr01, shipping_address_addr02,
     *   shipping_phone_number.
     */
    public function testOrderShippingRendersAllAddressFields(): void
    {
        $html = $this->resource->get('page://self/admin/order/shipping-address')->toString();

        foreach ([
            'id="shipping_name_name01"',
            'id="shipping_name_name02"',
            'id="shipping_postal_code"',
            'id="shipping_address_pref"',
            'id="shipping_address_addr01"',
            'id="shipping_address_addr02"',
            'id="shipping_phone_number"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, "form field missing: {$needle}");
        }
    }

    /**
     * L2 — PUT form: action includes shipping-address path, _method=put hidden field,
     * and orderNo hidden field are present.
     *
     * Derived from onPut() on ShippingAddress resource and the HTML tunnel pattern.
     */
    public function testOrderShippingPutFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/order/shipping-address')->toString();

        $this->assertStringContainsString('id="shipping_address_form"', $html);
        $this->assertStringContainsString('action="/admin/order/shipping-address', $html);
        $this->assertStringContainsString('name="_method" value="put"', $html);
        $this->assertStringContainsString('name="orderNo"', $html);
    }

    /**
     * L2 — back-navigation link targets the order detail page.
     *
     * rel="goOrder" is derived from #[Link(rel:'goOrder', href:'page://self/admin/order')]
     * on the ShippingAddress resource onPut / onPost methods.
     */
    public function testOrderShippingBackNavLinkHrefAndRel(): void
    {
        $html = $this->resource->get('page://self/admin/order/shipping-address')->toString();

        $this->assertStringContainsString('href="/admin/order', $html);
        $this->assertStringContainsString('rel="goOrder"', $html);
    }

    /**
     * L2 — address-book picker form (doSelectShippingAddress) is present.
     *
     * POST /admin/order/shipping-address with addressId is the doSelectShippingAddress
     * transition. The picker dialog must render an addressId input and the correct
     * form action.
     */
    public function testOrderShippingAddressBookPickerFormIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/shipping-address')->toString();

        $this->assertStringContainsString('id="shipping_address_pick_form"', $html);
        $this->assertStringContainsString('name="addressId"', $html);
        $this->assertStringContainsString('action="/admin/order/shipping-address"', $html);
    }
}
