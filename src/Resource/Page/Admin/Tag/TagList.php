<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Tag;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminTagListFetched;
use MyVendor\BeMart\Be\Final\TagCreated;
use MyVendor\BeMart\Be\Input\CreateTagInput;
use MyVendor\BeMart\Be\Input\GetAdminTagListInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goTagList + doCreateTag — collection endpoint (Wave 9).
 */
class TagList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    #[Link(rel: 'doCreateTag', href: 'page://self/admin/tag/tag-list', method: 'post')]
    #[Link(rel: 'doDeleteTag', href: 'page://self/admin/tag/tag', method: 'delete')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetAdminTagListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminTagListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'tags' => $final->tags,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $tagName
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goTagList', href: 'page://self/admin/tag/tag-list')]
    public function onPost(string $tagName, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new CreateTagInput(tagName: $tagName));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof TagCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/admin/tag/tag?tagId=%s', urlencode($final->tagId));
        $this->body = [
            'tagId' => $final->tagId,
            'tagName' => $final->tagName,
        ];

        return $this;
    }
}
