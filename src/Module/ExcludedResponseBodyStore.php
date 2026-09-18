<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;
use Override;

use function str_starts_with;

/**
 * Skips body storage entirely for every `page://` response.
 *
 * `page://` is BeMart's HTML-rendering layer: a form field filled for prefill (a hidden
 * `authKey`/`resetKey`, a CSRF token embedded for the next POST) is by-design client-facing, not
 * a bug the resource layer should fix — {@see \MyVendor\BeMart\Resource\Page\Reset::onGet()} and
 * {@see \MyVendor\BeMart\Resource\Page\Admin\TwoFactorAuthSet::onGet()} are the two resources this
 * was written against, but a `page://` body's exact shape (which fields a form fills, which
 * template renders what) is not something this decorator can enumerate and stay correct as pages
 * change. The bound `#[Filtered] ParamsFilterInterface` only redacts request params —
 * `SemanticLogInvoker::responseContext()` hands the full, unfiltered `$ro` to `BodyStoreInterface`
 * — so a key-stripping filter on the
 * response body would still miss a value a form embeds in its own filled state independent of the
 * body array (TwoFactorAuthSet's `authKey`), and would need updating every time a new `page://`
 * resource echoes something sensitive. Skipping the whole `page://` scheme is a fixed boundary
 * that does not need to track that.
 *
 * The nested `app://` resource a `page://` resource wraps is unaffected: that is the data layer,
 * still stored, still the more useful half of a page render to observe.
 */
final class ExcludedResponseBodyStore implements BodyStoreInterface
{
    private const string EXCLUDED_SCHEME = 'page://';

    public function __construct(private readonly BodyStoreInterface $store)
    {
    }

    #[Override]
    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
    {
        if (str_starts_with($request->toUri(), self::EXCLUDED_SCHEME)) {
            return null;
        }

        return ($this->store)($request, $ro);
    }
}
