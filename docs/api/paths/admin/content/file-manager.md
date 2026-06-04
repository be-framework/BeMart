<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/content/file-manager
EC-CUBE ファイル管理 — admin CMS thin renderer (Phase 3 HTML).

PORT-side note: EC-CUBE's `FileController` is a `user_data/` file
manager (browse / upload / create-folder / move / delete / download
directly on the filesystem). It has no Be domain entity — the
filesystem IS its model. This resource is therefore a THIN HTML
RENDERER only — it carries no `be/src/` Becoming chain, authenticating
at the resource layer via {@see \AdminSession}.

The body renders the file manager in its **fresh / empty-directory**
state: `arrFileList` empty, `tplIsTopDir` true (at the user_data root),
`tplNowDir` empty, the JS tree-data variables empty arrays. The
`Content/file.twig` port omits the per-file rows (no `arrFileList`
data) and the directory-tree JS payload — enumerated as residuals.

FLAGGED: a future wave should model the user_data file manager (a
`be/src/` filesystem-backed storage + Get/Upload/Delete Inputs) so this
resource can list real files. The current renderer proves only the
page chrome + upload/new-folder form.




## GET


### Request

_No parameters required_

### Response

_Not available_