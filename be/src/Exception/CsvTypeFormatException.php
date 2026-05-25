<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid csvType. Expected 1=order, 2=customer, 3=product, or 4=shipping.',
    'ja' => 'csvTypeが不正です。1=受注、2=会員、3=商品、4=出荷のいずれかを指定してください。',
])]
final class CsvTypeFormatException extends DomainException
{
}
