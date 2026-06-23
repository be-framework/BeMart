<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class ResetResourceTest extends TestCase
{
    private const ALICE_EMAIL = 'alice@example.com';
    private const ALICE_ID = '0123456789abcdef0123456789abcdef';
    private const NEW_PASSWORD = 'new-password-pilot15-2026';
    private const VALID_RESET_KEY = 'valid-reset-key-pilot15-aaaa1111';
    private const EXPIRED_RESET_KEY = 'expired-token-key-pilot15-aaaa1111';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    public function testHappyPath(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => self::VALID_RESET_KEY,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::OK, $ro->code);
        $this->assertSame(self::ALICE_ID, $ro->body['customerId']);
        // No email, no other profile fields — minimize info leak in body.
        $this->assertArrayNotHasKey('email', $ro->body);

        // Password hash persistence is covered by the SQL suite. Fake
        // context is static Ray.FakeQuery fixtures and does not mutate
        // customer state.
    }

    /**
     * A REAL browser form submit (登録する button carries name="mode") must
     * Post/Redirect/Get to the login page on success. Previously onPost
     * returned 200 and the Reset template re-rendered the SAME form, so a
     * successful reset showed the user no observable result at all.
     */
    public function testBrowserFormSubmitRedirectsToLogin(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => self::VALID_RESET_KEY,
            'password' => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
            'mode' => 'commit',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::SEE_OTHER, $ro->code);
        $this->assertSame('/login', $ro->headers['Location']);
    }

    /**
     * DEFECT (reset-error-and-confirm) part (b): a browser form submit whose
     * re-typed `password_confirm` does NOT match the password must re-render
     * the reset form with an inline 「パスワードが一致しません。」error and must
     * NOT reset the password (no 303 to /login).
     */
    public function testBrowserFormMismatchedConfirmReRendersWithInlineError(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => self::VALID_RESET_KEY,
            'password' => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD . '-typo',
            'mode' => 'commit',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertArrayNotHasKey('Location', $ro->headers);
        $this->assertSame('パスワードが一致しません。', $ro->body['message']);
        $this->assertSame('page://self/reset', $ro->body['submitTo']['href']);
    }

    /**
     * DEFECT (reset-error-and-confirm) part (a): an invalid/expired reset key
     * on the BROWSER form path is caught and re-rendered as an inline form
     * error (a readable message + the reset form), not an uncaught throw to a
     * generic error page.
     */
    public function testBrowserFormInvalidKeyReRendersWithInlineError(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => 'unknown-reset-key-not-in-storage-zzzz',
            'password' => self::NEW_PASSWORD,
            'password_confirm' => self::NEW_PASSWORD,
            'mode' => 'commit',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertArrayNotHasKey('Location', $ro->headers);
        $this->assertStringContainsString('無効', $ro->body['message']);
        $this->assertSame('page://self/reset', $ro->body['submitTo']['href']);
    }

    /**
     * DEFECT (reset-error-and-confirm) part (a): a malformed password on the
     * BROWSER form path is caught (SemanticVariableException) and re-rendered
     * inline rather than thrown.
     */
    public function testBrowserFormInvalidPasswordReRendersWithInlineError(): void
    {
        $ro = $this->resource->post('page://self/reset', [
            'resetKey' => self::VALID_RESET_KEY,
            'password' => 'short',
            'password_confirm' => 'short',
            'mode' => 'commit',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);
        $this->assertArrayNotHasKey('Location', $ro->headers);
        $this->assertArrayHasKey('message', $ro->body);
    }

    public function testUnknownKeyReturns400(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\ResetKeyInvalidException::class);

        $this->resource->post('page://self/reset', [
            'resetKey' => 'unknown-reset-key-not-in-storage-zzzz',
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Group('stateful-sql-covered')]
    public function testReusedKeyReturns400(): void
    {
        $this->markTestSkipped('Single-use token mutation is covered by the SQL suite.');
    }

    public function testExpiredKeyReturns400(): void
    {
        $this->expectException(\MyVendor\BeMart\Be\Exception\ResetKeyInvalidException::class);

        $this->resource->post('page://self/reset', [
            'resetKey' => self::EXPIRED_RESET_KEY,
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testInvalidPasswordFormatReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/reset', [
            'resetKey' => self::VALID_RESET_KEY,
            'password' => 'short',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

    public function testInvalidKeyFormatReturns400(): void
    {
        $this->expectException(\Be\Framework\Exception\SemanticVariableException::class);

        $this->resource->post('page://self/reset', [
            'resetKey' => 'short',
            'password' => self::NEW_PASSWORD,
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);
    }

}
