<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;
use function str_contains;

/**
 * Wave 9θ — admin TaxRule master CRUD resource coverage.
 *
 * Note: alps.json has no `doUpdateTaxRule` transition — edits flow as
 * delete + create. Only POST / GET / DELETE are exercised.
 */
final class AdminTaxRuleResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const TAX_10_ID = 'tax-10';
    private const TAX_8_ID = 'tax-8';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSessionInterface::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function seed(float $rate, string $applyDate = '2024-04-01T00:00:00+09:00'): string
    {
        // Static Ray.FakeQuery fixture, not a mutable seed.
        unset($applyDate);

        return $rate === 8.0 ? self::TAX_8_ID : self::TAX_10_ID;
    }

    public function testCreateReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/tax-rule/tax-rule-list', [
            'taxRate' => 10.0,
            'applyDate' => '2024-04-01T00:00:00+09:00',
            'roundingType' => 1,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame(10.0, $ro->body['taxRate']);
        $this->assertSame(1, $ro->body['roundingType']);
        $this->assertSame('2024-04-01T00:00:00+09:00', $ro->body['applyDate']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->post('page://self/admin/tax-rule/tax-rule-list', [
            'taxRate' => 10.0,
            'applyDate' => '2024-04-01T00:00:00+09:00',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testCreateRejectsMissingCsrf(): void
    {
        $ro = $this->resource->post('page://self/admin/tax-rule/tax-rule-list', [
            'taxRate' => 10.0,
            'applyDate' => '2024-04-01T00:00:00+09:00',
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }

    public function testListReturnsRows(): void
    {
        $this->seed(10.0);
        $this->seed(8.0, '2023-10-01T00:00:00+09:00');

        $ro = $this->resource->get('page://self/admin/tax-rule/tax-rule-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $ro = $this->resource->get('page://self/admin/tax-rule/tax-rule-list');
        $this->assertSame(Code::FORBIDDEN, $ro->code);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed(10.0);

        $ro = $this->resource->delete('page://self/admin/tax-rule/tax-rule', [
            'taxRuleId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($id, $ro->body['taxRuleId']);
    }

    public function testDeleteUnknownIdReturns404(): void
    {
        $ro = $this->resource->delete('page://self/admin/tax-rule/tax-rule', [
            'taxRuleId' => 'nonexistent-zzz',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testDeleteRejectsMissingCsrf(): void
    {
        $id = $this->seed(10.0);
        $ro = $this->resource->delete('page://self/admin/tax-rule/tax-rule', [
            'taxRuleId' => $id,
        ]);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertTrue(str_contains($ro->body['message'], 'CSRF'));
    }
}
