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
 * Real-HTTP regression for the お気に入り (favorite) add → list → remove
 * member journey through the SQL-backed storefront.
 *
 * DEFECT (favorite-list-sql-type): in the html-eccube-sql-hal-app
 * context {@see \MyVendor\BeMart\Be\Reason\Query\FavoriteStorageInterface}::listByCustomer
 * runs the REAL `var/sql/favorite_list.sql` and Ray.MediaQuery hydrates
 * each row POSITIONALLY into a {@see \MyVendor\BeMart\Be\Reason\Entity\FavoriteEntity}
 * via PDO::FETCH_FUNC (a direct `new FavoriteEntity(...)`, NOT the
 * type-coercing `factory()`). The query SELECTed `fav.customer_id`
 * (a native INT) into the entity's `string $customerId` and
 * `pc.price02` (a DECIMAL rendered as the string `'1200.00'`) into
 * `int $unitPrice`, so PHP threw a TypeError the moment a customer had
 * ANY favorite. BEAR's PhpClassInvoker maps that TypeError to a bare
 * `400 Bad Request — Invalid parameter type` error page.
 *
 * The user-visible break: adding a favorite 303-redirected to
 * /mypage/favorite-list, and FOLLOWING that redirect dead-ended on a
 * 400 error page — the affordance "worked" (the row was stored) but
 * the destination it points at only errored. An EMPTY favorites list
 * rendered fine (no row to hydrate), so the bug only surfaced for a
 * customer who actually had a favorite.
 *
 * EC-CUBE faithfulness: the sibling `address_list.sql` already CASTs
 * its id columns to CHAR so the string-typed AddressEntity params
 * bind; this test pins the same discipline for the favorite list —
 * `CAST(fav.customer_id AS CHAR)` and `CAST(pc.price02 AS SIGNED)`.
 *
 * Driven through the REAL stack: a real php -S server, the
 * html-eccube-sql-hal-app context, a real logged-in customer session,
 * a real CSRF round-trip, the real `dtb_customer_favorite_product`
 * table, and the rendered favorite.twig HTML — NOT the fake fixture
 * suite.
 */
final class HttpSqlMypageFavoriteFlowTest extends TestCase
{
    private const HOST = '127.0.0.1:18210';
    private const MEMBER_EMAIL = 'login-test@example.com';
    private const MEMBER_PASSWORD = 'local-dev-member-password';
    private const PRODUCT_CODE = 'sample-001';

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
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-favorite-cookie-');
    }

    public function testMemberAddsFavoriteThenListRendersInsteadOf400(): void
    {
        // --- log in as a seeded member -------------------------------------
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

        // --- start from a clean slate: remove any pre-existing favorite ----
        $this->form('POST', '/mypage/favorite', [
            'productCode' => self::PRODUCT_CODE,
            '_method' => 'delete',
            'csrfToken' => $this->anchorOrFormToken('/mypage/favorite-list'),
        ]);

        // An EMPTY favorites list always rendered fine (no row to hydrate).
        $empty = $this->form('GET', '/mypage/favorite-list');
        $this->assertSame(200, $empty['status'], $empty['body']);
        $this->assertStringContainsString('お気に入りは登録されていません。', $empty['body']);

        // --- add a favorite: Post/Redirect/Get to the list -----------------
        $product = $this->form('GET', '/product?productCode=' . self::PRODUCT_CODE);
        $this->assertSame(200, $product['status'], $product['body']);
        $added = $this->form('POST', '/mypage/favorite', [
            'productCode' => self::PRODUCT_CODE,
            'csrfToken' => $this->csrfToken($product['body']),
        ]);
        $this->assertSame(303, $added['status'], $added['body']);
        $this->assertSame('/mypage/favorite-list', $added['headers']['Location'] ?? null);

        // --- THE REGRESSION: following the redirect must render the list,
        //     NOT a 400 "Invalid parameter type" error page ----------------
        $list = $this->form('GET', '/mypage/favorite-list');
        $this->assertSame(200, $list['status'], $list['body']);
        $this->assertStringNotContainsString('Invalid parameter type', $list['body']);
        $this->assertStringNotContainsString('400 Bad Request', $list['body']);
        // The favorite row actually renders: count, the product title and the
        // remove affordance pointing back at this productCode.
        $this->assertStringContainsString('1件のお気に入りがあります', $list['body']);
        $this->assertStringContainsString('<h1>マイページ/お気に入り一覧</h1>', $list['body']);
        $this->assertStringContainsString(
            '/mypage/favorite?productCode=' . self::PRODUCT_CODE,
            $list['body'],
        );

        // --- remove it: the list returns to the empty state ----------------
        $removed = $this->form('POST', '/mypage/favorite', [
            'productCode' => self::PRODUCT_CODE,
            '_method' => 'delete',
            'csrfToken' => $this->anchorOrFormToken('/mypage/favorite-list'),
        ]);
        $this->assertSame(303, $removed['status'], $removed['body']);
        $this->assertSame('/mypage/favorite-list', $removed['headers']['Location'] ?? null);

        $after = $this->form('GET', '/mypage/favorite-list');
        $this->assertSame(200, $after['status'], $after['body']);
        $this->assertStringContainsString('お気に入りは登録されていません。', $after['body']);
    }

    private function anchorOrFormToken(string $path): string
    {
        $page = $this->form('GET', $path);
        if (preg_match('/token-for-anchor="([^"]*)"/', $page['body'], $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }

        // Fall back to any csrf token the page carries.
        if (preg_match('/name="csrfToken" value="([^"]*)"/', $page['body'], $m)) {
            return html_entity_decode($m[1], ENT_QUOTES);
        }

        return '';
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
