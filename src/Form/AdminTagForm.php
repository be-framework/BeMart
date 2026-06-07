<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE タグ登録フォーム（管理画面 タグ管理）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/TagType` + the
 * `admin/Product/tag.twig` `form_widget` calls. EC-CUBE renders the
 * inline create / edit inputs through the Symfony FormView; BeMart
 * renders the create input through Ray.WebFormModule, the same library
 * the {@see AdminNewsForm} pilot and the {@see AdminCustomerSearchForm}
 * (Customer wave) adopted.
 *
 * Scope — the inline-create input only
 * ------------------------------------
 * EC-CUBE's `tag.twig` renders TWO form families: the top-of-list
 * inline-CREATE form (`form.name`) and a per-row inline-EDIT form
 * (`forms[Tag.id].name`). BeMart's
 * {@see \MyVendor\BeMart\Resource\Page\Admin\Tag\TagList} resource
 * exposes `doCreateTag` (POST) but the per-row inline edit is a
 * `doUpdateTag` affordance ALPS does not declare (see the Tag resource
 * docblock — "ALPS exposes neither doUpdateTag nor goTag"). This form
 * therefore declares ONLY the create-form `name` field; the per-row
 * `mode-edit` panels in the ported template render plain `<input>`s
 * repopulated from the body row (they sit inside a `d-none` collapse and
 * carry no BeMart submit target).
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminTagForm extends AbstractForm
{
    /**
     * Declares the tag-name input.
     *
     * EC-CUBE's `TagType` block prefix is `admin_tag`, so the FormView
     * id is `admin_tag_name`.
     */
    #[Override]
    public function init(): void
    {
        $this->setField('tagName', 'text')
            ->setAttribs([
                'id' => 'admin_tag_name',
                'class' => 'form-control',
            ]);

        // NON-AUTHORITATIVE structural check only — the authoritative
        // tag-name rule lives in the Be domain (CreateTagInput Semantic).
        $this->filter->validate('tagName')->isNotBlank();
    }
}
