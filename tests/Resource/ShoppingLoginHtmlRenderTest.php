<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;


/**
 * Phase 3 — functional / semantic verification for the Shopping login
 * (goShoppingLogin) IdeaStore HTML port.
 *
 * L1 (required fields / data output) and L2 (form action/method, link href/rel)
 * assertions confirm that the IdeaStore template satisfies the contracts
 * declared by:
 *
 *  - {@see \MyVendor\BeMart\Resource\Page\Shopping\Login} #[Link] annotations
 *  - {@see \MyVendor\BeMart\Form\LoginForm} field definitions (email, password)
 *  - get-shopping-login.json JSON Schema
 *
 * EC-CUBE markup-parity assertions have been archived below — they are no
 * longer meaningful after the IdeaStore clean-room rebuild.
 */
final class ShoppingLoginHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -----------------------------------------------------------------------
    // L0 — document well-formedness
    // -----------------------------------------------------------------------

    public function testShoppingLoginRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/shopping/login');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // -----------------------------------------------------------------------
    // L1 — required fields / data output
    // -----------------------------------------------------------------------

    /** The page title must identify the checkout entry point in Japanese. */
    public function testPageTitleContainsJapaneseCheckoutLabel(): void
    {
        $html = $this->resource->get('page://self/shopping/login')->toString();

        $this->assertStringContainsString('購入', $html);
        $this->assertStringContainsString('IDEA STORE', $html);
    }

    /** Both email and password inputs must be present for the login form. */
    public function testShoppingLoginRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/shopping/login')->toString();

        // email field — id and name come from LoginForm::init()
        $this->assertStringContainsString('id="email"', $html);
        $this->assertStringContainsString('name="email"', $html);

        // password field
        $this->assertStringContainsString('id="password"', $html);
        $this->assertStringContainsString('type="password"', $html);
    }

    /** The guest purchase path must be offered as a distinct call-to-action. */
    public function testGuestCheckoutLinkIsPresent(): void
    {
        $html = $this->resource->get('page://self/shopping/login')->toString();

        // rel=goShoppingNonMember → page://self/shopping/non-member → /shopping/non-member
        $this->assertStringContainsString('/shopping/non-member', $html);
        $this->assertStringContainsString('ゲスト', $html);
    }

    // -----------------------------------------------------------------------
    // L2 — form action/method and link href/rel contracts
    // -----------------------------------------------------------------------

    /**
     * The login form must POST to /login.
     *
     * Derived from #[Link(rel: 'doLogin', href: 'page://self/login', method: 'post')]
     * in {@see \MyVendor\BeMart\Resource\Page\Shopping\Login}.
     */
    public function testLoginFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/shopping/login')->toString();

        $this->assertMatchesRegularExpression(
            '/method=["\']post["\']/i',
            $html,
            'Login form must use POST method',
        );
        $this->assertStringContainsString('action="/login"', $html);
    }

    /**
     * The registration link must point to /entry.
     *
     * Derived from #[Link(rel: 'goCustomerRegistration', href: 'page://self/entry')]
     * in {@see \MyVendor\BeMart\Resource\Page\Shopping\Login}.
     */
    public function testRegistrationLinkHref(): void
    {
        $html = $this->resource->get('page://self/shopping/login')->toString();

        $this->assertStringContainsString('href="/entry"', $html);
    }

    /**
     * The guest-purchase link must point to /shopping/non-member.
     *
     * Derived from #[Link(rel: 'goShoppingNonMember', href: 'page://self/shopping/non-member')]
     * in {@see \MyVendor\BeMart\Resource\Page\Shopping\Login}.
     */
    public function testGuestLinkHref(): void
    {
        $html = $this->resource->get('page://self/shopping/login')->toString();

        $this->assertStringContainsString('href="/shopping/non-member"', $html);
    }

    /** The hidden _target_path input must redirect to /shopping after login. */
    public function testLoginTargetPathHiddenField(): void
    {
        $html = $this->resource->get('page://self/shopping/login')->toString();

        $this->assertStringContainsString('name="_target_path"', $html);
        $this->assertStringContainsString('value="/shopping"', $html);
    }

    // -----------------------------------------------------------------------
    // @group ec-cube-parity-archived
    // EC-CUBE markup-parity assertions — archived after IdeaStore rebuild.
    // These tests matched BeMart's output against EC-CUBE 4.3 default-theme
    // rendering. The IdeaStore template uses idea-* vocabulary, so pixel-
    // level parity with EC-CUBE is no longer a goal.
    // -----------------------------------------------------------------------

    /** @group ec-cube-parity-archived */
    public function testShoppingLoginPreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup parity archived: IdeaStore clean-room rebuild uses idea-* '
            . 'vocabulary instead of ec-* classes. Functional contracts are verified '
            . 'by L1/L2 tests above.',
        );
    }

    /** @group ec-cube-parity-archived */
    public function testShoppingLoginHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE render diff archived: IdeaStore clean-room rebuild. '
            . 'Structural contracts (form action, field names, link hrefs) '
            . 'are covered by L2 tests in this class.',
        );
    }
}
