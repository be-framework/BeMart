<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Tag;

use MyVendor\BeMart\Annotation\CsrfProtected;
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
use MyVendor\BeMart\Form\AdminTagForm;
use Ray\WebFormModule\FormFactory;

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
        private readonly FormFactory $formFactory,
    ) {
    }

    #[Link(rel: 'doCreateTag', href: 'page://self/admin/tag/tag-list', method: 'post')]
    #[Link(rel: 'doDeleteTag', href: 'page://self/admin/tag/tag', method: 'delete')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new GetAdminTagListInput());

        assert($final instanceof AdminTagListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'tags' => $final->tags,
        ];
        // Phase 3: an empty AdminTagForm for the HTML list page to render
        // the inline-create input via `{{ form.input('name') }}`. JSON
        // contexts ignore `body.form`; the resource tests assert key-wise
        // on body and are unaffected.
        $this->body['form'] = $this->formFactory->newInstance(AdminTagForm::class);

        return $this;
    }

    /**
     * @psalm-taint-source input $tagName
     */
    #[Link(rel: 'goTagList', href: 'page://self/admin/tag/tag-list')]
    #[CsrfProtected]
    public function onPost(string $tagName): static
    {
        $final = ($this->becoming)(new CreateTagInput(tagName: $tagName));

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
