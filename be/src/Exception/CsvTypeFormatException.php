<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

#[Message([
    'en' => 'Invalid csvType. Expected 1=product, 2=customer, 3=order, or 4=shipping.',
    'ja' => 'csvTypeが不正です。1=商品、2=会員、3=受注、4=出荷のいずれかを指定してください。',
])]
final class CsvTypeFormatException extends DomainException
{
}
