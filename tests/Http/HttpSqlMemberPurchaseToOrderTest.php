<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PDO;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function explode;
use function html_entity_decode;
use function http_build_query;
use function is_string;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_match_all;
use function preg_split;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

use const ENT_QUOTES;

/**
 * End-to-end LOGGED-IN member purchase journey through the real SQL HTTP stack.
 *
 * A registered customer must be able to place an order: log in, add a product
 * to the cart, proceed to checkout (member /shopping with the address /
 * payment selection), confirm, and press 注文する to complete. This drives the
 * journey through ONE cookie jar so the session / cart / login persist across
 * steps, harvesting the REAL fields each rendered form carries (including the
 * submit button's name/value).
 *
 * It also pins the firewall regression this suite was added for: an ANONYMOUS
 * visitor of an auth-only customer page (/mypage, /mypage/change) is redirected
 * to the login form (303 → /login) the way EC-CUBE's `^/mypage` access_control
 * does — NOT shown a dead-end 401 error page. The JSON/HAL contexts keep the
 * API-faithful 401; only the browser context recovers via redirect.
 */
final class HttpSqlMemberPurchaseToOrderTest extends TestCase
{
    private const HOST = '127.0.0.1:18197';
    private const PRODUCT_CODE = 'sample-001';
    private const MEMBER_EMAIL = 'login-test@example.com';
    private const MEMBER_PASSWORD = 'local-dev-member-password';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server?->stop();
        self::$server = null;
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-member-purchase-cookie-');
    }

    /**
     * Firewall regression: an anonymous visitor following the マイページ
     * affordance must NOT hit a 401 dead-end page. EC-CUBE redirects to the
     * login form. (Before the fix this rendered "401 Unauthorized" HTML.)
     */
    public function testAnonymousCustomerPageRedirectsToLoginInsteadOf401(): void
    {
        // Every firewalled storefront page (EC-CUBE's `^/mypage` access_control).
        // /mypage/withdraw and /mypage/address returned a bare 401 before the
        // fix (they returned Code::UNAUTHORIZED directly instead of raising
        // UnauthenticatedException, so the html handler's login-redirect never
        // fired); the rest already redirected via the Be chain.
        $firewalled = [
            '/mypage',
            '/mypage/change',
            '/mypage/withdraw',
            '/mypage/address',
            '/mypage/address-list',
            '/mypage/favorite-list',
            '/mypage/order-history',
        ];
        foreach ($firewalled as $path) {
            $response = $this->form('GET', $path);
            $this->assertSame(303, $response['status'], $path . ' must redirect, got: ' . $response['body']);
            $this->assertSame('/login', $response['headers']['Location'] ?? null, $path);
            $this->assertStringNotContainsString('401 Unauthorized', $response['body'], $path);
            $this->assertStringNotContainsString('403 Forbidden', $response['body'], $path);
        }
    }

    /**
     * Affordance-gating regression: the global header must present the お気に入り
     * (goFavoriteList) auth-only affordance ONLY to a logged-in customer. An
     * anonymous visitor sees 新規会員登録 / ログイン and NO favorite link —
     * presenting it bare would only 401 when clicked (the user-flagged class).
     */
    public function testHeaderGatesFavoriteAffordanceByLoginState(): void
    {
        // Anonymous: register/login affordances, no favorites.
        $anon = $this->form('GET', '/');
        $this->assertSame(200, $anon['status'], $anon['body']);
        $this->assertStringContainsString('新規会員登録', $anon['body']);
        $this->assertStringContainsString('/login', $anon['body']);
        $this->assertStringNotContainsString('お気に入り', $anon['body']);
        $this->assertStringNotContainsString('/mypage/favorite-list', $anon['body']);

        // Log in, then the same header exposes マイページ + お気に入り.
        $login = $this->form('GET', '/login');
        $this->assertSame(200, $login['status']);
        $loggedIn = $this->form('POST', '/login', [
            'email' => self::MEMBER_EMAIL,
            'password' => self::MEMBER_PASSWORD,
            'mode' => 'login',
            'csrfToken' => $this->csrfToken($login['body']),
        ]);
        $this->assertSame(303, $loggedIn['status'], $loggedIn['body']);

        $home = $this->form('GET', '/');
        $this->assertSame(200, $home['status'], $home['body']);
        $this->assertStringContainsString('お気に入り', $home['body']);
        $this->assertStringContainsString('/mypage/favorite-list', $home['body']);
    }

    public function testMemberCompletesOrderThroughRenderedConfirmButton(): void
    {
        // 1. product detail page — issues the add-to-cart CSRF token.
        $product = $this->form('GET', '/product?productCode=' . self::PRODUCT_CODE);
        $this->assertSame(200, $product['status'], $product['body']);

        // 2. add to cart → Post/Redirect/Get to /cart.
        $added = $this->form('POST', '/cart/item', [
            'productCode' => self::PRODUCT_CODE,
            'quantity' => '1',
            'operation' => 'add',
            'csrfToken' => $this->csrfToken($product['body']),
        ]);
        $this->assertSame(303, $added['status'], $added['body']);
        $this->assertSame('/cart', $added['headers']['Location'] ?? null);

        // 3. cart shows the レジに進む affordance to /shopping.
        $cart = $this->form('GET', '/cart');
        $this->assertSame(200, $cart['status'], $cart['body']);
        $this->assertStringContainsString('レジに進む', $cart['body']);
        $this->assertStringContainsString('href="/shopping"', $cart['body']);

        // 4. log in as a seeded member (Post/Redirect/Get → /mypage).
        $login = $this->form('GET', '/login');
        $this->assertSame(200, $login['status']);
        $loggedIn = $this->form('POST', '/login', [
            'email' => self::MEMBER_EMAIL,
            'password' => self::MEMBER_PASSWORD,
            'mode' => 'login',
            'csrfToken' => $this->csrfToken($login['body']),
        ]);
        $this->assertSame(303, $loggedIn['status'], $loggedIn['body']);
        $this->assertSame('/mypage', $loggedIn['headers']['Location'] ?? null);

        // 5. member /shopping — checkout entry resolves (no 303 to the login
        //    gate, no 401/403 mid-flow); the page carries the preOrderId and
        //    the 確認する form posting to /shopping/confirm.
        $shopping = $this->form('GET', '/shopping');
        $this->assertSame(200, $shopping['status'], $shopping['body']);
        $this->assertStringContainsString('<h1>ご注文手続き</h1>', $shopping['body']);
        $this->assertStringContainsString('action="/shopping/confirm"', $shopping['body']);
        $this->assertStringNotContainsString('カートが空', $shopping['body']);
        $this->assertStringContainsString('name="preOrderId" value="', $shopping['body']);
        $this->assertSame(
            1,
            preg_match('/name="preOrderId" value="([^"]+)"/', $shopping['body'], $preMatch),
            $shopping['body'],
        );
        $preOrderId = $preMatch[1];
        $shoppingCsrf = $this->csrfToken($shopping['body']);

        // a default payment method must be selectable.
        $this->assertSame(
            1,
            preg_match('/name="payment" value="([^"]+)"/', $shopping['body'], $payMatch),
            'member checkout must offer at least one payment method',
        );
        $payment = $payMatch[1];

        // 6. 確認する → order-confirmation screen.
        $confirm = $this->form('POST', '/shopping/confirm', [
            'csrfToken' => $shoppingCsrf,
            'redirect_to' => '',
            'preOrderId' => $preOrderId,
            'payment' => $payment,
        ]);
        $this->assertSame(200, $confirm['status'], $confirm['body']);
        $this->assertStringContainsString('<h1>ご注文内容のご確認</h1>', $confirm['body']);
        $this->assertStringContainsString('注文する', $confirm['body']);

        // お客様情報 regression: the confirm page MUST show the logged-in
        // member's real name (dtb_customer id=4 = 鈴木次郎), NOT "- - 様".
        // The member flow builds no customerSnapshot, so order_register.sql
        // must copy the customer's name/address into the order; without that
        // copy the snapshot rebuilt from the order columns is a non-empty bag
        // of '-', defeating OrderConfirmed's customerFromEntity fallback.
        $this->assertStringContainsString('鈴木', $confirm['body'], 'confirm page must show member name01 鈴木');
        $this->assertStringContainsString('次郎', $confirm['body'], 'confirm page must show member name02 次郎');
        $this->assertStringNotContainsString('- - 様', $confirm['body'], 'confirm お客様情報 must not be the empty "- - 様"');

        // The 注文する button MUST carry mode=complete so the browser POST
        // redirects instead of stranding the member on /shopping/checkout.
        $this->assertStringContainsString(
            'name="mode" value="complete"',
            $confirm['body'],
            'The 注文する submit button must carry name="mode" value="complete".',
        );

        // 7. submit the confirm form exactly as a browser does: every input the
        //    form carries PLUS the clicked 注文する button's name/value.
        $checkoutFields = $this->shoppingFormFields($confirm['body']);
        $this->assertArrayHasKey('preOrderId', $checkoutFields);
        $this->assertArrayHasKey('csrfToken', $checkoutFields);
        $this->assertSame('complete', $checkoutFields['mode'] ?? null);

        $checkout = $this->form('POST', '/shopping/checkout', $checkoutFields);

        // Observable: a member order placement redirects to the complete page.
        $this->assertSame(303, $checkout['status'], $checkout['body']);
        $completeLocation = $checkout['headers']['Location'] ?? '';
        $this->assertStringStartsWith('/shopping/complete?orderNo=', $completeLocation);
        $this->assertSame(1, preg_match('/orderNo=([0-9a-f]+)/', $completeLocation, $orderMatch));
        $orderNo = $orderMatch[1];

        // 8. the complete screen renders the freshly-placed order.
        $complete = $this->form('GET', $completeLocation);
        $this->assertSame(200, $complete['status'], $complete['body']);
        $this->assertStringContainsString('ご注文完了', $complete['body']);
        $this->assertStringNotContainsString('Service Unavailable', $complete['body']);
        $this->assertStringNotContainsString('Fatal error', $complete['body']);

        // 9. the PERSISTED order row must carry the member's real name, not '-'.
        $placed = $this->placedOrderName($orderNo);
        $this->assertSame('鈴木', $placed['name01'], 'persisted dtb_order.name01 must be the member name, not "-"');
        $this->assertSame('次郎', $placed['name02'], 'persisted dtb_order.name02 must be the member name, not "-"');
    }

    /**
     * Query the freshly-placed dtb_order row's name01/name02 straight from the
     * test DB (DATABASE_URL = the same eccubedb_test the HTTP stack writes to),
     * proving the persisted order carries the member's real name.
     *
     * @return array{name01: string, name02: string}
     */
    private function placedOrderName(string $orderNo): array
    {
        $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;
        if (! is_string($databaseUrl) || $databaseUrl === '') {
            self::markTestSkipped('DATABASE_URL is not set; member order name regression requires the eccubedb_test DB.');
        }

        $parts = parse_url($databaseUrl);
        $this->assertIsArray($parts);
        $query = [];
        if (isset($parts['query']) && is_string($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $parts['host'] ?? '127.0.0.1',
            (int) ($parts['port'] ?? 3306),
            trim((string) ($parts['path'] ?? ''), '/'),
            is_string($query['charset'] ?? null) ? $query['charset'] : 'utf8mb4',
        );

        $pdo = new PDO(
            $dsn,
            $parts['user'] ?? 'root',
            $parts['pass'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $stmt = $pdo->prepare('SELECT name01, name02 FROM dtb_order WHERE order_no = :orderNo LIMIT 1');
        $stmt->execute([':orderNo' => $orderNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($row, 'placed order ' . $orderNo . ' not found in dtb_order');

        return ['name01' => (string) $row['name01'], 'name02' => (string) $row['name02']];
    }

    /**
     * Harvest the #shopping-form's hidden inputs plus the clicked 注文する
     * submit button's name/value, mirroring a real browser POST.
     *
     * @return array<string, string>
     */
    private function shoppingFormFields(string $html): array
    {
        $fields = [];

        if (preg_match_all('/<input[^>]*name="([^"]+)"[^>]*value="([^"]*)"/', $html, $inputs, PREG_SET_ORDER)) {
            foreach ($inputs as $input) {
                $fields[$input[1]] = html_entity_decode($input[2], ENT_QUOTES);
            }
        }

        if (preg_match('/<button[^>]*name="([^"]+)"[^>]*value="([^"]*)"[^>]*>\s*注文する/', $html, $button)) {
            $fields[$button[1]] = html_entity_decode($button[2], ENT_QUOTES);
        }

        return $fields;
    }

    /**
     * @param array<string, string> $fields
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function form(string $method, string $path, array $fields = []): array
    {
        $jar = escapeshellarg($this->cookieJar);
        $curl = sprintf('curl -s -i -b %s -c %s', $jar, $jar);
        if ($method !== 'GET') {
            $curl .= ' -X ' . escapeshellarg($method);
            $curl .= ' -d ' . escapeshellarg(http_build_query($fields));
        }

        $curl .= ' ' . escapeshellarg('http://' . self::HOST . $path);
        $raw = shell_exec($curl);
        $this->assertIsString($raw);

        return $this->parseResponse($raw);
    }

    /** @return array{status: int, headers: array<string, string>, body: string} */
    private function parseResponse(string $raw): array
    {
        $parts = preg_split("/\r?\n\r?\n/", $raw, 2);
        $this->assertIsArray($parts);
        $headerBlock = $parts[0] ?? '';
        $body = $parts[1] ?? '';
        $this->assertIsString($headerBlock);
        $this->assertIsString($body);

        $lines = preg_split('/\r?\n/', $headerBlock) ?: [];
        $statusLine = $lines[0] ?? '';
        $this->assertIsString($statusLine);
        $this->assertSame(1, preg_match('/\s(\d{3})\s/', $statusLine, $match));

        $headers = [];
        foreach ($lines as $line) {
            if (! is_string($line) || ! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[$name] = trim($value);
        }

        return ['status' => (int) $match[1], 'headers' => $headers, 'body' => $body];
    }

    private function csrfToken(string $body): string
    {
        $this->assertSame(1, preg_match('/name="csrfToken" value="([^"]*)"/', $body, $match), $body);

        return html_entity_decode($match[1], ENT_QUOTES);
    }
}
