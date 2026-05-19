<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Category name must be a non-empty string of at most 255 characters.',
    'ja' => 'カテゴリ名は1文字以上255文字以下で入力してください。',
])]
final class CategoryNameFormatException extends DomainException
{
}
