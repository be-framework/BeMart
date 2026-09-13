<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\EventSourcing\Resource\FilteredParams;
use BEAR\EventSourcing\Resource\ParamsFilterInterface;
use BEAR\EventSourcing\Resource\SensitiveParamsFilter;
use Override;

use function array_key_exists;

/**
 * Extends bear/event-sourcing's default SensitiveParamsFilter with BeMart's own `Key`-suffixed
 * credential field names.
 *
 * SensitiveParamsFilter deliberately does not match a generic `key` suffix — an app's own
 * `idempotencyKey` is domain input a replay needs, not a secret (see that class's docblock) —
 * so a `resetKey`/`authKey`-shaped field an application actually wants redacted needs its own
 * filter. `resetKey` ({@see \MyVendor\BeMart\Resource\Page\Reset::onPost()}) is BeMart's
 * single-use password-reset token; `authKey` ({@see
 * \MyVendor\BeMart\Resource\Page\Admin\TwoFactorAuthSet::onPut()}) is the TOTP shared secret
 * carried during two-factor device setup. Both are exact field names this application knows,
 * not a naming pattern — matching a blanket `key` suffix here would reintroduce the same
 * idempotencyKey false positive the library avoids.
 */
final class AppParamsFilter implements ParamsFilterInterface
{
    /** @var list<string> exact param key names this application knows are credentials */
    private const array CREDENTIAL_KEYS = ['resetKey', 'authKey'];

    public function __construct(private readonly SensitiveParamsFilter $filter = new SensitiveParamsFilter())
    {
    }

    /** @param array<string, mixed> $params */
    #[Override]
    public function __invoke(array $params): FilteredParams
    {
        $filtered = ($this->filter)($params);
        $params = $filtered->params;
        $replayable = $filtered->replayable;

        foreach (self::CREDENTIAL_KEYS as $key) {
            if (array_key_exists($key, $params)) {
                unset($params[$key]);
                $replayable = false;
            }
        }

        return new FilteredParams($params, $replayable);
    }
}
