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
 * Phase 3 — HTML render check for the admin メール設定 page.
 *
 * L1: required data and fields are present in the rendered output.
 * L2: form action / method, link href / rel, ARIA landmark contracts.
 *
 * EC-CUBE residual-diff parity checks (exact EC-CUBE 4.3 markup
 * comparison) are archived — they require the EC-CUBE 4.3 reference
 * clone (`tools/ec-cube-source/`) which is not present in CI.
 *
 * @group admin-html
 */
final class AdminMailTemplateHtmlRenderTest extends TestCase
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

    /** L1 — page renders a complete HTML document */
    public function testRendersFullHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/mail-template');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** L1 — idea-admin-shell and idea-admin-content frame landmarks are present */
    public function testIdeaAdminShellLandmarkPresent(): void
    {
        $html = $this->resource->get('page://self/admin/mail-template')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html);
        $this->assertStringContainsString('class="idea-admin-content"', $html);
    }

    /** L1 — template roster table is rendered when no template is selected */
    public function testTemplateRosterTablePresent(): void
    {
        $html = $this->resource->get('page://self/admin/mail-template')->toString();

        // The roster panel must always be visible regardless of selection state.
        $this->assertStringContainsString('idea-admin-table', $html);
        $this->assertStringContainsString('/admin/mail-template?mailTemplateId=', $html);
    }

    /** L1 — CSRF token hidden input is present when a template is selected */
    public function testCsrfTokenInputPresentWhenSelected(): void
    {
        $html = $this->resource->get('page://self/admin/mail-template', ['mailTemplateId' => 1])->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    /** L1 — mail_subject input is rendered when a template is selected */
    public function testMailSubjectFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/mail-template', ['mailTemplateId' => 1])->toString();

        $this->assertStringContainsString('id="mail_mail_subject"', $html);
    }

    /** L1 — tpl_data and html_tpl_data textareas are rendered when editing */
    public function testBodyTextareasPresent(): void
    {
        $html = $this->resource->get('page://self/admin/mail-template', ['mailTemplateId' => 1])->toString();

        $this->assertStringContainsString('id="mail_tpl_data"', $html);
        $this->assertStringContainsString('id="mail_html_tpl_data"', $html);
    }

    /** L2 — update form posts to /admin/mail-template (doUpdateMailTemplate) */
    public function testUpdateFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/mail-template', ['mailTemplateId' => 1])->toString();

        $this->assertStringContainsString('action="/admin/mail-template"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    /** L2 — doDeleteMailTemplate link carries correct rel and href pattern */
    public function testDeleteLinkRelAndHref(): void
    {
        // Request a deletable template (id=1 is observed as isDeletable=true in Fake data).
        $html = $this->resource->get('page://self/admin/mail-template', ['mailTemplateId' => 1])->toString();

        // Conditional: only assert when the delete dialog is rendered (isDeletable=true).
        if (str_contains($html, 'id="delete-confirm-dialog"')) {
            $this->assertStringContainsString('rel="doDeleteMailTemplate"', $html);
            $this->assertStringContainsString('_method=delete', $html);
            $this->assertStringContainsString('mailTemplateId=', $html);
        } else {
            // Template is protected — delete affordance must not appear.
            $this->assertStringNotContainsString('doDeleteMailTemplate', $html);
        }
    }

    /** L2 — goPaymentList back-nav link is present on every render (no-selection and edit state) */
    public function testPaymentListBackNavPresent(): void
    {
        $html = $this->resource->get('page://self/admin/mail-template')->toString();

        $this->assertStringContainsString('rel="goPaymentList"', $html);
        $this->assertStringContainsString('/admin/payment/payment-list', $html);
    }

    /** L2 — goPaymentList back-nav also present when a template is selected */
    public function testPaymentListBackNavPresentWhenSelected(): void
    {
        $html = $this->resource->get('page://self/admin/mail-template', ['mailTemplateId' => 1])->toString();

        $this->assertStringContainsString('rel="goPaymentList"', $html);
        $this->assertStringContainsString('/admin/payment/payment-list', $html);
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
