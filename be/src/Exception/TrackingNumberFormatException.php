<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when `doUpdateTrackingNumber` is given a tracking number that
 * is empty or exceeds the dtb_shipping `tracking_number` column bound
 * (varchar(255)).
 */
#[Message([
    'en' => 'The tracking number must be between 1 and 255 characters.',
    'ja' => '伝票番号は1文字以上255文字以内で入力してください。',
])]
final class TrackingNumberFormatException extends DomainException
{
}
