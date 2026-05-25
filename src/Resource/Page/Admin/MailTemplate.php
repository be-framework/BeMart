<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

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
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * Wave 9ι: goMailTemplateList — admin lists every mail template.
     */
    #[Link(rel: 'doUpdateMailTemplate', href: 'page://self/admin/mail-template', method: 'post')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetMailTemplateListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof MailTemplateListFetched);

        $this->code = Code::OK;
        $this->body = [
            'mailTemplates' => $final->mailTemplates,
            'count' => $final->count,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $mailTemplateId
     * @psalm-taint-source input $mailSubject
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goTop', href: 'page://self/admin')]
    public function onPost(
        int $mailTemplateId,
        string $mailSubject,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

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
}
