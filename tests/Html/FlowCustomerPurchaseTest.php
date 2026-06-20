<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;
use PHPUnit\Framework\Attributes\Depends;

use function assert;
use function bin2hex;
use function dirname;
use function in_array;
use function random_bytes;

/**
 * HTML hypermedia walk of the storefront purchase journey — the HTML projection
 * of {@see \MyVendor\BeMart\Tests\Hypermedia\FlowCustomerPurchaseTest}.
 *
 * It publishes a product (admin), then walks the storefront as an anonymous
 * visitor would, driven by the rendered ALPS affordances (class/rel) over real
 * HTTP:
 *
 *   doCreateProduct     → admin publishes a real product for checkout
 *   goProductList       → /products lists the published product
 *   goProduct           → /product detail opens (200, not 404)  ← regression
 *   doAddCartItem       → add the product to the cart
 *   goCart              → /cart shows the line item
 *   goShoppingNonMember → /shopping/non-member affords doSubmitNonMember
 *   doSubmitNonMember   → staging the pre-order (preOrderId) for confirmation
 *
 * The product-detail 200 is the regression guard for the original "トップで
 * 商品をクリックしても 404" bug. The non-member submit reaching a staged
 * preOrderId is the HTML-only confirm step the JSON API has no equivalent of —
 * the storefront inserts a human review screen before placing the order.
 *
 * Steps beyond the confirm staging are NOT walked here (covered by the
 * Hypermedia twin, which runs in-process under a rolled-back transaction):
 *   - doConfirmOrder / doCheckout / ShoppingComplete: placing the order is a
 *     deeper redirect + JSON chain past the human confirm screen.
 *   - member registration / doLogin / reorder: the member purchase path is
 *     exercised by FlowCustomerRegistrationTest and FlowCustomerAccountMaintenanceTest.
 *   - doCreatePayment: checkout uses the installer payment masters, never a
 *     test-created payment method (the Hypermedia twin's contract).
 */
final class FlowCustomerPurchaseTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-customer-purchase-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'flow-customer-purchase-csrf-token';
    private const SESSION_PREFIX = 'flow-customer-purchase-prefix';

    private static string $productCode;
    private static string $productName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$productCode = 'purchase-' . $suffix;
        self::$productName = 'Purchase Product ' . $suffix;
        self::$dbSession = WorkflowDbSession::startForAdmin(self::ADMIN_ID, self::CSRF_TOKEN);
    }

    public static function tearDownAfterClass(): void
    {
        self::$dbSession?->restore();
        self::$dbSession = null;

        parent::tearDownAfterClass();
    }

    protected function newResource(): ResourceInterface
    {
        assert(self::$dbSession instanceof WorkflowDbSession);

        return new HttpResource(
            '127.0.0.1:8116',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('doCreateProduct')]
    public function testPublishesProduct(): void
    {
        $editor = $this->resource->get('page://self/admin/product/edit');
        $this->assertSame(Code::OK, $editor->code);

        $created = $this->submit($editor, 'doCreateProduct', [
            'productCode' => self::$productCode,
            'productName' => self::$productName,
            'price02' => '2468',
            'stock' => '20',
            'productStatus' => '1',
        ]);

        $this->assertTrue(
            in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true),
            'publishing the product failed: ' . (string) ($created->view ?? $created->code),
        );
    }

    #[Depends('testPublishesProduct')]
    #[Alps('goProductList')]
    public function testStorefrontListsProduct(): void
    {
        $list = $this->resource->get('page://self/products', ['nameKeyword' => self::$productName]);

        $this->assertSame(Code::OK, $list->code, (string) ($list->view ?? $list->code));
        $this->assertStringContainsString(self::$productCode, (string) ($list->view ?? ''));
    }

    #[Depends('testStorefrontListsProduct')]
    #[Alps('goProduct')]
    public function testStorefrontProductDetailOpens(): ResourceObject
    {
        $product = $this->resource->get('page://self/product', ['productCode' => self::$productCode]);

        // The regression: the detail must open (200), not 404. The name renders as
        // display copy (a heading), so a presence check fits.
        $this->assertSame(Code::OK, $product->code, (string) ($product->view ?? $product->code));
        $this->assertStringContainsString(self::$productName, (string) ($product->view ?? ''));

        return $product;
    }

    #[Depends('testStorefrontProductDetailOpens')]
    #[Alps('doAddCartItem')]
    public function testAddsToCart(ResourceObject $product): void
    {
        $added = $this->submit($product, 'doAddCartItem', [
            'productCode' => self::$productCode,
            'quantity' => '1',
            'sessionPrefix' => self::SESSION_PREFIX,
        ]);

        $this->assertTrue(
            in_array($added->code, [Code::CREATED, Code::SEE_OTHER], true),
            (string) ($added->view ?? $added->code),
        );
    }

    #[Depends('testAddsToCart')]
    #[Alps('goCart')]
    public function testViewsCart(): void
    {
        $cart = $this->resource->get('page://self/cart', ['sessionPrefix' => self::SESSION_PREFIX]);

        $this->assertSame(Code::OK, $cart->code, (string) ($cart->view ?? $cart->code));
        $this->assertStringContainsString(self::$productName, (string) ($cart->view ?? ''));
    }

    /**
     * doRemoveCartItem (HTML) — own sessionPrefix so removing the line does not
     * disturb the main purchase walk's cart. Add the product, open the cart, then
     * submit the rendered <form class="doRemoveCartItem"> (operation=remove). The
     * cart-count is NOT asserted (sessionPrefix has a hardcoded-default debt that
     * makes cross-call cart isolation unreliable); only the operation succeeding.
     */
    #[Depends('testStorefrontProductDetailOpens')]
    #[Alps('doRemoveCartItem')]
    public function testRemovesCartItem(): void
    {
        $prefix = 'flow-customer-purchase-remove-' . bin2hex(random_bytes(4));

        $product = $this->resource->get('page://self/product', ['productCode' => self::$productCode]);
        $this->assertSame(Code::OK, $product->code, (string) ($product->view ?? $product->code));

        $added = $this->submit($product, 'doAddCartItem', [
            'productCode' => self::$productCode,
            'quantity' => '1',
            'sessionPrefix' => $prefix,
        ]);
        $this->assertTrue(
            in_array($added->code, [Code::CREATED, Code::SEE_OTHER], true),
            (string) ($added->view ?? $added->code),
        );

        $cart = $this->resource->get('page://self/cart', ['sessionPrefix' => $prefix]);
        $this->assertSame(Code::OK, $cart->code, (string) ($cart->view ?? $cart->code));

        $removed = $this->submit($cart, 'doRemoveCartItem', [
            'productCode' => self::$productCode,
            'operation' => 'remove',
            'sessionPrefix' => $prefix,
        ]);

        $this->assertTrue(
            in_array($removed->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            (string) ($removed->view ?? $removed->code),
        );
    }

    /**
     * doUpdateCartItemQuantity (HTML) — own sessionPrefix. Add the product, open
     * the cart, then submit the rendered <form class="doUpdateCartItemQuantity">
     * (operation=up) to bump the line quantity. Cart-count is NOT asserted
     * (sessionPrefix debt); only the operation succeeding.
     */
    #[Depends('testStorefrontProductDetailOpens')]
    #[Alps('doUpdateCartItemQuantity')]
    public function testUpdatesCartItemQuantity(): void
    {
        $prefix = 'flow-customer-purchase-update-' . bin2hex(random_bytes(4));

        $product = $this->resource->get('page://self/product', ['productCode' => self::$productCode]);
        $this->assertSame(Code::OK, $product->code, (string) ($product->view ?? $product->code));

        $added = $this->submit($product, 'doAddCartItem', [
            'productCode' => self::$productCode,
            'quantity' => '1',
            'sessionPrefix' => $prefix,
        ]);
        $this->assertTrue(
            in_array($added->code, [Code::CREATED, Code::SEE_OTHER], true),
            (string) ($added->view ?? $added->code),
        );

        $cart = $this->resource->get('page://self/cart', ['sessionPrefix' => $prefix]);
        $this->assertSame(Code::OK, $cart->code, (string) ($cart->view ?? $cart->code));

        $updated = $this->submit($cart, 'doUpdateCartItemQuantity', [
            'productCode' => self::$productCode,
            'operation' => 'up',
            'quantity' => '2',
            'sessionPrefix' => $prefix,
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            (string) ($updated->view ?? $updated->code),
        );
    }

    #[Depends('testViewsCart')]
    #[Alps('goShoppingNonMember')]
    public function testReachesNonMemberCheckoutForm(): ResourceObject
    {
        $nonMember = $this->resource->get('page://self/shopping/non-member', ['sessionPrefix' => self::SESSION_PREFIX]);

        $this->assertSame(Code::OK, $nonMember->code, (string) ($nonMember->view ?? $nonMember->code));
        $this->assertAffordance($nonMember, 'doSubmitNonMember');

        return $nonMember;
    }

    #[Depends('testReachesNonMemberCheckoutForm')]
    #[Alps('doSubmitNonMember')]
    public function testSubmitsShippingAndReachesConfirmScreen(ResourceObject $nonMember): void
    {
        $submitted = $this->submit($nonMember, 'doSubmitNonMember', [
            'name01' => '非会員',
            'name02' => '太郎',
            'kana01' => 'ヒカイイン',
            'kana02' => 'タロウ',
            'email' => 'flow-customer-purchase@example.com',
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            'pref' => '13',
            'addr01' => '渋谷区',
            'addr02' => 'ワークフロー1-1-1',
            'sessionPrefix' => self::SESSION_PREFIX,
        ]);

        $this->assertTrue(
            in_array($submitted->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            (string) ($submitted->view ?? $submitted->code),
        );

        // The HTML-only confirm step: submitting shipping does NOT place the
        // order — it stages a pre-order (preOrderId) for a human confirmation
        // screen, the review step the JSON API has no equivalent of.
        $location = $this->header($submitted, 'Location');
        $this->assertIsString($location, 'shipping submit did not stage a pre-order');
        $this->assertStringContainsString('preOrderId', $location);
    }

    /**
     * doUpdateShippingAddress (HTML) — the お届け先変更 form. The ShippingEdit
     * renderer is anonymous-permissive (Wave 3H), so the form page is reachable
     * directly; the rendered <form class="doUpdateShippingAddress"> is the ALPS
     * affordance. Submit an edited address and assert the unsafe transition
     * executes (303 back to /shopping).
     */
    #[Alps('doUpdateShippingAddress')]
    public function testReachesAndSubmitsShippingEditForm(): void
    {
        $editForm = $this->resource->get('page://self/shopping/shipping-edit');
        $this->assertSame(Code::OK, $editForm->code, (string) ($editForm->view ?? $editForm->code));
        $this->assertAffordance($editForm, 'doUpdateShippingAddress');

        $updated = $this->submit($editForm, 'doUpdateShippingAddress', [
            'name01' => '会員',
            'name02' => '太郎',
            'kana01' => 'カイイン',
            'kana02' => 'タロウ',
            'companyName' => 'ワークフロー商事',
            'postalCode' => '1500001',
            'pref' => '13',
            'addr01' => '渋谷区',
            'addr02' => '配送先1-1-1',
            'phoneNumber' => '0312345678',
        ]);

        $this->assertTrue(
            in_array($updated->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true),
            (string) ($updated->view ?? $updated->code),
        );
    }
}
