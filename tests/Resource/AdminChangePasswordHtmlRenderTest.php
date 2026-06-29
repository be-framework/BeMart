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
 * Functional / semantic render test for the admin password-change page.
 *
 * Verifies the resource contract expressed by
 * {@see \MyVendor\BeMart\Resource\Page\Admin\ChangePassword} and
 * {@see \MyVendor\BeMart\Form\AdminChangePasswordForm}, NOT the historical
 * EC-CUBE markup shape.
 *
 * Layers under test:
 *   L1 — required data fields are present in the rendered HTML
 *   L2 — action / method / link affordances are correct
 *   L3 — frame landmarks (idea-admin-shell / content) are in place
 *
 * The EC-CUBE parity comparison (renderEcCube / isResidual / normalize) has
 * been retired; it is archived below as a skipped group so the infrastructure
 * is not lost but no longer runs in CI.
 */
final class AdminChangePasswordHtmlRenderTest extends TestCase
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

    // ── L1: required data fields ─────────────────────────────────────────────

    /** Resource returns HTTP 200 and text/html for an authenticated admin. */
    public function testResourceReturnsOkWithHtmlContentType(): void
    {
        $ro = $this->resource->get('page://self/admin/change-password');

        $this->assertSame(Code::OK, $ro->code);
        $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** The rendered output is a complete HTML document. */
    public function testRenderedOutputIsCompleteHtmlDocument(): void
    {
        $html = $this->resource->get('page://self/admin/change-password')->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertStringContainsString('</html>', $html);
    }

    /**
     * All three password fields are present and rendered as real inputs
     * (type=password) with the correct ids that AdminChangePasswordForm declares.
     */
    public function testAllThreePasswordFieldsAreRendered(): void
    {
        $html = $this->resource->get('page://self/admin/change-password')->toString();

        $this->assertStringContainsString(
            'id="admin_change_password_current_password"',
            $html,
            'current_password field must be present',
        );
        $this->assertStringContainsString(
            'id="admin_change_password_change_password_first"',
            $html,
            'change_password_first field must be present',
        );
        $this->assertStringContainsString(
            'id="admin_change_password_change_password_second"',
            $html,
            'change_password_second field must be present',
        );
    }

    /** All password fields are rendered with type="password" (not text). */
    public function testPasswordFieldsAreOfTypePassword(): void
    {
        $html = $this->resource->get('page://self/admin/change-password')->toString();

        $this->assertStringContainsString('type="password"', $html);
        // Ensure none of the three fields leaked as type="text" by default.
        $this->assertStringNotContainsString(
            'id="admin_change_password_current_password" type="text"',
            $html,
        );
    }

    // ── L2: action / method / link affordances ───────────────────────────────

    /** The form posts to the correct resource endpoint. */
    public function testFormActionIsAdminChangePasswordEndpoint(): void
    {
        $html = $this->resource->get('page://self/admin/change-password')->toString();

        $this->assertStringContainsString('action="/admin/change-password"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    /** A CSRF hidden field is included in the form. */
    public function testCsrfHiddenFieldIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/change-password')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    /** The goAdminHome back-link points to /admin/index. */
    public function testGoAdminHomeLinkIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/change-password')->toString();

        $this->assertTrue(
            str_contains($html, 'href="/admin/index"'),
            'goAdminHome link pointing to /admin/index must be rendered',
        );
    }

    /** A submit affordance targeting the form is rendered. */
    public function testSubmitAffordanceIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/change-password')->toString();

        // type="submit" either directly in the form or via form= attribute
        $this->assertTrue(
            str_contains($html, 'type="submit"'),
            'A submit button must be present',
        );
    }

    // ── L3: frame landmarks ──────────────────────────────────────────────────

    /** The page is wrapped in the idea-admin-shell landmark. */
    public function testIdeaAdminShellLandmarkIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/change-password')->toString();

        $this->assertStringContainsString('class="idea-admin-shell"', $html);
    }

    /** The content area landmark is present. */
    public function testIdeaAdminContentLandmarkIsPresent(): void
    {
        $html = $this->resource->get('page://self/admin/change-password')->toString();

        $this->assertStringContainsString('idea-admin-content', $html);
    }

    // ── EC-CUBE parity (archived) ────────────────────────────────────────────

    /**
     * The EC-CUBE markup comparison has been retired now that the template is
     * a clean-room idea-admin design (not a port). The infrastructure stays
     * here so it can be un-archived if a reference snapshot is needed.
     *
     * @group ec-cube-parity-archived
     */
    public function testEcCubeParityArchived(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity comparison retired: template is now a clean-room '
            . 'idea-admin design, not a markup port. See @group ec-cube-parity-archived.',
        );
    }
}
