<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;

use function php_uname;

/**
 * EC-CUBE システム情報 — Setting/System Tier-2.
 *
 * Thin GET renderer for `Setting/System/system.twig`. The screen is
 * operational metadata rather than an ALPS domain transition, but the
 * body is still shaped explicitly so the HTML template does not invent
 * server facts.
 */
class System extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
    ) {
    }

    #[Link(rel: 'doAdminLogout', href: 'page://self/admin/logout', method: 'post')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [
            'info' => [
                ['title' => 'PHP Version', 'value' => PHP_VERSION],
                ['title' => 'PHP SAPI', 'value' => PHP_SAPI],
                ['title' => 'OS', 'value' => PHP_OS_FAMILY],
                ['title' => 'Server', 'value' => php_uname('n')],
                ['title' => 'Application', 'value' => 'BeMart'],
            ],
            'phpinfoEnabled' => false,
        ];

        return $this;
    }
}
