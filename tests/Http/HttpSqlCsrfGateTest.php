<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function explode;
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

/**
 * Both BEAR.Csrf gates refuse over a real HTTP boundary.
 *
 * The in-process tiers stand the gates down or satisfy them by binding. This server
 * runs them armed: a POST without the session token, or one a browser marks as
 * cross-site, must come back 403 before the form is evaluated, while the same POST
 * with the token from the rendered page reaches the form.
 */
final class HttpSqlCsrfGateTest extends TestCase
{
    private const HOST = '127.0.0.1:18208';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-csrf-gate-cookie-');
    }

    public function testSameOriginPostWithTheRenderedTokenReachesTheForm(): void
    {
        $token = $this->csrfToken($this->request('GET', '/entry')['body']);

        $submitted = $this->request('POST', '/entry', $this->entryFields($token), ['Sec-Fetch-Site: same-origin']);

        $this->assertSame(400, $submitted['status'], $submitted['body']);
        $this->assertStringContainsString('入力してください。', $submitted['body']);
    }

    public function testPostWithoutTheTokenIsRefused(): void
    {
        $this->request('GET', '/entry');

        $submitted = $this->request('POST', '/entry', $this->entryFields(null), ['Sec-Fetch-Site: same-origin']);

        $this->assertSame(403, $submitted['status'], $submitted['body']);
        $this->assertStringNotContainsString('入力してください。', $submitted['body']);
    }

    public function testPostWithAForeignTokenIsRefused(): void
    {
        $this->request('GET', '/entry');

        $submitted = $this->request('POST', '/entry', $this->entryFields('not-the-session-token'), ['Sec-Fetch-Site: same-origin']);

        $this->assertSame(403, $submitted['status'], $submitted['body']);
    }

    public function testCrossSitePostIsRefusedEvenWithTheToken(): void
    {
        $token = $this->csrfToken($this->request('GET', '/entry')['body']);

        $submitted = $this->request('POST', '/entry', $this->entryFields($token), ['Sec-Fetch-Site: cross-site']);

        $this->assertSame(403, $submitted['status'], $submitted['body']);
    }

    public function testForeignOriginWithoutFetchMetadataIsRefused(): void
    {
        $token = $this->csrfToken($this->request('GET', '/entry')['body']);

        $submitted = $this->request('POST', '/entry', $this->entryFields($token), ['Origin: https://attacker.example']);

        $this->assertSame(403, $submitted['status'], $submitted['body']);
    }

    /**
     * @param array<string, string> $fields
     * @param list<string>          $headers
     *
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function request(string $method, string $path, array $fields = [], array $headers = []): array
    {
        $jar = escapeshellarg($this->cookieJar);
        $curl = sprintf('curl -s -i -b %s -c %s', $jar, $jar);
        foreach ($headers as $header) {
            $curl .= ' -H ' . escapeshellarg($header);
        }

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
    private function entryFields(string|null $csrfToken): array
    {
        $fields = [
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
        if ($csrfToken !== null) {
            $fields['csrfToken'] = $csrfToken;
        }

        return $fields;
    }
}
