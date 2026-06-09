<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support\Hypermedia;

use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Auth\HtmlSessionAdapter;

use function is_array;

final class WorkflowTestSession
{
    /** @param array<string, mixed>|null $previousSession */
    private function __construct(
        private array|null $previousSession,
    ) {
    }

    public static function fromCurrent(): self
    {
        return new self(isset($_SESSION) && is_array($_SESSION) ? $_SESSION : null);
    }

    public function loginAsAdmin(string $adminId, string $csrfToken): void
    {
        $this->setAdminId($adminId);
        $this->setCsrfToken($csrfToken);
    }

    public function setAdminId(string $adminId): void
    {
        $this->ensureSession();
        $_SESSION[HtmlAdminSessionAdapter::ADMIN_ID_KEY] = $adminId;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->ensureSession();
        $_SESSION[HtmlSessionAdapter::CUSTOMER_ID_KEY] = $customerId;
    }

    public function setCsrfToken(string $csrfToken): void
    {
        $this->ensureSession();
        $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = $csrfToken;
    }

    public function restore(): void
    {
        if ($this->previousSession === null) {
            unset($_SESSION);

            return;
        }

        $_SESSION = $this->previousSession;
    }

    private function ensureSession(): void
    {
        if (isset($_SESSION) && is_array($_SESSION)) {
            return;
        }

        $_SESSION = [];
    }
}
