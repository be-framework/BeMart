<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Template;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Exception\TemplateNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminTemplateListFetched;
use MyVendor\BeMart\Be\Final\TemplateDeleted;
use MyVendor\BeMart\Be\Final\TemplateDownloaded;
use MyVendor\BeMart\Be\Final\TemplateSelected;
use MyVendor\BeMart\Be\Input\DeleteTemplateInput;
use MyVendor\BeMart\Be\Input\DownloadTemplateInput;
use MyVendor\BeMart\Be\Input\GetAdminTemplateListInput;
use MyVendor\BeMart\Be\Input\SelectTemplateInput;

use function assert;

/**
 * EC-CUBE goTemplateList — list-only endpoint (Wave 9). ALPS exposes
 * no other affordances; template upload / activation is Phase 2.
 */
class TemplateList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'goTemplateAdd', href: 'page://self/admin/template/template-add')]
    #[Link(rel: 'goTemplateInstall', href: 'page://self/admin/template/template-add', method: 'get')]
    #[Link(rel: 'doSelectTemplate', href: 'page://self/admin/template/template-list', method: 'put')]
    #[Link(rel: 'doDownloadTemplate', href: 'page://self/admin/template/template-list', method: 'post')]
    #[Link(rel: 'doDeleteTemplate', href: 'page://self/admin/template/template-list', method: 'delete')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new GetAdminTemplateListInput());

        assert($final instanceof AdminTemplateListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'templates' => $final->templates,
            'links' => [
                'goTemplateAdd' => 'page://self/admin/template/template-add',
            ],
        ];

        return $this;
    }

    /** Activates a template (doSelectTemplate). ALPS idempotent → PUT.
     *
     * @psalm-taint-source input $templateId
     */
    #[Link(rel: 'doDownloadTemplate', href: 'page://self/admin/template/template-list', method: 'post')]
    #[CsrfProtected]
    public function onPut(string $templateId): static
    {
        return $this->run('doSelectTemplate', static fn (BecomingInterface $b) => $b(new SelectTemplateInput(templateId: $templateId)), 'テンプレートを適用しました。');
    }

    /** Deletes a template (doDeleteTemplate). ALPS idempotent → DELETE.
     *
     * @psalm-taint-source input $templateId
     */
    #[Link(rel: 'goTemplateList', href: 'page://self/admin/template/template-list', method: 'get')]
    #[CsrfProtected]
    public function onDelete(string $templateId): static
    {
        return $this->run('doDeleteTemplate', static fn (BecomingInterface $b) => $b(new DeleteTemplateInput(templateId: $templateId)), 'テンプレートを削除しました。');
    }

    /** Downloads a template zip (doDownloadTemplate). ALPS unsafe → POST.
     *
     * @psalm-taint-source input $templateId
     */
    #[Link(rel: 'doDeleteTemplate', href: 'page://self/admin/template/template-list', method: 'delete')]
    #[CsrfProtected]
    public function onPost(string $templateId): static
    {
        $final = ($this->becoming)(new DownloadTemplateInput(templateId: $templateId));

        assert($final instanceof TemplateDownloaded);

        $this->code = Code::OK;
        $this->headers['Content-Type'] = 'application/zip';
        $this->headers['Content-Disposition'] = $final->archive->contentDisposition;
        $this->body = $final->archive->content;

        return $this;
    }

    /**
     * Shared select/delete handler: maps the Be transition to HTTP and a
     * redirect-on-success body.
     *
     * @param callable(BecomingInterface): object $run
     */
    private function run(string $transitionId, callable $run, string $message): static
    {
        $final = $run($this->becoming);

        assert($final instanceof TemplateSelected || $final instanceof TemplateDeleted);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin_store_template';
        $this->body = [
            'transitionId' => $transitionId,
            'templateId' => $final->templateId,
            'message' => $message,
        ];

        return $this;
    }
}
