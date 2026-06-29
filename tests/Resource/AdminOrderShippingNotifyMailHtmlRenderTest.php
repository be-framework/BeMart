<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Entity\FinalizedOrderEntity;
use MyVendor\BeMart\Be\Reason\Entity\OrderEntity;
use MyVendor\BeMart\Be\Reason\Query\OrderQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * HTML render test for admin 出荷通知メール送信確認 (doSendShippingNotifyMail).
 *
 * Verification layers:
 *   L1  Required data present in output: orderNo, customerId/guest label.
 *   L2  Action contract: POST /admin/order/shipping-notify-mail;
 *       hidden fields (csrfToken, orderNo); back link rel="goOrder".
 *   Frame  idea-admin-shell / idea-admin-content landmarks present.
 *
 * GET renders the pre-send confirmation view (form visible, isSent=false).
 * POST result rendering (isSent=true, trackingNumber key present) is not
 * tested here via onGet — that state is produced by onPost; it is covered
 * by the Be Final unit test (ShippingNotifyMailSent) and HTTP flow tests.
 */
final class AdminOrderShippingNotifyMailHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const TEST_ORDER_NO = 'past0000000000000000000000000001';

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

    /** L0 — document envelope: complete HTML document with correct Content-Type. */
    public function testRendersCompleteHtmlDocument(): void
    {
        $ro = $this->resource->get(
            'page://self/admin/order/shipping-notify-mail',
            ['orderNo' => self::TEST_ORDER_NO],
        );

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    /** Frame — idea-admin-shell + idea-admin-content landmarks present. */
    public function testIdeaAdminFrameLandmarksPresent(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/shipping-notify-mail',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html, 'idea-admin-shell landmark missing');
        $this->assertStringContainsString('idea-admin-content', $html, 'idea-admin-content landmark missing');
    }

    /** L1 — required data: orderNo is rendered in the page. */
    public function testOrderNoRenderedInPage(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/shipping-notify-mail',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertStringContainsString(self::TEST_ORDER_NO, $html, 'orderNo must be rendered');
    }

    /**
     * L1 — required data: null/empty customerId renders a guest label,
     * not a blank cell.
     *
     * Injects a stub OrderQueryInterface that returns a guest order
     * (empty customerId) so the template branch `{% if customerId %}` is false.
     */
    public function testGuestPurchaseLabelRenderedWhenCustomerIdNull(): void
    {
        $guestOrderNo = 'guest-order-0000000000000000001';
        $guestOrder = new FinalizedOrderEntity(
            orderNo: $guestOrderNo,
            preOrderId: 'pre-00000000000000000000000001',
            customerId: '',   // empty = guest
            paymentMethodId: 1,
            subtotal: 1000,
            deliveryFeeTotal: 0,
            charge: 0,
            discount: 0,
            tax: 100,
            total: 1000,
            paymentTotal: 1000,
            addPoint: 0,
            usePoint: 0,
            orderStatus: FinalizedOrderEntity::STATUS_NEW,
            orderDate: '2026-01-01T00:00:00+09:00',
            paymentDate: '2026-01-01T00:00:00+09:00',
        );

        $fakeOrderQuery = new class ($guestOrder, $guestOrderNo) implements OrderQueryInterface {
            public function __construct(
                private readonly FinalizedOrderEntity $order,
                private readonly string $orderNo,
            ) {
            }

            public function byPreOrderId(string $preOrderId): ?OrderEntity
            {
                return null;
            }

            public function byOrderNo(string $orderNo): ?FinalizedOrderEntity
            {
                return $orderNo === $this->orderNo ? $this->order : null;
            }

            public function listByCustomer(string $customerId, int $limit = 10, int $offset = 0): array
            {
                return [];
            }

            public function list(int $limit = 50, int $offset = 0): array
            {
                return [];
            }
        };

        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $injector = HtmlTestInjector::getOverrideInstance(
            new class ($session, $fakeOrderQuery) extends AbstractModule {
                public function __construct(
                    private readonly FakeAdminSession $session,
                    private readonly OrderQueryInterface $orderQuery,
                ) {
                    parent::__construct();
                }

                protected function configure(): void
                {
                    $this->bind(AdminSession::class)->toInstance($this->session);
                    $this->bind(OrderQueryInterface::class)->toInstance($this->orderQuery);
                }
            },
        );
        $resource = $injector->getInstance(ResourceInterface::class);
        $html = $resource->get(
            'page://self/admin/order/shipping-notify-mail',
            ['orderNo' => $guestOrderNo],
        )->toString();

        $this->assertStringContainsString('ゲスト購入', $html, 'Guest purchase label must appear when customerId is empty');
    }

    /** L2 — action: form POSTs to /admin/order/shipping-notify-mail. */
    public function testFormPostsToShippingNotifyMailEndpoint(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/shipping-notify-mail',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertStringContainsString('method="post"', $html, 'form POST method missing');
        $this->assertStringContainsString(
            'action="/admin/order/shipping-notify-mail"',
            $html,
            'form action must be /admin/order/shipping-notify-mail',
        );
    }

    /** L2 — action: hidden csrfToken field present (CsrfProtected on onPost). */
    public function testHiddenCsrfTokenFieldPresent(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/shipping-notify-mail',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertMatchesRegularExpression(
            '/type="hidden"[^>]*name="csrfToken"/',
            $html,
            'Hidden csrfToken field must be present for CSRF protection',
        );
    }

    /** L2 — action: hidden orderNo field present (POST body field). */
    public function testHiddenOrderNoFieldPresent(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/shipping-notify-mail',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertMatchesRegularExpression(
            '/type="hidden"[^>]*name="orderNo"/',
            $html,
            'Hidden orderNo field must be present for POST submission',
        );
    }

    /** L2 — navigation link: back link carries rel="goOrder". */
    public function testBackLinkCarriesGoOrderRel(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/shipping-notify-mail',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertMatchesRegularExpression(
            '/rel="goOrder"/',
            $html,
            'Back link must carry rel="goOrder" per ALPS link contract',
        );
        $this->assertStringContainsString('/admin/order', $html, 'Back link href must point to /admin/order');
    }

    /**
     * EC-CUBE parity: old markup (c-headerBar, c-contentsArea, card/card-body,
     * btn-ec-conversion, admin_shipping_notify_mail CSRF) no longer applies.
     *
     * @group ec-cube-parity-archived
     */
    public function testEcCubeMarkupParityArchived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity retired. '
            . 'Template is a clean-room idea-admin design; '
            . 'functional assertions are in the other test methods.',
        );
    }
}
