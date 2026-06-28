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
 * HTML render checks for the admin 支払方法設定（編集／新規）page.
 *
 * L1 — required data / field output present in the rendered HTML.
 * L2 — action/method affordances correct (form action, _method, rel, href).
 * L3 — structural landmarks present (idea-admin-shell, idea-admin-content).
 */
final class AdminPaymentEditHtmlRenderTest extends TestCase
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

    // ── L3: Frame landmarks ─────────────────────────────────────────────────

    /** @test */
    public function frameIsWellFormedHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/admin/payment/payment');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** @test */
    public function frameContainsIdeaAdminShellAndContentLandmarks(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html);
        $this->assertStringContainsString('class="idea-admin-content"', $html);
    }

    // ── L1: Required data / field output (create mode) ──────────────────────

    /** @test */
    public function createModeRendersAllRequiredFormFields(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment')->toString();

        foreach (['paymentMethodName', 'charge', 'ruleMin', 'ruleMax'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html, "field missing: {$field}");
        }
        // visible is a checkbox; the form helper renders name="visible[]"
        $this->assertStringContainsString('id="payment_visible"', $html, 'visible checkbox missing');
    }

    /** @test */
    public function createModeRendersFormIdAndTitle(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment')->toString();

        $this->assertStringContainsString('id="payment_create_form"', $html);
        $this->assertStringContainsString('支払方法登録', $html);
    }

    // ── L2: Action / method affordances (create mode) ───────────────────────

    /** @test */
    public function createModeFormPostsToPaymentListCollection(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment')->toString();

        $this->assertStringContainsString('action="/admin/payment/payment-list"', $html);
        $this->assertStringNotContainsString('_method" value="put"', $html);
        $this->assertStringNotContainsString('_method" value="delete"', $html);
    }

    // ── L1: Required data / field output (edit mode) ────────────────────────

    /** @test */
    public function editModeRendersAllRequiredFormFields(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment', [
            'paymentId' => 'pay-credit',
        ])->toString();

        foreach (['paymentMethodName', 'charge', 'ruleMin', 'ruleMax'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html, "field missing: {$field}");
        }
        // visible is a checkbox; the form helper renders name="visible[]"
        $this->assertStringContainsString('id="payment_visible"', $html, 'visible checkbox missing');
    }

    /** @test */
    public function editModeRendersFormIdAndTitle(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment', [
            'paymentId' => 'pay-credit',
        ])->toString();

        $this->assertStringContainsString('id="payment_edit_form"', $html);
        $this->assertStringContainsString('支払方法編集', $html);
    }

    /** @test */
    public function editModeRendersPaymentIdInHiddenField(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment', [
            'paymentId' => 'pay-credit',
        ])->toString();

        $this->assertStringContainsString('name="paymentId" value="pay-credit"', $html);
    }

    // ── L2: Action / method affordances (edit mode) ─────────────────────────

    /** @test */
    public function editModeFormExposesMethodOverridePut(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment', [
            'paymentId' => 'pay-credit',
        ])->toString();

        $this->assertStringContainsString(
            'action="/admin/payment/payment?paymentId=pay-credit&amp;_method=put"',
            $html,
        );
        $this->assertStringContainsString('name="_method" value="put"', $html);
    }

    /** @test */
    public function editModeDeleteFormExposesMethodOverrideDelete(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment', [
            'paymentId' => 'pay-credit',
        ])->toString();

        $this->assertStringContainsString(
            'action="/admin/payment/payment?paymentId=pay-credit&amp;_method=delete"',
            $html,
        );
        $this->assertStringContainsString('name="_method" value="delete"', $html);
    }

    /** @test */
    public function editModeToolbarBackLinkGoesToPaymentList(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment', [
            'paymentId' => 'pay-credit',
        ])->toString();

        $this->assertStringContainsString('href="/admin/payment/payment-list"', $html);
    }

    /** @test */
    public function createModeToolbarBackLinkGoesToPaymentList(): void
    {
        $html = $this->resource->get('page://self/admin/payment/payment')->toString();

        $this->assertStringContainsString('href="/admin/payment/payment-list"', $html);
    }
}
