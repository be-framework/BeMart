<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use Aura\Sql\ExtendedPdoInterface;
use Override;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\AbstractModule;
use Ray\MediaQuery\MediaQuerySqlModule;

use function dirname;

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
        $root = dirname(__DIR__, 2);
        $this->install(new MediaQuerySqlModule(
            interfaceDir: $root . '/be/src/Reason/Query/MediaQuery',
            sqlDir: $root . '/sql/media-query',
        ));

        if ($this->connection !== null) {
            $this->bind(ExtendedPdoInterface::class)->toInstance($this->connection);

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
