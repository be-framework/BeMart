<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Final;

use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Be\Reason\Service\CustomizeAssetWriterInterface;
use Ray\Di\Di\Inject;
use Ray\InputQuery\Attribute\Input;

use function strlen;

/**
 * Content CSS updated — Final, proof an admin saved the customize CSS
 * (doUpdateContentCss).
 *
 *   UpdateContentCssInput → ContentCssUpdated   (Direct, idempotent)
 *
 * AUTHZ: no admin session → UnauthorizedAdminAccessException (403). The
 * public-file write is delegated to {@see CustomizeAssetWriterInterface}.
 */
final readonly class ContentCssUpdated
{
    public int $length;

    public function __construct(
        #[Input] string $css,
        #[Inject] AdminSession $adminSession,
        #[Inject] CustomizeAssetWriterInterface $assetWriter,
    ) {
        if ($adminSession->adminId === null) {
            throw new UnauthorizedAdminAccessException();
        }

        $assetWriter->writeCss($css);
        $this->length = strlen($css);
    }
}
