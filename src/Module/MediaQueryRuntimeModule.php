<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Aura\Sql\ExtendedPdoInterface;
use Override;
use Ray\AuraSqlModule\AuraSqlBaseModule;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;

final class MediaQueryRuntimeModule extends AbstractModule
{
    public function __construct(
        private readonly ExtendedPdoInterface|null $connection = null,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->install(new MediaQueryProxyModule());

        if ($this->connection !== null) {
            $this->bind(ExtendedPdoInterface::class)->toInstance($this->connection);
            $this->install(new AuraSqlBaseModule('mysql:'));

            return;
        }

        $database = DatabaseUrl::fromEnvironment();
        /** @psalm-suppress InvalidArgument AuraSqlModule accepts driver option arrays keyed by driver constants. */
        $this->install(new AuraSqlModule(
            $database->dsn,
            $database->user,
            $database->pass,
            options: $database->options,
        ));
    }
}
