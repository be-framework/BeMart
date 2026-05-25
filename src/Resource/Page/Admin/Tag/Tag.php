<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Tag;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\TagNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\TagDeleted;
use MyVendor\BeMart\Be\Input\DeleteTagInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doDeleteTag — single-row endpoint (Wave 9). ALPS exposes
 * neither doUpdateTag nor goTag — only DELETE.
 */
class Tag extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $tagId
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goTagList', href: 'page://self/admin/tag/tag-list')]
    public function onDelete(string $tagId, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new DeleteTagInput(tagId: $tagId));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (TagNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたタグは見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof TagDeleted);

        $this->code = Code::OK;
        $this->body = ['tagId' => $final->tagId];

        return $this;
    }
}
