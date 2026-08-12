<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support\Hypermedia;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\ResourceInterface;
use Closure;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Support\Resource\AdminLoginFormSubmissionInterface;
use MyVendor\BeMart\Support\Resource\ApiMutationResponse;
use MyVendor\BeMart\Support\Resource\ExplicitAdminLoginFormSubmission;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Override;
use Ray\Di\AbstractModule;
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
        // In-process hypermedia workflows need Resource operations and
        // fixture/readback queries to share one SQL connection so class-level
        // rollback actually protects operational rows. The `prod` compiled
        // context resolves Resource SQL calls through a separate connection;
        // HTTP workflow tests cover that boundary separately.
        //
        // The HTML context is borrowed only for that shared connection: the
        // workflow drives resources as a Resource/JSON client, so the two
        // browser-shaped ports (mutation response, login form submission) are
        // overridden back to their API bindings.
        $injector = Injector::getOverrideInstance('html-eccube-sql-hal-app', new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->bind(MutationResponseInterface::class)->to(ApiMutationResponse::class);
                $this->bind(AdminLoginFormSubmissionInterface::class)->to(ExplicitAdminLoginFormSubmission::class);
            }
        });

        // Internal hook shape: callers that only need the injector should use
        // startForAdmin() or startWithCsrfToken(), which adapt this signature.
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

        $this->resource = $this->injector->getInstance(ResourceInterface::class);
        assert($this->resource instanceof ResourceInterface);

        return $this->resource;
    }

    /** @param (Closure(): void)|null $afterRollback */
    public function restore(Closure|null $afterRollback = null): void
    {
        $this->rollBack();
        try {
            $afterRollback?->__invoke();
        } finally {
            $this->session->restore();
            $this->resource = null;
        }
    }

    private function rollBack(): void
    {
        if (! $this->db->inTransaction()) {
            return;
        }

        $this->db->rollBack();
    }
}
