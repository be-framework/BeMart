"""Markdown ledger writers for BeMart Semantic-Ex schema audits."""
from __future__ import annotations

from pathlib import Path
from typing import Any


def _md(value: object) -> str:
    return str(value).replace("|", "\\|")


def write_form_boundary_ledger(path: Path, report: dict[str, Any]) -> None:
    lines = [
        "# Form Boundary Ledger\n",
        "`form` / `searchForm` はAura/WebForm由来の内部構造か、Resource文脈でshape化できるフォーム補助値かを区別して管理します。",
        "shapeable-form-context が0であることを品質ゲートにします。\n",
        "| path | classification | properties | reason | schema decision |",
        "|---|---|---:|---|---|",
    ]
    for row in report.get("formBoundaryLedger", []):
        lines.append(f"| `{row.get('path', '')}` | {row.get('classification', '')} | {row.get('properties', 0)} | {_md(row.get('reason', ''))} | {_md(row.get('schemaDecision', ''))} |")
    path.write_text("\n".join(lines) + "\n")


def write_dynamic_row_ledger(path: Path, report: dict[str, Any]) -> None:
    lines = [
        "# Dynamic Row Ledger\n",
        "動的行は、固定できる安定列をpropertiesへ昇格し、対象マスタ/CSV/外部カタログで増える列だけ互換境界として許容します。",
        "dynamicRowsWithoutReason と、理由なしproperties=0を品質ゲートにします。\n",
        "| path | classification | title | properties | reason | schema decision |",
        "|---|---|---|---:|---|---|",
    ]
    for row in report.get("dynamicRowLedger", []):
        lines.append(f"| `{row.get('path', '')}` | {row.get('classification', '')} | {_md(row.get('title', ''))} | {row.get('properties', 0)} | {_md(row.get('reason', ''))} | {_md(row.get('schemaDecision', ''))} |")
    path.write_text("\n".join(lines) + "\n")


def write_mixed_boundary_ledger(path: Path, report: dict[str, Any]) -> None:
    lines = [
        "# Mixed Boundary Ledger\n",
        "string|integer|null は互換境界だけで許可します。token/key/tracking系やDB採番IDへ寄せられるものはschemaを狭めます。\n",
        "| path | name | classification | reason | schema decision |",
        "|---|---|---|---|---|",
    ]
    for row in report.get("mixedBoundaryLedger", []):
        lines.append(f"| `{row.get('path', '')}` | `{row.get('name', '')}` | {row.get('classification', '')} | {_md(row.get('reason', ''))} | {_md(row.get('schemaDecision', ''))} |")
    path.write_text("\n".join(lines) + "\n")
