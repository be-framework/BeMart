<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;

/**
 * IdeaStore cleanroom render test — Shopping shipping-multiple
 * (goShoppingShippingMultiple).
 *
 * The template was cleanroom-rebuilt in IdeaStore design language (idea-*
 * classes). EC-CUBE parity assertions are retired below.
 *
 * L1 — required fields and data output present in the rendered HTML.
 * L2 — form action/method and link href/rel semantics match the resource
 *      contract declared via #[Link] in ShippingMultiple.php.
 */
final class ShoppingShippingMultipleHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // ── L1: 必須フィールド / データ出力 ────────────────────────────────────

    /** ページが正常な HTML ドキュメントとしてレンダリングされる。 */
    public function testRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/shopping/shipping-multiple');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    /** ページタイトルに日本語タイトルが含まれる。 */
    public function testTitleContainsJaLabel(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-multiple')->toString();
        $this->assertStringContainsString('複数配送先', $html);
        $this->assertStringContainsString('IDEA STORE', $html);
    }

    /** IdeaStore の idea-* クラスで骨格が組まれている。 */
    public function testUsesIdeaStoreClasses(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-multiple')->toString();

        foreach ([
            'idea-container',
            'idea-checkout-hero',
            'idea-checkout-progress',
            'idea-button',
            'idea-eyebrow',
        ] as $class) {
            $this->assertStringContainsString($class, $html, "IdeaStore class missing: {$class}");
        }
    }

    /** EC-CUBE 固有クラスが含まれていない（cleanroom 確認）。 */
    public function testNoEcCubeClasses(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-multiple')->toString();

        foreach ([
            'ec-AddAddress',
            'ec-blockBtn',
            'ec-role',
            'ec-pageHeader',
        ] as $ecClass) {
            $this->assertStringNotContainsString($ecClass, $html, "EC-CUBE class found: {$ecClass}");
        }
    }

    /**
     * cartItems が空のとき「配送対象の商品がありません」フォールバックを表示する。
     * Resource の Wave-future 状態（cartItems = []）に対応。
     */
    public function testEmptyCartItemsShowsFallback(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-multiple')->toString();
        // cartItems が空 → フォールバックセクションが表示される
        $this->assertStringContainsString('配送対象の商品がありません', $html);
    }

    // ── L2: form action/method・リンク href/rel ─────────────────────────────

    /**
     * 「新規お届け先を追加する」リンクが /shopping/shipping-multiple-edit を
     * 指している（rel=goShoppingShippingMultipleEdit）。
     */
    public function testAddNewAddressLinkHref(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-multiple')->toString();
        $this->assertStringContainsString('href="/shopping/shipping-multiple-edit"', $html);
        $this->assertStringContainsString('rel="goShoppingShippingMultipleEdit"', $html);
    }

    /**
     * 戻るリンクが /shopping を指している
     * （rel=goShopping — ShippingMultiple::onGet の #[Link]）。
     */
    public function testBackToShoppingLinkHref(): void
    {
        $html = $this->resource->get('page://self/shopping/shipping-multiple')->toString();
        $this->assertStringContainsString('href="/shopping"', $html);
    }

    /**
     * CSRF hidden input のフィールド名が csrfToken であることを確認する。
     *
     * 現在 Resource は Wave-future 状態（cartItems = []）のため配送割当フォームが
     * 描画されず、CSRF フィールドも出力されない。
     * このテストはテンプレートの直接参照でフィールド名契約を検証する。
     */
    public function testCsrfFieldNameContract(): void
    {
        $template = (string) file_get_contents(
            dirname(__DIR__, 2) . '/var/templates/Page/Shopping/ShippingMultiple.html.twig',
        );
        // テンプレート内の hidden CSRF フィールドのフィールド名が csrfToken であること
        $this->assertStringContainsString('name="csrfToken"', $template);
        // フォームの POST action が Resource の onPost と一致すること
        $this->assertStringContainsString('action="/shopping/shipping-multiple"', $template);
        $this->assertStringContainsString('method="post"', $template);
    }

    // ── EC-CUBE parity — archived ────────────────────────────────────────────

    /**
     * EC-CUBE 4.3 reference 描画との突合せテスト（クリーンルーム再構築により引退）。
     *
     * @group ec-cube-parity-archived
     */
    public function testShippingMultipleHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity check retired: template was cleanroom-rebuilt in IdeaStore '
            . 'design language. Functional and semantic coverage is provided by the '
            . 'L1/L2 tests in this class.',
        );
    }
}
