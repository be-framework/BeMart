<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Smoke;

use BEAR\AppMeta\Meta as AppMeta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Support\Resource\HtmlMutationResponse;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;
use function md5;
use function str_starts_with;

/**
 * Post/Redirect/Get regression guard (HTML / browser context).
 *
 * Every browser-facing mutation or redirect resource must answer a
 * browser form post with `303 See Other` + `Location`, because a browser
 * does not follow a `Location` returned with `200 OK` / `201 Created`.
 * The JSON / Resource-client contract (200 / 201 + body, with `Location`
 * as a hint) is pinned by {@see ResourceSmokeTest}; this suite pins the
 * HTML counterpart.
 *
 * The wiring mirrors {@see ResourceSmokeModule} (admin firewall, CSRF
 * bypass, customer session) and then rebinds
 * {@see MutationResponseInterface} to {@see HtmlMutationResponse} —
 * exactly what `HtmlModule` installs for the `html` context. Each
 * fixture reuses the proven smoke parameters so a green case here is a
 * faithful HTML-context replay of the same call.
 *
 * Contact is the one mode-driven exception: it runs an EC-CUBE-faithful
 * mode state machine (confirm renders the review screen; only commit
 * sends), so its fixture passes `mode=complete` — the commit step that
 * actually sends and drives the same 303 to /contact/complete.
 */
final class PostRedirectGetHtmlTest extends TestCase
{
    /**
     * @param array<string, mixed> $params
     */
    #[DataProvider('htmlRedirectProvider')]
    public function testBrowserContextEmitsSeeOther(
        string $method,
        string $uri,
        array $params,
        string $expectedLocation,
        bool $locationIsPrefix,
    ): void {
        $resource = $this->resource($method, $uri);

        $ro = $resource->{$method}($uri, $params);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertArrayHasKey('Location', $ro->headers);

        if ($locationIsPrefix) {
            $this->assertStringStartsWith($expectedLocation, $ro->headers['Location']);

            return;
        }

        $this->assertSame($expectedLocation, $ro->headers['Location']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>, 3: string, 4: bool}>
     */
    public static function htmlRedirectProvider(): array
    {
        return [
            'POST page://self/admin/product' => [
                'post',
                'page://self/admin/product',
                ['productCode' => 'wave8-resource-001', 'productName' => 'Smoke Product', 'price02' => '1200'],
                '/admin/product?productCode=wave8-resource-001',
                false,
            ],
            'PUT page://self/admin/product' => [
                'put',
                'page://self/admin/product',
                ['productCode' => 'admin-active-001'],
                '/admin/product?productCode=admin-active-001',
                false,
            ],
            'POST page://self/admin/product-copy' => [
                'post',
                'page://self/admin/product-copy',
                ['productCode' => 'admin-active-001', 'newProductCode' => 'admin-active-001.copy'],
                '/admin/product?productCode=admin-active-001.copy',
                false,
            ],
            'POST page://self/admin/create-customer' => [
                'post',
                'page://self/admin/create-customer',
                ['email' => 'smoke-a1e2c345@example.com', 'password' => 'smoke-passphrase-2026', 'name01' => '山田', 'name02' => '太郎'],
                '/admin/customer?email=smoke-a1e2c345%40example.com',
                false,
            ],
            'POST page://self/admin/mail-template/create' => [
                'post',
                'page://self/admin/mail-template/create',
                ['mailTemplateName' => 'Smoke Template', 'fileName' => 'Mail/smoke_template.twig', 'mailSubject' => 'Smoke mail subject'],
                '/admin/mail-template',
                false,
            ],
            'POST page://self/admin/tax-rule/tax-rule-list' => [
                'post',
                'page://self/admin/tax-rule/tax-rule-list',
                ['taxRate' => '10', 'applyDate' => '2026-01-01T00:00:00+09:00'],
                '/admin/tax-rule/tax-rule?taxRuleId=',
                true,
            ],
            'POST page://self/admin/change-password' => [
                'post',
                'page://self/admin/change-password',
                ['currentPassword' => 'local-dev-admin-password', 'changePasswordFirst' => 'new-strong-password-2026', 'changePasswordSecond' => 'new-strong-password-2026'],
                '/admin/change-password',
                false,
            ],
            'PUT page://self/admin/security' => [
                'put',
                'page://self/admin/security',
                [],
                '/admin/security',
                false,
            ],
            'POST page://self/admin/order/import-shipping' => [
                'post',
                'page://self/admin/order/import-shipping',
                ['csv' => "受注番号,お問い合わせ番号\npast0000000000000000000000000001,TRACK123456789\n"],
                '/admin/order-list',
                false,
            ],
            'POST page://self/admin/action-redirect' => [
                'post',
                'page://self/admin/action-redirect',
                [],
                '/admin',
                false,
            ],
            'GET page://self/admin/action-redirect' => [
                'get',
                'page://self/admin/action-redirect',
                [],
                '/admin',
                false,
            ],
            'POST page://self/admin/unsupported-route' => [
                'post',
                'page://self/admin/unsupported-route',
                [],
                '/admin',
                false,
            ],
            'POST page://self/mypage/reorder' => [
                'post',
                'page://self/mypage/reorder',
                ['orderNo' => 'past0000000000000000000000000001'],
                '/cart',
                false,
            ],
            'POST page://self/contact' => [
                'post',
                'page://self/contact',
                ['contactName01' => '山田', 'contactName02' => '太郎', 'contactEmail' => 'contact-smoke@example.com', 'contactContents' => 'Smoke inquiry body', 'mode' => 'complete'],
                '/contact/complete?ticketId=',
                true,
            ],
            'POST page://self/action-redirect' => [
                'post',
                'page://self/action-redirect',
                [],
                '/',
                false,
            ],
            'GET page://self/action-redirect' => [
                'get',
                'page://self/action-redirect',
                [],
                '/',
                false,
            ],
            'POST page://self/unsupported-route' => [
                'post',
                'page://self/unsupported-route',
                [],
                '/',
                false,
            ],
        ];
    }

    private function resource(string $method, string $uri): ResourceInterface
    {
        $module = new ResourceSmokeModule(
            new AppMeta('MyVendor\\BeMart', 'test'),
            str_starts_with($uri, 'page://self/admin'),
            null,
        );
        $module->override(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(MutationResponseInterface::class)->to(HtmlMutationResponse::class);
            }
        });

        $injector = new Injector(
            $module,
            dirname(__DIR__, 2) . '/var/tmp/prg-html-' . md5($method . ' ' . $uri),
        );

        return $injector->getInstance(ResourceInterface::class);
    }
}
