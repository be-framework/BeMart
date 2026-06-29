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

use function str_contains;

/**
 * Semantic render verification for the favorites list (goFavoriteList) HTML page.
 *
 * L1 — Required fields and data output: the rendered HTML must contain
 *      the product name, unit price, and product code from the fixture
 *      favorite row.
 *
 * L2 — Form action / method and link href / rel: the delete form must
 *      target POST /mypage/favorite with _method=delete and the CSRF
 *      token field. Product links must point to /product?productCode=…
 *
 * The FavoriteList resource requires AUTHN, so CustomerSession is rebound
 * to the alice fixture (customer ID 'favorite-html-customer'). The fake
 * storage returns one favorite row: sample-001 / サンプル商品 A / ¥1,200.
 */
final class FavoriteListHtmlRenderTest extends TestCase
{
    private const ALICE_ID = 'favorite-html-customer';

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

    /** L1: response is HTTP 200 and renders a complete HTML document. */
    public function testRendersCompleteHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/mypage/favorite-list');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    /** L1: page title contains the IdeaStore brand. */
    public function testTitleContainsIdeaStoreBrand(): void
    {
        $html = $this->resource->get('page://self/mypage/favorite-list')->toString();

        $this->assertStringContainsString('IDEA STORE', $html);
    }

    /** L1: fixture favorite product name is rendered. */
    public function testFavoriteProductNameIsRendered(): void
    {
        $html = $this->resource->get('page://self/mypage/favorite-list')->toString();

        // Fixture row from be/var/fake/query/favorite_list.jsonl (alice)
        $this->assertStringContainsString('サンプル商品 A', $html);
    }

    /** L1: fixture favorite unit price is rendered. */
    public function testFavoriteUnitPriceIsRendered(): void
    {
        $html = $this->resource->get('page://self/mypage/favorite-list')->toString();

        // unitPrice=1200 — rendered as ¥1,200
        $this->assertTrue(
            str_contains($html, '1,200') || str_contains($html, '1200'),
            'Expected unit price (1200) not found in rendered HTML',
        );
    }

    /** L1: fixture product code appears in the rendered page. */
    public function testFavoriteProductCodeIsRendered(): void
    {
        $html = $this->resource->get('page://self/mypage/favorite-list')->toString();

        $this->assertStringContainsString('sample-001', $html);
    }

    /** L2: delete form posts to /mypage/favorite with _method=delete. */
    public function testDeleteFormTargetsCorrectActionAndMethod(): void
    {
        $html = $this->resource->get('page://self/mypage/favorite-list')->toString();

        $this->assertStringContainsString('action="/mypage/favorite"', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('name="_method"', $html);
        $this->assertStringContainsString('value="delete"', $html);
    }

    /** L2: delete form carries a CSRF token field (field name: csrfToken, per CsrfProtected annotation). */
    public function testDeleteFormHasCsrfTokenField(): void
    {
        $html = $this->resource->get('page://self/mypage/favorite-list')->toString();

        $this->assertStringContainsString('name="csrfToken"', $html);
    }

    /** L2: product link href points to /product?productCode=<code>. */
    public function testProductLinkHrefContainsProductCode(): void
    {
        $html = $this->resource->get('page://self/mypage/favorite-list')->toString();

        $this->assertStringContainsString('/product?productCode=sample-001', $html);
    }

    /** L2: page contains a navigation link back to /mypage. */
    public function testBackToMypageLinkIsPresent(): void
    {
        $html = $this->resource->get('page://self/mypage/favorite-list')->toString();

        $this->assertStringContainsString('href="/mypage"', $html);
    }

    /**
     * EC-CUBE layout parity check — archived.
     *
     * This test verified that BeMart's HTML matched EC-CUBE 4.3's default-
     * theme `Mypage/favorite.twig` within a small residual allowlist.  The
     * FavoriteList template has been rebuilt as an IdeaStore clean-room
     * design and no longer targets EC-CUBE markup parity.
     *
     * @group ec-cube-parity-archived
     */
    public function testFavoriteHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity check retired: FavoriteList.html.twig is now an '
            . 'IdeaStore clean-room design (idea-* classes) and does not target '
            . 'ec-* markup compatibility. Functional coverage is provided by the '
            . 'L1/L2 tests in this class.',
        );
    }

    /**
     * EC-CUBE markup structure check — archived.
     *
     * Previously asserted the presence of ec-favoriteRole, ec-closeBtn--circle,
     * token-for-anchor, data-method="delete" etc.  These are EC-CUBE-specific
     * artefacts replaced by the IdeaStore design language.
     *
     * @group ec-cube-parity-archived
     */
    public function testFavoritePreservesEcCubeMarkupStructure(): void
    {
        $this->markTestSkipped(
            'EC-CUBE markup structure assertions retired: FavoriteList.html.twig '
            . 'no longer uses ec-* classes. Equivalent structural coverage is '
            . 'provided by testRendersCompleteHtmlDocument and the L2 form/link tests.',
        );
    }
}
