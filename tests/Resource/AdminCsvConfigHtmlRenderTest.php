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
 * HTML render verification for the admin CSV column config page.
 *
 * L1 — required data/fields present in output.
 * L2 — action contract: form action/method, CSRF token, links.
 * L3 — shell landmark: idea-admin-shell / idea-admin-content.
 *
 * EC-CUBE markup parity tests are retired; the template is a clean-room
 * design that does not mirror EC-CUBE frame/class structure.
 */
final class AdminCsvConfigHtmlRenderTest extends TestCase
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

    /** L0 — HTTP 200, content-type text/html. */
    public function testRendersWithOkStatusAndHtmlContentType(): void
    {
        $ro = $this->resource->get('page://self/admin/csv-config');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** L0 — document is a full HTML page. */
    public function testRendersFullHtmlDocument(): void
    {
        $html = $this->resource->get('page://self/admin/csv-config')->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    /** L3 — shell landmark: idea-admin-shell wraps page content. */
    public function testContainsAdminShellLandmark(): void
    {
        $html = $this->resource->get('page://self/admin/csv-config')->toString();

        $this->assertStringContainsString('idea-admin-shell', $html);
        $this->assertStringContainsString('idea-admin-content', $html);
    }

    /** L1 — CSV type selector is rendered (csvType field). */
    public function testContainsCsvTypeSelector(): void
    {
        $html = $this->resource->get('page://self/admin/csv-config')->toString();

        $this->assertStringContainsString('id="csv-type-select"', $html);
    }

    /** L1 — output column labels appear in the page body. */
    public function testOutputColumnsAreRendered(): void
    {
        $html = $this->resource->get('page://self/admin/csv-config')->toString();

        // outputColumns from AdminCsvConfigForm::outputColumns()
        $this->assertStringContainsString('注文番号', $html);
        $this->assertStringContainsString('注文日時', $html);
        $this->assertStringContainsString('顧客名', $html);
        $this->assertStringContainsString('お支払い合計', $html);
    }

    /** L1 — disabled column labels also appear in the page body. */
    public function testNotOutputColumnsAreRendered(): void
    {
        $html = $this->resource->get('page://self/admin/csv-config')->toString();

        // notOutputColumns from AdminCsvConfigForm::notOutputColumns()
        $this->assertStringContainsString('支払方法', $html);
        $this->assertStringContainsString('配送方法', $html);
        $this->assertStringContainsString('お問い合わせ番号', $html);
    }

    /** L2 — POST form targets the correct endpoint with CSRF token. */
    public function testFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/csv-config')->toString();

        $this->assertStringContainsString('id="csv-config-form"', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('action="/admin/csv-config"', $html);
        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    /** L2 — link back to admin top (goTop). */
    public function testContainsGoTopLink(): void
    {
        $html = $this->resource->get('page://self/admin/csv-config')->toString();

        $this->assertStringContainsString('href="/admin"', $html);
        $this->assertStringContainsString('rel="goTop"', $html);
    }

    /** L2 — link to product CSV export (goExportProduct). */
    public function testContainsGoExportProductLink(): void
    {
        $html = $this->resource->get('page://self/admin/csv-config')->toString();

        $this->assertStringContainsString('href="/admin/product-csv"', $html);
        $this->assertStringContainsString('rel="goExportProduct"', $html);
    }

    /** L2 — submit button is present. */
    public function testContainsSubmitButton(): void
    {
        $html = $this->resource->get('page://self/admin/csv-config')->toString();

        $this->assertStringContainsString('type="submit"', $html);
    }

    /** L1 — sortable list container is rendered (JS hook). */
    public function testContainsSortableListContainer(): void
    {
        $html = $this->resource->get('page://self/admin/csv-config')->toString();

        $this->assertStringContainsString('id="col-sortable-list"', $html);
    }

    /**
     * EC-CUBE markup parity — retired.
     *
     * @group ec-cube-parity-archived
     */
    public function testCsvConfigPreservesEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity retired: template is a clean-room design ' .
            'that does not mirror EC-CUBE frame/class structure (c-headerBar, c-contentsArea, etc.).',
        );
    }
}
