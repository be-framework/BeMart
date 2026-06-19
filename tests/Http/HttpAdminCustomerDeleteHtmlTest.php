<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\Code;
use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use PHPUnit\Framework\TestCase;

use function bin2hex;
use function html_entity_decode;
use function is_string;
use function preg_match;
use function preg_quote;
use function random_bytes;

use const ENT_HTML5;
use const ENT_QUOTES;

final class HttpAdminCustomerDeleteHtmlTest extends TestCase
{
    private const HOST = '127.0.0.1:8102';
    private const ADMIN_ID = 'ad000000000000000000000000000001';
    private const CSRF_TOKEN = 'http-admin-delete-customer-csrf-token';

    public function testDeleteCustomerPostUsesHtmlRedirectInsteadOfTemplateRendering(): void
    {
        $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = self::ADMIN_ID;
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = self::CSRF_TOKEN;

        $resource = new HttpResource(
            self::HOST,
            __DIR__ . '/html-sql-index.php',
            __DIR__ . '/log/admin-customer-delete-html.log',
        );
        $email = 'http-admin-delete-customer-' . bin2hex(random_bytes(4)) . '@example.test';

        $list = $resource->get('page://self/admin/customer-list');
        $this->assertSame(Code::OK, $list->code, $list->toString());

        $created = $resource->post('page://self/admin/create-customer', [
            'email' => $email,
            'password' => 'http-admin-delete-password-2026',
            'name01' => '削除',
            'name02' => '対象',
            'kana01' => 'サクジョ',
            'kana02' => 'タイショウ',
            'phoneNumber' => '0312345678',
            'postalCode' => '1000001',
            'pref' => 13,
            'addr01' => '千代田区',
            'addr02' => '1-1',
            'csrfToken' => self::CSRF_TOKEN,
        ]);
        $this->assertSame(Code::SEE_OTHER, $created->code, $created->toString());

        $search = $resource->get('page://self/admin/customer-list', ['emailKeyword' => $email]);
        $this->assertSame(Code::OK, $search->code, $search->toString());
        $this->assertStringContainsString($email, $search->view);
        $customerId = $this->customerIdFromList($search->view, $email);

        $deleted = $resource->post('page://self/admin/delete-customer', [
            'customerId' => $customerId,
            'csrfToken' => self::CSRF_TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $deleted->code, $deleted->toString());
        $this->assertSame('/admin/customer-list', $this->headerString($deleted, 'Location'));
        $this->assertStringNotContainsString('TemplateNotFound', $deleted->view);

        $after = $resource->get('page://self/admin/customer-list', ['emailKeyword' => $email]);
        $this->assertSame(Code::OK, $after->code, $after->toString());
        $this->assertCustomerRowAbsent($after->view, $email);
    }

    private function customerIdFromList(string $html, string $email): string
    {
        $pattern = '/<tr\s+id="ex-customer-([^"]+)"[^>]*>.*?' . preg_quote($email, '/') . '.*?<\/tr>/s';
        $this->assertSame(1, preg_match($pattern, $html, $match), $html);
        $customerId = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
        $this->assertNotSame('', $customerId);

        return $customerId;
    }

    private function assertCustomerRowAbsent(string $html, string $email): void
    {
        $pattern = '/<tr\s+id="ex-customer-[^"]+"[^>]*>.*?' . preg_quote($email, '/') . '.*?<\/tr>/s';
        $this->assertSame(0, preg_match($pattern, $html), $html);
    }

    private function headerString(HttpResponse $response, string $key): string
    {
        $value = $response->headers[$key] ?? null;
        $this->assertTrue(is_string($value) && $value !== '', $response->toString());

        return $value;
    }
}
