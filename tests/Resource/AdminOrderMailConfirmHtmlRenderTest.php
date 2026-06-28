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
 * HTML render test for admin 受注メール送信確認 (goAdminOrderMailConfirm).
 *
 * Verification layers:
 *   L1 — Required data present: orderNo rendered in the page.
 *   L2 — Action contract: form POSTs to /admin/order/send-mail with
 *         required hidden fields (csrfToken, orderNo); back link carries
 *         rel="goOrderMail" to /admin/order/send-mail.
 *   Frame — idea-admin shell landmarks present (idea-admin-shell,
 *            idea-admin-content).
 *
 * EC-CUBE markup parity checks (c-headerBar, c-contentsArea, etc.)
 * are archived — they tested the old EC-CUBE-mirrored template.
 */
final class AdminOrderMailConfirmHtmlRenderTest extends TestCase
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

    /** Frame: renders a full HTML document with idea-admin shell. */
    public function testRendersFullHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/order/mail-confirm');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    /** Frame landmark: idea-admin-shell wraps the entire admin chrome. */
    public function testIdeaAdminShellLandmarkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/mail-confirm')->toString();

        $this->assertStringContainsString('idea-admin-shell', $html);
        $this->assertStringContainsString('idea-admin-content', $html);
    }

    /** L1 — required data: orderNo passed through the GET is rendered. */
    public function testOrderNoRenderedInPage(): void
    {
        $ro = $this->resource->get(
            'page://self/admin/order/mail-confirm',
            ['orderNo' => self::TEST_ORDER_NO],
        );
        $html = $ro->toString();

        $this->assertStringContainsString(self::TEST_ORDER_NO, $html);
    }

    /** L2 — action contract: form POSTs to /admin/order/send-mail. */
    public function testFormActionPostsToSendMail(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/mail-confirm',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('action="/admin/order/send-mail"', $html);
    }

    /** L2 — action contract: hidden orderNo field is present in the form. */
    public function testHiddenOrderNoFieldPresent(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/mail-confirm',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertMatchesRegularExpression(
            '/type="hidden"[^>]*name="orderNo"/',
            $html,
            'Hidden orderNo field must be present for POST submission',
        );
    }

    /** L2 — action contract: hidden csrfToken field is present in the form. */
    public function testHiddenCsrfTokenFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/mail-confirm')->toString();

        $this->assertMatchesRegularExpression(
            '/type="hidden"[^>]*name="csrfToken"/',
            $html,
            'Hidden csrfToken field must be present for CSRF protection',
        );
    }

    /** L2 — navigation link: back link carries rel="goOrderMail". */
    public function testBackLinkHasGoOrderMailRel(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/mail-confirm',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertMatchesRegularExpression(
            '/rel="goOrderMail"/',
            $html,
            'Back link must carry rel="goOrderMail" per ALPS link contract',
        );
        $this->assertStringContainsString('/admin/order/send-mail', $html);
    }

    /**
     * EC-CUBE parity: old markup structure assertions (c-headerBar,
     * c-contentsArea, id="mail_confirm_form" with mode=complete, etc.)
     * no longer apply — the template is a clean-room idea-admin design.
     *
     * @group ec-cube-parity-archived
     */
    public function testEcCubeMarkupParityArchived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity checks retired. '
            . 'Template is a clean-room idea-admin design; '
            . 'functional assertions are in the other test methods.',
        );
    }
}
