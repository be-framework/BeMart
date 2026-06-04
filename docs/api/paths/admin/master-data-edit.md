<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/master-data-edit
EC-CUBE マスタデータ編集 — Setting/System (doUpdateMasterData).

Separate resource from {@see \MasterData} (which owns GET + the
`doSelectMasterData` PUT on the same `/admin_setting_system_masterdata`
URL) so the edit verb does not collide. `onPut` drives the Be
`doUpdateMasterData` transition; the destructive bulk write is isolated
behind {@see \MyVendor\BeMart\Be\Reason\Service\MasterDataWriterInterface}.




## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| masterType | string |  |  | Required |  |  |
| rows | array |  | array () | Optional |  |  |


### Response

_Not available_