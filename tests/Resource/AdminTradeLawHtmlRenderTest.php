<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * Phase 3 — HTML render verification for admin 特定商取引法設定.
 *
 * Checks three semantic layers:
 *   L1 — Required data surfaces in the output
 *         (page title, CSRF token slot, required form fields for row 1)
 *   L2 — Action contract: action URI, HTTP method, hidden mode field
 *   Frame — landmark classes: idea-admin-shell, idea-admin-content
 *
 * EC-CUBE markup parity assertions (c-headerBar, c-contentsArea,
 * tradeLawRow_1, Bootstrap classes) are retired — they tested an
 * EC-CUBE clone, not the BeMart idea-admin design.
 *
 * @group html-render
 */
final class AdminTradeLawHtmlRenderTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeAdminSession(self::TEST_ADMIN_ID);
        $injector = HtmlTestInjector::getOverrideInstance(
            new class ($session) extends AbstractModule {
                public function __construct(
                    private readonly FakeAdminSession $session,
                ) {
                    parent::__construct();
                }

                protected function configure(): void
                {
                    $this->bind(AdminSession::class)->toInstance($this->session);
                }
            }
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /** Smoke: resource returns 200 with HTML content-type. */
    public function testRendersWithOkStatus(): void
    {
        $ro = $this->resource->get('page://self/admin/trade-law');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * L1 — Required data surfaces.
     *
     * Verifies that the rendered page contains the document skeleton and
     * the key data elements derived from the resource body contract:
     *   - Standard HTML document markers
     *   - Page title text
     *   - CSRF token input (name attribute present)
     *   - Form fields for the first trade-law row (name + description inputs,
     *     plus the displayOrderScreen checkbox field name)
     */
    public function testL1RequiredDataSurfaces(): void
    {
        $html = $this->resource->get('page://self/admin/trade-law')->toString();

        $this->assertStringContainsString('<!doctype html>', $html, 'document type');
        $this->assertStringContainsString('<html lang="ja">', $html, 'html element with lang');
        $this->assertStringContainsString('</body>', $html, 'body closing tag');

        // Page title
        $this->assertStringContainsString('特定商取引法設定', $html, 'page title text');

        // CSRF token field
        $this->assertStringContainsString('name="csrfToken"', $html, 'CSRF token input');

        // Form fields for the first row (driven by AdminTradeLawForm::fieldPrefix(1))
        $this->assertStringContainsString('trade_law_1_name', $html, 'name field for row 1');
        $this->assertStringContainsString('trade_law_1_description', $html, 'description field for row 1');
        $this->assertStringContainsString('trade_law_1_displayOrderScreen', $html, 'visibility checkbox for row 1');
    }

    /**
     * L2 — Action and method contract.
     *
     * The form must target the correct endpoint with POST and include the
     * mode discriminator required by onPost to trigger a redirect (303).
     * The goContentCss link (page://self/admin/content/css) must also appear.
     */
    public function testL2ActionAndMethodContract(): void
    {
        $html = $this->resource->get('page://self/admin/trade-law')->toString();

        // POST action to the correct endpoint
        $this->assertStringContainsString('action="/admin/trade-law"', $html, 'form action URI');
        $this->assertStringContainsString('method="post"', $html, 'form method POST');

        // mode hidden field required by onPost to issue 303 redirect
        $this->assertStringContainsString('name="mode"', $html, 'mode hidden field present');
        $this->assertStringContainsString('value="trade_law_form"', $html, 'mode=trade_law_form value');

        // goContentCss link (rel declared in #[Link])
        $this->assertStringContainsString('/admin/content/css', $html, 'goContentCss link href present');
    }

    /**
     * Frame — landmark classes.
     *
     * The page must be rendered inside the idea-admin design-system shell.
     * These are structural landmarks used by accessibility tools and tests
     * that verify visual regression at the shell level.
     */
    public function testFrameLandmarkClasses(): void
    {
        $html = $this->resource->get('page://self/admin/trade-law')->toString();

        $this->assertStringContainsString('idea-admin-shell', $html, 'shell landmark');
        $this->assertStringContainsString('idea-admin-content', $html, 'content landmark');
        $this->assertStringContainsString('idea-admin-toolbar', $html, 'action toolbar');
    }
}
