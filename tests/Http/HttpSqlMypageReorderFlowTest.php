<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function explode;
use function html_entity_decode;
use function http_build_query;
use function is_string;
use function preg_match;
use function preg_split;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

use const ENT_QUOTES;

/**
 * Real-HTTP regression for 再注文 (doReorder) — the order-history detail
 * "再注文する" button must repopulate the LIVE shopping cart.
 *
 * DEFECT (reorder-session-prefix): {@see \MyVendor\BeMart\Resource\Page\Mypage\Reorder}::onPost
 * built `new ReorderInput(orderNo: $orderNo)` WITHOUT threading the
 * browser session's cart prefix. The cart partition key is
 * `{sessionPrefix}_{saleTypeId}`; every other cart-mutating resource
 * (Cart, Cart\Item, Shopping, Shopping\NonMember) injects
 * `CartSessionPrefixInterface->prefix()` so writes land in the live
 * session's partition. Reorder did NOT — so {@see \MyVendor\BeMart\Be\Final\Reordered}
 * persisted the cart under the fallback `session-prefix-1` key, while
 * GET /cart reads the REAL session prefix. Result: clicking 再注文する
 * 303-redirected to /cart, and the cart was EMPTY — a reorder that
 * observably did nothing (the very "success with no observable result"
 * class of bug).
 *
 * EC-CUBE faithfulness: MypageController::order() (the reorder action)
 * calls CartService::addProduct on the customer's live cart; the
 * customer is then redirected to mtb cart and SEES the items. This test
 * pins that the BeMart port now writes into the same partition the
 * /cart page reads, so the reordered product actually shows.
 *
 * Driven through the REAL stack: a real php -S server, the
 * html-eccube-sql-hal-app context, a real logged-in customer session
 * (a seeded member who owns a past order), a real CSRF round-trip, the
 * real `dtb_cart` table, and the rendered cart HTML — NOT the fake
 * fixture suite.
 */
final class HttpSqlMypageReorderFlowTest extends TestCase
{
    private const HOST = '127.0.0.1:18211';
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
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-reorder-cookie-');
    }

    public function testReorderRepopulatesTheLiveCartInsteadOfRedirectingToEmpty(): void
    {
        // --- log in as a seeded member who owns a past order ----------------
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

        // --- find a past order from the order history -----------------------
        $history = $this->form('GET', '/mypage/order-history');
        $this->assertSame(200, $history['status'], $history['body']);
        $this->assertSame(
            1,
            preg_match('/orderNo=([0-9a-f]+)/', $history['body'], $orderMatch),
            'the member must have a past order to reorder: ' . $history['body'],
        );
        $orderNo = $orderMatch[1];

        // --- the order-history detail carries the 再注文する form -----------
        $detail = $this->form('GET', '/mypage/history?orderNo=' . $orderNo);
        $this->assertSame(200, $detail['status'], $detail['body']);
        $this->assertStringContainsString('再注文する', $detail['body']);
        $this->assertStringContainsString('action="/mypage/reorder"', $detail['body']);

        // --- click 再注文する: Post/Redirect/Get to /cart -------------------
        $reorder = $this->form('POST', '/mypage/reorder', [
            'orderNo' => $orderNo,
            'csrfToken' => $this->csrfToken($detail['body']),
        ]);
        $this->assertSame(303, $reorder['status'], $reorder['body']);
        $this->assertSame('/cart', $reorder['headers']['Location'] ?? null);

        // --- THE REGRESSION: the cart the user lands on is NOT empty --------
        $cart = $this->form('GET', '/cart');
        $this->assertSame(200, $cart['status'], $cart['body']);
        $this->assertStringNotContainsString('現在カート内に商品はございません', $cart['body']);
        // The reordered product line is actually rendered in the live cart.
        $this->assertStringContainsString('ec-cartRow__name', $cart['body']);
        $this->assertStringContainsString('/product?productCode=', $cart['body']);

        // --- THE OTHER HALF: the reorder cart can actually CHECK OUT ---------
        // A reorder started a NEW cart whose preOrderId was '' (Reordered copied
        // it verbatim instead of issuing one), so /shopping rendered an empty
        // preOrderId hidden field and 注文する dead-ended at
        // "400 — preOrderId は 40 文字の小文字 16 進数文字列で…". /shopping MUST
        // carry a real 40-hex preOrderId after a reorder.
        $shopping = $this->form('GET', '/shopping');
        $this->assertSame(200, $shopping['status'], $shopping['body']);
        $this->assertSame(
            1,
            preg_match('/name="preOrderId" value="([0-9a-f]{40})"/', $shopping['body']),
            'reorder /shopping must carry a 40-hex preOrderId (was empty → checkout 400): ' . $shopping['body'],
        );
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
