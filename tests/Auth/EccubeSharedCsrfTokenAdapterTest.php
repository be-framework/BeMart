<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use PHPUnit\Framework\TestCase;

use function getenv;
use function putenv;

/**
 * Unit tests for the Slice 8 production CSRF adapter.
 *
 * The adapter has three resolution paths, in order:
 *   1. $_SESSION[_csrf_token] matches submitted token (HTTP context, or
 *      test fixture)
 *   2. CLI + BEMART_CLI_CSRF_TOKEN env var matches submitted token
 *      (operator scripts, subprocess tests)
 *   3. Otherwise reject (false)
 *
 * These tests run under PHP_SAPI=cli (PHPUnit), so the CLI fallback is
 * exercised directly. The HTTP path is covered transitively by
 * ProdModuleTest and AppEntryPointTest (subprocess).
 */
final class EccubeSharedCsrfTokenAdapterTest extends TestCase
{
    private string|false $envBefore;

    protected function setUp(): void
    {
        $this->envBefore = getenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR);
        putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR);
        unset($_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
    }

    protected function tearDown(): void
    {
        unset($_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
        if ($this->envBefore === false) {
            putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR);

            return;
        }

        putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR . '=' . $this->envBefore);
    }

    public function testReturnsTrueWhenSubmittedTokenMatchesSession(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertTrue($adapter->isValid('session-token-abc'));
    }

    public function testReturnsFalseWhenSubmittedTokenDoesNotMatchSession(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid('different-token'));
    }

    public function testReturnsFalseWhenSessionAndEnvUnset(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid('any-token'));
    }

    public function testReturnsFalseForNullToken(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid(null));
    }

    public function testReturnsFalseForEmptyToken(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid(''));
    }

    public function testCliEnvFallbackAcceptsMatchingToken(): void
    {
        putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR . '=cli-trusted-token');

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertTrue($adapter->isValid('cli-trusted-token'));
    }

    public function testCliEnvFallbackRejectsMismatchingToken(): void
    {
        putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR . '=cli-trusted-token');

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid('not-the-cli-token'));
    }

    public function testEmptyStringSessionTreatedAsNoReference(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = '';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid(''));
        $this->assertFalse($adapter->isValid('any-token'));
    }

    public function testNonStringSessionTreatedAsNoReference(): void
    {
        // Defensive: someone misuses the session key with a non-string
        // value. Adapter rejects rather than coercing.
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 12345;

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->isValid('12345'));
    }

    public function testCustomSessionKeyHonored(): void
    {
        // Multi-tenant / non-default deployments can mirror a different key.
        $_SESSION['alt_csrf_field'] = 'alt-token-value';

        $adapter = new EccubeSharedCsrfTokenAdapter(sessionKey: 'alt_csrf_field');

        $this->assertTrue($adapter->isValid('alt-token-value'));
        $this->assertFalse($adapter->isValid('session-token-abc'));
    }

    public function testSessionMatchPreferredOverCliEnvMatch(): void
    {
        // If both stores hold the submitted value, the session match wins
        // (no fallthrough to env). The observable behaviour is the same —
        // true either way — but the test pins down the intended order in
        // case future changes add side effects to one branch.
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'shared-token';
        putenv(EccubeSharedCsrfTokenAdapter::CLI_ENV_VAR . '=shared-token');

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertTrue($adapter->isValid('shared-token'));
    }

    public function testGetTokenReturnsStoredSessionReference(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertSame('session-token-abc', $adapter->getToken());
    }

    public function testGetTokenSeedsAReferenceWhenSessionIsEmpty(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $token = $adapter->getToken();

        // A reference is generated, stored back into the session, and
        // accepted by the matching isValid() call — the form-render ->
        // form-POST round-trip the interface guarantees.
        $this->assertNotSame('', $token);
        $this->assertSame($token, $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
        $this->assertTrue($adapter->isValid($token));
    }

    public function testGetTokenDoesNotRotateAnExistingReference(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $first = $adapter->getToken();
        $second = $adapter->getToken();

        // Concurrent form pages in one session must all carry the same
        // valid token — getToken() seeds once, never rotates.
        $this->assertSame($first, $second);
    }

    public function testGetTokenHonorsACustomSessionKey(): void
    {
        $_SESSION['alt_csrf_field'] = 'alt-token-value';

        $adapter = new EccubeSharedCsrfTokenAdapter(sessionKey: 'alt_csrf_field');

        $this->assertSame('alt-token-value', $adapter->getToken());
    }
}
