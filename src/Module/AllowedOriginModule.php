<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Csrf\Http\AllowedOrigin;
use Override;
use Ray\Di\AbstractModule;

/**
 * Arms BEAR.Csrf's same-origin gate with a concrete origin.
 *
 * {@see AppModule} always installs `CsrfModule::withoutSameOriginCheck()`, so
 * `AllowedOrigin` is null (gate stood down) unless something composed after it
 * rebinds the value. This module is that rebinding: the context that knows the
 * canonical origin — `ProdModule` from the env, a test harness from the request
 * it is about to issue — installs it to turn the gate on.
 */
final class AllowedOriginModule extends AbstractModule
{
    public function __construct(private readonly string $origin)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->bind(AllowedOrigin::class)->toInstance(new AllowedOrigin($this->origin));
    }
}
