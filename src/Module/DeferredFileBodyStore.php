<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;
use Override;

/**
 * Creates the generation directory only once a body is actually stored.
 *
 * `FileBodyStore::__construct()` creates its directory eagerly, and `ObserveModule::configure()`
 * runs on every injector build — before routing decides the request method. A request that records
 * nothing (an `OPTIONS` preflight never reaches `SemanticLogInvoker`'s recorded methods, and
 * `LogFileWriter::write()` returns early when nothing was opened) would therefore add a generation
 * directory while adding no log session. Retention counts the two separately, so the body window
 * would advance ahead of the log window and eventually prune a generation a still-retained log's
 * `body_ref` points at — the failure {@see \MyVendor\BeMart\Module\ObserveModule} exists to prevent.
 *
 * Deferring construction makes a generation strictly imply a stored body, and a stored body implies
 * a recorded request, so generations can only ever be rarer than log sessions. Bodies then outlive
 * the logs referencing them for any retention count the two share.
 */
final class DeferredFileBodyStore implements BodyStoreInterface
{
    private FileBodyStore|null $store = null;

    public function __construct(private readonly string $dir)
    {
    }

    #[Override]
    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
    {
        $this->store ??= new FileBodyStore($this->dir);

        return ($this->store)($request, $ro);
    }
}
