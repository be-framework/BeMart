<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminLogForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE ログ表示 — Setting/System Tier-2.
 *
 * Thin GET renderer for `Setting/System/log.twig`. EC-CUBE reads log
 * files from Symfony's log directory; BeMart has no ALPS transition for
 * log inspection, so this resource exposes a stable form and a bounded
 * sample body without adding a file-read mutation surface.
 */
class Log extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }
    /** ALPS `goAdminLog` に対応する GET 操作。 */
    #[Alps('goAdminLog')]
    #[JsonSchema(schema: 'get-admin-log.json')]

    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminLogForm::class);
        assert($form instanceof AdminLogForm);
        $form->fillValues('site.log', 50);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'log' => [
                '[2026-05-21T00:00:00+09:00] bemart.INFO: admin log viewer opened',
                '[2026-05-21T00:00:01+09:00] bemart.INFO: no application log file is bundled',
            ],
        ];

        return $this;
    }
}
