<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\MailTemplate;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;

use function array_map;
use function max;

/**
 * Creates a disposable admin mail template row through the Web/HTTP boundary.
 *
 * EC-CUBE stores mail bodies on disk; this Resource only creates the database
 * master row needed for the admin subject/update/delete workflow.
 */
class Create extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly MailTemplateStorageInterface $mailTemplates,
    ) {
    }

    /**
     * Creates an admin mail template database row for workflow verification.
     */
    #[Alps('doCreateMailTemplate')]
    #[JsonSchema(schema: 'post-admin-mail-template-create.json', params: 'post-admin-mail-template-create.param.json')]
    #[Link(rel: 'goMailTemplateList', href: 'page://self/admin/mail-template', method: 'get')]
    #[Link(rel: 'doUpdateMailTemplate', href: 'page://self/admin/mail-template', method: 'post')]
    #[Link(rel: 'doDeleteMailTemplate', href: 'page://self/admin/mail-template', method: 'delete')]
    #[CsrfProtected]
    public function onPost(
        string $mailTemplateName,
        string $fileName,
        string $mailSubject,
    ): static {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $mailTemplateId = $this->nextId();
        $this->mailTemplates->put(new MailTemplateEntity(
            mailTemplateId: $mailTemplateId,
            mailTemplateName: $mailTemplateName,
            fileName: $fileName,
            subject: $mailSubject,
        ));

        $this->code = Code::CREATED;
        $this->headers['Location'] = '/admin/mail-template';
        $this->body = [
            'mailTemplateId' => $mailTemplateId,
            'mailTemplateName' => $mailTemplateName,
            'fileName' => $fileName,
            'mailSubject' => $mailSubject,
        ];

        return $this;
    }

    private function nextId(): int
    {
        $ids = array_map(
            static fn (MailTemplateEntity $mailTemplate): int => $mailTemplate->mailTemplateId,
            $this->mailTemplates->list(),
        );

        if ($ids === []) {
            return 1;
        }

        return max($ids) + 1;
    }
}
