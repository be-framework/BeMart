<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Exception;

use Be\Framework\Attribute\Message;
use DomainException;

/**
 * Raised when an admin attempts to update a mail template by an id
 * that does not exist in dtb_mail_template.
 *
 * The resource layer maps this to 404. The migration scope does not
 * cover creating a new template (that requires setting the underlying
 * file_name and is Phase 2 scope); for update only, an unknown id is
 * a hard 404.
 */
#[Message([
    'en' => 'Mail template not found.',
    'ja' => 'メールテンプレートが見つかりませんでした。',
])]
final class MailTemplateNotFoundException extends DomainException
{
}
