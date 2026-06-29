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

use function assert;
use function is_string;

/**
 * Phase 3 — functional/semantic render check for the address book list
 * (goCustomerAddressList) IdeaStore HTML port.
 *
 * L1 assertions: required data fields are rendered in the document.
 * L2 assertions: edit/delete links carry the correct href/rel contracts;
 *                delete path uses a POST form with _method=delete.
 *
 * The EC-CUBE markup-parity test
 * {@see testAddressListHtmlMatchesEcCubeRenderingWithinResidualAllowlist}
 * is archived below (@group ec-cube-parity-archived) as the template is
 * now an independent IdeaStore design rather than a port of EC-CUBE's
 * default theme.
 */
final class AddressListHtmlRenderTest extends TestCase
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

    /** L0 — document is valid HTML with IdeaStore base structure. */
    public function testRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/address-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        assert(is_string($html));

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** L0 — page extends IdeaStore base layout (no ec-layoutRole frame). */
    public function testUsesIdeaStoreLayout(): void
    {
        $html = $this->resource->get('page://self/mypage/address-list')->toString();
        assert(is_string($html));

        $this->assertStringContainsString('idea-store', $html);
        $this->assertStringContainsString('IDEA STORE', $html);
        $this->assertStringNotContainsString('ec-layoutRole', $html);
        $this->assertStringNotContainsString('ec-mypageRole', $html);
    }

    /**
     * L1 — required data fields appear in the rendered document.
     *
     * The fixture for ALICE_ID seeds at least one address row via
     * FakeAddressStorage; the assertions verify those fields are output.
     */
    public function testRequiredAddressFieldsAreRendered(): void
    {
        $html = $this->resource->get('page://self/mypage/address-list')->toString();
        assert(is_string($html));

        // Page identity
        $this->assertStringContainsString('お届け先の管理', $html, 'page heading');

        // Address fields from FakeAddressStorage seed (ALICE_ID)
        $this->assertStringContainsString('山田', $html, 'name01');
        $this->assertStringContainsString('アリス', $html, 'name02');
        $this->assertStringContainsString('1500001', $html, 'postalCode');
        $this->assertStringContainsString('渋谷区', $html, 'addr01');
    }

    /**
     * L2 — form action and link href/method contracts match resource layer.
     *
     * Resource declares:
     *   #[Link(rel: 'doUpdateCustomerAddress', href: 'page://self/mypage/address', method: 'put')]
     *   #[Link(rel: 'doDeleteCustomerAddress', href: 'page://self/mypage/address', method: 'delete')]
     *   #[Link(rel: 'doCreateCustomerAddress', href: 'page://self/mypage/address-list', method: 'post')]
     */
    public function testLinkContractsMatchResourceLayer(): void
    {
        $html = $this->resource->get('page://self/mypage/address-list')->toString();
        assert(is_string($html));

        // Edit link targets /mypage/address with the seed addressId
        $this->assertStringContainsString(
            'href="/mypage/address?addressId=addr00000000000000000000000000a1"',
            $html,
            'edit link href with addressId',
        );

        // Delete uses POST form with _method=delete tunnelling (REST over HTML forms)
        $this->assertStringContainsString(
            'action="/mypage/address"',
            $html,
            'delete form action',
        );
        $this->assertStringContainsString(
            'name="_method" value="delete"',
            $html,
            'HTTP method override: delete',
        );
        $this->assertStringContainsString(
            'name="addressId" value="addr00000000000000000000000000a1"',
            $html,
            'delete form carries addressId',
        );

        // Add-new link targets /mypage/address (doCreateCustomerAddress entry)
        $this->assertStringContainsString(
            'href="/mypage/address"',
            $html,
            'add-new address link',
        );
    }

    /** L1 — account navigation is present with correct links. */
    public function testAccountNavigationLinksArePresent(): void
    {
        $html = $this->resource->get('page://self/mypage/address-list')->toString();
        assert(is_string($html));

        $this->assertStringContainsString('href="/mypage"', $html, 'link to order history');
        $this->assertStringContainsString('href="/mypage/favorite-list"', $html, 'link to favourites');
        $this->assertStringContainsString('href="/mypage/change"', $html, 'link to profile edit');
        $this->assertStringContainsString('href="/mypage/address-list"', $html, 'link to address list (self)');
        $this->assertStringContainsString('href="/mypage/withdraw"', $html, 'link to withdrawal');
    }

    /**
     * EC-CUBE markup-parity test — archived.
     *
     * This test compared BeMart's rendered output to EC-CUBE 4.3's
     * default-theme delivery.twig rendering. Now that AddressList.html.twig
     * is an independent IdeaStore design (no ec-* classes), parity with
     * EC-CUBE's DOM is neither meaningful nor achievable.
     *
     * @group ec-cube-parity-archived
     */
    public function testAddressListHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup-parity archived: template is now an independent '
            . 'IdeaStore design. Functional/semantic coverage is provided by '
            . 'testRequiredAddressFieldsAreRendered() and testLinkContractsMatchResourceLayer().',
        );
    }
}
