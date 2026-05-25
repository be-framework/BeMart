<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Class category name must be a non-empty string of at most 255 characters.',
    'ja' => '規格分類名は1文字以上255文字以下で入力してください。',
])]
final class ClassCategoryNameFormatException extends DomainException
{
}
