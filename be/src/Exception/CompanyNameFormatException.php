<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid company name. Must be 100 characters or less.',
    'ja' => '会社名の形式が不正です。100 文字以下で指定してください。',
])]
final class CompanyNameFormatException extends DomainException
{
}
