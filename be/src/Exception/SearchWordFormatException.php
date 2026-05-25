<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Search keyword string is too long.',
    'ja' => '検索ワード（admin入力）が長すぎます。',
])]
final class SearchWordFormatException extends DomainException
{
}
