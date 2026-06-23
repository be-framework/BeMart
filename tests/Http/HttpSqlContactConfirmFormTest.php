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
 * Real-HTTP regression for the EC-CUBE contact confirm/send state machine.
 *
 * DEFECT (contact-confirm-flow): {@see \MyVendor\BeMart\Resource\Page\Contact::onPost}
 * used to treat ANY `mode !== null` as a final submit, so `mode=confirm`
 * immediately sent the inquiry and 303'd to /contact/complete — SKIPPING the
 * read-only review page entirely; and the confirm screen's 戻る (back) button
 * also sent instead of returning to the editable form.
 *
 * EC-CUBE ContactController (tools/ec-cube-source/.../ContactController.php)
 * branches on the `mode` POST param: `confirm` renders the read-only confirm
 * (review) screen, the confirm screen's 送信する (complete) actually sends and
 * redirects, and 戻る (back) returns to the editable input form. Only commit
 * sends.
 *
 * This proves the fixed flow through the REAL stack — a real php -S server,
 * the html-eccube-sql-hal-app context, real cookies, a real CSRF round-trip,
 * and the rendered HTML frame — NOT the static fake fixture suite:
 *
 *   1. GET /contact -> 200, extract the CSRF token from the rendered form.
 *   2. POST mode=confirm -> 200 CONFIRM (review) page: the entered inquiry is
 *      re-shown read-only AND carried as hidden inputs; it is NOT a redirect
 *      to /contact/complete (nothing sent yet).
 *   3. POST mode=complete -> 303 -> /contact/complete (the inquiry is sent,
 *      proven by the issued ticketId in the Location).
 *   4. POST mode=back -> 200 editable input form re-shown with the entered
 *      values; NOT the confirm page, NOT a send.
 */
final class HttpSqlContactConfirmFormTest extends TestCase
{
    private const HOST = '127.0.0.1:18187';

    private static PhpServer|null $server = null;
    private string $cookieJar;

    public static function setUpBeforeClass(): void
    {
        self::$server = new PhpServer(self::HOST, __DIR__ . '/html-sql-index.php');
        self::$server->start();
    }

    protected function setUp(): void
    {
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-sql-contact-cookie-');
    }

    public function testConfirmModeRendersReviewPageWithoutSendingThenCommitRedirectsToComplete(): void
    {
        $form = $this->form('GET', '/contact');
        $this->assertSame(200, $form['status']);
        $this->assertStringContainsString('<h1>お問い合わせ</h1>', $form['body']);
        $this->assertStringContainsString('確認ページへ', $form['body']);
        $csrfToken = $this->csrfToken($form['body']);

        $contents = 'お問い合わせの本文です。注文番号は12345です。';
        $fields = [
            'contactName01' => '山田',
            'contactName02' => '太郎',
            'contactEmail' => 'yamada-confirm@example.com',
            'contactContents' => $contents,
            'csrfToken' => $csrfToken,
        ];

        // --- mode=confirm: the read-only review page, NOT a send -----------
        $confirm = $this->form('POST', '/contact', $fields + ['mode' => 'confirm']);

        $this->assertSame(200, $confirm['status'], $confirm['body']);
        // It is the CONFIRM (review) screen, not a redirect to completion.
        $this->assertArrayNotHasKey('Location', $confirm['headers']);
        $this->assertStringNotContainsString('/contact/complete', $confirm['body']);
        // EC-CUBE confirm.twig markers: the confirm role + the 送信する /
        // 修正(戻る) actions only the review screen carries.
        $this->assertStringContainsString('ec-contactConfirmRole', $confirm['body']);
        $this->assertStringContainsString('送信する', $confirm['body']);
        $this->assertStringContainsString('value="complete"', $confirm['body']);
        $this->assertStringContainsString('value="back"', $confirm['body']);
        // The entered inquiry is re-shown for review AND carried as hidden
        // inputs so the final submit re-posts it.
        $this->assertStringContainsString($contents, $confirm['body']);
        $this->assertStringContainsString('type="hidden" name="contactContents"', $confirm['body']);
        // The confirm page must NOT show the completion message — nothing sent.
        $this->assertStringNotContainsString('お問い合わせ内容の送信が完了いたしました', $confirm['body']);

        // --- mode=complete: the inquiry is actually sent + redirected ------
        $committed = $this->form('POST', '/contact', $fields + ['mode' => 'complete']);

        $this->assertSame(303, $committed['status'], $committed['body']);
        $location = $committed['headers']['Location'] ?? '';
        $this->assertStringContainsString('/contact/complete', $location);
        // The send-proof: a ticketId is issued only when the inquiry is sent.
        $this->assertSame(1, preg_match('#/contact/complete\?ticketId=INQ-#', $location), $location);
    }

    public function testBackModeReturnsToEditableInputFormWithEnteredValues(): void
    {
        $form = $this->form('GET', '/contact');
        $this->assertSame(200, $form['status']);
        $csrfToken = $this->csrfToken($form['body']);

        $back = $this->form('POST', '/contact', [
            'contactName01' => '佐藤',
            'contactName02' => '花子',
            'contactEmail' => 'hanako-back@example.com',
            'contactContents' => '修正したい本文です。',
            'mode' => 'back',
            'csrfToken' => $csrfToken,
        ]);

        $this->assertSame(200, $back['status'], $back['body']);
        $this->assertArrayNotHasKey('Location', $back['headers']);
        // The editable INPUT form (ec-contactRole + 確認ページへ button), NOT
        // the confirm review screen, NOT a send.
        $this->assertStringContainsString('ec-contactRole', $back['body']);
        $this->assertStringContainsString('確認ページへ', $back['body']);
        $this->assertStringNotContainsString('ec-contactConfirmRole', $back['body']);
        $this->assertStringNotContainsString('/contact/complete', $back['body']);
        // The entered inquiry is pre-filled so the customer can edit it.
        $this->assertStringContainsString('修正したい本文です。', $back['body']);
    }

    public function testEmptyConfirmReRendersInputFormWithInlineErrorsNotConfirmPage(): void
    {
        $form = $this->form('GET', '/contact');
        $this->assertSame(200, $form['status']);
        $csrfToken = $this->csrfToken($form['body']);

        $rejected = $this->form('POST', '/contact', [
            'contactName01' => '',
            'contactName02' => '',
            'contactEmail' => '',
            'contactContents' => '',
            'mode' => 'confirm',
            'csrfToken' => $csrfToken,
        ]);

        // Invalid input on confirm re-renders the editable input form with
        // inline errors — it does NOT advance to the confirm review page.
        $this->assertSame(400, $rejected['status'], $rejected['body']);
        $this->assertStringContainsString('<h1>お問い合わせ</h1>', $rejected['body']);
        $this->assertStringContainsString('入力してください。', $rejected['body']);
        $this->assertStringContainsString('確認ページへ', $rejected['body']);
        $this->assertStringNotContainsString('ec-contactConfirmRole', $rejected['body']);
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
