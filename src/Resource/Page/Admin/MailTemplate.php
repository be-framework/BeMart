<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
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
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
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
        private readonly CsrfToken $csrf,
        private readonly FormFactory $formFactory,
        private readonly MailTemplateStorageInterface $mailTemplates,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * Wave 9ι: goMailTemplateList — admin lists every mail template.
     */
    #[Alps('goMailTemplateList')]
    #[JsonSchema(schema: 'get-admin-mail-template.json')]
    #[Link(rel: 'doCreateMailTemplate', href: 'page://self/admin/mail-template/create', method: 'post')]
    #[Link(rel: 'doUpdateMailTemplate', href: 'page://self/admin/mail-template', method: 'post')]
    #[Link(rel: 'goOrderMail', href: 'page://self/admin/order/send-mail', method: 'get')]
    #[Link(rel: 'goPaymentList', href: 'page://self/admin/payment/payment-list')]
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    public function onGet(int|null $mailTemplateId = null): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $final = ($this->becoming)(new GetMailTemplateListInput());

        assert($final instanceof MailTemplateListFetched);

        $form = $this->formFactory->newInstance(AdminMailTemplateForm::class);
        assert($form instanceof AdminMailTemplateForm);
        $selected = $this->selectedTemplate($final->mailTemplates, $mailTemplateId);
        $selectedId = $selected['mailTemplateId'] ?? null;
        $form->fillValues([
            'mailTemplateId' => $selectedId === null ? '' : (string) $selectedId,
            'template' => $selectedId === null ? '' : (string) $selectedId,
            'name' => $selected['mailTemplateName'] ?? '',
            'file_name' => $selected['fileName'] ?? '',
            'mail_subject' => $selected['mailSubject'] ?? '',
            'tpl_data' => '',
            'html_tpl_data' => '',
        ], $this->templateOptions($final->mailTemplates));

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'id' => $selectedId,
            'Mail' => [
                'id' => $selectedId,
                'file_name' => $selected['fileName'] ?? '',
                'isDeletable' => (bool) ($selected['isDeletable'] ?? false),
            ],
            'mailTemplates' => $final->mailTemplates,
            'count' => $final->count,
            'csrfToken' => $this->csrf->token,
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
        string $mailSubject = '',
        string $mail_subject = '',
    ): static {
        $final = ($this->becoming)(new UpdateMailTemplateInput(
            mailTemplateId: $mailTemplateId,
            mailSubject: $mailSubject !== '' ? $mailSubject : $mail_subject,
        ));

        assert($final instanceof MailTemplateUpdated);

        $this->code = Code::OK;
        $this->body = [
            'mailTemplateId' => $final->mailTemplateId,
            'mailTemplateName' => $final->mailTemplateName,
            'fileName' => $final->fileName,
            'mailSubject' => $final->mailSubject,
            'changed' => $final->changed,
        ];
        $this->mutationResponse->redirectOnSuccess($this, '/admin/mail-template?mailTemplateId=' . $final->mailTemplateId);

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
        $this->mutationResponse->redirectOnSuccess($this, '/admin/mail-template');

        return $this;
    }

    /**
     * @param list<array{mailTemplateId: int, mailTemplateName: string, fileName: string, mailSubject: string, isDeletable?: bool}> $mailTemplates
     *
     * @return array{mailTemplateId: int, mailTemplateName: string, fileName: string, mailSubject: string, isDeletable?: bool}|null
     */
    private function selectedTemplate(array $mailTemplates, int|null $mailTemplateId): array|null
    {
        if ($mailTemplateId === null) {
            return null;
        }

        foreach ($mailTemplates as $mailTemplate) {
            if ($mailTemplate['mailTemplateId'] === $mailTemplateId) {
                return $mailTemplate;
            }
        }

        return null;
    }

    /**
     * @param list<array{mailTemplateId: int, mailTemplateName: string, fileName: string, mailSubject: string, isDeletable?: bool}> $mailTemplates
     *
     * @return array<int|string, string>
     */
    private function templateOptions(array $mailTemplates): array
    {
        $options = ['' => '選択してください'];
        foreach ($mailTemplates as $mailTemplate) {
            $options[(string) $mailTemplate['mailTemplateId']] = $mailTemplate['mailTemplateName'];
        }

        return $options;
    }

}
