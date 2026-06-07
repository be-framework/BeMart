<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Tag;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\TagNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\TagDeleted;
use MyVendor\BeMart\Be\Input\DeleteTagInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doDeleteTag — single-row endpoint (Wave 9). ALPS exposes
 * neither doUpdateTag nor goTag — only DELETE.
 */
class Tag extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `doDeleteTag` に対応する DELETE 操作。
     * @psalm-taint-source input $tagId
     */
    #[Alps('doDeleteTag')]
    #[JsonSchema(schema: 'delete-admin-tag-tag.json', params: 'delete-admin-tag-tag.param.json')]
    #[Link(rel: 'goTagList', href: 'page://self/admin/tag/tag-list')]
    #[CsrfProtected]
    public function onDelete(string $tagId): static
    {
        $final = ($this->becoming)(new DeleteTagInput(tagId: $tagId));

        assert($final instanceof TagDeleted);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin/tag/tag-list';
        $this->body = ['tagId' => $final->tagId];

        return $this;
    }
}
