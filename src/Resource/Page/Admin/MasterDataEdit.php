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
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MasterDataUpdated;
use MyVendor\BeMart\Be\Input\UpdateMasterDataInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE マスタデータ編集 — Setting/System (doUpdateMasterData).
 *
 * Separate resource from {@see MasterData} (which owns GET + the
 * `doSelectMasterData` PUT on the same `/admin/master-data`
 * URL) so the edit verb does not collide. `onPut` drives the Be
 * `doUpdateMasterData` transition; the destructive bulk write is isolated
 * behind {@see \MyVendor\BeMart\Be\Reason\Service\MasterDataWriterInterface}.
 */
class MasterDataEdit extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `doUpdateMasterData` に対応する PUT 操作。
     * @param list<array{id: string, name: string, sortNo?: int}> $rows
     *
     * @psalm-taint-source input $masterType
     * @psalm-taint-source input $rows
     */
    #[Alps('doUpdateMasterData')]
    #[JsonSchema(schema: 'put-admin-master-data-edit.json', params: 'put-admin-master-data-edit.param.json')]
    #[Link(rel: 'goMasterData', href: 'page://self/admin/master-data')]
    #[CsrfProtected]
    public function onPut(string $masterType, array $rows = [], string|null $csrfToken = null): static
    {
        $final = ($this->becoming)(new UpdateMasterDataInput(masterType: $masterType, rows: $rows));

        assert($final instanceof MasterDataUpdated);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin/master-data';
        $this->body = [
            'transitionId' => 'doUpdateMasterData',
            'masterType' => $final->masterType,
            'count' => $final->count,
            'message' => 'マスタデータを更新しました。',
        ];

        return $this;
    }
}
