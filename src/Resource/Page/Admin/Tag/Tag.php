<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Tag;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\TagNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\TagDeleted;
use MyVendor\BeMart\Be\Input\DeleteTagInput;

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
     * @psalm-taint-source input $tagId
     */
    #[Link(rel: 'goTagList', href: 'page://self/admin/tag/tag-list')]
    #[CsrfProtected]
    public function onDelete(string $tagId): static
    {
        $final = ($this->becoming)(new DeleteTagInput(tagId: $tagId));

        assert($final instanceof TagDeleted);

        $this->code = Code::OK;
        $this->body = ['tagId' => $final->tagId];

        return $this;
    }
}
