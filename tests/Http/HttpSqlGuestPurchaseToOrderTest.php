<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function escapeshellarg;
use function explode;
use function html_entity_decode;
use function http_build_query;
use function is_string;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_split;
use function random_bytes;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

use const ENT_QUOTES;

/**
 * End-to-end GUEST purchase journey through the real SQL HTTP stack.
 *
 * THE most critical journey: an anonymous visitor must be able to place an
 * order. Earlier browser-form smoke (HttpCheckoutEntryFormTest) drove the
 * checkout POST with a HAND-CRAFTED `mode=checkout` field that the rendered
 * confirm form never actually emitted — so it green-lit a flow a real browser
 * could not perform. The 注文する button carried no submit name/value, so a
 * real POST omitted `mode`; Checkout::onPost then returned 201 CREATED with an
 * empty body instead of a 303 redirect, leaving the browser stranded on
 * /shopping/checkout ("注文できない") even though the order was written.
 *
 * This test drives the journey through ONE cookie jar, following the real
 * in-app affordances (cart レジに進む → /shopping → /shopping/login →
 * /shopping/non-member → /shopping/confirm), and submits the confirm form by
 * harvesting EVERY field the rendered HTML carries — including the submit
 * BUTTON's name/value — exactly as a browser would. It asserts the corrected
 * observable: a 303 SEE_OTHER to /shopping/complete and a complete screen that
 * renders the placed order number. EC-CUBE ref: ShoppingController::checkout
 * always redirectToRoute('shopping_complete') on success.
 */
final class HttpSqlGuestPurchaseToOrderTest extends TestCase
{
    private const HOST = '127.0.0.1:18196';
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
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-guest-purchase-cookie-');
    }

    public function testGuestCompletesOrderThroughRenderedConfirmButton(): void
    {
        // 1. product detail page — issues the add-to-cart CSRF token.
        $product = $this->form('GET', '/product?productCode=' . self::PRODUCT_CODE);
        $this->assertSame(200, $product['status'], $product['body']);

        // 2. add to cart (POST /cart/item operation=add) — Post/Redirect/Get to /cart.
        $added = $this->form('POST', '/cart/item', [
            'productCode' => self::PRODUCT_CODE,
            'quantity' => '1',
            'operation' => 'add',
            'csrfToken' => $this->csrfToken($product['body']),
        ]);
        $this->assertSame(303, $added['status'], $added['body']);
        $this->assertSame('/cart', $added['headers']['Location'] ?? null);

        // 3. cart shows the レジに進む affordance to /shopping.
        $cart = $this->form('GET', '/cart');
        $this->assertSame(200, $cart['status'], $cart['body']);
        $this->assertStringContainsString('レジに進む', $cart['body']);
        $this->assertStringContainsString('href="/shopping"', $cart['body']);

        // 4. guest hits /shopping → redirected to the checkout login/guest gate.
        $shopping = $this->form('GET', '/shopping');
        $this->assertSame(303, $shopping['status'], $shopping['body']);
        $this->assertSame('/shopping/login', $shopping['headers']['Location'] ?? null);

        // 5. guest-info entry form.
        $nonMemberForm = $this->form('GET', '/shopping/non-member');
        $this->assertSame(200, $nonMemberForm['status'], $nonMemberForm['body']);
        $this->assertStringContainsString('<h1>お客様情報の入力</h1>', $nonMemberForm['body']);

        // 6. submit guest info → persists a processing order, redirects to confirm.
        $email = 'guest-purchase-' . bin2hex(random_bytes(4)) . '@example.test';
        $nonMember = $this->form('POST', '/shopping/non-member', [
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
            'csrfToken' => $this->csrfToken($nonMemberForm['body']),
        ]);
        $this->assertSame(303, $nonMember['status'], $nonMember['body']);
        $confirmLocation = $nonMember['headers']['Location'] ?? '';
        $this->assertStringStartsWith('/shopping/confirm?preOrderId=', $confirmLocation);

        // 7. order-confirmation screen with the 注文する submit form.
        $confirm = $this->form('GET', $confirmLocation);
        $this->assertSame(200, $confirm['status'], $confirm['body']);
        $this->assertStringContainsString('<h1>ご注文内容のご確認</h1>', $confirm['body']);
        $this->assertStringContainsString('注文する', $confirm['body']);

        // The 注文する button MUST carry mode=complete; without it the browser
        // POST omits mode and the resource cannot redirect (the exact bug).
        $this->assertStringContainsString(
            'name="mode" value="complete"',
            $confirm['body'],
            'The 注文する submit button must carry name="mode" value="complete" so the real browser POST redirects to the complete page.',
        );

        // 8. submit the confirm form exactly as a browser does: every input the
        // form carries PLUS the clicked submit button's name/value. No field is
        // synthesised by the test.
        $checkoutFields = $this->shoppingFormFields($confirm['body']);
        $this->assertArrayHasKey('preOrderId', $checkoutFields);
        $this->assertArrayHasKey('csrfToken', $checkoutFields);
        $this->assertArrayHasKey('mode', $checkoutFields);
        $this->assertSame('complete', $checkoutFields['mode']);

        $checkout = $this->form('POST', '/shopping/checkout', $checkoutFields);

        // Observable: a guest order placement redirects to the complete page.
        $this->assertSame(303, $checkout['status'], $checkout['body']);
        $completeLocation = $checkout['headers']['Location'] ?? '';
        $this->assertStringStartsWith('/shopping/complete?orderNo=', $completeLocation);
        $this->assertSame(1, preg_match('/orderNo=([0-9a-f]+)/', $completeLocation, $orderMatch));
        $orderNo = $orderMatch[1];

        // 9. the complete screen renders the freshly-placed order number.
        $complete = $this->form('GET', $completeLocation);
        $this->assertSame(200, $complete['status'], $complete['body']);
        $this->assertStringContainsString('ご注文完了', $complete['body']);
        $this->assertStringContainsString($orderNo, $complete['body']);
        $this->assertStringNotContainsString('Service Unavailable', $complete['body']);
        $this->assertStringNotContainsString('Fatal error', $complete['body']);
    }

    /**
     * Harvest the #shopping-form's hidden inputs plus the clicked submit
     * button's name/value, mirroring what a browser sends when 注文する is
     * pressed.
     *
     * @return array<string, string>
     */
    private function shoppingFormFields(string $html): array
    {
        $fields = [];

        // hidden / text inputs inside the confirm form
        if (preg_match_all('/<input[^>]*name="([^"]+)"[^>]*value="([^"]*)"/', $html, $inputs, PREG_SET_ORDER)) {
            foreach ($inputs as $input) {
                $fields[$input[1]] = html_entity_decode($input[2], ENT_QUOTES);
            }
        }

        // the 注文する submit button carries name="mode" value="complete"
        if (preg_match('/<button[^>]*name="([^"]+)"[^>]*value="([^"]*)"[^>]*>\s*注文する/', $html, $button)) {
            $fields[$button[1]] = html_entity_decode($button[2], ENT_QUOTES);
        }

        return $fields;
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
