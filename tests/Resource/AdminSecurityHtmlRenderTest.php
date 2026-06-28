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
 * Phase 3 — HTML render check for the admin セキュリティ設定 page.
 *
 * Render-smoke standard: the page renders a full admin-frame HTML document
 * through {@see HtmlModule} driven by
 * {@see \MyVendor\BeMart\Resource\Page\Admin\Security}.
 *
 * L1 — required fields are present in the output.
 * L2 — form action / method contract and link hrefs are correct.
 */
final class AdminSecurityHtmlRenderTest extends TestCase
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

    public function testSecurityRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/security');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testAdminShellFrameIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/security')->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-content"',
            'class="idea-admin-sidebar"',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "idea-admin frame landmark missing: {$landmark}");
        }
    }

    /** L1 — all required fields appear in the rendered output. */
    public function testRequiredFieldInputsAreRendered(): void
    {
        $html = $this->resource->get('page://self/admin/security')->toString();

        foreach ([
            'admin_security_admin_route_dir',
            'admin_security_trusted_hosts',
        ] as $fieldId) {
            $this->assertStringContainsString($fieldId, $html, "required field missing: {$fieldId}");
        }
    }

    /** L1 — all non-required fields appear in the rendered output. */
    public function testOptionalFieldInputsAreRendered(): void
    {
        $html = $this->resource->get('page://self/admin/security')->toString();

        foreach ([
            'admin_security_admin_allow_hosts',
            'admin_security_admin_deny_hosts',
            'admin_security_front_allow_hosts',
            'admin_security_front_deny_hosts',
            'admin_security_force_ssl',
        ] as $fieldId) {
            $this->assertStringContainsString($fieldId, $html, "optional field missing: {$fieldId}");
        }
    }

    /** L2 — form action and tunnelled method are correct (PUT via ?_method). */
    public function testFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/security')->toString();

        $this->assertStringContainsString('action="/admin/security?_method=put"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    /** L2 — CSRF token hidden field is present. */
    public function testCsrfTokenFieldIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/security')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    /**
     * EC-CUBE markup parity tests archived.
     *
     * These tests asserted the presence of EC-CUBE / Bootstrap derived
     * landmarks (c-container, c-headerBar, c-contentsArea, etc.) that were
     * intentionally removed during the cleanroom rebuild.
     *
     * @group ec-cube-parity-archived
     */
    public function testEcCubeMarkupParityArchived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity assertions retired: cleanroom rebuild uses idea-admin-* frame. '
            . 'Re-enable only if a deliberate regression to EC-CUBE markup is required.',
        );
    }
}
