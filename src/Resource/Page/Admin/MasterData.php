<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Exception\MasterTypeFormatException;
use MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Form\AdminMasterDataForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE マスタデータ管理 — Setting/System Tier-2.
 *
 * GET renderer backed by the existing Be admin-master registry. This is
 * body-shape work for the generic master-data page: the resource exposes
 * selectable master types plus rows as `{id, name}` without inventing
 * values in Twig.
 */
class MasterData extends ResourceObject
{
    public function __construct(
        private readonly AdminSessionInterface $adminSession,
        private readonly AdminMasterRegistryInterface $masters,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * @psalm-taint-source input $masterType
     */
    public function onGet(string $masterType = 'tag'): static
    {
        if ($this->adminSession->adminId() === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        try {
            $rows = $this->masters->listRows($masterType);
        } catch (MasterTypeFormatException) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => '指定されたマスタデータは見つかりませんでした。'];

            return $this;
        }

        $masterTypes = $this->masters->listMasterTypes();
        $form = $this->formFactory->newInstance(AdminMasterDataForm::class);
        assert($form instanceof AdminMasterDataForm);
        $form->fillValues($masterTypes, $masterType);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'masterTypes' => $masterTypes,
            'selectedMaster' => $masterType,
            'rows' => $rows,
        ];

        return $this;
    }
}
