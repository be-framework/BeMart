<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Annotation\CsrfProtected;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\MasterDataUpdated;
use MyVendor\BeMart\Be\Input\UpdateMasterDataInput;

use function assert;

/**
 * EC-CUBE マスタデータ編集 — Setting/System (doUpdateMasterData).
 *
 * Separate resource from {@see MasterData} (which owns GET + the
 * `doSelectMasterData` PUT on the same `/admin_setting_system_masterdata`
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
     * @param list<array{id: string, name: string, sortNo?: int}> $rows
     *
     * @psalm-taint-source input $masterType
     * @psalm-taint-source input $rows
     */
    #[Link(rel: 'goMasterData', href: 'page://self/admin/master-data')]
    #[CsrfProtected]
    public function onPut(string $masterType, array $rows = []): static
    {
        try {
            $final = ($this->becoming)(new UpdateMasterDataInput(masterType: $masterType, rows: $rows));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? '指定されたマスタデータは見つかりませんでした。'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof MasterDataUpdated);

        $this->code = Code::OK;
        $this->headers['Location'] = '/admin_setting_system_masterdata';
        $this->body = [
            'transitionId' => 'doUpdateMasterData',
            'masterType' => $final->masterType,
            'count' => $final->count,
            'message' => 'マスタデータを更新しました。',
        ];

        return $this;
    }
}
