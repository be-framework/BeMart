<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PDO;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function escapeshellarg;
use function explode;
use function http_build_query;
use function is_string;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_split;
use function random_bytes;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

/**
 * Real-HTTP regression for the EC-CUBE 退会 (withdraw) confirm/commit
 * state machine.
 *
 * DEFECT (withdraw-confirm-flow): {@see \MyVendor\BeMart\Resource\Page\Mypage\Withdraw}::onPost
 * used to IGNORE `mode` and perform the withdrawal IMMEDIATELY (clear cart,
 * replace email, send mail, flip status to 退会=3) then 303 to
 * /mypage/withdraw-complete. So clicking 退会手続きへ on the FIRST warning
 * page (`Page/Mypage/Withdraw.html.twig`, which POSTs `mode=confirm`)
 * withdrew the account with NO final confirmation — a one-click, irreversible
 * account loss.
 *
 * EC-CUBE WithdrawController::index (tools/ec-cube-source/.../Mypage/WithdrawController.php)
 * branches on the `mode` POST param: `confirm` renders the
 * withdraw_confirm.twig "退会手続きを実行してもよろしいでしょうか？" screen
 * with NO side-effects; only the confirm screen's `mode=complete` button
 * actually withdraws and redirects to mypage_withdraw_complete.
 *
 * This proves the fixed flow through the REAL stack — a real php -S server,
 * the html-eccube-sql-hal-app context, a real logged-in customer session,
 * a real CSRF round-trip, the rendered HTML frame AND the real SQL
 * dtb_customer table — NOT the static fake fixture suite:
 *
 *   1. Register a fresh customer via /entry, then log in via /login so the
 *      session carries a real customer_id (active, customer_status_id=2).
 *   2. GET /mypage/withdraw -> 200 warning page; extract the CSRF token.
 *   3. POST mode=confirm -> 200 CONFIRM screen ("退会手続きを実行しても
 *      よろしいでしょうか？" + はい、退会します); it is NOT a redirect to
 *      /mypage/withdraw-complete, and the customer is STILL ACTIVE in
 *      dtb_customer (customer_status_id=2, original email intact).
 *   4. POST mode=complete -> 303 -> /mypage/withdraw-complete; the customer
 *      is now WITHDRAWN in dtb_customer (customer_status_id=3, email
 *      replaced with the withdrawn-* dummy).
 */
final class HttpSqlWithdrawConfirmFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18189';
    private const PASSWORD = 'withdraw-sql-password-2026';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-withdraw-cookie-');
    }

    public function testConfirmModeShowsConfirmPageWithoutWithdrawingThenCompleteWithdraws(): void
    {
        $pdo = $this->pdo();

        // --- 1. Register a fresh active customer + log in -------------------
        $email = 'sql-withdraw-' . bin2hex(random_bytes(4)) . '@example.test';
        $this->registerCustomer($email);
        $this->login($email);

        $customerId = (int) $this->columnFor($pdo, $email, 'id');
        $this->assertGreaterThan(0, $customerId);
        $this->assertSame('2', $this->columnFor($pdo, $email, 'customer_status_id'));

        // --- 2. GET the warning page, extract the CSRF token ---------------
        $withdraw = $this->form('GET', '/mypage/withdraw');
        $this->assertSame(200, $withdraw['status'], $withdraw['body']);
        $this->assertStringContainsString('退会手続きの前にご確認ください', $withdraw['body']);
        $this->assertStringContainsString('name="mode"', $withdraw['body']);
        $this->assertStringContainsString('value="confirm"', $withdraw['body']);
        $csrfToken = $this->csrfToken($withdraw['body']);

        // --- 3. mode=confirm: the final confirmation screen, NOT a withdraw -
        $confirm = $this->form('POST', '/mypage/withdraw', [
            'mode' => 'confirm',
            'csrfToken' => $csrfToken,
        ]);

        $this->assertSame(200, $confirm['status'], $confirm['body']);
        // It is the confirm review screen, not a redirect to completion.
        $this->assertArrayNotHasKey('Location', $confirm['headers']);
        $this->assertStringNotContainsString('/mypage/withdraw-complete', $confirm['body']);
        // EC-CUBE withdraw_confirm.twig markers: the final question + the
        // execute/cancel actions only the confirm screen carries.
        $this->assertStringContainsString('退会手続きを実行してもよろしいでしょうか？', $confirm['body']);
        $this->assertStringContainsString('はい、退会します', $confirm['body']);
        $this->assertStringContainsString('value="complete"', $confirm['body']);
        $this->assertStringContainsString('いいえ、退会しません', $confirm['body']);
        // PROOF: nothing was withdrawn — the customer is still active and the
        // original email is intact in the real dtb_customer table.
        $this->assertSame('2', $this->columnFor($pdo, $email, 'customer_status_id'), 'confirm must NOT withdraw');
        $this->assertSame('2', $this->statusForId($pdo, $customerId));

        // --- 4. mode=complete: the account is actually withdrawn -----------
        $confirmCsrf = $this->csrfToken($confirm['body']);
        $committed = $this->form('POST', '/mypage/withdraw', [
            'mode' => 'complete',
            'csrfToken' => $confirmCsrf,
        ]);

        $this->assertSame(303, $committed['status'], $committed['body']);
        $this->assertSame('/mypage/withdraw-complete', $committed['headers']['Location'] ?? null);
        // PROOF: the customer is now withdrawn (status 3) and the email has
        // been replaced with the reserved withdrawn-* dummy address.
        $this->assertSame('3', $this->statusForId($pdo, $customerId), 'complete must withdraw');
        $this->assertSame(
            sprintf('withdrawn-%d@example.test', $customerId),
            $this->emailForId($pdo, $customerId),
        );

        // The completion page renders the user-visible signal.
        $complete = $this->form('GET', '/mypage/withdraw-complete');
        $this->assertSame(200, $complete['status'], $complete['body']);
        $this->assertStringContainsString('退会が完了いたしました', $complete['body']);
    }

    private function registerCustomer(string $email): void
    {
        $entry = $this->form('GET', '/entry');
        $this->assertSame(200, $entry['status']);
        $csrf = $this->csrfToken($entry['body']);

        $registered = $this->form('POST', '/entry', [
            'name01' => '退会',
            'name02' => 'テスト',
            'kana01' => 'タイカイ',
            'kana02' => 'テスト',
            'companyName' => '',
            'postalCode' => '1000001',
            'pref' => '13',
            'addr01' => '千代田区',
            'addr02' => '1-1',
            'phoneNumber' => '0312345678',
            'email' => $email,
            'email_confirm' => $email,
            'password' => self::PASSWORD,
            'password_confirm' => self::PASSWORD,
            'birth_year' => '1991',
            'birth_month' => '8',
            'birth_day' => '1',
            'sex' => '1',
            'job' => '18',
            'user_policy_check' => '1',
            'mode' => 'confirm',
            'csrfToken' => $csrf,
        ]);
        $this->assertSame(303, $registered['status'], $registered['body']);
        $this->assertSame('/entry/complete', $registered['headers']['Location'] ?? null);
    }

    private function login(string $email): void
    {
        $login = $this->form('GET', '/login');
        $this->assertSame(200, $login['status']);
        $csrf = $this->csrfToken($login['body']);

        $loggedIn = $this->form('POST', '/login', [
            'email' => $email,
            'password' => self::PASSWORD,
            'mode' => 'login',
            'csrfToken' => $csrf,
        ]);
        $this->assertSame(303, $loggedIn['status'], $loggedIn['body']);
        $this->assertSame('/mypage', $loggedIn['headers']['Location'] ?? null);
    }

    private function pdo(): PDO
    {
        $databaseUrl = $_SERVER['DATABASE_URL'] ?? null;
        if (! is_string($databaseUrl) || $databaseUrl === '') {
            self::markTestSkipped('DATABASE_URL is not set; SQL withdraw regression requires the eccubedb_test DB.');
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

        return new PDO(
            $dsn,
            $parts['user'] ?? 'root',
            $parts['pass'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }

    private function columnFor(PDO $pdo, string $email, string $column): string
    {
        $stmt = $pdo->prepare(sprintf('SELECT %s FROM dtb_customer WHERE email = :email', $column));
        $stmt->execute(['email' => $email]);
        $value = $stmt->fetchColumn();
        $this->assertNotFalse($value, sprintf('no dtb_customer row for %s', $email));

        return (string) $value;
    }

    private function statusForId(PDO $pdo, int $id): string
    {
        $stmt = $pdo->prepare('SELECT customer_status_id FROM dtb_customer WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return (string) $stmt->fetchColumn();
    }

    private function emailForId(PDO $pdo, int $id): string
    {
        $stmt = $pdo->prepare('SELECT email FROM dtb_customer WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return (string) $stmt->fetchColumn();
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

        return $match[1];
    }
}
