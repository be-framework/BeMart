<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

/**
 * Semantic/functional render checks for the profile-edit (mypage_change)
 * IdeaStore HTML page.
 *
 * L1 — Required fields and data output: asserts that the rendered HTML
 * contains the expected form inputs pre-populated with the logged-in
 * customer's profile, and key page landmarks.
 *
 * L2 — Form action/method and navigation links: asserts the form posts
 * to the correct endpoint and that key rel-links are present.
 *
 * The EC-CUBE DOM parity test (byte-level diff against EC-CUBE 4.3's own
 * rendering) is archived below — it is no longer meaningful because the
 * template is now built in the IdeaStore design language (idea-* classes),
 * not in EC-CUBE's (ec-* classes).
 */
final class ChangeHtmlRenderTest extends TestCase
{
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeSession(self::ALICE_ID);
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
            }
        });
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -----------------------------------------------------------------------
    // L1 — Required fields and data output
    // -----------------------------------------------------------------------

    public function testRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/change');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    public function testPageTitleContainsStoreName(): void
    {
        $html = $this->resource->get('page://self/mypage/change')->toString();

        $this->assertStringContainsString('IDEA STORE', $html);
    }

    public function testRendersRequiredFormFields(): void
    {
        $html = $this->resource->get('page://self/mypage/change')->toString();

        foreach (['name01', 'name02', 'email', 'password', 'postalCode', 'addr01'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html, "required field missing: {$field}");
        }
    }

    public function testRendersOptionalFormFields(): void
    {
        $html = $this->resource->get('page://self/mypage/change')->toString();

        foreach (['kana01', 'kana02', 'companyName', 'phoneNumber', 'addr02',
            'email_confirm', 'password_confirm',
            'birth_year', 'birth_month', 'birth_day', 'sex', 'job'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html, "optional field missing: {$field}");
        }
    }

    public function testFormIsPrePopulatedWithCustomerProfile(): void
    {
        $html = $this->resource->get('page://self/mypage/change')->toString();

        // Fixture customer alice (ALICE_ID) is pre-loaded with these values
        // (pinned by ChangeResourceTest::testOnGetReturnsFormPrePopulated).
        $this->assertStringContainsString('value="山田"', $html, 'name01 pre-population missing');
        $this->assertStringContainsString('value="alice@example.com"', $html, 'email pre-population missing');
    }

    public function testPasswordFieldsAreNeverPrePopulated(): void
    {
        $html = $this->resource->get('page://self/mypage/change')->toString();

        // Password inputs must always be empty — never echoed from storage.
        $this->assertStringNotContainsString('name="password" value=', $html);
        $this->assertStringNotContainsString('name="password_confirm" value=', $html);
    }

    // -----------------------------------------------------------------------
    // L2 — Form action / method and navigation links
    // -----------------------------------------------------------------------

    public function testFormPostsToMypageChange(): void
    {
        $html = $this->resource->get('page://self/mypage/change')->toString();

        $this->assertMatchesRegularExpression(
            '#<form[^>]+method=["\']post["\'][^>]+action=["\'][^"\']*\/mypage\/change["\']|<form[^>]+action=["\'][^"\']*\/mypage\/change["\'][^>]+method=["\']post["\']#i',
            $html,
            'form must POST to /mypage/change',
        );
    }

    public function testFormContainsCsrfHiddenField(): void
    {
        $html = $this->resource->get('page://self/mypage/change')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    public function testNavigationLinksToMypage(): void
    {
        $html = $this->resource->get('page://self/mypage/change')->toString();

        $this->assertStringContainsString('href="/mypage"', $html, 'link back to /mypage missing');
    }

    public function testBreadcrumbPresent(): void
    {
        $html = $this->resource->get('page://self/mypage/change')->toString();

        $this->assertStringContainsString('href="/"', $html, 'breadcrumb root link missing');
        $this->assertStringContainsString('href="/mypage"', $html, 'breadcrumb mypage link missing');
    }

    // -----------------------------------------------------------------------
    // EC-CUBE DOM parity — archived
    //
    // The template now uses the IdeaStore design language (idea-* classes).
    // Byte-level diff against EC-CUBE 4.3's DOM is no longer meaningful.
    // -----------------------------------------------------------------------

    /**
     * @group ec-cube-parity-archived
     */
    public function testChangeHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE DOM parity archived: template rebuilt in IdeaStore design language (idea-* classes). '
            . 'Functional coverage is provided by the L1/L2 tests above.',
        );
    }
}
