<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when the requested taxRuleId does not resolve to any row in
 * the admin tax-rule master (Wave 9θ).
 */
#[Message([
    'en' => 'The requested tax rule was not found.',
    'ja' => '指定された税率ルールは見つかりませんでした。',
])]
final class TaxRuleNotFoundException extends DomainException
{
}
