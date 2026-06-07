<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Exception\MasterTypeFormatException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MasterDataSelected;
use MyVendor\BeMart\Be\Input\SelectMasterDataInput;
use MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminMasterDataForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

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
        private readonly AdminSession $adminSession,
        private readonly AdminMasterRegistryInterface $masters,
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `goMasterData` に対応する GET 操作。
     * @psalm-taint-source input $masterType
     */
    #[Alps('goMasterData')]
    #[JsonSchema(schema: 'get-admin-master-data.json', params: 'get-admin-master-data.param.json')]
    #[Link(rel: 'doSelectMasterData', href: 'page://self/admin/master-data', method: 'put')]
    public function onGet(string $masterType = 'tag'): static
    {
        if ($this->adminSession->adminId === null) {
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

    /**
     * Selects which master to view (doSelectMasterData). ALPS marks it
     * `idempotent` → PUT; returns the chosen master's rows.
     *
     * @psalm-taint-source input $masterType
     */
    #[Alps('doSelectMasterData')]
    #[JsonSchema(schema: 'put-admin-master-data.json', params: 'put-admin-master-data.param.json')]
    #[Link(rel: 'doUpdateMasterData', href: 'page://self/admin/master-data-edit', method: 'put')]
    #[CsrfProtected]
    public function onPut(string $masterType = 'tag'): static
    {
        try {
            $final = ($this->becoming)(new SelectMasterDataInput(masterType: $masterType));
        } catch (SemanticVariableException) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => '指定されたマスタデータは見つかりませんでした。'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof MasterDataSelected);

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'doSelectMasterData',
            'selectedMaster' => $final->masterType,
            'rows' => $final->rows,
        ];

        return $this;
    }
}
