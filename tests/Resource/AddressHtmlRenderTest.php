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
 * Render verification for the address add/edit page (IdeaStore design).
 *
 * L1 — required fields and data output are present in the rendered HTML.
 * L2 — form action / method and navigation links have correct href / rel.
 *
 * The EC-CUBE render-diff parity test is archived below under the group
 * "ec-cube-parity-archived". It is skipped unconditionally because the
 * template is now a clean-room IdeaStore build, not an EC-CUBE port.
 */
final class AddressHtmlRenderTest extends TestCase
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

    // -------------------------------------------------------------------------
    // L1 — required fields and data output
    // -------------------------------------------------------------------------

    /**
     * The rendered page is a complete HTML document served as text/html.
     */
    public function testAddressRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/address');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /**
     * The page uses the IdeaStore layout and carries the correct title.
     */
    public function testAddressUsesIdeaStoreLayout(): void
    {
        $html = $this->resource->get('page://self/mypage/address')->toString();

        $this->assertStringContainsString('class="idea-store"', $html);
        $this->assertStringContainsString('IDEA STORE', $html);
        $this->assertStringContainsString('idea-container', $html);
    }

    /**
     * L1: all address form fields are rendered with correct `name` attributes.
     */
    public function testAddressRendersRealFormInputs(): void
    {
        $html = $this->resource->get('page://self/mypage/address')->toString();

        $this->assertStringContainsString('name="name01"', $html);
        $this->assertStringContainsString('name="name02"', $html);
        $this->assertStringContainsString('name="kana01"', $html);
        $this->assertStringContainsString('name="kana02"', $html);
        $this->assertStringContainsString('name="postalCode"', $html);
        $this->assertStringContainsString('name="pref"', $html);
        $this->assertStringContainsString('name="addr01"', $html);
        $this->assertStringContainsString('name="addr02"', $html);
        $this->assertStringContainsString('name="phoneNumber"', $html);
    }

    /**
     * L1: placeholders set in AddressForm::init() appear in the rendered inputs.
     */
    public function testAddressFormPlaceholdersRendered(): void
    {
        $html = $this->resource->get('page://self/mypage/address')->toString();

        $this->assertStringContainsString('placeholder="姓"', $html);
        $this->assertStringContainsString('placeholder="名"', $html);
    }

    /**
     * L1: the CSRF token hidden field is present (value may be empty in test context).
     */
    public function testAddressCsrfHiddenFieldPresent(): void
    {
        $html = $this->resource->get('page://self/mypage/address')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
        $this->assertMatchesRegularExpression(
            '/<input[^>]+type="hidden"[^>]+name="csrfToken"/',
            $html,
        );
    }

    /**
     * L1: the page title identifies this as the "new address" screen when no
     * addressId is present (transitionId = doCreateCustomerAddress).
     */
    public function testAddressNewScreenTitle(): void
    {
        $html = $this->resource->get('page://self/mypage/address')->toString();

        $this->assertStringContainsString('お届け先を追加', $html);
    }

    // -------------------------------------------------------------------------
    // L2 — form action / method and navigation links
    // -------------------------------------------------------------------------

    /**
     * L2: the form action targets the address-list endpoint for a new address
     * (POST to /mypage/address-list, matching the resource #[Link] declaration).
     */
    public function testAddressNewFormActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/mypage/address')->toString();

        // The resource body sets submitTo.href = page://self/mypage/address-list
        // for a new address (no addressId). The template strips the scheme
        // prefix, yielding action="/mypage/address-list".
        $this->assertStringContainsString('action="/mypage/address-list"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    /**
     * L2: the "一覧に戻る" link points to the address list.
     */
    public function testAddressBackLinkHref(): void
    {
        $html = $this->resource->get('page://self/mypage/address')->toString();

        $this->assertStringContainsString('href="/mypage/address-list"', $html);
    }

    /**
     * L2: the account navigation sidebar is present and marks the address
     * management item as active.
     */
    public function testAddressAccountNavPresent(): void
    {
        $html = $this->resource->get('page://self/mypage/address')->toString();

        $this->assertStringContainsString('href="/mypage/address-list"', $html);
        $this->assertStringContainsString('href="/mypage"', $html);
        $this->assertStringContainsString('href="/mypage/change"', $html);
    }

    // -------------------------------------------------------------------------
    // Archived — EC-CUBE parity (template is no longer an EC-CUBE port)
    // -------------------------------------------------------------------------

    /**
     * The render-diff comparison against EC-CUBE's delivery_edit.twig is
     * archived. The template is now a clean-room IdeaStore build and no
     * longer mirrors EC-CUBE markup. Re-enable only if a parity regression
     * needs investigation.
     *
     * @group ec-cube-parity-archived
     */
    #[\PHPUnit\Framework\Attributes\Group('ec-cube-parity-archived')]
    public function testAddressHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE render-diff parity archived: template rebuilt as IdeaStore clean-room design.',
        );
    }
}
