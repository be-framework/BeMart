<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support\Hypermedia;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\ResourceInterface;
use Closure;
use MyVendor\BeMart\Injector;
use Ray\Di\InjectorInterface;

use function assert;

final class WorkflowDbSession
{
    private ResourceInterface|null $resource = null;

    private function __construct(
        private readonly InjectorInterface $injector,
        private readonly ExtendedPdoInterface $db,
        private readonly WorkflowTestSession $session,
    ) {
    }

    /** @param (Closure(WorkflowTestSession, InjectorInterface): void)|null $beforeTransaction */
    public static function start(Closure|null $beforeTransaction = null): self
    {
        $session = WorkflowTestSession::fromCurrent();
        $injector = Injector::getInstance('html-prod-hal-api-app');

        $beforeTransaction?->__invoke($session, $injector);

        $db = $injector->getInstance(ExtendedPdoInterface::class);
        assert($db instanceof ExtendedPdoInterface);
        $db->beginTransaction();

        return new self($injector, $db, $session);
    }

    /** @param (Closure(InjectorInterface): void)|null $beforeTransaction */
    public static function startForAdmin(
        string $adminId,
        string $csrfToken,
        Closure|null $beforeTransaction = null,
    ): self {
        return self::start(static function (
            WorkflowTestSession $session,
            InjectorInterface $injector,
        ) use ($adminId, $csrfToken, $beforeTransaction): void {
            $session->loginAsAdmin($adminId, $csrfToken);
            $beforeTransaction?->__invoke($injector);
        });
    }

    /** @param (Closure(InjectorInterface): void)|null $beforeTransaction */
    public static function startWithCsrfToken(string $csrfToken, Closure|null $beforeTransaction = null): self
    {
        return self::start(static function (
            WorkflowTestSession $session,
            InjectorInterface $injector,
        ) use ($csrfToken, $beforeTransaction): void {
            $session->setCsrfToken($csrfToken);
            $beforeTransaction?->__invoke($injector);
        });
    }

    public function injector(): InjectorInterface
    {
        return $this->injector;
    }

    public function session(): WorkflowTestSession
    {
        return $this->session;
    }

    public function resource(): ResourceInterface
    {
        if ($this->resource instanceof ResourceInterface) {
            return $this->resource;
        }

        $resource = $this->injector->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);
        $this->resource = $resource;

        return $resource;
    }

    /** @param (Closure(): void)|null $afterRollback */
    public function restore(Closure|null $afterRollback = null): void
    {
        $this->rollBack();
        $afterRollback?->__invoke();
        $this->session->restore();
        $this->resource = null;
    }

    private function rollBack(): void
    {
        if (! $this->db->inTransaction()) {
            return;
        }

        $this->db->rollBack();
    }
}
