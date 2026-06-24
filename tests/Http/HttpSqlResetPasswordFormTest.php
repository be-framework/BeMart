<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PDO;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function explode;
use function http_build_query;
use function is_string;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_split;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

/**
 * Real-HTTP regression for the EC-CUBE パスワード再設定 (reset) screen.
 *
 * DEFECT (reset-error-and-confirm): {@see \MyVendor\BeMart\Resource\Page\Reset}::onPost
 * had two browser-screen defects:
 *
 *  (a) ResetKeyInvalidException / SemanticVariableException (an invalid /
 *      expired / used reset key, or a malformed password) were NOT caught on
 *      the browser-form path — they propagated uncaught and the user landed on
 *      the generic Page/Error.html.twig error page instead of the reset form's
 *      inline message, with no way to correct and retry.
 *
 *  (b) the reset form submits a re-typed `password_confirm`, but onPost
 *      IGNORED it — a confirmation typo was silently accepted and the password
 *      was reset anyway (proven below: the FIRST probe consumed the reset_key
 *      despite the mismatch).
 *
 * EC-CUBE ForgotController::reset (tools/ec-cube-source/.../ForgotController.php)
 * re-renders Forgot/reset.twig with the form errors on a failed submit — a
 * password mismatch (RepeatedPasswordType) or a reset-key/email miss sets
 * `error` and re-renders, never an error page. The fix matches that:
 *   - mismatched password_confirm -> re-render with 「パスワードが一致しません。」
 *   - invalid/expired/malformed key -> re-render with the readable verdict
 *   - the JSON / hypermedia path (no `mode`) keeps THROWING so the
 *     ResetResourceTest 400 expectations stay intact.
 *
 * Proven through the REAL stack — a real php -S server, the
 * html-eccube-sql-hal-app context, a real session + CSRF round-trip, the
 * rendered HTML frame AND the real eccubedb_test dtb_customer.reset_key /
 * .password columns — NOT the static fake fixture suite.
 *
 * The reset key is never returned in any response body (anti-enumeration), so
 * each scenario issues a fresh key by driving the real /forgot-password POST,
 * then reads dtb_customer.reset_key straight off alice's row — the SQL
 * analogue of the emailed reset link.
 */
final class HttpSqlResetPasswordFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18193';
    private const EMAIL = 'alice@example.com';
    private const NEW_PASSWORD = 'reset-sql-password-2026!';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-reset-cookie-');
        // Each scenario starts from a clean token + a known password hash so
        // "did the reset land?" is observable on the real row.
        $pdo = $this->pdo();
        $stmt = $pdo->prepare(
            'UPDATE dtb_customer SET reset_key = NULL, reset_expire = NULL, '
            . "password = 'sentinel-original-hash' WHERE email = :email",
        );
        $stmt->execute(['email' => self::EMAIL]);
    }

    /**
     * DEFECT (b): a re-typed password_confirm that does NOT match must
     * re-render the reset form with an inline 「パスワードが一致しません。」error
     * and must NOT reset the password — the reset_key survives, the password
     * hash is untouched.
     */
    public function testMismatchedConfirmReRendersInlineErrorAndDoesNotReset(): void
    {
        $pdo = $this->pdo();
        $resetKey = $this->issueResetKey($pdo);

        $reset = $this->form('GET', '/reset?resetKey=' . $resetKey);
        $this->assertSame(200, $reset['status'], $reset['body']);
        $csrf = $this->csrfToken($reset['body']);

        $submitted = $this->form('POST', '/reset', [
            'resetKey' => $resetKey,
            'password' => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD . '-typo',
            'mode' => 'commit',
            'csrfToken' => $csrf,
        ]);

        // It re-renders the reset FORM (not a 303 to /login, not an error page).
        $this->assertSame(400, $submitted['status'], $submitted['body']);
        $this->assertArrayNotHasKey('Location', $submitted['headers']);
        $this->assertStringContainsString('class="doResetPassword"', $submitted['body']);
        $this->assertStringContainsString('パスワードが一致しません。', $submitted['body']);
        $this->assertStringNotContainsString('Fatal error', $submitted['body']);
        // The password inputs are never echoed back.
        $this->assertStringNotContainsString(self::NEW_PASSWORD, $submitted['body']);

        // PROOF the reset did NOT happen: the token is still live and the
        // sentinel password hash is unchanged on the real row.
        $this->assertSame($resetKey, $this->columnFor($pdo, 'reset_key'), 'mismatch must NOT consume the token');
        $this->assertSame('sentinel-original-hash', $this->columnFor($pdo, 'password'), 'mismatch must NOT reset password');
    }

    /**
     * DEFECT (a): an invalid reset key on the browser-form path is caught and
     * re-rendered as a readable inline error on the reset form — NOT a 500 and
     * NOT the generic error page.
     */
    public function testInvalidKeyReRendersReadableInlineErrorNotError(): void
    {
        $badKey = 'invalidresetkeyZZZ0123456789abc';

        $reset = $this->form('GET', '/reset?resetKey=' . $badKey);
        $this->assertSame(200, $reset['status'], $reset['body']);
        $csrf = $this->csrfToken($reset['body']);

        $submitted = $this->form('POST', '/reset', [
            'resetKey' => $badKey,
            'password' => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
            'mode' => 'commit',
            'csrfToken' => $csrf,
        ]);

        $this->assertSame(400, $submitted['status'], $submitted['body']);
        $this->assertArrayNotHasKey('Location', $submitted['headers']);
        // The reset form is re-rendered with the readable verdict inline.
        $this->assertStringContainsString('class="doResetPassword"', $submitted['body']);
        $this->assertStringContainsString('無効', $submitted['body']);
        $this->assertStringNotContainsString('Fatal error', $submitted['body']);
        $this->assertStringNotContainsString('Service Unavailable', $submitted['body']);
    }

    /**
     * Happy path stays green: a matching confirm + a fresh valid key
     * Post/Redirect/Gets to /login, consumes the token and rewrites the
     * password hash on the real row.
     */
    public function testMatchingConfirmAndValidKeyResetsAndRedirectsToLogin(): void
    {
        $pdo = $this->pdo();
        $resetKey = $this->issueResetKey($pdo);

        $reset = $this->form('GET', '/reset?resetKey=' . $resetKey);
        $this->assertSame(200, $reset['status'], $reset['body']);
        $csrf = $this->csrfToken($reset['body']);

        $submitted = $this->form('POST', '/reset', [
            'resetKey' => $resetKey,
            'password' => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
            'mode' => 'commit',
            'csrfToken' => $csrf,
        ]);

        $this->assertSame(303, $submitted['status'], $submitted['body']);
        $this->assertSame('/login', $submitted['headers']['Location'] ?? null);

        // PROOF the reset landed: token consumed, password hash rewritten.
        $this->assertNull($this->columnForNullable($pdo, 'reset_key'), 'token must be cleared');
        $this->assertNotSame('sentinel-original-hash', $this->columnFor($pdo, 'password'), 'password must be rewritten');
    }

    /**
     * Issue a fresh reset key by driving the REAL forgot-password POST, then
     * read it straight off alice's dtb_customer row (EC-CUBE stores the token
     * in the reset_key column).
     *
     * @return non-empty-string
     */
    private function issueResetKey(PDO $pdo): string
    {
        $forgot = $this->form('GET', '/forgot-password');
        $this->assertSame(200, $forgot['status'], $forgot['body']);
        $csrf = $this->csrfToken($forgot['body']);

        $requested = $this->form('POST', '/forgot-password', [
            'email' => self::EMAIL,
            'mode' => 'commit',
            'csrfToken' => $csrf,
        ]);
        $this->assertSame(303, $requested['status'], $requested['body']);

        $resetKey = $this->columnFor($pdo, 'reset_key');
        $this->assertNotSame('', $resetKey, 'forgot POST must write a reset_key');

        /** @var non-empty-string $resetKey */
        return $resetKey;
    }

    private function columnFor(PDO $pdo, string $column): string
    {
        return (string) $this->columnForNullable($pdo, $column);
    }

    private function columnForNullable(PDO $pdo, string $column): string|null
    {
        $stmt = $pdo->prepare(sprintf('SELECT %s FROM dtb_customer WHERE email = :email', $column));
        $stmt->execute(['email' => self::EMAIL]);
        $value = $stmt->fetchColumn();
        $this->assertNotFalse($value, sprintf('no dtb_customer row for %s', self::EMAIL));

        return $value === null ? null : (string) $value;
    }

    private function pdo(): PDO
    {
        // phpunit.xml sets DATABASE_URL via <env>, which lands in $_ENV/getenv
        // but NOT $_SERVER — reading $_SERVER alone made this DB assertion skip
        // silently. Read $_ENV first, and fail-loud (not skip) if it is truly
        // absent: this SQL regression must run, not quietly pass.
        $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;
        if (! is_string($databaseUrl) || $databaseUrl === '') {
            self::fail('DATABASE_URL is not set; SQL reset regression requires the eccubedb_test DB to be up.');
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
