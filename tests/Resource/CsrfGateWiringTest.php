<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Csrf\Exception\CrossOriginForbiddenException;
use BEAR\Csrf\Exception\ForbiddenException;
use BEAR\Csrf\Exception\InvalidCsrfTokenForbiddenException;
use BEAR\Csrf\Exception\MissingCsrfTokenForbiddenException;
use BEAR\Csrf\Http\AllowedOrigin;
use BEAR\Csrf\Http\RequestOriginInterface;
use BEAR\Csrf\Http\RequestTokenInterface;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Router\RouterMatch;
use MyVendor\BeMart\Module\TestModule;
use MyVendor\BeMart\Provide\Error\ExceptionStatusMapper;
use MyVendor\BeMart\Provide\Error\HtmlThrowableHandler;
use MyVendor\BeMart\Tests\Fake\Csrf\FakeRequestOrigin;
use MyVendor\BeMart\Tests\Fake\Csrf\FakeRequestToken;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use MyVendor\BeMart\Tests\Support\RecordingErrorHandler;
use MyVendor\BeMart\Tests\Support\RecordingResponder;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Twig\Environment;

use function dirname;

/**
 * The CSRF gates are BEAR.Csrf's; this proves they stand in front of BeMart's pages.
 *
 * FakeModule stands both gates down, so every other resource test is silent about CSRF.
 * Here the fakes are rebound one at a time to make each gate refuse, and a refusal is
 * followed to the 403 page the HTML context renders. {@see CsrfProtectionCoverageTest}
 * proves every unsafe page verb carries the attributes these gates key on.
 */
final class CsrfGateWiringTest extends TestCase
{
    private const ALLOWED_ORIGIN = 'https://shop.example';
    private const LOGIN = ['email' => 'login-test@example.com', 'password' => 'local-dev-member-password'];

    public function testAMissingTokenIsRefusedBeforeTheResourceRuns(): void
    {
        $resource = $this->resource(requestToken: new FakeRequestToken(null));

        $this->expectException(MissingCsrfTokenForbiddenException::class);
        $resource->post('page://self/login', self::LOGIN);
    }

    public function testAWrongTokenIsRefused(): void
    {
        $resource = $this->resource(requestToken: new FakeRequestToken('not-the-session-token'));

        $this->expectException(InvalidCsrfTokenForbiddenException::class);
        $resource->post('page://self/login', self::LOGIN);
    }

    public function testACrossSiteRequestIsRefusedOnceAnOriginIsConfigured(): void
    {
        $resource = $this->resource(
            allowedOrigin: new AllowedOrigin(self::ALLOWED_ORIGIN),
            requestOrigin: new FakeRequestOrigin(fetchSite: 'cross-site'),
        );

        $this->expectException(CrossOriginForbiddenException::class);
        $resource->post('page://self/login', self::LOGIN);
    }

    public function testARequestWithoutAnyOriginEvidenceIsRefused(): void
    {
        $resource = $this->resource(
            allowedOrigin: new AllowedOrigin(self::ALLOWED_ORIGIN),
            requestOrigin: new FakeRequestOrigin(),
        );

        $this->expectException(CrossOriginForbiddenException::class);
        $resource->post('page://self/login', self::LOGIN);
    }

    public function testASameOriginRequestWithTheSessionTokenPassesBothGates(): void
    {
        $resource = $this->resource(
            allowedOrigin: new AllowedOrigin(self::ALLOWED_ORIGIN),
            requestOrigin: new FakeRequestOrigin(fetchSite: 'same-origin'),
        );

        $ro = $resource->post('page://self/login', self::LOGIN);

        $this->assertSame(Code::OK, $ro->code);
    }

    public function testSafeVerbsAreNotGated(): void
    {
        $resource = $this->resource(
            requestToken: new FakeRequestToken(null),
            allowedOrigin: new AllowedOrigin(self::ALLOWED_ORIGIN),
            requestOrigin: new FakeRequestOrigin(fetchSite: 'cross-site'),
        );

        $ro = $resource->get('page://self/login');

        $this->assertSame(Code::OK, $ro->code);
    }

    /** @return iterable<string, array{ForbiddenException}> */
    public static function refusals(): iterable
    {
        yield 'missing token' => [new MissingCsrfTokenForbiddenException()];
        yield 'invalid token' => [new InvalidCsrfTokenForbiddenException()];
        yield 'cross-origin' => [new CrossOriginForbiddenException('Same-origin policy: Sec-Fetch-Site: cross-site.')];
    }

    #[DataProvider('refusals')]
    public function testARefusalIsA403CarryingTheGatesOwnMessage(ForbiddenException $e): void
    {
        $mapper = new ExceptionStatusMapper();

        $this->assertSame(Code::FORBIDDEN, $mapper->status($e));
        $this->assertSame($e->getMessage(), $mapper->message($e, Code::FORBIDDEN));
    }

    public function testTheHtmlContextRendersARefusalAsAnErrorPage(): void
    {
        $responder = new RecordingResponder();
        $handler = new HtmlThrowableHandler(
            $responder,
            new RecordingErrorHandler(),
            new ExceptionStatusMapper(),
            HtmlTestInjector::getInstance()->getInstance(Environment::class),
        );

        $handler->handle(new MissingCsrfTokenForbiddenException(), new RouterMatch());
        $handler->transfer();

        $ro = $responder->ro;
        $this->assertInstanceOf(ResourceObject::class, $ro);
        $this->assertSame(Code::FORBIDDEN, $ro->code);
        $this->assertStringContainsString('CSRF token missing.', (string) $ro->view);
    }

    private function resource(
        RequestTokenInterface|null $requestToken = null,
        AllowedOrigin|null $allowedOrigin = null,
        RequestOriginInterface|null $requestOrigin = null,
    ): ResourceInterface {
        $module = new TestModule(new Meta('MyVendor\\BeMart', 'test'));
        $module->override(new class ($requestToken, $allowedOrigin, $requestOrigin) extends AbstractModule {
            public function __construct(
                private readonly RequestTokenInterface|null $requestToken,
                private readonly AllowedOrigin|null $allowedOrigin,
                private readonly RequestOriginInterface|null $requestOrigin,
            ) {
                parent::__construct();
            }

            #[Override]
            protected function configure(): void
            {
                if ($this->requestToken !== null) {
                    $this->bind(RequestTokenInterface::class)->toInstance($this->requestToken);
                }

                if ($this->allowedOrigin !== null) {
                    $this->bind(AllowedOrigin::class)->toInstance($this->allowedOrigin);
                }

                if ($this->requestOrigin !== null) {
                    $this->bind(RequestOriginInterface::class)->toInstance($this->requestOrigin);
                }
            }
        });

        return (new Injector($module, dirname(__DIR__, 2) . '/var/tmp/test'))->getInstance(ResourceInterface::class);
    }
}
