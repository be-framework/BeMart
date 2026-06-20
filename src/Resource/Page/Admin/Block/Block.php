<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Block;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Final\AdminBlockFetched;
use MyVendor\BeMart\Be\Final\BlockDeleted;
use MyVendor\BeMart\Be\Final\BlockUpdated;
use MyVendor\BeMart\Be\Input\DeleteBlockInput;
use MyVendor\BeMart\Be\Input\GetAdminBlockInput;
use MyVendor\BeMart\Be\Input\UpdateBlockInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use Ray\Csrf\CsrfTokenInterface;
use MyVendor\BeMart\Form\AdminBlockForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doUpdateBlock + doDeleteBlock — single-row endpoint (Wave 9).
 *
 * ALPS has no goBlock — the admin edits a block from the list view
 * directly. Only PUT and DELETE are exposed here for the domain.
 *
 * Phase 3 — HTML FORM page. `onGet` exposes an {@see AdminBlockForm}
 * (Ray.WebFormModule AbstractForm) as `body['form']` so the admin block
 * edit page (`Content/block_edit.twig` port) can render real `<input>`s
 * via `{{ form.input(...) }}`.
 *
 * `onGet` renders the NEW-block form when no blockId is supplied, and
 * pre-fills the edit form when a blockId is supplied.
 */
class Block extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * Renders the block edit form.
     *
     * The JSON contexts (`app`, `prod`, `test`) ignore `body['form']`.
     *
     * @psalm-taint-source input $blockId
     */
    #[Alps('goBlock')]
    #[JsonSchema(schema: 'get-admin-block-block.json', params: 'get-admin-block-block.param.json')]
    #[Link(rel: 'goBlockList', href: 'page://self/admin/block/block-list')]
    #[Link(rel: 'doCreateBlock', href: 'page://self/admin/block/block-list', method: 'post')]
    #[Link(rel: 'doUpdateBlock', href: 'page://self/admin/block/block', method: 'put')]
    public function onGet(string|null $blockId = null): static
    {
        if ($blockId === null || $blockId === '') {
            if ($this->adminSession->adminId === null) {
                $this->code = Code::FORBIDDEN;
                $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

                return $this;
            }

            $this->code = Code::OK;
            $this->body = [
                'blockId' => '',
                'blockName' => '',
                'blockFileName' => '',
                'blockDeletable' => true,
                'csrfToken' => $this->csrf->issue(),
            ];
            $this->body['form'] = $this->editForm($this->body);

            return $this;
        }

        $final = ($this->becoming)(new GetAdminBlockInput(blockId: $blockId));

        assert($final instanceof AdminBlockFetched);

        $this->code = Code::OK;
        $this->body = [
            'blockId' => $final->blockId,
            'blockName' => $final->blockName,
            'blockFileName' => $final->blockFileName,
            'blockDeletable' => $final->blockDeletable,
            'csrfToken' => $this->csrf->issue(),
        ];
        $this->body['form'] = $this->editForm($this->body);

        return $this;
    }

    /**
     * Builds an AdminBlockForm filled from a block body.
     *
     * @param array<string, mixed> $body
     */
    private function editForm(array $body): AdminBlockForm
    {
        $form = $this->formFactory->newInstance(AdminBlockForm::class);
        assert($form instanceof AdminBlockForm);
        $form->fillValues($body);

        return $form;
    }

    /**
     * ALPS `doUpdateBlock` に対応する PUT 操作。
     * @psalm-taint-source input $blockId
     * @psalm-taint-source input $blockName
     * @psalm-taint-source input $blockFileName
     */
    #[Alps('doUpdateBlock')]
    #[JsonSchema(schema: 'put-admin-block-block.json', params: 'put-admin-block-block.param.json')]
    #[Link(rel: 'goBlockList', href: 'page://self/admin/block/block-list')]
    #[CsrfToken]
    public function onPut(
        string $blockId,
        string|null $blockName = null,
        string|null $blockFileName = null,
    ): static {
        $final = ($this->becoming)(new UpdateBlockInput(
            blockId: $blockId,
            blockName: $blockName,
            blockFileName: $blockFileName,
        ));

        assert($final instanceof BlockUpdated);

        ($this->mutationResponse)($this, Code::OK, '/admin/block/block-list');
        $this->body = [
            'blockId' => $final->blockId,
            'blockName' => $final->blockName,
            'blockFileName' => $final->blockFileName,
            'blockDeletable' => $final->blockDeletable,
        ];

        return $this;
    }

    /**
     * ALPS `doDeleteBlock` に対応する DELETE 操作。
     * @psalm-taint-source input $blockId
     */
    #[Alps('doDeleteBlock')]
    #[JsonSchema(schema: 'delete-admin-block-block.json', params: 'delete-admin-block-block.param.json')]
    #[Link(rel: 'goBlockList', href: 'page://self/admin/block/block-list')]
    #[Link(rel: 'goLayoutList', href: 'page://self/admin/layout/layout-list')]
    #[CsrfToken]
    public function onDelete(string $blockId): static
    {
        $final = ($this->becoming)(new DeleteBlockInput(blockId: $blockId));

        assert($final instanceof BlockDeleted);

        ($this->mutationResponse)($this, Code::OK, '/admin/block/block-list');
        $this->body = ['blockId' => $final->blockId];

        return $this;
    }
}
