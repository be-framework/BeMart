<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Block;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminBlockListFetched;
use MyVendor\BeMart\Be\Final\BlockCreated;
use MyVendor\BeMart\Be\Input\CreateBlockInput;
use MyVendor\BeMart\Be\Input\GetAdminBlockListInput;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goBlockList + doCreateBlock — collection endpoint (Wave 9 CMS).
 */
class BlockList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'doCreateBlock', href: 'page://self/admin/block/block-list', method: 'post')]
    #[Link(rel: 'doUpdateBlock', href: 'page://self/admin/block/block', method: 'put')]
    #[Link(rel: 'doDeleteBlock', href: 'page://self/admin/block/block', method: 'delete')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetAdminBlockListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminBlockListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'blocks' => $final->blocks,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $blockName
     * @psalm-taint-source input $blockFileName
     */
    #[Link(rel: 'goBlockList', href: 'page://self/admin/block/block-list')]
    #[CsrfProtected]
    public function onPost(
        string $blockName,
        string $blockFileName,
    ): static {
        try {
            $final = ($this->becoming)(new CreateBlockInput(
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
        }

        assert($final instanceof BlockCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/admin/block/block?blockId=%s', urlencode($final->blockId));
        $this->body = [
            'blockId' => $final->blockId,
            'blockName' => $final->blockName,
            'blockFileName' => $final->blockFileName,
            'blockDeletable' => $final->blockDeletable,
        ];

        return $this;
    }
}
