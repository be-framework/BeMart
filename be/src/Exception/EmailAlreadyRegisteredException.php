<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'This email is already registered. Please use a different address or recover the existing account.',
    'ja' => 'このメールアドレスは既に登録されています。別のアドレスを使用するか、既存アカウントの再開手続きを行ってください。',
])]
final class EmailAlreadyRegisteredException extends DomainException
{
}
