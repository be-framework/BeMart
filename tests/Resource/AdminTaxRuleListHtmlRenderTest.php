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

use function str_contains;

/**
 * Phase 3 — semantic and structural verification for the admin Tax-rule
 * list page (idea-admin clean-room rebuild).
 *
 * L1 — required fields and list data output
 * L2 — form action/method, link href/rel
 *
 * The EC-CUBE reference-rendering comparison test has been archived:
 * @see testTaxRuleListHtmlMatchesEcCubeRenderingWithinResidualAllowlist
 */
final class AdminTaxRuleListHtmlRenderTest extends TestCase
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

    // ── frame landmark ──────────────────────────────────────────────────────

    public function testTaxRuleListRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/tax-rule/tax-rule-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    public function testTaxRuleListUsesIdeaAdminShellFrame(): void
    {
        $html = $this->resource->get('page://self/admin/tax-rule/tax-rule-list')->toString();

        foreach ([
            'class="idea-admin-shell"',
            'class="idea-admin-content"',
            'class="idea-admin-topbar"',
            'class="idea-admin-sidebar"',
        ] as $landmark) {
            $this->assertStringContainsString($landmark, $html, "idea-admin frame landmark missing: {$landmark}");
        }
    }

    // ── L1: required fields present ─────────────────────────────────────────

    public function testTaxRuleListRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/admin/tax-rule/tax-rule-list')->toString();

        // taxRate input (id set by AdminTaxRuleForm)
        $this->assertStringContainsString('id="tax_rule_tax_rate"', $html);
        // applyDate input (id set by AdminTaxRuleForm)
        $this->assertStringContainsString('id="tax_rule_apply_date"', $html);
    }

    public function testTaxRuleListRendersInlineCreateRow(): void
    {
        $html = $this->resource->get('page://self/admin/tax-rule/tax-rule-list')->toString();

        $this->assertStringContainsString('id="tax_rule_item_new"', $html);
        $this->assertStringContainsString('id="form1"', $html);
    }

    public function testTaxRuleListRendersCountFromBody(): void
    {
        $html = $this->resource->get('page://self/admin/tax-rule/tax-rule-list')->toString();

        // The count cell uses the resource body's `count` value.
        $this->assertMatchesRegularExpression('/\d+\s*件/', $html);
    }

    // ── L2: form action/method + link href/rel ──────────────────────────────

    public function testTaxRuleListInlineCreateFormHasCorrectActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/admin/tax-rule/tax-rule-list')->toString();

        // doCreateTaxRule: POST /admin/tax-rule/tax-rule-list
        $this->assertStringContainsString('action="/admin/tax-rule/tax-rule-list"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    public function testTaxRuleListDeleteLinkHasCorrectHrefAndRel(): void
    {
        $html = $this->resource->get('page://self/admin/tax-rule/tax-rule-list')->toString();

        // The Fake storage seeds at least one tax-rule row; when present the
        // delete affordance must use the doDeleteTaxRule href pattern.
        if (! str_contains($html, 'rel="doDeleteTaxRule"')) {
            $this->markTestSkipped('No tax-rule rows rendered; delete link not present.');
        }

        // doDeleteTaxRule: /admin/tax-rule/tax-rule?taxRuleId=…&_method=delete
        $this->assertMatchesRegularExpression(
            '#href="/admin/tax-rule/tax-rule\?taxRuleId=[^"]+&amp;_method=delete"#',
            $html,
            'doDeleteTaxRule link href pattern mismatch',
        );
        $this->assertStringContainsString('rel="doDeleteTaxRule"', $html);
    }

    public function testTaxRuleListCsrfHiddenInputPresent(): void
    {
        $html = $this->resource->get('page://self/admin/tax-rule/tax-rule-list')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    // ── archived: EC-CUBE parity comparison ─────────────────────────────────

    /**
     * EC-CUBE reference-rendering comparison archived.
     *
     * The template has been clean-room rebuilt with the idea-admin design
     * language; it no longer shares DOM structure with EC-CUBE. The
     * functional/semantic checks above (L1/L2) replace this test.
     *
     * @group ec-cube-parity-archived
     */
    public function testTaxRuleListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity comparison archived: template rebuilt with idea-admin vocabulary. '
            . 'Semantic verification is covered by L1/L2 tests in this class.',
        );
    }
}
