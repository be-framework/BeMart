<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Exception\PreOrderAlreadyClaimedException;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class CheckoutResourceTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        // Default to customer-001 (owns the `aaaa…` / `bbbb…` fixtures).
        // Tests touching `cccc…` (customer-002) or asserting AUTHZ rejection
        // call rebindSession() to swap the session before invoking the
        // resource.
        $this->rebindSession('customer-001');
    }

    /** Build a fresh resource client with the given session customerId (null = anonymous). */
    private function rebindSession(string|null $customerId): void
    {
        $session = new FakeSession($customerId);
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $override = new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(CustomerSession::class)->toInstance($this->session);
            }
        };
        $base->override($override);

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testOnPostCheckoutReturns201WithCompleteBody(): void
    {
        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $ro->body['orderNo']);
        $this->assertSame('customer-001', $ro->body['customerId']);
        $this->assertSame(2250, $ro->body['total']);
        $this->assertSame(2250, $ro->body['paymentTotal']);
        $this->assertSame(22, $ro->body['addPoint']);
        $this->assertSame('', $ro->body['completeMessage']);
        $this->assertArrayHasKey('Location', $ro->headers);
    }

    public function testReplayedCheckoutIsRefused(): void
    {
        // The pre-order is claimed by the first POST. A replay — the
        // double-submitted form, or a second request racing the first —
        // must not reach the gateway or the confirmation mail again.
        $first = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
        $this->assertSame(Code::CREATED, $first->code);

        $this->expectException(PreOrderAlreadyClaimedException::class);
        $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostUnknownPreOrderReturns404(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\PreOrderNotFoundException::class);

        $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'eeee00000000000000000000000000000000eeee',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostInsufficientStockReturns422(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\InsufficientStockException::class);

        $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'bbbb00000000000000000000000000000000bbbb',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostPaymentDeclinedReturns422(): void
    {
        // `cccc…` belongs to customer-002 — rebind so we reach PurchaseFlow
        // rather than tripping AUTHZ first.
        $this->rebindSession('customer-002');

        $this->expectException(\MyVendor\BeMart\Be\Exception\PaymentDeclinedException::class);

        $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'cccc00000000000000000000000000000000cccc',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostForeignCustomerReturns403(): void
    {
        // Phase B Slice 6 (Pilot 5 F-1): a logged-in customer cannot confirm
        // someone else's pre-order. The resource layer maps the domain
        // exception to HTTP 403.
        $this->rebindSession('customer-999');

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedPreOrderAccessException::class);

        $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostAnonymousReturns403(): void
    {
        // Anonymous sessions are also rejected: a customer-scoped pre-order
        // requires the matching logged-in customer.
        $this->rebindSession(null);

        $this->expectException(\MyVendor\BeMart\Be\Exception\UnauthorizedPreOrderAccessException::class);

        $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testOnPostMalformedPreOrderIdReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'not-a-hex-id',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testClientSuppliedPaymentMethodIdIsIgnored(): void
    {
        // Pilot 5 F-2: even if a client tries to inject a different payment
        // method id via the request body, the gateway must be charged against
        // the persisted OrderEntity's paymentMethodId (2 for this preOrderId).
        // We can't directly assert on the gateway here, but the request must
        // still succeed — the extra key is silently ignored.
        $ro = $this->resource->post('page://self/shopping/checkout', [
            'preOrderId' => 'aaaa00000000000000000000000000000000aaaa',
            'paymentMethodId' => 9, // would otherwise trigger PaymentDeclinedException
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::CREATED, $ro->code);
    }

}
