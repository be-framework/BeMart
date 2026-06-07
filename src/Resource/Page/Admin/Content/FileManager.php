<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Content;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminFileForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE ファイル管理 — admin CMS thin renderer (Phase 3 HTML).
 *
 * PORT-side note: EC-CUBE's `FileController` is a `user_data/` file
 * manager (browse / upload / create-folder / move / delete / download
 * directly on the filesystem). It has no Be domain entity — the
 * filesystem IS its model. This resource is therefore a THIN HTML
 * RENDERER only — it carries no `be/src/` Becoming chain, authenticating
 * at the resource layer via {@see AdminSession}.
 *
 * The body renders the file manager in its **fresh / empty-directory**
 * state: `arrFileList` empty, `tplIsTopDir` true (at the user_data root),
 * `tplNowDir` empty, the JS tree-data variables empty arrays. The
 * `Content/file.twig` port omits the per-file rows (no `arrFileList`
 * data) and the directory-tree JS payload — enumerated as residuals.
 *
 * FLAGGED: a future wave should model the user_data file manager (a
 * `be/src/` filesystem-backed storage + Get/Upload/Delete Inputs) so this
 * resource can list real files. The current renderer proves only the
 * page chrome + upload/new-folder form.
 */
class FileManager extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }
    /** ALPS `goAdminContentFileManager` に対応する GET 操作。 */
    #[Alps('goAdminContentFileManager')]
    #[JsonSchema(schema: 'get-admin-content-file-manager.json')]

    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminFileForm::class);
        assert($form instanceof AdminFileForm);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            // Fresh / empty user_data root state.
            'tplNowDir' => '',
            'tplParentDir' => '',
            'tplIsTopDir' => false,
            'arrFileList' => [],
            'errors' => [],
        ];

        return $this;
    }
}
