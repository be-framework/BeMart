<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function html_entity_decode;
use function http_build_query;
use function is_string;
use function preg_match;
use function preg_quote;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use const ENT_QUOTES;

/**
 * Browser-form smoke for the anonymous cart → checkout entry feature.
 *
 * Hypermedia workflow tests call Resources with perfect parameters; this
 * test submits application/x-www-form-urlencoded forms through the real HTTP
 * router so product/cart form fields, cookies and redirects are covered too.
 */
final class HttpCheckoutEntryFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18181';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-browser-form-cookie-');
    }

    public function testAnonymousCartFormsAndCheckoutEntryDoNotExposeJsonErrors(): void
    {
        $added = $this->form('POST', '/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => '1',
            'operation' => 'add',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(303, $added['status']);
        $this->assertSame('/cart', $added['headers']['Location'] ?? null);

        $cart = $this->form('GET', '/cart');
        $this->assertSame(200, $cart['status']);
        $this->assertStringContainsString('ec-cartRow__amountUpForm', $cart['body']);
        $this->assertStringContainsString('rel="goCheckoutEntry"', $cart['body']);
        $this->assertStringNotContainsString('"code":404', $cart['body']);
        $this->assertStringNotContainsString('"code":401', $cart['body']);

        $increased = $this->form('POST', '/cart/item', [
            'productCode' => 'sample-001',
            'operation' => 'up',
            'quantity' => '4',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(303, $increased['status']);
        $this->assertSame('/cart', $increased['headers']['Location'] ?? null);
        $this->assertStringNotContainsString('"code":404', $increased['body']);

        $decreased = $this->form('POST', '/cart/item', [
            'productCode' => 'sample-001',
            'operation' => 'down',
            'quantity' => '3',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(303, $decreased['status']);
        $this->assertSame('/cart', $decreased['headers']['Location'] ?? null);

        $removed = $this->form('POST', '/cart/item', [
            'productCode' => 'sample-001',
            'operation' => 'remove',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(303, $removed['status']);
        $this->assertSame('/cart', $removed['headers']['Location'] ?? null);

        $entry = $this->form('GET', '/shopping');
        $this->assertSame(303, $entry['status']);
        $this->assertSame('/shopping/login', $entry['headers']['Location'] ?? null);
        $this->assertStringNotContainsString('"code":401', $entry['body']);
        $this->assertStringNotContainsString('doConfirmOrder', $entry['body']);
    }

    public function testNonMemberEmptyBrowserFormRendersValidationErrors(): void
    {
        $form = $this->form('GET', '/shopping/non-member');
        $this->assertSame(200, $form['status']);
        $this->assertStringContainsString('<h1>お客様情報の入力</h1>', $form['body']);
        $this->assertStringContainsString('name="pref"', $form['body']);
        $this->assertStringContainsString('<option value="13">東京都</option>', $form['body']);

        $rejected = $this->form('POST', '/shopping/non-member', [
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
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(400, $rejected['status']);
        $this->assertStringContainsString('text/html', $rejected['headers']['Content-Type'] ?? '');
        $this->assertStringContainsString('<h1>お客様情報の入力</h1>', $rejected['body']);
        $this->assertStringContainsString('入力してください。', $rejected['body']);
        $this->assertStringNotContainsString('Invalid parameter type', $rejected['body']);
        $this->assertStringNotContainsString('application/json', $rejected['headers']['Content-Type'] ?? '');
    }

    public function testNonMemberValidBrowserFormRedirectsToOrderConfirm(): void
    {
        $added = $this->form('POST', '/cart/item', [
            'productCode' => 'sample-001',
            'quantity' => '1',
            'operation' => 'add',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(303, $added['status']);

        $form = $this->form('GET', '/shopping/non-member');
        $this->assertSame(200, $form['status']);
        $this->assertStringContainsString('<h1>お客様情報の入力</h1>', $form['body']);

        $email = 'http-non-member-' . str_replace('.', '-', uniqid('', true)) . '@example.test';
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
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(303, $submitted['status']);
        $location = $submitted['headers']['Location'] ?? '';
        $this->assertStringStartsWith('/shopping/confirm?preOrderId=', $location);
        $this->assertStringContainsString('paymentMethodId=', $location);
        $this->assertStringNotContainsString('Invalid parameter type', $submitted['body']);

        $confirm = $this->form('GET', $location);
        $this->assertSame(200, $confirm['status']);
        $this->assertStringContainsString('ご注文内容', $confirm['body']);
        $this->assertStringContainsString('サンプル商品 A', $confirm['body']);
        $this->assertStringNotContainsString('確認できる注文内容がありません。', $confirm['body']);

        // The 注文する button must carry the submit mode the browser POSTs;
        // without it the checkout cannot redirect to the complete page.
        $this->assertStringContainsString('name="mode" value="complete"', $confirm['body']);
        $checkout = $this->form('POST', '/shopping/checkout', [
            'mode' => 'complete',
            'preOrderId' => $this->inputValue($confirm['body'], 'preOrderId'),
            'csrfToken' => $this->inputValue($confirm['body'], 'csrfToken'),
        ]);
        $this->assertSame(303, $checkout['status'], $checkout['body']);
        $this->assertStringStartsWith('/shopping/complete?orderNo=', $checkout['headers']['Location'] ?? '');
    }

    public function testEntryEmptyBrowserFormRendersValidationErrorsWithoutPasswordEcho(): void
    {
        $form = $this->form('GET', '/entry');
        $this->assertSame(200, $form['status']);
        $this->assertStringContainsString('<h1>新規会員登録</h1>', $form['body']);
        $this->assertStringContainsString('name="email_confirm"', $form['body']);
        $this->assertStringContainsString('name="password_confirm"', $form['body']);

        $rejected = $this->form('POST', '/entry', [
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
            'email' => 'broken',
            'email_confirm' => 'different@example.test',
            'password' => 'short',
            'password_confirm' => 'different-password',
            'birth_year' => '1991',
            'birth_month' => '8',
            'birth_day' => '1',
            'sex' => '1',
            'job' => '',
            'mode' => 'confirm',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(400, $rejected['status']);
        $this->assertStringContainsString('text/html', $rejected['headers']['Content-Type'] ?? '');
        $this->assertStringContainsString('<h1>新規会員登録</h1>', $rejected['body']);
        $this->assertStringContainsString('メールアドレスが一致しません。', $rejected['body']);
        $this->assertStringContainsString('パスワードが一致しません。', $rejected['body']);
        $this->assertStringContainsString('value="broken"', $rejected['body']);
        $this->assertStringNotContainsString('short', $rejected['body']);
        $this->assertStringNotContainsString('different-password', $rejected['body']);
        $this->assertStringNotContainsString('Invalid parameter type', $rejected['body']);
    }

    public function testEntryBrowserFormRegistersWithConfirmFields(): void
    {
        $email = 'http-entry-' . str_replace('.', '-', uniqid('', true)) . '@example.test';

        $fields = [
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
            'password' => 'entry-browser-password-2026',
            'password_confirm' => 'entry-browser-password-2026',
            'birth_year' => '1991',
            'birth_month' => '8',
            'birth_day' => '1',
            'sex' => '1',
            'job' => '18',
            'user_policy_check' => '1',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ];

        // EC-CUBE EntryController two-step flow: `mode=confirm` renders the
        // read-only review screen (no account created); `mode=complete` commits
        // and Post/Redirect/Gets to /entry/complete.
        $confirmed = $this->form('POST', '/entry', $fields + ['mode' => 'confirm']);
        $this->assertSame(200, $confirmed['status'], $confirmed['body']);
        $this->assertArrayNotHasKey('Location', $confirmed['headers']);
        $this->assertStringContainsString('<h1>新規会員登録(確認)</h1>', $confirmed['body']);
        $this->assertStringContainsString('会員登録をする', $confirmed['body']);
        $this->assertStringContainsString('<input type="hidden" name="email" value="' . $email . '"', $confirmed['body']);

        $registered = $this->form('POST', '/entry', $fields + ['mode' => 'complete']);

        $this->assertSame(303, $registered['status']);
        $this->assertSame('/entry/complete', $registered['headers']['Location'] ?? null);
        $this->assertStringNotContainsString('Invalid parameter type', $registered['body']);
    }

    public function testLoginEmptyBrowserFormRendersValidationErrorsWithoutJsonOrPasswordEcho(): void
    {
        $form = $this->form('GET', '/login');
        $this->assertSame(200, $form['status']);
        $this->assertStringContainsString('<h1>ログイン</h1>', $form['body']);
        $this->assertStringContainsString('name="mode" value="login"', $form['body']);

        $rejected = $this->form('POST', '/login', [
            'email' => '',
            'password' => '',
            'mode' => 'login',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(400, $rejected['status']);
        $this->assertStringContainsString('text/html', $rejected['headers']['Content-Type'] ?? '');
        $this->assertStringContainsString('<h1>ログイン</h1>', $rejected['body']);
        $this->assertStringContainsString('入力してください。', $rejected['body']);
        $this->assertStringContainsString('class="ec-errorMessage"', $rejected['body']);
        $this->assertStringNotContainsString('"code":400', $rejected['body']);
        $this->assertStringNotContainsString('application/json', $rejected['headers']['Content-Type'] ?? '');
        $this->assertStringNotContainsString('value="local-dev-member-password"', $rejected['body']);
    }

    public function testAdminLoginWrongCredentialBrowserFormRendersHtmlError(): void
    {
        $form = $this->form('GET', '/admin/login');
        $this->assertSame(200, $form['status']);
        $this->assertStringContainsString('action="/admin/login"', $form['body']);
        $this->assertStringContainsString('name="mode" value="login"', $form['body']);

        $rejected = $this->form('POST', '/admin/login', [
            'loginId' => 'missing-admin',
            'password' => 'wrong-password-2026',
            'mode' => 'login',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(401, $rejected['status']);
        $this->assertStringContainsString('text/html', $rejected['headers']['Content-Type'] ?? '');
        $this->assertStringContainsString('action="/admin/login"', $rejected['body']);
        $this->assertStringContainsString('text-danger', $rejected['body']);
        $this->assertStringContainsString('ログインIDまたはパスワードが正しくありません。', $rejected['body']);
        $this->assertStringNotContainsString('{"code":401', $rejected['body']);
        $this->assertStringNotContainsString('application/json', $rejected['headers']['Content-Type'] ?? '');
        $this->assertStringNotContainsString('value="wrong-password-2026"', $rejected['body']);
    }

    public function testContactEmptyBrowserFormRendersValidationErrors(): void
    {
        $form = $this->form('GET', '/contact');
        $this->assertSame(200, $form['status']);
        $this->assertStringContainsString('<h1>お問い合わせ</h1>', $form['body']);

        $rejected = $this->form('POST', '/contact', [
            'contactName01' => '',
            'contactName02' => '',
            'contactEmail' => '',
            'contactContents' => '',
            'mode' => 'confirm',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(400, $rejected['status']);
        $this->assertStringContainsString('text/html', $rejected['headers']['Content-Type'] ?? '');
        $this->assertStringContainsString('<h1>お問い合わせ</h1>', $rejected['body']);
        $this->assertStringContainsString('入力してください。', $rejected['body']);
        $this->assertStringContainsString('class="ec-halfInput error"', $rejected['body']);
        $this->assertStringContainsString('class="ec-input error"', $rejected['body']);
        $this->assertStringContainsString('class="ec-errorMessage">入力してください。</p>', $rejected['body']);
        $this->assertStringNotContainsString('Invalid parameter type', $rejected['body']);
        $this->assertStringNotContainsString('application/json', $rejected['headers']['Content-Type'] ?? '');
    }

    public function testContactTransportSchemaRejectionIsBadRequestNotServerError(): void
    {
        $rejected = $this->form('POST', '/contact', [
            'contactName01' => '山田',
            'contactName02' => '太郎',
            'contactEmail' => 'broken',
            'contactContents' => str_repeat('x', 5000),
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(400, $rejected['status']);
        // Transport-schema rejection now renders the HTML error page in the
        // html context (HtmlThrowableHandler), not the legacy JSON body —
        // consistent with this class's "do not expose JSON errors" intent.
        $this->assertStringContainsString('text/html', $rejected['headers']['Content-Type'] ?? '');
        $this->assertStringContainsString('Invalid input.', $rejected['body']);
        $this->assertStringContainsString('[contactContents]', $rejected['body']);
        $this->assertStringNotContainsString('Internal Server Error', $rejected['body']);
        $this->assertStringNotContainsString('application/json', $rejected['headers']['Content-Type'] ?? '');
    }

    private function inputValue(string $html, string $name): string
    {
        $pattern = '/<input[^>]*name="' . preg_quote($name, '/') . '"[^>]*value="([^"]*)"/';
        $this->assertSame(1, preg_match($pattern, $html, $match));

        return html_entity_decode($match[1], ENT_QUOTES);
    }

    /**
     * @param array<string, string> $fields
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
}
