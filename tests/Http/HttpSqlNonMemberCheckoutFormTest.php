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
 * Browser-form regression against the real SQL context.
 *
 * The fake HTTP smoke only proves form encoding and redirect shape. This
 * sibling uses html-eccube-sql-hal-app so the non-member pre-order is written
 * through dtb_order and its payment_id FK must resolve to a real dtb_payment
 * installer master row.
 */
final class HttpSqlNonMemberCheckoutFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18182';

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
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-non-member-cookie-');
    }

    public function testValidNonMemberBrowserFormReachesSqlConfirmPage(): void
    {
        $form = $this->form('GET', '/shopping/non-member');
        $this->assertSame(200, $form['status']);
        $this->assertStringContainsString('<h1>お客様情報の入力</h1>', $form['body']);
        $csrfToken = $this->csrfToken($form['body']);

        $email = 'sql-non-member-' . bin2hex(random_bytes(4)) . '@example.test';
        $submitted = $this->form('POST', '/shopping/non-member', [
            'name01' => '山田',
            'name02' => '太郎',
            'kana01' => 'ヤマダ',
            'kana02' => 'タロウ',
            'companyName' => '',
            'email' => $email,
            'email_confirm' => $email,
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => '13',
            'addr01' => '千代田区',
            'addr02' => '1-1',
            'csrfToken' => $csrfToken,
        ]);

        $this->assertSame(303, $submitted['status'], $submitted['body']);
        $location = $submitted['headers']['Location'] ?? '';
        $this->assertStringStartsWith('/shopping/confirm?preOrderId=', $location);
        $this->assertStringContainsString('paymentMethodId=1', $location);
        $this->assertStringNotContainsString('PdoPerformException', $submitted['body']);
        $this->assertStringNotContainsString('Integrity constraint violation', $submitted['body']);

        $confirm = $this->form('GET', $location);
        $this->assertSame(200, $confirm['status'], $confirm['body']);
        $this->assertStringContainsString('<h1>ご注文内容のご確認</h1>', $confirm['body']);
        $this->assertStringContainsString($email, $confirm['body']);
        $this->assertStringContainsString('代金引換', $confirm['body']);
        $this->assertStringNotContainsString('Service Unavailable', $confirm['body']);
    }

    public function testEmptyNonMemberBrowserFormReturnsInlineErrorsWithoutException(): void
    {
        $form = $this->form('GET', '/shopping/non-member');
        $this->assertSame(200, $form['status']);
        $csrfToken = $this->csrfToken($form['body']);

        $fields = $this->emptyNonMemberFields();
        $fields['csrfToken'] = $csrfToken;
        $submitted = $this->form('POST', '/shopping/non-member', $fields);

        $this->assertSame(400, $submitted['status'], $submitted['body']);
        $this->assertStringContainsString('<h1>お客様情報の入力</h1>', $submitted['body']);
        $this->assertStringContainsString('入力してください。', $submitted['body']);
        $this->assertStringContainsString('class="ec-halfInput error"', $submitted['body']);
        $this->assertStringContainsString('class="ec-input error"', $submitted['body']);
        $this->assertStringContainsString('class="ec-errorMessage">入力してください。</p>', $submitted['body']);
        $this->assertStringContainsString('name="name01"', $submitted['body']);
        $this->assertStringContainsString('name="email_confirm"', $submitted['body']);
        $this->assertStringNotContainsString('PdoPerformException', $submitted['body']);
        $this->assertStringNotContainsString('Service Unavailable', $submitted['body']);
        $this->assertStringNotContainsString('Fatal error', $submitted['body']);
    }

    public function testEmailMismatchNonMemberBrowserFormRedisplaysValuesAndError(): void
    {
        $form = $this->form('GET', '/shopping/non-member');
        $this->assertSame(200, $form['status']);
        $csrfToken = $this->csrfToken($form['body']);

        $email = 'sql-non-member-mismatch-' . bin2hex(random_bytes(4)) . '@example.test';
        $confirmEmail = 'different-' . bin2hex(random_bytes(4)) . '@example.test';
        $fields = $this->validNonMemberFields($email);
        $fields['email_confirm'] = $confirmEmail;
        $fields['csrfToken'] = $csrfToken;

        $submitted = $this->form('POST', '/shopping/non-member', $fields);

        $this->assertSame(400, $submitted['status'], $submitted['body']);
        $this->assertStringContainsString('<h1>お客様情報の入力</h1>', $submitted['body']);
        $this->assertStringContainsString('メールアドレスが一致しません。', $submitted['body']);
        $this->assertStringContainsString('value="' . $email . '"', $submitted['body']);
        $this->assertStringContainsString('value="' . $confirmEmail . '"', $submitted['body']);
        $this->assertStringNotContainsString('PdoPerformException', $submitted['body']);
        $this->assertStringNotContainsString('Service Unavailable', $submitted['body']);
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
    private function validNonMemberFields(string $email): array
    {
        return [
            'name01' => '山田',
            'name02' => '太郎',
            'kana01' => 'ヤマダ',
            'kana02' => 'タロウ',
            'companyName' => '',
            'email' => $email,
            'email_confirm' => $email,
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => '13',
            'addr01' => '千代田区',
            'addr02' => '1-1',
        ];
    }

    /** @return array<string, string> */
    private function emptyNonMemberFields(): array
    {
        return [
            'name01' => '',
            'name02' => '',
            'kana01' => '',
            'kana02' => '',
            'companyName' => '',
            'email' => '',
            'email_confirm' => '',
            'phoneNumber' => '',
            'postalCode' => '',
            'pref' => '',
            'addr01' => '',
            'addr02' => '',
        ];
    }
}
