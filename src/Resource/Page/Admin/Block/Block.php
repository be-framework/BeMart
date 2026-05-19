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

use function assert;

/**
 * EC-CUBE doUpdateBlock + doDeleteBlock — single-row endpoint (Wave 9).
 *
 * ALPS has no goBlock — the admin edits a block from the list view
 * directly. Only PUT and DELETE are exposed here.
 */
class Block extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
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
