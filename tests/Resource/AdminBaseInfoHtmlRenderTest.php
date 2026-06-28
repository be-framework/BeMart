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
 * Phase 3 — HTML render check for the admin 店舗基本情報 page.
 *
 * L1: required data fields are present in rendered output.
 * L2: action endpoint / method / link hrefs are present.
 * Frame: idea-admin-shell / idea-admin-content landmarks are present.
 *
 * EC-CUBE parity assertions from the original test are archived below
 * and skipped — they test EC-CUBE-specific markup that no longer exists.
 */
final class AdminBaseInfoHtmlRenderTest extends TestCase
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

    // ── L0: HTTP contract ───────────────────────────────────────────────────

    public function testGetReturns200(): void
    {
        $ro = $this->resource->get('page://self/admin/base-info');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // ── L1: required data / form fields present ─────────────────────────────

    public function testHtmlDocumentStructure(): void
    {
        $html = $this->resource->get('page://self/admin/base-info')->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testRequiredFormFieldRendered(): void
    {
        $html = $this->resource->get('page://self/admin/base-info')->toString();

        // shop_name is the only required field per AdminShopMasterForm
        $this->assertStringContainsString('id="shop_master_shop_name"', $html, 'shop_name input must be present');
    }

    public function testAllFormFieldsRendered(): void
    {
        $html = $this->resource->get('page://self/admin/base-info')->toString();

        $fields = [
            'shop_master_shop_name',
            'shop_master_shop_kana',
            'shop_master_shop_name_eng',
            'shop_master_company_name',
            'shop_master_postal_code',
            'shop_master_pref',
            'shop_master_addr01',
            'shop_master_addr02',
            'shop_master_phone_number',
            'shop_master_business_hour',
            'shop_master_email01',
            'shop_master_shop_message',
        ];

        foreach ($fields as $fieldId) {
            $this->assertStringContainsString(
                'id="' . $fieldId . '"',
                $html,
                "form field missing: {$fieldId}",
            );
        }
    }

    // ── L2: action / method / link hrefs ───────────────────────────────────

    public function testFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/base-info')->toString();

        $this->assertStringContainsString('action="/admin/base-info"', $html, 'POST action must point to /admin/base-info');
        $this->assertStringContainsString('method="post"', $html, 'form method must be POST');
    }

    public function testCsrfTokenFieldPresent(): void
    {
        $html = $this->resource->get('page://self/admin/base-info')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html, 'CSRF token field must be present');
    }

    public function testRelatedLinkHrefsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/base-info')->toString();

        $this->assertStringContainsString('href="/admin/payment/payment-list"', $html, 'goPaymentList link must be present');
        $this->assertStringContainsString('href="/admin/product-list"', $html, 'goProductList link must be present');
        $this->assertStringContainsString('href="/admin/order-list"', $html, 'goOrderList link must be present');
    }

    // ── Frame landmarks ─────────────────────────────────────────────────────

    public function testIdeaAdminShellPresent(): void
    {
        $html = $this->resource->get('page://self/admin/base-info')->toString();

        $this->assertStringContainsString('idea-admin-shell', $html, 'idea-admin-shell landmark must be present');
        $this->assertStringContainsString('idea-admin-content', $html, 'idea-admin-content landmark must be present');
    }

    public function testPageFormIdPresent(): void
    {
        $html = $this->resource->get('page://self/admin/base-info')->toString();

        $this->assertStringContainsString('id="base_info_form"', $html, 'form id must be base_info_form');
    }

    // ── EC-CUBE parity (archived) ───────────────────────────────────────────

    /**
     * @group ec-cube-parity-archived
     */
    public function testBaseInfoPreservesEcCubeAdminMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity check archived: BeMart uses idea-admin design language, '
            . 'not EC-CUBE Bootstrap markup. Re-enable only for cross-reference diff tooling.',
        );
    }
}
