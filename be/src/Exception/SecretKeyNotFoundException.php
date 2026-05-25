<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Pilot 7 (doActivateCustomer): no provisional customer carries the
 * supplied secretKey. Either the key is wrong, the link expired (the
 * key was rotated), or activation already ran. The caller MUST NOT
 * disambiguate these cases — surfacing one tells an attacker which
 * keys correspond to live provisional accounts.
 */
#[Message([
    'en' => 'The activation link is invalid or has already been used.',
    'ja' => '本登録リンクが無効か、既に使用済みです。',
])]
final class SecretKeyNotFoundException extends DomainException
{
}
