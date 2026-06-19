<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Auth;

use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the EC-CUBE-compatible {@see \Ray\Csrf\CsrfTokenInterface}
 * adapter.
 *
 *   issue()  -> returns the stored $_SESSION[_csrf_token] reference, or seeds one
 *   verify() -> timing-safe hash_equals against the stored reference
 *   clear()  -> drops the stored reference
 */
final class EccubeSharedCsrfTokenAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        unset($_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
    }

    protected function tearDown(): void
    {
        unset($_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
    }

    public function testVerifyReturnsTrueWhenSubmittedTokenMatchesSession(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertTrue($adapter->verify('session-token-abc'));
    }

    public function testVerifyReturnsFalseWhenSubmittedTokenDoesNotMatchSession(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->verify('different-token'));
    }

    public function testVerifyReturnsFalseWhenNoSessionReferenceExists(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->verify('any-token'));
    }

    public function testVerifyReturnsFalseForEmptyToken(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->verify(''));
    }

    public function testEmptyStringSessionTreatedAsNoReference(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = '';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->verify(''));
        $this->assertFalse($adapter->verify('any-token'));
    }

    public function testNonStringSessionTreatedAsNoReference(): void
    {
        // Defensive: someone misuses the session key with a non-string value.
        // The adapter rejects rather than coercing.
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 12345;

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertFalse($adapter->verify('12345'));
    }

    public function testVerifyHonorsACustomSessionKey(): void
    {
        // Multi-tenant / non-default deployments can mirror a different key.
        $_SESSION['alt_csrf_field'] = 'alt-token-value';

        $adapter = new EccubeSharedCsrfTokenAdapter(sessionKey: 'alt_csrf_field');

        $this->assertTrue($adapter->verify('alt-token-value'));
        $this->assertFalse($adapter->verify('session-token-abc'));

        unset($_SESSION['alt_csrf_field']);
    }

    public function testIssueReturnsStoredSessionReference(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();

        $this->assertSame('session-token-abc', $adapter->issue());
    }

    public function testIssueSeedsAReferenceWhenSessionIsEmpty(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $token = $adapter->issue();

        // A reference is generated, stored back into the session, and accepted
        // by the matching verify() call — the form-render -> form-POST round
        // trip the interface guarantees.
        $this->assertNotSame('', $token);
        $this->assertSame($token, $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY]);
        $this->assertTrue($adapter->verify($token));
    }

    public function testIssueDoesNotRotateAnExistingReference(): void
    {
        $adapter = new EccubeSharedCsrfTokenAdapter();

        $first = $adapter->issue();
        $second = $adapter->issue();

        // Concurrent form pages in one session must all carry the same valid
        // token — issue() seeds once, never rotates.
        $this->assertSame($first, $second);
    }

    public function testIssueHonorsACustomSessionKey(): void
    {
        $_SESSION['alt_csrf_field'] = 'alt-token-value';

        $adapter = new EccubeSharedCsrfTokenAdapter(sessionKey: 'alt_csrf_field');

        $this->assertSame('alt-token-value', $adapter->issue());

        unset($_SESSION['alt_csrf_field']);
    }

    public function testClearRemovesTheStoredReference(): void
    {
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = 'session-token-abc';

        $adapter = new EccubeSharedCsrfTokenAdapter();
        $adapter->clear();

        $this->assertArrayNotHasKey(EccubeSharedCsrfTokenAdapter::SESSION_KEY, $_SESSION);
        $this->assertFalse($adapter->verify('session-token-abc'));
    }
}
