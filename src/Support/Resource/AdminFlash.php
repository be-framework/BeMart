<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use function array_values;
use function is_array;
use function is_string;

/**
 * Session-backed success-flash store for the admin frame.
 *
 * Port of EC-CUBE 4.3's admin save feedback. EC-CUBE controllers call
 * `$this->addSuccess('admin.common.save_complete', 'admin')` (e.g.
 * {@see tools/ec-cube-source/.../Admin/Setting/System/MemberController.php})
 * which pushes the message onto Symfony's flashbag under the
 * `eccube.admin.success` namespace; the admin frame's `@admin/alert.twig`
 * then renders it as an `alert alert-success` banner near the top of the
 * content area, surviving the POST-redirect-GET because the flashbag lives
 * in the session.
 *
 * BeMart has no Symfony flashbag. This is the minimal faithful equivalent:
 * the admin write resources redirect via {@see HtmlMutationResponse}, which
 * pushes 「保存しました」 onto a flat session key here; the redirected GET
 * renders the admin frame, and {@see \MyVendor\BeMart\Module\BeMartTwigExtension::adminFlashes()}
 * consumes (reads-and-clears) the messages so the banner shows exactly once.
 *
 * The session boundary (`$_SESSION`) is touched directly, consistent with
 * the rest of the html-context session adapters
 * ({@see \MyVendor\BeMart\Auth\EccubeSharedSessionAdapter},
 * {@see \MyVendor\BeMart\Module\BeMartTwigExtension::csrfToken()}).
 *
 * @SuppressWarnings("PHPMD.Superglobals") Session boundary, like csrfToken().
 */
final class AdminFlash
{
    /** EC-CUBE's `eccube.admin.success` flashbag namespace, flattened. */
    public const SESSION_KEY = 'eccube.admin.success';

    /** EC-CUBE messages.ja.yaml `admin.common.save_complete`. */
    public const SAVE_COMPLETE = '保存しました';

    /** Push a success message onto the admin flash store. */
    public static function add(string $message): void
    {
        if (! isset($_SESSION)) {
            return;
        }

        /** @var mixed $existing */
        $existing = $_SESSION[self::SESSION_KEY] ?? [];
        $messages = is_array($existing) ? array_values($existing) : [];
        $messages[] = $message;
        $_SESSION[self::SESSION_KEY] = $messages;
    }

    /**
     * Read and clear the queued success messages (consume-once).
     *
     * @return list<string>
     */
    public static function consume(): array
    {
        if (! isset($_SESSION)) {
            return [];
        }

        /** @var mixed $existing */
        $existing = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);

        if (! is_array($existing)) {
            return [];
        }

        $messages = [];
        foreach ($existing as $message) {
            if (is_string($message) && $message !== '') {
                $messages[] = $message;
            }
        }

        return $messages;
    }
}
