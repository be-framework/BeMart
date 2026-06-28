<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

use function str_contains;

/**
 * HTML render tests for the WithdrawConfirm page (goMypageWithdrawConfirm).
 *
 * Verifies the IdeaStore clean-room implementation of the withdrawal
 * final-confirmation screen at page://self/mypage/withdraw-confirm.
 *
 * L1 — required fields and data output:
 *   - HTTP 200 with Content-Type text/html; charset=utf-8
 *   - Valid HTML document shell (doctype, lang=ja, body close)
 *   - IdeaStore layout landmarks present
 *   - Title block contains the Japanese page title
 *   - csrfToken hidden input rendered (value may be empty in test context)
 *
 * L2 — form action/method and link href/rel:
 *   - POST form action targets /mypage/withdraw  (doWithdrawCustomer)
 *   - Cancel link href targets /mypage            (goMypage)
 *   - Account nav contains link to /mypage/withdraw marked active
 *   - Breadcrumb includes /mypage and /mypage/withdraw
 */
final class MypageWithdrawConfirmHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /** L1: page renders as a valid HTML document with HTTP 200. */
    public function testWithdrawConfirmPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/withdraw-confirm');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** L1: IdeaStore layout shell and page title are present. */
    public function testWithdrawConfirmPageHasIdeaStoreLayout(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-confirm')->toString();

        $this->assertStringContainsString('class="idea-store"', $html);
        $this->assertStringContainsString('IDEA STORE', $html);
        $this->assertStringContainsString('退会手続き', $html);
    }

    /** L1: CSRF hidden input is present with the correct field name. */
    public function testWithdrawConfirmPageHasCsrfTokenField(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-confirm')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
        $this->assertTrue(
            str_contains($html, 'type="hidden"'),
            'csrfToken must be a hidden input',
        );
    }

    /** L2: Submit form targets /mypage/withdraw with method POST (doWithdrawCustomer). */
    public function testWithdrawConfirmFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-confirm')->toString();

        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('action="/mypage/withdraw"', $html);
    }

    /** L2: Cancel link points to /mypage (goMypage). */
    public function testWithdrawConfirmCancelLinkHref(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-confirm')->toString();

        $this->assertStringContainsString('href="/mypage"', $html);
    }

    /** L2: Account navigation includes the withdraw entry as the active item. */
    public function testWithdrawConfirmAccountNavActiveEntry(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-confirm')->toString();

        $this->assertStringContainsString('href="/mypage/withdraw"', $html);
        $this->assertStringContainsString('is-active', $html);
    }

    /** L2: Breadcrumb includes /mypage and /mypage/withdraw links. */
    public function testWithdrawConfirmBreadcrumb(): void
    {
        $html = $this->resource->get('page://self/mypage/withdraw-confirm')->toString();

        $this->assertStringContainsString('href="/mypage"', $html);
        $this->assertStringContainsString('href="/mypage/withdraw"', $html);
        $this->assertStringContainsString('idea-breadcrumb', $html);
    }

    /**
     * EC-CUBE parity comparison — archived.
     *
     * The template is now a clean-room IdeaStore implementation;
     * pixel-level parity with EC-CUBE's default theme no longer applies.
     *
     * @group ec-cube-parity-archived
     */
    public function testWithdrawConfirmHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity check retired: template rebuilt in IdeaStore design language.',
        );
    }
}
