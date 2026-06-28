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
 * Phase 3 — HTML render check for the admin 受注メール送信 page.
 *
 * L1: required data fields and form inputs are present in the rendered output.
 * L2: form action / method, link href / rel, ARIA frame landmark contracts.
 *
 * EC-CUBE markup parity checks (exact EC-CUBE 4.3 class / structure comparison)
 * are archived — they require the EC-CUBE 4.3 reference clone
 * (`tools/ec-cube-source/`) which is not present in CI.
 *
 * @group admin-html
 */
final class AdminOrderMailHtmlRenderTest extends TestCase
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

    /** L1 — page renders a complete HTML document with correct Content-Type */
    public function testRendersFullHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/order/send-mail');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** L2 — idea-admin-shell and idea-admin-content frame landmarks are present */
    public function testIdeaAdminShellLandmarkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/send-mail')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html);
        $this->assertStringContainsString('class="idea-admin-content"', $html);
    }

    /** L1 — CSRF token hidden input is present in the send-mail form */
    public function testCsrfTokenInputPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/send-mail')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    /** L1 — orderNo hidden input is present in the send-mail form */
    public function testOrderNoHiddenInputPresent(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/send-mail',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertStringContainsString('name="orderNo"', $html);
        $this->assertStringContainsString(self::TEST_ORDER_NO, $html);
    }

    /** L1 — template select field is rendered */
    public function testTemplateSelectFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/send-mail')->toString();

        $this->assertStringContainsString('id="mail_template"', $html);
    }

    /** L1 — mail_subject input is rendered with required affordance */
    public function testMailSubjectFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/send-mail')->toString();

        $this->assertStringContainsString('id="mail_mail_subject"', $html);
    }

    /** L1 — mail_header and mail_footer textareas are rendered */
    public function testHeaderAndFooterTextareasPresent(): void
    {
        $html = $this->resource->get('page://self/admin/order/send-mail')->toString();

        $this->assertStringContainsString('id="mail_mail_header"', $html);
        $this->assertStringContainsString('id="mail_mail_footer"', $html);
    }

    /** L2 — send-mail form posts to /admin/order/send-mail (doSendOrderMail) */
    public function testSendMailFormActionAndMethod(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/send-mail',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertStringContainsString('action="/admin/order/send-mail', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('rel="doSendOrderMail"', $html);
    }

    /** L2 — goOrder back-navigation link carries correct rel and href */
    public function testGoOrderBackNavPresent(): void
    {
        $html = $this->resource->get(
            'page://self/admin/order/send-mail',
            ['orderNo' => self::TEST_ORDER_NO],
        )->toString();

        $this->assertStringContainsString('rel="goOrder"', $html);
        $this->assertStringContainsString('/admin/order', $html);
    }

    /**
     * EC-CUBE parity — archived; requires EC-CUBE 4.3 reference clone.
     *
     * @group ec-cube-parity-archived
     */
    public function testEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE 4.3 markup parity check requires tools/ec-cube-source/. '
            . 'Archived in @group ec-cube-parity-archived.',
        );
    }
}
