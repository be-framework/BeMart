<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\MailTemplateNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MailTemplateListFetched;
use MyVendor\BeMart\Be\Final\MailTemplateUpdated;
use MyVendor\BeMart\Be\Input\GetMailTemplateListInput;
use MyVendor\BeMart\Be\Input\UpdateMailTemplateInput;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminMailTemplateForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doUpdateMailTemplate + goMailTemplateList — メールテンプレート
 * (Wave 8 + Wave 9).
 *
 *   - GET  → goMailTemplateList (collection list, safe, admin, Wave 9ι)
 *   - POST → doUpdateMailTemplate (per-id update, idempotent, Wave 8ε)
 *
 * The migration scope only covers UPDATE of the subject — creating a
 * new template requires setting the underlying file_name, which is
 * Phase 2 scope. 厳密移植 alignment: dtb_mail_template has no body
 * columns (mail bodies are on-disk Twig files), so the former
 * mailBody / mailHtmlBody inputs were dropped.
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403 (POST only)
 *   - SemanticVariableException             → 400 (subject format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - MailTemplateNotFoundException         → 404 (unknown id)
 */
class MailTemplate extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly MailTemplateStorageInterface $mailTemplates,
    ) {
    }

    /**
     * Wave 9ι: goMailTemplateList — admin lists every mail template.
     */
    #[Alps('goMailTemplateList')]
    #[JsonSchema(schema: 'get-admin-mail-template.json')]
    #[Link(rel: 'doUpdateMailTemplate', href: 'page://self/admin/mail-template', method: 'post')]
    #[Link(rel: 'goOrderMail', href: 'page://self/admin/order/send-mail', method: 'get')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new GetMailTemplateListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof MailTemplateListFetched);

        $form = $this->formFactory->newInstance(AdminMailTemplateForm::class);
        assert($form instanceof AdminMailTemplateForm);
        $form->fillValues([
            'template' => '',
            'name' => '',
            'file_name' => '',
            'mail_subject' => '',
            'tpl_data' => '',
            'html_tpl_data' => '',
        ]);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'id' => null,
            'Mail' => [
                'id' => null,
                'file_name' => '',
                'isDeletable' => false,
            ],
            'mailTemplates' => $final->mailTemplates,
            'count' => $final->count,
        ];

        return $this;
    }

    /**
     * ALPS `doUpdateMailTemplate` に対応する POST 操作。
     * @psalm-taint-source input $mailTemplateId
     * @psalm-taint-source input $mailSubject
     */
    #[Alps('doUpdateMailTemplate')]
    #[JsonSchema(schema: 'post-admin-mail-template.json', params: 'post-admin-mail-template.param.json')]
    #[Link(rel: 'goTop', href: 'page://self/admin')]
    #[Link(rel: 'goOrderMail', href: 'page://self/admin/order/send-mail', method: 'get')]
    #[Link(rel: 'doDeleteMailTemplate', href: 'page://self/admin/mail-template', method: 'delete')]
    #[CsrfProtected]
    public function onPost(
        int $mailTemplateId,
        string $mailSubject,
    ): static {
        try {
            $final = ($this->becoming)(new UpdateMailTemplateInput(
                mailTemplateId: $mailTemplateId,
                mailSubject: $mailSubject,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (MailTemplateNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'メールテンプレートが見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof MailTemplateUpdated);

        $this->code = Code::OK;
        $this->body = [
            'mailTemplateId' => $final->mailTemplateId,
            'mailTemplateName' => $final->mailTemplateName,
            'fileName' => $final->fileName,
            'mailSubject' => $final->mailSubject,
            'changed' => $final->changed,
        ];

        return $this;
    }

    /**
     * EC-CUBE doDeleteMailTemplate.
     *
     * The mail-template master still needs a full file-backed delete in
     * a later adapter pass; this surface is intentionally narrow and
     * concrete so the legacy route reaches a Resource with CSRF/AUTHZ
     * semantics instead of generic ActionRedirect.
     *
     * @psalm-taint-source input $mailTemplateId
     */
    #[Alps('doDeleteMailTemplate')]
    #[JsonSchema(schema: 'delete-admin-mail-template.json', params: 'delete-admin-mail-template.param.json')]
    #[Link(rel: 'goMailTemplateList', href: 'page://self/admin/mail-template', method: 'get')]
    #[CsrfProtected]
    public function onDelete(int $mailTemplateId): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $template = $this->mailTemplates->item($mailTemplateId);
        if ($template === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'メールテンプレートが見つかりませんでした。'];

            return $this;
        }

        $this->mailTemplates->delete($mailTemplateId);

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'doDeleteMailTemplate',
            'mailTemplateId' => $template->mailTemplateId,
            'mailTemplateName' => $template->mailTemplateName,
            'fileName' => $template->fileName,
            'message' => 'メールテンプレート削除Resourceへ到達しました。',
        ];

        return $this;
    }
}
