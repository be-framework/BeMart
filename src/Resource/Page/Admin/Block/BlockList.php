<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Block;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminBlockListFetched;
use MyVendor\BeMart\Be\Final\BlockCreated;
use MyVendor\BeMart\Be\Input\CreateBlockInput;
use MyVendor\BeMart\Be\Input\GetAdminBlockListInput;
use BEAR\Resource\Annotation\JsonSchema;

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
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /** ALPS `goBlockList` に対応する GET 操作。 */
    #[Alps('goBlockList')]
    #[JsonSchema(schema: 'get-admin-block-block-list.json')]
    #[Link(rel: 'doCreateBlock', href: 'page://self/admin/block/block-list', method: 'post')]
    #[Link(rel: 'doUpdateBlock', href: 'page://self/admin/block/block', method: 'put')]
    #[Link(rel: 'doDeleteBlock', href: 'page://self/admin/block/block', method: 'delete')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new GetAdminBlockListInput());

        assert($final instanceof AdminBlockListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'blocks' => $final->blocks,
        ];

        return $this;
    }

    /**
     * ALPS `doCreateBlock` に対応する POST 操作。
     * @psalm-taint-source input $blockName
     * @psalm-taint-source input $blockFileName
     */
    #[Alps('doCreateBlock')]
    #[JsonSchema(schema: 'post-admin-block-block-list.json', params: 'post-admin-block-block-list.param.json')]
    #[Link(rel: 'goBlockList', href: 'page://self/admin/block/block-list')]
    #[CsrfProtected]
    public function onPost(
        string $blockName,
        string $blockFileName,
    ): static {
        $final = ($this->becoming)(new CreateBlockInput(
            blockName: $blockName,
            blockFileName: $blockFileName,
        ));

        assert($final instanceof BlockCreated);

        ($this->mutationResponse)($this, Code::CREATED, sprintf('/admin/block/block?blockId=%s', urlencode($final->blockId)));
        $this->body = [
            'blockId' => $final->blockId,
            'blockName' => $final->blockName,
            'blockFileName' => $final->blockFileName,
            'blockDeletable' => $final->blockDeletable,
        ];

        return $this;
    }
}
