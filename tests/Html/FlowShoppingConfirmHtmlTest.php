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
 * HTML purchase walk to the confirm screen — the case path C exists for: the
 * storefront inserts human-facing steps (cart -> checkout entry -> shipping
 * form -> a CONFIRM screen) that the JSON API has no equivalent of, so it can
 * never share the JSON workflow scenario.
 *
 * It publishes a product (admin), adds it to the cart, walks the anonymous
 * checkout to the non-member shipping form, and submits it — which stages a
 * pre-order (preOrderId) for a human confirmation step instead of placing the
 * order directly. That staging is the review step the JSON API has no equivalent
 * of; it does not place the order (rendering the full confirm page is a deeper
 * redirect chain, out of scope).
 */
final class FlowShoppingConfirmHtmlTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-shopping-confirm-html';

    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'flow-shopping-confirm-csrf-token';
    private const SESSION_PREFIX = 'flow-shopping-confirm-prefix';

    private static string $productCode;
    private static string $productName;
    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        $suffix = bin2hex(random_bytes(4));
        self::$productCode = 'shop-' . $suffix;
        self::$productName = 'Shop Product ' . $suffix;
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
            'price02' => '2200',
            'stock' => '20',
            'productStatus' => '1',
        ]);

        $this->assertTrue(in_array($created->code, [Code::CREATED, Code::SEE_OTHER], true), (string) ($created->view ?? $created->code));
    }

    #[Depends('testPublishesProduct')]
    #[Alps('doAddCartItem')]
    public function testAddsToCart(): void
    {
        $product = $this->resource->get('page://self/product', ['productCode' => self::$productCode]);
        $this->assertSame(Code::OK, $product->code);

        $added = $this->submit($product, 'doAddCartItem', [
            'productCode' => self::$productCode,
            'quantity' => '1',
            'sessionPrefix' => self::SESSION_PREFIX,
        ]);

        $this->assertTrue(in_array($added->code, [Code::CREATED, Code::SEE_OTHER], true), (string) ($added->view ?? $added->code));
    }

    #[Depends('testAddsToCart')]
    #[Alps('goShoppingNonMember')]
    public function testReachesNonMemberCheckoutForm(): ResourceObject
    {
        // Anonymous checkout entry is gated through the human shopping-login step.
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
            'email' => 'flow-shopping-confirm@example.com',
            'phoneNumber' => '0312345678',
            'postalCode' => '1500001',
            'pref' => '13',
            'addr01' => '渋谷区',
            'addr02' => 'ワークフロー1-1-1',
            'sessionPrefix' => self::SESSION_PREFIX,
        ]);

        $this->assertTrue(in_array($submitted->code, [Code::OK, Code::CREATED, Code::SEE_OTHER], true), (string) ($submitted->view ?? $submitted->code));

        // The HTML-only confirm step: submitting shipping does NOT place the
        // order — it stages a pre-order (preOrderId) for a human confirmation
        // screen. That staging is the review step the JSON API has no equivalent
        // of (it would post the order directly).
        $location = $this->header($submitted, 'Location');
        $this->assertIsString($location, 'shipping submit did not stage a pre-order');
        $this->assertStringContainsString('preOrderId', $location);
    }
}
