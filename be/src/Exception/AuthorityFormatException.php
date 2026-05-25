<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the supplied `authority` does not match an EC-CUBE
 * mtb_authority value (0=システム管理者 / 1=店舗オーナー).
 */
#[Message([
    'en' => 'Invalid authority level. Must be 0 (system admin) or 1 (shop owner).',
    'ja' => '権限の形式が不正です。0（システム管理者）または 1（店舗オーナー）で指定してください。',
])]
final class AuthorityFormatException extends DomainException
{
}
