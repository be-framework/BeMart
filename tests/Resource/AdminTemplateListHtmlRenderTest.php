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
 * Phase 3 — HTML render fidelity for the admin Template-list page
 * (Store section, goTemplateList).
 *
 * Verification levels:
 *   L1 — required body fields rendered (count, templateName, active badge)
 *   L2 — form/link contract (action, method, href, rel)
 *
 * EC-CUBE parity comparison tests are archived: the template is a
 * cleanroom rebuild using idea-admin-* vocabulary and no longer tracks
 * EC-CUBE DOM structure.
 */
final class AdminTemplateListHtmlRenderTest extends TestCase
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
    public function testTemplateListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/template/template-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** L0 — idea-admin shell landmarks are present (admin-base frame). */
    public function testTemplateListUsesIdeaAdminShell(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-list')->toString();

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
     * L1 — Required body fields appear in rendered output.
     * Schema: count (int), templates[].templateName (string),
     *         templates[].active (bool → badge).
     */
    public function testTemplateListRendersRequiredBodyFields(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-list')->toString();

        // count chip
        $this->assertMatchesRegularExpression('/\d+件/', $html, 'count chip not rendered');

        // At least one template name row from Fake corpus
        $this->assertStringContainsString('デフォルト', $html, 'templateName not rendered');

        // Template table exists
        $this->assertStringContainsString('class="idea-admin-table"', $html, 'idea-admin-table missing');

        // Radio inputs (doSelectTemplate affordance)
        $this->assertStringContainsString('type="radio"', $html, 'radio input for template selection missing');
        $this->assertStringContainsString('name="template"', $html, 'radio name="template" missing');
    }

    /**
     * L2 — Form action / method contract (doSelectTemplate PUT,
     * doDownloadTemplate POST, doDeleteTemplate DELETE).
     */
    public function testTemplateListFormActionContracts(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-list')->toString();

        // doSelectTemplate: POST tunnel for PUT
        $this->assertStringContainsString(
            'action="/admin/template/template-list?_method=put"',
            $html,
            'doSelectTemplate form action missing',
        );
        $this->assertStringContainsString(
            'name="templateId"',
            $html,
            'templateId hidden field missing',
        );
        $this->assertStringContainsString(
            'name="csrfToken"',
            $html,
            'csrfToken hidden field missing',
        );

        // doDeleteTemplate: POST tunnel for DELETE
        $this->assertStringContainsString(
            '_method=delete',
            $html,
            'doDeleteTemplate _method=delete missing',
        );
    }

    /**
     * L2 — Link href / rel contract.
     * goTemplateAdd: GET /admin/template/template-add
     */
    public function testTemplateListContainsGoTemplateAddLink(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-list')->toString();

        $this->assertStringContainsString(
            'href="/admin/template/template-add"',
            $html,
            'goTemplateAdd link missing',
        );
    }

    /**
     * L2 — Delete confirmation dialog is rendered for each template row.
     * Uses idea-admin-dialog vocabulary.
     */
    public function testTemplateListRendersDeleteDialogs(): void
    {
        $html = $this->resource->get('page://self/admin/template/template-list')->toString();

        $this->assertStringContainsString(
            'class="idea-admin-dialog-backdrop"',
            $html,
            'idea-admin-dialog-backdrop missing',
        );
        $this->assertStringContainsString(
            'class="idea-admin-dialog"',
            $html,
            'idea-admin-dialog missing',
        );
        $this->assertStringContainsString(
            'idea-admin-action--destructive',
            $html,
            'destructive delete action missing',
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
    public function testTemplateListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $adminTemplates = dirname(__DIR__, 2)
            . '/tools/ec-cube-source/src/Eccube/Resource/template/admin';
        if (! is_dir($adminTemplates)) {
            $this->markTestSkipped('EC-CUBE 4.3 reference clone not present — parity test archived.');
        }

        $this->markTestSkipped(
            'EC-CUBE parity test archived: TemplateList.html.twig is a cleanroom '
            . 'rebuild in idea-admin-* vocabulary and intentionally diverges from '
            . 'EC-CUBE DOM structure. Functional coverage is provided by L1/L2 tests above.',
        );
    }
}
