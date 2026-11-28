<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support;

use MyVendor\BeMart\Be\Reason\Fake\Service\FakeSession;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Module\MediaQueryQueries;
use Override;
use Ray\Di\AbstractModule;
use Ray\FakeQuery\FakeQueryModule;

use function dirname;

/**
 * Gives the Fake HAL app under `php -S` the bindings of the in-process stories: the FakeQuery fixtures in place of the
 * session-backed storages, and the given customer (a guest when null).
 */
final class HttpStoryModule extends AbstractModule
{
    /** @param non-empty-string|null $customerId */
    public function __construct(
        private readonly string|null $customerId,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $root = dirname(__DIR__, 2);
        $this->install(new FakeQueryModule($root . '/be/var/fake/query', MediaQueryQueries::fromAppRoot($root)));
        $this->bind(CustomerSession::class)->toInstance(new FakeSession($this->customerId));
    }
}
