<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Html;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Tests\Http\HttpResource;
use MyVendor\BeMart\Tests\Support\Hypermedia\WorkflowDbSession;

use function assert;
use function dirname;
use function in_array;

/**
 * HTML hypermedia walk of the logged-in customer account-maintenance surface —
 * the HTML projection of {@see \MyVendor\BeMart\Tests\Hypermedia\FlowCustomerAccountMaintenanceTest}.
 *
 * Seeded customer id 3 is logged in via the test session header, so every
 * mypage sub-page is rendered against a real customer with order history. The
 * walk reaches each account-maintenance page a browser would and asserts the
 * page renders its do* affordance (the ALPS class/rel token on the <form>):
 *
 *   goMypage                → /mypage              renders, no schema rejection
 *   goMypageChange          → /mypage/change       affords doUpdateCustomer
 *   goCustomerAddressList   → /mypage/address-list renders the address list
 *   doCreateCustomerAddress → /mypage/address      affords doCreateCustomerAddress
 *   goFavoriteList          → /mypage/favorite-list renders the favorite list
 *   goMypageWithdraw        → /mypage/withdraw     renders the confirm form
 *   goHome                  → /                    header reflects logged-in state
 *
 * Mypage navigation links (Mypage/navi.html.twig) are plain hrefs, not ALPS
 * rel/class tokens, so each page is navigated directly by URL — the same way
 * FlowAdminOrderFulfillmentTest reaches its untagged editor pages.
 *
 * Steps NOT submitted here (covered by the Hypermedia twin, which runs inside a
 * rolled-back transaction):
 *   - doUpdateCustomer / doCreateCustomerAddress / doUpdateCustomerAddress /
 *     doDeleteCustomerAddress / doAddFavorite / doRemoveFavorite /
 *     doWithdrawCustomer: these mutate (or destroy) the shared seeded customer
 *     through the HTTP server's own connection, which the Html suite's session
 *     transaction does not roll back. The HTML walk proves the affordances are
 *     rendered; the Hypermedia twin proves the transitions execute.
 *   - doDeleteCustomerAddress / doRemoveFavorite: rendered as `&_method=delete`
 *     anchors (JS-confirmed), not a <form class="…"> submit() can drive.
 *   - doWithdrawCustomer: the confirm <form> is driven by a name="mode" button,
 *     not a class="doWithdrawCustomer" token, so the page is asserted by its
 *     confirm form, not assertAffordance().
 *   - goMypageChange / goCustomerAddressList / goFavoriteList / goMypageWithdraw
 *     as anchors: not ALPS-tagged in navi.html.twig; navigated by URL.
 *
 * Also a regression guard (path C): an order-backed mypage once rendered an
 * "Invalid input" JSON-Schema rejection (empty productCode on a recent order
 * item) and the header once showed the anonymous ログイン link while logged in.
 */
final class FlowCustomerAccountMaintenanceTest extends AbstractHtmlWorkflowTestCase
{
    public const FLOW_ID = 'flow-customer-account-maintenance-html';

    private const CUSTOMER_ID = '3';
    private const CSRF_TOKEN = 'flow-customer-account-maintenance-csrf-token';

    private static WorkflowDbSession|null $dbSession = null;

    public static function setUpBeforeClass(): void
    {
        self::$dbSession = WorkflowDbSession::startWithCsrfToken(self::CSRF_TOKEN);
        self::$dbSession->session()->setCustomerId(self::CUSTOMER_ID);
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
            '127.0.0.1:8113',
            dirname(__DIR__) . '/Http/html-sql-index.php',
            dirname(__DIR__) . '/Http/log/' . self::FLOW_ID . '.log',
        );
    }

    #[Alps('goMypage')]
    public function testMypageRendersWithoutSchemaRejection(): ResourceObject
    {
        $mypage = $this->resource->get('page://self/mypage');

        $this->assertSame(Code::OK, $mypage->code, (string) ($mypage->view ?? ''));
        $this->assertStringNotContainsString('Invalid input', (string) ($mypage->view ?? ''));
        $this->assertStringContainsString('マイページ', (string) ($mypage->view ?? ''));

        return $mypage;
    }

    #[Alps('goMypageChange')]
    public function testChangeFormAffordsUpdateCustomer(): void
    {
        $change = $this->resource->get('page://self/mypage/change');

        $this->assertSame(Code::OK, $change->code, (string) ($change->view ?? ''));
        $this->assertAffordance($change, 'doUpdateCustomer');
    }

    #[Alps('goCustomerAddressList')]
    public function testAddressListRenders(): void
    {
        $addressList = $this->resource->get('page://self/mypage/address-list');

        $this->assertSame(Code::OK, $addressList->code, (string) ($addressList->view ?? ''));
    }

    #[Alps('doCreateCustomerAddress')]
    public function testAddressFormAffordsCreateAddress(): void
    {
        $address = $this->resource->get('page://self/mypage/address');

        $this->assertSame(Code::OK, $address->code, (string) ($address->view ?? ''));
        $this->assertAffordance($address, 'doCreateCustomerAddress');
    }

    #[Alps('goFavoriteList')]
    public function testFavoriteListRenders(): void
    {
        $favoriteList = $this->resource->get('page://self/mypage/favorite-list');

        $this->assertSame(Code::OK, $favoriteList->code, (string) ($favoriteList->view ?? ''));
    }

    #[Alps('goMypageWithdraw')]
    public function testWithdrawConfirmRenders(): void
    {
        $withdraw = $this->resource->get('page://self/mypage/withdraw');

        // The withdrawal page renders a confirm <form action="/mypage/withdraw">
        // driven by a name="mode" button — not a class="doWithdrawCustomer"
        // affordance — so assert the confirm form is present rather than the rel.
        $this->assertSame(Code::OK, $withdraw->code, (string) ($withdraw->view ?? ''));
        $this->assertStringContainsString('action="/mypage/withdraw"', (string) ($withdraw->view ?? ''));
        $this->assertStringContainsString('退会', (string) ($withdraw->view ?? ''));
    }

    #[Alps('goHome')]
    public function testStorefrontHeaderReflectsLoggedInState(): void
    {
        $home = $this->resource->get('page://self/');

        // While logged in the header must offer ログアウト (the doLogout form),
        // not the anonymous ログイン link — the Block/login is_logged_in() toggle.
        // Assert the ALPS affordance is rendered, not a raw action= substring.
        $this->assertSame(Code::OK, $home->code, (string) ($home->view ?? ''));
        $this->assertAffordance($home, 'doLogout');
    }

    /**
     * doLogout: submit the header's logout form (class="doLogout"). Kept last —
     * it clears the server session for this per-class HTTP harness.
     */
    #[Alps('doLogout')]
    public function testLogsOut(): void
    {
        $home = $this->resource->get('page://self/');
        $this->assertSame(Code::OK, $home->code, (string) ($home->view ?? ''));

        $loggedOut = $this->submit($home, 'doLogout');

        $this->assertTrue(
            in_array($loggedOut->code, [Code::OK, Code::SEE_OTHER], true),
            'doLogout did not succeed: ' . (string) ($loggedOut->view ?? $loggedOut->code),
        );
    }
}
