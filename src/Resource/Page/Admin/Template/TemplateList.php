<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Template;

use BEAR\ApiDoc\Annotation\Alps;
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
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function getenv;
use function str_contains;

/**
 * EC-CUBE goTemplateList — list-only endpoint (Wave 9). ALPS exposes
 * no other affordances; template upload / activation is Phase 2.
 */
class TemplateList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfToken $csrfToken,
    ) {
    }

    /** ALPS `goTemplateList` に対応する GET 操作。 */
    #[Alps('goTemplateList')]
    #[JsonSchema(schema: 'get-admin-template-template-list.json')]
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
            'csrfToken' => $this->csrfToken->token,
            'links' => [
                'goTemplateAdd' => 'page://self/admin/template/template-add',
            ],
        ];

        return $this;
    }

    /** Activates a template (doSelectTemplate). ALPS idempotent → PUT.
     * ALPS `doSelectTemplate` に対応する PUT 操作。
     *
     * @psalm-taint-source input $templateId
     */
    #[Alps('doSelectTemplate')]
    #[JsonSchema(schema: 'put-admin-template-template-list.json', params: 'put-admin-template-template-list.param.json')]
    #[Link(rel: 'doDownloadTemplate', href: 'page://self/admin/template/template-list', method: 'post')]
    #[CsrfProtected]
    public function onPut(string $templateId): static
    {
        return $this->run('doSelectTemplate', static fn (BecomingInterface $b) => $b(new SelectTemplateInput(templateId: $templateId)), 'テンプレートを適用しました。');
    }

    /** Deletes a template (doDeleteTemplate). ALPS idempotent → DELETE.
     * ALPS `doDeleteTemplate` に対応する DELETE 操作。
     *
     * @psalm-taint-source input $templateId
     */
    #[Alps('doDeleteTemplate')]
    #[JsonSchema(schema: 'delete-admin-template-template-list.json', params: 'delete-admin-template-template-list.param.json')]
    #[Link(rel: 'goTemplateList', href: 'page://self/admin/template/template-list', method: 'get')]
    #[CsrfProtected]
    public function onDelete(string $templateId): static
    {
        return $this->run('doDeleteTemplate', static fn (BecomingInterface $b) => $b(new DeleteTemplateInput(templateId: $templateId)), 'テンプレートを削除しました。');
    }

    /** Downloads a template zip (doDownloadTemplate). ALPS unsafe → POST.
     * ALPS `doDownloadTemplate` に対応する POST 操作。
     *
     * @psalm-taint-source input $templateId
     */
    #[Alps('doDownloadTemplate')]
    #[JsonSchema(schema: 'post-admin-template-template-list.json', params: 'post-admin-template-template-list.param.json')]
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

        $this->code = str_contains((string) getenv('APP_CONTEXT'), 'html') ? Code::SEE_OTHER : Code::OK;
        $this->headers['Location'] = '/admin/template/template-list';
        $this->body = [
            'transitionId' => $transitionId,
            'templateId' => $final->templateId,
            'message' => $message,
        ];

        return $this;
    }
}
