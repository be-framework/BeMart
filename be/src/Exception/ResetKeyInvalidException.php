<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Pilot 15 (doResetPassword): the supplied reset key does not currently
 * map to a live, unexpired reset token. Either the key is wrong, the
 * token expired, or it was already consumed by a previous successful
 * reset (single-use per ALPS doc: "キーは1回のみ使用可"). The caller
 * MUST NOT disambiguate these cases — surfacing one tells an attacker
 * whether a given key was ever live.
 *
 * Same merged-failure-mode design as Pilot 7's SecretKeyNotFoundException.
 */
#[Message([
    'en' => 'The password-reset link is invalid or has already been used.',
    'ja' => 'パスワードリセットリンクが無効か、既に使用済みです。',
])]
final class ResetKeyInvalidException extends DomainException
{
}
