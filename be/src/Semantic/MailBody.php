<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\MailBodyFormatException;

use function mb_strlen;
use function trim;

/**
 * Mail body — EC-CUBE 4.3 dtb_mail_template.body. Plain-text Twig
 * template body. Non-empty, <= 65,535 chars (MySQL TEXT bound). The
 * length cap is defensive — admins rarely paste anything close to
 * 64 KiB, and unbounded body submissions are an abuse surface.
 *
 * The body may contain Twig variable references (`{{ orderNo }}`,
 * etc.); the Semantic deliberately does NOT validate Twig syntax —
 * that is the template engine's job, and a syntactically valid Twig
 * fragment is still a valid mail body if it never resolves.
 */
final class MailBody
{
    #[Validate]
    public function validate(string $mailBody): void
    {
        $length = mb_strlen($mailBody);
        if (trim($mailBody) === '' || $length > 65535) {
            throw new MailBodyFormatException();
        }
    }
}
