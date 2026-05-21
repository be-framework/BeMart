<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Block;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\BlockNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\BlockDeleted;
use MyVendor\BeMart\Be\Final\BlockUpdated;
use MyVendor\BeMart\Be\Input\DeleteBlockInput;
use MyVendor\BeMart\Be\Input\UpdateBlockInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;
use MyVendor\BeMart\Form\AdminBlockForm;
use Ray\WebFormModule\FormFactory;

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
 * NOTE — single-row prefill: ALPS / the Be domain expose no
 * `GetAdminBlockInput` / `AdminBlockFetched` (single-row fetch), so
 * `onGet` renders the NEW-block form (the `admin_content_block_new`
 * case). Pre-filling an existing row would need a Be fetch Input — a
 * `be/src/` change out of this Phase 3 HTML wave's scope. FLAGGED:
 * follow-up to add `GetAdminBlockInput` for existing-block edit prefill.
 */
class Block extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * Renders the block edit form (new-block case).
     *
     * The JSON contexts (`app`, `prod`, `test`) ignore `body['form']`.
     */
    #[Link(rel: 'goBlockList', href: 'page://self/admin/block/block-list')]
    public function onGet(): static
    {
        $form = $this->formFactory->newInstance(AdminBlockForm::class);
        assert($form instanceof AdminBlockForm);

        $this->code = Code::OK;
        $this->body = ['form' => $form];

        return $this;
    }

    /**
     * @psalm-taint-source input $blockId
     * @psalm-taint-source input $blockName
     * @psalm-taint-source input $blockFileName
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goBlockList', href: 'page://self/admin/block/block-list')]
    public function onPut(
        string $blockId,
        string|null $blockName = null,
        string|null $blockFileName = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateBlockInput(
                blockId: $blockId,
                blockName: $blockName,
                blockFileName: $blockFileName,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (BlockNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたブロックは見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof BlockUpdated);

        $this->code = Code::OK;
        $this->body = [
            'blockId' => $final->blockId,
            'blockName' => $final->blockName,
            'blockFileName' => $final->blockFileName,
            'blockDeletable' => $final->blockDeletable,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $blockId
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goBlockList', href: 'page://self/admin/block/block-list')]
    public function onDelete(string $blockId, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new DeleteBlockInput(blockId: $blockId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (BlockNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたブロックは見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof BlockDeleted);

        $this->code = Code::OK;
        $this->body = ['blockId' => $final->blockId];

        return $this;
    }
}
