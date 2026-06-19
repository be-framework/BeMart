<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use Ray\Csrf\CsrfTokenInterface;
use Ray\Csrf\Exception\MissingCsrfTokenException;
use Ray\Csrf\Http\CompositeRequestToken;
use Ray\Csrf\Http\RequestTokenInterface;
use MyVendor\BeMart\Form\AdminPaymentForm;
use MyVendor\BeMart\Module\TestModule;
use MyVendor\BeMart\Support\Resource\HtmlMutationResponse;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;
use function str_contains;

/**
 * Wave 9θ — admin Payment master CRUD resource coverage.
 */
final class AdminPaymentResourceTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';
    private const COD_PAYMENT_ID = 'pay-cod';
    private const CREDIT_PAYMENT_ID = 'pay-credit';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID);
    }

    private function rebindAdminSession(string|null $adminId, bool $htmlMutation = false): void
    {
        $session = new FakeAdminSession($adminId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session, $htmlMutation) extends AbstractModule {
            public function __construct(
                private readonly FakeAdminSession $session,
                private readonly bool $htmlMutation,
            ) {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
                if ($this->htmlMutation) {
                    $this->bind(MutationResponseInterface::class)->to(HtmlMutationResponse::class);
                }
                $this->bind(CsrfTokenInterface::class)->to(FakeCsrfToken::class);
                $this->bind(RequestTokenInterface::class)->to(CompositeRequestToken::class);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function seed(string $name, int $charge = 0): string
    {
        // Static Ray.FakeQuery fixture, not a mutable seed.
        unset($charge);

        return $name === '代金引換' ? self::COD_PAYMENT_ID : self::CREDIT_PAYMENT_ID;
    }

    public function testCreateReturns201(): void
    {
        $ro = $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => 'クレジットカード',
            'charge' => 0,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertSame('クレジットカード', $ro->body['paymentMethodName']);
        $this->assertTrue($ro->body['visible']);
    }

    public function testCreateHtmlContextRedirectsToPaymentDetail(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID, true);
        $ro = $this->resource->post('page://self/admin/payment/payment-list', [
                'paymentMethodName' => 'クレジットカード',
                'charge' => 0,
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertStringContainsString('/admin/payment/payment?paymentId=', $ro->headers['Location']);
    }

    public function testCreateRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->post('page://self/admin/payment/payment-list', [
            'paymentMethodName' => 'クレジットカード',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testListReturnsRows(): void
    {
        $this->seed('代金引換', 300);
        $this->seed('クレジットカード');

        $ro = $this->resource->get('page://self/admin/payment/payment-list');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(2, $ro->body['count']);
    }

    public function testListRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);
        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->get('page://self/admin/payment/payment-list');
    }

    public function testPutEditsMaster(): void
    {
        $id = $this->seed('クレジットカード');

        $ro = $this->resource->put('page://self/admin/payment/payment', [
            'paymentId' => $id,
            'paymentMethodName' => 'クレジット',
            'charge' => 200,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame('クレジット', $ro->body['paymentMethodName']);
        $this->assertSame(200, $ro->body['charge']);
    }

    public function testPutHtmlContextRedirectsToPaymentDetail(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID, true);
        $id = $this->seed('クレジットカード');
        $ro = $this->resource->put('page://self/admin/payment/payment', [
                'paymentId' => $id,
                'paymentMethodName' => 'クレジット',
                'charge' => 200,
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/payment/payment?paymentId=' . $id, $ro->headers['Location']);
    }

    public function testPutUnknownIdReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\PaymentMethodAdminNotFoundException::class);

        $this->resource->put('page://self/admin/payment/payment', [
            'paymentId' => 'nonexistent-zzz',
            'paymentMethodName' => 'X',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testDeleteHappyPath(): void
    {
        $id = $this->seed('代金引換');

        $ro = $this->resource->delete('page://self/admin/payment/payment', [
            'paymentId' => $id,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame($id, $ro->body['paymentId']);
    }

    public function testDeleteHtmlContextRedirectsToPaymentList(): void
    {
        $this->rebindAdminSession(self::TEST_ADMIN_ID, true);
        $id = $this->seed('代金引換');
        $ro = $this->resource->delete('page://self/admin/payment/payment', [
                'paymentId' => $id,
                'csrfToken' => FakeCsrfToken::TOKEN,
            ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/admin/payment/payment-list', $ro->headers['Location']);
    }

    public function testDeleteRejectsMissingCsrf(): void
    {
        $id = $this->seed('代金引換');
        $this->expectException(MissingCsrfTokenException::class);
        $this->resource->delete('page://self/admin/payment/payment', [
            'paymentId' => $id,
        ]);
    }

    public function testOnGetNewReturnsBlankForm(): void
    {
        $ro = $this->resource->get('page://self/admin/payment/payment');

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminPaymentForm::class, $ro->body['form']);
        $this->assertSame('', $ro->body['paymentId']);
        $this->assertNull($ro->body['payment']);
    }

    public function testOnGetReturnsPaymentForm(): void
    {
        $id = $this->seed('クレジットカード', 200);

        $ro = $this->resource->get('page://self/admin/payment/payment', ['paymentId' => $id]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertInstanceOf(AdminPaymentForm::class, $ro->body['form']);
        $this->assertSame($id, $ro->body['paymentId']);
        $this->assertSame('クレジットカード', $ro->body['payment']['paymentMethodName']);
        $this->assertSame(200, $ro->body['payment']['charge']);
    }

    public function testOnGetUnknownIdReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/payment/payment', ['paymentId' => 'nonexistent-zzz']);

        $this->assertSame(Code::NOT_FOUND, $ro->code);
    }

    public function testOnGetRejectsAnonymousAdmin(): void
    {
        $this->rebindAdminSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException::class);

        $this->resource->get('page://self/admin/payment/payment');
    }
}
