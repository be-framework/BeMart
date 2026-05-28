# Repository Guidelines

## Project Structure & Module Organization
BeMart is a **demonstration project**: [`alps.json`](alps.json) is the canonical ALPS profile (source of truth), and the lower layers implement it — [`be/`](be) (Be Framework domain), [`src/`](src) (BEAR.Sunday application/resource), [`sql/`](sql) (EC-CUBE schema + SQL persistence), [`var/templates/`](var/templates) (Twig HTML ports). Generated deliverables (`alps.json.html`, `openapi.yaml`, `openapi.html`, `alps.svg`) are derived from `alps.json` — do not hand-edit. [`docs/`](docs) holds the documentation and the GitHub Pages site; [`docs/README.md`](docs/README.md) is the doc map and [`docs/migration-status.md`](docs/migration-status.md) is the source of truth for migration status. [`docs/tag.md`](docs/tag.md) defines the tag taxonomy.

## Build, Test, and Development Commands
The implementation is a Composer project; ALPS artifacts are validated separately.

- `vendor/bin/phpunit` runs the test suites (Resource / SQL / HTML render / HTTP hypermedia). SQL suites need a local MariaDB prepared from `sql/`.
- `vendor/bin/psalm` runs static analysis + taint tracking.
- `composer fake -- get '/products/list'` / `composer page -- get '/'` run serverless requests.
- `asd --validate alps.json` validates the ALPS profile before review or commit.
- `asd -f html -o alps.json.html alps.json` / `asd -f svg -o alps.svg alps.json` regenerate the HTML/SVG; keep the `docs/` copies in sync.

## Coding Style & Naming Conventions
Use 2-space indentation in JSON and YAML. Keep Markdown concise and instructional; the existing public docs are written in Japanese, so match that tone for user-facing prose. Follow existing naming patterns: lowerCamelCase for ALPS descriptor IDs such as `productName`, kebab-case for note files such as `verify-similar-names.md`, and short ATX headings in Markdown. Avoid reformatting generated HTML by hand unless the generation source is unavailable.

## Testing Guidelines
BeMart treats tests as **boundary contracts** (see [`README.md`](README.md) "Testing"). The bar before review or commit is `vendor/bin/phpunit` green (non-SQL suites run without a database) and `vendor/bin/psalm` clean. For ALPS-only changes, `asd --validate alps.json` plus a review of regenerated HTML and changed links in `README.md` / `docs/index.md`; verify GitHub Pages-facing files under `docs/` still render.

## Commit & Pull Request Guidelines
Recent history favors short, single-purpose commit subjects in either Japanese or English, for example `Add OpenAPI HTML documentation` or `ブログ記事をdocs/に移動しGitHub Pages対応`. Keep commits scoped to one concern, and name the affected artifact or topic early in the subject. PRs should summarize the source material consulted, list regenerated files, and call out whether both root artifacts and `docs/` copies were updated. Include screenshots only when rendered HTML or diagrams changed.
