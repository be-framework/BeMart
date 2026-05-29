<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Thrown when a template operation (select / delete / download) names a
 * template id that does not exist. Resource layer maps this to HTTP 404.
 */
#[Message([
    'en' => 'The requested template was not found.',
    'ja' => '指定されたテンプレートは見つかりませんでした。',
])]
final class TemplateNotFoundException extends DomainException
{
}
