<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\ClassName;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminClassNameListFetched;
use MyVendor\BeMart\Be\Final\ClassNameCreated;
use MyVendor\BeMart\Be\Input\CreateClassNameInput;
use MyVendor\BeMart\Be\Input\GetAdminClassNameListInput;
use MyVendor\BeMart\Form\AdminClassNameForm;
use Ray\WebFormModule\FormFactory;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE goClassNameList + doCreateClassName — collection endpoint
 * (Wave 7).
 *
 *   - GET  → goClassNameList   (admin lists axes — safe read)
 *   - POST → doCreateClassName (admin adds a new axis)
 *
 * Single-row affordances (`doUpdateClassName`, `doDeleteClassName`)
 * live at `page://self/admin/class-name/class-name`. There is no
 * dedicated `goClassName` (admin reads the list directly).
 */
class ClassNameList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
    ) {
    }

    #[Link(rel: 'doCreateClassName', href: 'page://self/admin/class-name/class-name-list', method: 'post')]
    #[Link(rel: 'doUpdateClassName', href: 'page://self/admin/class-name/class-name', method: 'put')]
    #[Link(rel: 'doDeleteClassName', href: 'page://self/admin/class-name/class-name', method: 'delete')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new GetAdminClassNameListInput());

        assert($final instanceof AdminClassNameListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'classNames' => $final->classNames,
        ];
        // Phase 3: an empty AdminClassNameForm for the HTML list page to
        // render the inline-create inputs via `{{ form.input(...) }}`.
        // JSON contexts ignore `body.form`.
        $this->body['form'] = $this->formFactory->newInstance(AdminClassNameForm::class);

        return $this;
    }

    /**
     * @psalm-taint-source input $classNameLabel
     */
    #[Link(rel: 'goClassNameList', href: 'page://self/admin/class-name/class-name-list')]
    #[CsrfProtected]
    public function onPost(string $classNameLabel): static
    {
        $final = ($this->becoming)(new CreateClassNameInput(classNameLabel: $classNameLabel));

        assert($final instanceof ClassNameCreated);

        $this->code = Code::CREATED;
        $this->headers['Location'] = sprintf('/admin/class-name/class-name?classNameId=%s', urlencode($final->classNameId));
        $this->body = [
            'classNameId' => $final->classNameId,
            'name' => $final->name,
        ];

        return $this;
    }
}
