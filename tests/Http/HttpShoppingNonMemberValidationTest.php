<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function http_build_query;
use function is_string;
use function preg_match;
use function preg_split;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;

final class HttpShoppingNonMemberValidationTest extends TestCase
{
    private const HOST = '127.0.0.1:18182';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-nonmember-cookie-');
    }

    public function testEmptyNonMemberSubmitReturnsHtmlBadRequestWithInlineJapaneseErrors(): void
    {
        $get = $this->request('GET', '/shopping/non-member');
        $this->assertSame(200, $get['status']);
        $this->assertMatchesRegularExpression(
            '/name="csrfToken" value="[^"]+"/',
            $get['body'],
            'The rendered HTML form must carry a CSRF token for normal form-submit validation.',
        );
        $this->assertSame(1, preg_match('/name="csrfToken" value="([^"]+)"/', $get['body'], $match));

        $post = $this->request('POST', '/shopping/non-member', ['csrfToken' => $match[1]]);

        $this->assertSame(400, $post['status']);
        $this->assertStringContainsString('text/html', $post['headers']['Content-Type'] ?? '');
        $this->assertStringContainsString('入力してください。', $post['body']);
        $this->assertStringNotContainsString('Invalid parameter type', $post['body']);
        $this->assertStringNotContainsString('"code":400', $post['body']);
    }

    /**
     * @param array<string, string> $fields
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private function request(string $method, string $path, array $fields = []): array
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
        $this->assertSame(1, preg_match('/\s(\d{3})\s/', $statusLine, $statusMatch));

        $headers = [];
        foreach ($lines as $line) {
            if (! is_string($line) || ! str_contains($line, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $line, 2);
            $headers[$name] = trim($value);
        }

        return [
            'status' => (int) $statusMatch[1],
            'headers' => $headers,
            'body' => $body,
        ];
    }
}
