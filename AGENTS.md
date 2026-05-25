# Repository Guidelines

## Project Structure & Module Organization
This repository is artifact-first: [`alps.json`](/Users/akihito/git/ec-cube-alps/alps.json) is the canonical ALPS profile, and the root-level [`alps.json.html`](/Users/akihito/git/ec-cube-alps/alps.json.html), [`openapi.yaml`](/Users/akihito/git/ec-cube-alps/openapi.yaml), and [`openapi.html`](/Users/akihito/git/ec-cube-alps/openapi.html) are generated deliverables. [`tag.md`](/Users/akihito/git/ec-cube-alps/tag.md) defines tag taxonomy, and [`HANDOVER.md`](/Users/akihito/git/ec-cube-alps/HANDOVER.md) records provenance, coverage notes, and Pilot 1/2 completion reports. [`docs/`](/Users/akihito/git/ec-cube-alps/docs) contains the GitHub Pages site: [`docs/index.md`](/Users/akihito/git/ec-cube-alps/docs/index.md) is the published article, while [`docs/alps.json.html`](/Users/akihito/git/ec-cube-alps/docs/alps.json.html) and [`docs/openapi.html`](/Users/akihito/git/ec-cube-alps/docs/openapi.html) mirror the published HTML artifacts. Files such as `verify-*.md` and `improvements-*.md` are working notes, not primary outputs.

## Build, Test, and Development Commands
There is no package-based build system here; contributors work directly with ALPS artifacts.

- `asd --lint alps.json` validates the profile before review or commit.
- `asd -e alps.json` regenerates the HTML documentation from the ALPS source.
- `asd -s alps.json` exports SVG state diagrams for spot checks and discussion.

If you regenerate HTML, keep the published copies under `docs/` in sync with the root artifacts.

## Coding Style & Naming Conventions
Use 2-space indentation in JSON and YAML. Keep Markdown concise and instructional; the existing public docs are written in Japanese, so match that tone for user-facing prose. Follow existing naming patterns: lowerCamelCase for ALPS descriptor IDs such as `productName`, kebab-case for note files such as `verify-similar-names.md`, and short ATX headings in Markdown. Avoid reformatting generated HTML by hand unless the generation source is unavailable.

## Testing Guidelines
This repository has no automated unit test suite. The minimum validation bar is `asd --lint alps.json`, followed by manual review of regenerated HTML and any changed links in `README.md` or `docs/index.md`. When editing published content, verify that GitHub Pages-facing files in `docs/` still render correctly.

## Commit & Pull Request Guidelines
Recent history favors short, single-purpose commit subjects in either Japanese or English, for example `Add OpenAPI HTML documentation` or `ブログ記事をdocs/に移動しGitHub Pages対応`. Keep commits scoped to one concern, and name the affected artifact or topic early in the subject. PRs should summarize the source material consulted, list regenerated files, and call out whether both root artifacts and `docs/` copies were updated. Include screenshots only when rendered HTML or diagrams changed.
