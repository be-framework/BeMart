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

use function dirname;
use function is_dir;

/**
 * Phase 3 — HTML render verification for the admin テンプレートアップロード page
 * (Store section, goAdminTemplateTemplateAdd / doInstallTemplate).
 *
 * Template is a cleanroom rebuild using idea-admin-* vocabulary;
 * EC-CUBE DOM structure is intentionally not mirrored.
 *
 * Verification levels:
 *   L0 — HTTP 200 and correct Content-Type
 *   L1 — required body fields / form inputs rendered
 *   L2 — action/method contract, link href/rel, frame landmarks
 *   Archived — EC-CUBE parity tests (@group ec-cube-parity-archived)
 */
final class AdminTemplateAddHtmlRenderTest extends TestCase
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

    /** L0 — HTTP 200 and correct Content-Type. */
    public function testTemplateAddRendersOk(): void
    {
        $ro = $this->resource->get('page://self/admin/template/template-add');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** L0 — Full HTML document structure. */
    public function testTemplateAddRendersAsHtmlDocument(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-add')->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    /** L0 — idea-admin shell landmarks from admin-base.html.twig. */
    public function testTemplateAddUsesIdeaAdminShell(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-add')->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-topbar',
            'class="idea-admin-sidebar"',
            'class="idea-admin-content"',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "idea-admin frame missing: {$landmark}");
        }
    }

    /**
     * L1 — Required form inputs rendered (templateCode, templateName, file).
     * Validates field presence from AdminTemplateAddForm contract.
     */
    public function testTemplateAddRendersRequiredFormInputs(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-add')->toString();

        $this->assertStringContainsString('name="templateCode"', $html, 'templateCode input missing');
        $this->assertStringContainsString('name="templateName"', $html, 'templateName input missing');
        $this->assertStringContainsString('name="file"', $html, 'file input missing');
        $this->assertStringContainsString('type="file"', $html, 'file input type missing');
    }

    /**
     * L1 — CSRF token hidden field rendered.
     * Required for doInstallTemplate POST to pass CsrfProtected check.
     */
    public function testTemplateAddRendersCsrfToken(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-add')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html, 'csrfToken hidden field missing');
    }

    /**
     * L2 — doInstallTemplate action contract.
     * POST /admin/template/template-add with multipart/form-data encoding.
     */
    public function testTemplateAddFormActionContract(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-add')->toString();

        $this->assertStringContainsString(
            'action="/admin/template/template-add"',
            $html,
            'doInstallTemplate form action missing',
        );
        $this->assertStringContainsString(
            'method="post"',
            $html,
            'form method post missing',
        );
        $this->assertStringContainsString(
            'enctype="multipart/form-data"',
            $html,
            'multipart/form-data enctype missing',
        );
    }

    /**
     * L2 — goTemplateList link contract.
     * GET /admin/template/template-list — back navigation to list.
     */
    public function testTemplateAddContainsGoTemplateListLink(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-add')->toString();

        $this->assertStringContainsString(
            'href="/admin/template/template-list"',
            $html,
            'goTemplateList back link missing',
        );
    }

    /**
     * EC-CUBE 4.3 rendering parity comparison.
     *
     * Archived: the template is a cleanroom rebuild using idea-admin-*
     * vocabulary. DOM structure no longer tracks EC-CUBE admin theme.
     *
     * @group ec-cube-parity-archived
     */
    public function testTemplateAddHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $adminTemplates = dirname(__DIR__, 2)
            . '/tools/ec-cube-source/src/Eccube/Resource/template/admin';
        if (! is_dir($adminTemplates)) {
            $this->markTestSkipped('EC-CUBE 4.3 reference clone not present — parity test archived.');
        }

        $this->markTestSkipped(
            'EC-CUBE parity test archived: TemplateAdd.html.twig is a cleanroom '
            . 'rebuild in idea-admin-* vocabulary and intentionally diverges from '
            . 'EC-CUBE DOM structure. Functional coverage is provided by L0/L1/L2 tests above.',
        );
    }
}
