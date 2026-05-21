<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE ファイル管理フォーム（管理画面）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/Content/FileManagerType` + the
 * `admin/Content/file.twig` `form_widget` calls. EC-CUBE renders these
 * inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule — the same recipe as the admin pilot {@see AdminNewsForm}.
 *
 * Field names ported from `file.twig`'s `form_widget` calls: `file`
 * (file-upload input — multiple), `create_file` (text — new-folder name).
 * EC-CUBE's block prefix is `form` (inline-built form type), so the
 * rendered ids are `form_file` / `form_create_file`.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be Becoming chain. This form is a
 * field-definition + renderer only. The `#[FormValidation]` aspect is
 * NOT used. See var/templates/README.md, "Admin pages".
 */
final class AdminFileForm extends AbstractForm
{
    /** @var array<string, string> */
    private array $domainErrors = [];

    #[Override]
    public function init(): void
    {
        $this->setField('file', 'file')
            ->setAttribs([
                'id' => 'form_file',
                'class' => 'form-control',
                'multiple' => 'multiple',
            ]);

        $this->setField('create_file', 'text')
            ->setAttribs([
                'id' => 'form_create_file',
                'class' => 'form-control',
            ]);
    }

    /**
     * Repopulates the form inputs. The file manager form has no
     * persisted values to restore; provided for recipe symmetry.
     *
     * @param array<string, mixed> $body the File resource body
     */
    public function fillValues(array $body): void
    {
        $this->fill([
            'create_file' => (string) ($body['createFile'] ?? ''),
        ]);
    }

    public function setDomainError(string $field, string $message): void
    {
        $this->domainErrors[$field] = $message;
    }

    #[Override]
    public function error(string $input): string
    {
        if (isset($this->domainErrors[$input])) {
            return $this->domainErrors[$input];
        }

        return parent::error($input);
    }
}
