<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function escapeshellarg;
use function explode;
use function http_build_query;
use function is_string;
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
 * SQL-backed browser-form regression for public customer registration.
 *
 * The workflow tests prove the ALPS story at Resource level. This HTTP sibling
 * proves the same story through real HTML forms, cookies and the SQL customer
 * table: register, log in, and read the registered email back from My Page.
 */
final class HttpSqlCustomerRegistrationFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18185';
    private const PASSWORD = 'entry-sql-password-2026';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-entry-cookie-');
    }

    public function testEmptyEntryBrowserFormReturnsInlineErrorsWithoutPasswordEcho(): void
    {
        $form = $this->form('GET', '/entry');
        $this->assertSame(200, $form['status']);
        $csrfToken = $this->csrfToken($form['body']);

        $fields = $this->emptyEntryFields();
        $fields['password'] = 'short-secret';
        $fields['password_confirm'] = 'different-secret';
        $fields['csrfToken'] = $csrfToken;

        $submitted = $this->form('POST', '/entry', $fields);

        $this->assertSame(400, $submitted['status'], $submitted['body']);
        $this->assertStringContainsString('会員登録', $submitted['body']);
        $this->assertStringContainsString('入力してください。', $submitted['body']);
        $this->assertStringContainsString('パスワードが一致しません。', $submitted['body']);
        $this->assertStringContainsString('class="idea-form-error"', $submitted['body']);
        $this->assertStringNotContainsString('short-secret', $submitted['body']);
        $this->assertStringNotContainsString('different-secret', $submitted['body']);
        $this->assertStringNotContainsString('Service Unavailable', $submitted['body']);
        $this->assertStringNotContainsString('Fatal error', $submitted['body']);
    }

    public function testCustomerCanRegisterLoginAndReadBackMypageInSqlContext(): void
    {
        $entry = $this->form('GET', '/entry');
        $this->assertSame(200, $entry['status']);
        $entryCsrf = $this->csrfToken($entry['body']);
        $email = 'sql-entry-' . bin2hex(random_bytes(4)) . '@example.test';

        $registered = $this->form('POST', '/entry', $this->validEntryFields($email, $entryCsrf));

        $this->assertSame(303, $registered['status'], $registered['body']);
        $this->assertSame('/entry/complete', $registered['headers']['Location'] ?? null);
        $this->assertStringNotContainsString('Service Unavailable', $registered['body']);

        $complete = $this->form('GET', '/entry/complete');
        $this->assertSame(200, $complete['status'], $complete['body']);
        $this->assertStringContainsString('ご登録ありがとうございます', $complete['body']);

        $login = $this->form('GET', '/login');
        $this->assertSame(200, $login['status']);
        $loginCsrf = $this->csrfToken($login['body']);
        $loggedIn = $this->form('POST', '/login', [
            'email' => $email,
            'password' => self::PASSWORD,
            'mode' => 'login',
            'csrfToken' => $loginCsrf,
        ]);

        $this->assertSame(303, $loggedIn['status'], $loggedIn['body']);
        $this->assertSame('/mypage', $loggedIn['headers']['Location'] ?? null);

        $mypage = $this->form('GET', '/mypage');
        $this->assertSame(200, $mypage['status'], $mypage['body']);
        $this->assertStringContainsString('山田 太郎', $mypage['body']);

        $change = $this->form('GET', '/mypage/change');
        $this->assertSame(200, $change['status'], $change['body']);
        $this->assertStringContainsString('会員情報の編集', $change['body']);
        $this->assertStringContainsString('value="' . $email . '"', $change['body']);
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

    /** @return array<string, string> */
    private function validEntryFields(string $email, string $csrfToken): array
    {
        return [
            'name01' => '山田',
            'name02' => '太郎',
            'kana01' => 'ヤマダ',
            'kana02' => 'タロウ',
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
            'csrfToken' => $csrfToken,
        ];
    }

    /** @return array<string, string> */
    private function emptyEntryFields(): array
    {
        return [
            'name01' => '',
            'name02' => '',
            'kana01' => '',
            'kana02' => '',
            'companyName' => '',
            'postalCode' => '',
            'pref' => '',
            'addr01' => '',
            'addr02' => '',
            'phoneNumber' => '',
            'email' => '',
            'email_confirm' => '',
            'password' => '',
            'password_confirm' => '',
            'birth_year' => '',
            'birth_month' => '',
            'birth_day' => '',
            'sex' => '',
            'job' => '',
            'user_policy_check' => '',
            'mode' => 'confirm',
        ];
    }
}
