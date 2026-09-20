<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminLogForm;
use Ray\Di\Di\Named;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function array_map;
use function array_slice;
use function assert;
use function explode;
use function file_get_contents;
use function is_file;
use function mb_substr;
use function rtrim;

/**
 * EC-CUBE ログ表示 — Setting/System Tier-2.
 *
 * GET renderer for `Setting/System/log.twig`. EC-CUBE tails log files
 * from Symfony's log directory; BeMart does the same, but reads ONE
 * FIXED path injected by the module ({@see \MyVendor\BeMart\Module\AppModule}
 * `adminLogPath`) — never a request-supplied filename — so there is no
 * path-traversal surface. Admin-only (403 for anonymous). Read-only:
 * the last {@see LINE_MAX} lines, each truncated to the response-schema
 * bound; an absent file renders the template's 「ログがありません」.
 */
class Log extends ResourceObject
{
    /** Tail size + per-line cap (the `log` schema bounds each line to 255). */
    private const int LINE_MAX = 50;
    private const int LINE_LENGTH = 255;

    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        #[Named('adminLogPath')]
        private readonly string $logPath,
    ) {
    }
    /** ALPS `goLog` に対応する GET 操作。 */
    #[Alps('goLog')]
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
        $form->fillValues('site.log', self::LINE_MAX);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'log' => $this->tail(),
        ];

        return $this;
    }

    /**
     * Last LINE_MAX lines of the module-fixed log file, each truncated to
     * the schema bound. Empty when the file is absent.
     *
     * @return list<string>
     */
    private function tail(): array
    {
        if (! is_file($this->logPath)) {
            return [];
        }

        $lines = explode("\n", rtrim((string) file_get_contents($this->logPath), "\n"));

        return array_map(
            static fn (string $line): string => mb_substr($line, 0, self::LINE_LENGTH),
            array_slice($lines, -self::LINE_MAX),
        );
    }
}
