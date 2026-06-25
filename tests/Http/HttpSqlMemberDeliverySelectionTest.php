<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use Koriym\PhpServer\PhpServer;
use PDO;
use PHPUnit\Framework\TestCase;

use function escapeshellarg;
use function explode;
use function html_entity_decode;
use function http_build_query;
use function is_string;
use function parse_str;
use function parse_url;
use function preg_match;
use function preg_match_all;
use function preg_split;
use function shell_exec;
use function sprintf;
use function str_contains;
use function sys_get_temp_dir;
use function tempnam;
use function trim;

use const ENT_QUOTES;

/**
 * Real-HTTP regression for the checkout delivery-method selection.
 *
 * The 配送方法 / お届け日 / お届け時間 selects on /shopping must be POPULATED
 * from the seeded delivery master (dtb_delivery サンプル宅配便 + its
 * dtb_delivery_time slots + dtb_delivery_fee), the member must be able to
 * pick one, and a delivery-selected checkout must complete with the chosen
 * 送料 persisted on the placed dtb_order row.
 *
 * Mirrors HttpSqlMemberPurchaseToOrderTest's member journey through ONE
 * cookie jar; this one pins the delivery <option>s + the seeded 550 送料.
 */
final class HttpSqlMemberDeliverySelectionTest extends TestCase
{
    private const HOST = '127.0.0.1:18248';
    private const PRODUCT_CODE = 'sample-001';
    private const MEMBER_EMAIL = 'login-test@example.com';
    private const MEMBER_PASSWORD = 'local-dev-member-password';
    private const SEEDED_FEE = 550;

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
        $this->cookieJar = (string) tempnam(sys_get_temp_dir(), 'bemart-delivery-cookie-');
    }

    public function testShoppingCarriesDeliveryOptionsAndDeliverySelectedCheckoutCompletes(): void
    {
        // 1. add the seeded product to the cart.
        $product = $this->form('GET', '/product?productCode=' . self::PRODUCT_CODE);
        $this->assertSame(200, $product['status'], $product['body']);
        $added = $this->form('POST', '/cart/item', [
            'productCode' => self::PRODUCT_CODE,
            'quantity' => '1',
            'operation' => 'add',
            'csrfToken' => $this->csrfToken($product['body']),
        ]);
        $this->assertSame(303, $added['status'], $added['body']);

        // 2. log in as the seeded member.
        $login = $this->form('GET', '/login');
        $loggedIn = $this->form('POST', '/login', [
            'email' => self::MEMBER_EMAIL,
            'password' => self::MEMBER_PASSWORD,
            'mode' => 'login',
            'csrfToken' => $this->csrfToken($login['body']),
        ]);
        $this->assertSame(303, $loggedIn['status'], $loggedIn['body']);

        // 3. /shopping — the 配送方法 / お届け日 / お届け時間 selects must be
        //    populated from the seeded delivery master (NOT empty stubs).
        $shopping = $this->form('GET', '/shopping');
        $this->assertSame(200, $shopping['status'], $shopping['body']);

        $deliveryOptions = $this->selectOptions($shopping['body'], 'delivery');
        $this->assertNotEmpty($deliveryOptions, '配送方法 <select> must carry seeded <option> rows');
        $this->assertStringContainsString('サンプル宅配便', $shopping['body'], '配送方法 must show the seeded サンプル宅配便');

        $dateOptions = $this->selectOptions($shopping['body'], 'shipping_delivery_date');
        $this->assertNotEmpty($this->nonEmptyValues($dateOptions), 'お届け日 <select> must carry date <option> rows');

        $timeOptions = $this->selectOptions($shopping['body'], 'delivery_time');
        $this->assertNotEmpty($this->nonEmptyValues($timeOptions), 'お届け時間 <select> must carry slot <option> rows');
        $timeLabels = [];
        foreach ($timeOptions as $option) {
            if ($option['value'] !== '') {
                $timeLabels[] = $option['label'];
            }
        }

        $this->assertContains('午前中', $timeLabels, 'お届け時間 must include the seeded 午前中 slot');

        $this->assertSame(
            1,
            preg_match('/name="preOrderId" value="([^"]+)"/', $shopping['body'], $preMatch),
            $shopping['body'],
        );
        $preOrderId = $preMatch[1];
        $shoppingCsrf = $this->csrfToken($shopping['body']);
        $this->assertSame(1, preg_match('/name="payment" value="([^"]+)"/', $shopping['body'], $payMatch));
        $payment = $payMatch[1];

        // chosen delivery selection = the first real options.
        $deliveryId = $deliveryOptions[0]['value'];
        $deliveryDate = $this->nonEmptyValues($dateOptions)[0];
        $deliveryTime = $timeOptions[0]['value'] !== '' ? $timeOptions[0]['value'] : ($this->nonEmptyValues($timeOptions)[0] ?? '');

        // 4. confirm WITH a selected delivery — the confirm screen must show
        //    the chosen 配送方法 and a NON-ZERO 送料 (the seeded 550).
        $confirm = $this->form('POST', '/shopping/confirm', [
            'csrfToken' => $shoppingCsrf,
            'redirect_to' => '',
            'preOrderId' => $preOrderId,
            'payment' => $payment,
            'delivery' => $deliveryId,
            'shipping_delivery_date' => $deliveryDate,
            'delivery_time' => $deliveryTime,
        ]);
        $this->assertSame(200, $confirm['status'], $confirm['body']);
        $this->assertStringContainsString('<h1>ご注文内容のご確認</h1>', $confirm['body']);
        $this->assertStringContainsString(
            (string) self::SEEDED_FEE,
            $confirm['body'],
            'confirm 送料 must reflect the seeded ' . self::SEEDED_FEE,
        );

        // 5. submit the rendered confirm form as a browser does.
        $checkoutFields = $this->shoppingFormFields($confirm['body']);
        $this->assertSame('complete', $checkoutFields['mode'] ?? null);
        $checkout = $this->form('POST', '/shopping/checkout', $checkoutFields);
        $this->assertSame(303, $checkout['status'], $checkout['body']);
        $completeLocation = $checkout['headers']['Location'] ?? '';
        $this->assertStringStartsWith('/shopping/complete?orderNo=', $completeLocation);
        $this->assertSame(1, preg_match('/orderNo=([0-9a-f]+)/', $completeLocation, $orderMatch));
        $orderNo = $orderMatch[1];

        $complete = $this->form('GET', $completeLocation);
        $this->assertSame(200, $complete['status'], $complete['body']);
        $this->assertStringContainsString('ご注文完了', $complete['body']);

        // 6. the PERSISTED order's delivery_fee_total must be the seeded 送料.
        $feeTotal = $this->placedOrderDeliveryFee($orderNo);
        $this->assertSame(
            self::SEEDED_FEE,
            $feeTotal,
            'persisted dtb_order.delivery_fee_total must reflect the chosen seeded 送料',
        );
    }

    private function placedOrderDeliveryFee(string $orderNo): int
    {
        $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;
        if (! is_string($databaseUrl) || $databaseUrl === '') {
            self::markTestSkipped('DATABASE_URL is not set; delivery-fee regression requires the eccubedb_test DB.');
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

        $pdo = new PDO(
            $dsn,
            $parts['user'] ?? 'root',
            $parts['pass'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $stmt = $pdo->prepare('SELECT delivery_fee_total FROM dtb_order WHERE order_no = :orderNo LIMIT 1');
        $stmt->execute([':orderNo' => $orderNo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($row, 'placed order ' . $orderNo . ' not found in dtb_order');

        return (int) $row['delivery_fee_total'];
    }

    /**
     * Extract the value/label of every <option> inside the named <select>.
     *
     * @return list<array{value: string, label: string}>
     */
    private function selectOptions(string $html, string $name): array
    {
        if (! preg_match('/<select[^>]*name="' . $name . '"[^>]*>(.*?)<\/select>/s', $html, $block)) {
            return [];
        }

        $options = [];
        if (preg_match_all('/<option[^>]*value="([^"]*)"[^>]*>(.*?)<\/option>/s', $block[1], $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $options[] = [
                    'value' => html_entity_decode($match[1], ENT_QUOTES),
                    'label' => trim(html_entity_decode($match[2], ENT_QUOTES)),
                ];
            }
        }

        return $options;
    }

    /**
     * @param list<array{value: string, label: string}> $options
     *
     * @return list<string>
     */
    private function nonEmptyValues(array $options): array
    {
        $values = [];
        foreach ($options as $option) {
            if ($option['value'] !== '') {
                $values[] = $option['value'];
            }
        }

        return $values;
    }

    /** @return array<string, string> */
    private function shoppingFormFields(string $html): array
    {
        $fields = [];

        if (preg_match_all('/<input[^>]*name="([^"]+)"[^>]*value="([^"]*)"/', $html, $inputs, PREG_SET_ORDER)) {
            foreach ($inputs as $input) {
                $fields[$input[1]] = html_entity_decode($input[2], ENT_QUOTES);
            }
        }

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
