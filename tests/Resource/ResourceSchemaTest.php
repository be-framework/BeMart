<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Module\TestModule;
use MyVendor\BeMart\Support\Resource\ResourceSchemas;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function dirname;

final class ResourceSchemaTest extends TestCase
{
    private const TEST_ADMIN_ID = 'ad000000000000000000000000000001';

    public function testMajorHappyPathResponsesMatchSchemas(): void
    {
        $resource = $this->resource();

        $productInput = ['productCode' => 'sample-001'];
        ResourceSchemas::productGetInput()->assertMatches($productInput);
        $product = $resource->get('page://self/product', $productInput);
        $this->assertSame(Code::OK, $product->code);
        ResourceSchemas::productGetOk()->assertMatches($product->body);

        $cartInput = ['sessionPrefix' => 'session-prefix-1'];
        ResourceSchemas::cartGetInput()->assertMatches($cartInput);
        $cart = $resource->get('page://self/cart', $cartInput);
        $this->assertSame(Code::OK, $cart->code);
        ResourceSchemas::cartGetOk()->assertMatches($cart->body);

        $loginForm = $resource->get('page://self/login');
        $this->assertSame(Code::OK, $loginForm->code);
        ResourceSchemas::loginGetOk()->assertMatches($loginForm->body);

        $loginInput = [
            'email' => 'login-test@example.com',
            'password' => 'local-dev-member-password',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ];
        ResourceSchemas::loginPostInput()->assertMatches($loginInput);
        $login = $resource->post('page://self/login', $loginInput);
        $this->assertSame(Code::OK, $login->code);
        ResourceSchemas::loginPostOk()->assertMatches($login->body);

        $adminResource = $this->resource(self::TEST_ADMIN_ID);
        $adminProductInput = ['productCode' => 'admin-active-001'];
        ResourceSchemas::adminProductGetInput()->assertMatches($adminProductInput);
        $adminProduct = $adminResource->get('page://self/admin/product', $adminProductInput);
        $this->assertSame(Code::OK, $adminProduct->code);
        ResourceSchemas::adminProductGetOk()->assertMatches($adminProduct->body);
    }

    public function testErrorResponsesThrowMappedExceptions(): void
    {
        // ProductNotFoundException → 404 (mapped in AppThrowableHandler)
        $this->expectException(ProductNotFoundException::class);
        $this->resource()->get('page://self/product', ['productCode' => 'missing-xyz']);
    }

    public function testUnauthenticatedAdminAccessThrowsException(): void
    {
        // UnauthorizedAdminAccessException → 403 (mapped in AppThrowableHandler)
        $this->expectException(UnauthorizedAdminAccessException::class);
        $this->resource(null)->get('page://self/admin/product', ['productCode' => 'admin-active-001']);
    }

    public function testSchemaReportsMissingRequiredField(): void
    {
        $errors = ResourceSchemas::productGetOk()->validate(['productCode' => 'sample-001']);

        $this->assertNotSame([], $errors);
        $this->assertStringContainsString('missing required field `productName`', $errors[0]);
    }

    private function resource(string|null $adminId = null): ResourceInterface
    {
        $base = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        if ($adminId !== null) {
            $session = new FakeAdminSession($adminId);
            $override = new class ($session) extends AbstractModule {
                public function __construct(private readonly FakeAdminSession $session)
                {
                    parent::__construct();
                }

                protected function configure(): void
                {
                    $this->bind(AdminSession::class)->toInstance($this->session);
                }
            };
            $base->override($override);
        }

        $injector = new Injector($base, dirname(__DIR__, 2) . '/var/tmp/test');

        return $injector->getInstance(ResourceInterface::class);
    }
}
