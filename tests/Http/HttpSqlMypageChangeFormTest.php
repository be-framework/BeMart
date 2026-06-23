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
 * SQL-backed browser-form regression for the My Page profile edit
 * (EC-CUBE doUpdateCustomer / mypage_change).
 *
 * DEFECT (mypage-change-complete): {@see \MyVendor\BeMart\Resource\Page\Mypage\Change}
 * ::onPost set only Code::OK + a tiny body — no Location, no redirect — so a
 * successful profile edit re-rendered the same edit form with no observable
 * result, and the existing ChangeComplete page (/mypage/change-complete) was
 * never reached.
 *
 * This test drives the REAL stack (real php -S, real eccubedb_test, real
 * session + cookies + CSRF round-trip): register, log in, GET /mypage/change,
 * POST the FULL edit form INCLUDING the submit button's mode=commit, and assert
 * the EC-CUBE-faithful Post/Redirect/Get: 303 -> /mypage/change-complete and a
 * 変更完了 signal on the rendered complete page.
 */
final class HttpSqlMypageChangeFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18186';
    private const PASSWORD = 'change-sql-password-2026';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-change-cookie-');
    }

    public function testProfileEditBrowserFormRedirectsToChangeComplete(): void
    {
        $email = $this->registerAndLogin();

        // GET the edit form for a real CSRF token.
        $change = $this->form('GET', '/mypage/change');
        $this->assertSame(200, $change['status'], $change['body']);
        $this->assertStringContainsString('<h1>マイページ/会員情報編集</h1>', $change['body']);
        $changeCsrf = $this->csrfToken($change['body']);

        // POST the full edit form the browser sends, INCLUDING the submit
        // button's name/value (mode=commit), changing the phone number.
        $fields = $this->editFields($email, $changeCsrf);
        $fields['phoneNumber'] = '0399998888';
        $submitted = $this->form('POST', '/mypage/change', $fields);

        // EC-CUBE-faithful Post/Redirect/Get.
        $this->assertSame(303, $submitted['status'], $submitted['body']);
        $this->assertSame('/mypage/change-complete', $submitted['headers']['Location'] ?? null);
        $this->assertStringNotContainsString('Service Unavailable', $submitted['body']);
        $this->assertStringNotContainsString('Fatal error', $submitted['body']);

        // The complete page renders the 変更完了 signal.
        $complete = $this->form('GET', '/mypage/change-complete');
        $this->assertSame(200, $complete['status'], $complete['body']);
        $this->assertStringContainsString('<h1>マイページ/会員情報編集(完了)</h1>', $complete['body']);
        $this->assertStringContainsString('会員登録内容の変更が完了いたしました', $complete['body']);

        // Read-back: the patched phone number persisted to dtb_customer.
        $reread = $this->form('GET', '/mypage/change');
        $this->assertSame(200, $reread['status'], $reread['body']);
        $this->assertStringContainsString('value="0399998888"', $reread['body']);
    }

    public function testModelessProfileEditStays200(): void
    {
        $email = $this->registerAndLogin();

        $change = $this->form('GET', '/mypage/change');
        $changeCsrf = $this->csrfToken($change['body']);

        // A hypermedia/JSON client submits no mode -> classic 200, no redirect.
        $fields = $this->editFields($email, $changeCsrf);
        unset($fields['mode']);
        $submitted = $this->form('POST', '/mypage/change', $fields);

        // No mode -> classic 200 (no 303 redirect status), the JSON/hypermedia
        // contract stays green. The edit form page re-renders.
        $this->assertSame(200, $submitted['status'], $submitted['body']);
        $this->assertStringContainsString('<h1>マイページ/会員情報編集</h1>', $submitted['body']);
    }

    private function registerAndLogin(): string
    {
        $entry = $this->form('GET', '/entry');
        $this->assertSame(200, $entry['status']);
        $entryCsrf = $this->csrfToken($entry['body']);
        $email = 'sql-change-' . bin2hex(random_bytes(4)) . '@example.test';

        // EC-CUBE two-step registration: mode=confirm shows the review (200),
        // mode=complete commits and redirects to /entry/complete.
        $confirmed = $this->form('POST', '/entry', $this->validEntryFields($email, $entryCsrf, 'confirm'));
        $this->assertSame(200, $confirmed['status'], $confirmed['body']);
        $confirmCsrf = $this->csrfToken($confirmed['body']);
        $registered = $this->form('POST', '/entry', $this->validEntryFields($email, $confirmCsrf, 'complete'));
        $this->assertSame(303, $registered['status'], $registered['body']);

        $login = $this->form('GET', '/login');
        $loginCsrf = $this->csrfToken($login['body']);
        $loggedIn = $this->form('POST', '/login', [
            'email' => $email,
            'password' => self::PASSWORD,
            'mode' => 'login',
            'csrfToken' => $loginCsrf,
        ]);
        $this->assertSame(303, $loggedIn['status'], $loggedIn['body']);
        $this->assertSame('/mypage', $loggedIn['headers']['Location'] ?? null);

        return $email;
    }

    /** @return array<string, string> */
    private function editFields(string $email, string $csrfToken): array
    {
        return [
            'name01' => '山田',
            'name02' => '太郎',
            'kana01' => 'ヤマダ',
            'kana02' => 'タロウ',
            'companyName' => '',
            'postalCode' => '1500001',
            'pref' => '13',
            'addr01' => '渋谷区',
            'addr02' => '神宮前1-1-1',
            'phoneNumber' => '0312345678',
            'email' => $email,
            'mode' => 'commit',
            'csrfToken' => $csrfToken,
        ];
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
    private function validEntryFields(string $email, string $csrfToken, string $mode = 'confirm'): array
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
            'mode' => $mode,
            'csrfToken' => $csrfToken,
        ];
    }
}
