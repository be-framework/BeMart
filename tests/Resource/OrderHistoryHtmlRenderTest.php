<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

use function rawurlencode;
use function str_contains;

/**
 * Semantic render verification for the order-history list (goOrderHistory) HTML page.
 *
 * L1 — Required fields and data output: the rendered HTML must surface
 *      order-list fields that the OrderHistoryFetched projection supplies
 *      (orderNo, orderDate, paymentTotal, orderStatus) for the seeded
 *      fixture customer.
 *
 * L2 — Link href / rel: each order row must carry a detail link that
 *      points to /mypage/history?orderNo=<orderNo> with rel="goMypageHistory".
 *      Navigation links back to /mypage must be present.
 *
 * The OrderHistory resource requires AUTHN; CustomerSession is rebound to
 * the fixture customer 'customer-001' whose order appears in
 * be/var/fake/query/order_list_by_customer.jsonl
 * (orderNo: past0000000000000000000000000001, paymentTotal: 12700,
 *  orderDate: 2026-04-01 10:00:00, orderStatus: 1).
 */
final class OrderHistoryHtmlRenderTest extends TestCase
{
    /** Fixture customer that owns the seeded order in the Fake JSONL store. */
    private const CUSTOMER_ID = 'customer-001';

    /** Fixture order from be/var/fake/query/order_list_by_customer.jsonl */
    private const ORDER_NO = 'past0000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeSession(self::CUSTOMER_ID);
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
            }
        });

        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /** L1: response is HTTP 200 and the document is a complete HTML page. */
    public function testRendersCompleteHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/order-history');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** L1: page title and brand are present. */
    public function testTitleContainsIdeaStoreBrand(): void
    {
        $html = $this->resource->get('page://self/mypage/order-history')->toString();

        $this->assertStringContainsString('IDEA STORE', $html);
    }

    /** L1: fixture order number is rendered in the list. */
    public function testFixtureOrderNoIsRendered(): void
    {
        $html = $this->resource->get('page://self/mypage/order-history')->toString();

        $this->assertStringContainsString(self::ORDER_NO, $html);
    }

    /** L1: fixture order payment total (12700) is rendered. */
    public function testFixturePaymentTotalIsRendered(): void
    {
        $html = $this->resource->get('page://self/mypage/order-history')->toString();

        // paymentTotal=12700 — template renders it with number_format
        $this->assertTrue(
            str_contains($html, '12,700') || str_contains($html, '12700'),
            'Expected paymentTotal (12700) not found in rendered HTML',
        );
    }

    /** L1: order status label is rendered (status 1 = 新規受付). */
    public function testFixtureOrderStatusLabelIsRendered(): void
    {
        $html = $this->resource->get('page://self/mypage/order-history')->toString();

        $this->assertStringContainsString('新規受付', $html);
    }

    /** L2: detail link href targets /mypage/history?orderNo=<orderNo>. */
    public function testOrderDetailLinkHrefIsCorrect(): void
    {
        $html = $this->resource->get('page://self/mypage/order-history')->toString();

        $this->assertStringContainsString(
            'href="/mypage/history?orderNo=' . rawurlencode(self::ORDER_NO) . '"',
            $html,
        );
    }

    /** L2: detail link carries rel="goMypageHistory". */
    public function testOrderDetailLinkHasRelGoMypageHistory(): void
    {
        $html = $this->resource->get('page://self/mypage/order-history')->toString();

        $this->assertStringContainsString('rel="goMypageHistory"', $html);
    }

    /** L2: navigation link back to /mypage is present. */
    public function testNavigationLinkToMypageIsPresent(): void
    {
        $html = $this->resource->get('page://self/mypage/order-history')->toString();

        $this->assertStringContainsString('href="/mypage"', $html);
    }

    /** L2: navigation link to favourite list is present. */
    public function testNavigationLinkToFavouriteListIsPresent(): void
    {
        $html = $this->resource->get('page://self/mypage/order-history')->toString();

        $this->assertStringContainsString('href="/mypage/favorite-list"', $html);
    }
}
