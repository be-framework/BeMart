"""Audit classification helpers for BeMart Semantic-Ex schemas."""
from __future__ import annotations

from typing import Any


def form_boundary_entry(file: str, path: str, node: dict[str, Any]) -> dict[str, Any]:
    comment = str(node.get("$comment", ""))
    props = node.get("properties") if isinstance(node.get("properties"), dict) else {}
    classification = "framework-form-boundary" if "Aura/WebForm" in comment else "shapeable-form-context"
    if props:
        classification = "shapeable-form-context"
    return {
        "path": f"{file}:{path}",
        "classification": classification,
        "properties": len(props),
        "reason": comment or "フォーム境界の例外理由が未記録。",
        "schemaDecision": "Aura/WebForm内部構造はアプリケーション契約外。Resource上はフォーム文脈の存在だけを契約する。" if classification == "framework-form-boundary" else "Fake/Resourceから安定shapeを観察し、schema propertyへ昇格する候補。",
    }


def dynamic_row_entry(file: str, path: str, node: dict[str, Any]) -> dict[str, Any]:
    props = node.get("properties") if isinstance(node.get("properties"), dict) else {}
    comment = str(node.get("$comment", ""))
    title = str(node.get("title", ""))
    if "推奨プラグイン" in title or "プラグインカタログ" in comment:
        classification = "external-catalog-row"
    elif "ステータス" in title or "ステータス" in comment:
        classification = "status-summary-row"
    elif "CSV" in comment or "マスタ" in comment or "option map" in comment:
        classification = "dynamic-master-or-csv-row"
    else:
        classification = "dynamic-row-candidate"
    reason = comment or "動的行の例外理由が未記録。"
    return {
        "path": f"{file}:{path}",
        "classification": classification,
        "title": title,
        "properties": len(props),
        "reason": reason,
        "schemaDecision": "安定列はpropertiesへ昇格し、対象マスタ/CSV/外部カタログで増える列だけ互換境界として許容する。",
    }


def mixed_boundary_entry(
    name: str,
    file: str,
    path: str,
    node: dict[str, Any],
    request_transport_names: set[str],
    db_id_names: set[str],
) -> dict[str, Any]:
    comment = str(node.get("$comment", ""))
    token_names = {"authKey", "resetKey", "secretKey", "deviceToken", "trackingNumber"}
    legacy_key_names = {"displayOrderCountKey", "displayOrderScreenKey", "customerNameKey", "nameKey", "colorKey", "descriptionKey"}
    if name in token_names:
        classification = "should-be-string"
    elif "var/json_validate/" in file and (name in request_transport_names or name.endswith("Id")):
        classification = "request-transport"
    elif name in db_id_names and "var/json_validate/" not in file:
        classification = "should-be-db-id"
    elif name in legacy_key_names or name.endswith("Key"):
        classification = "legacy-map-key"
    else:
        classification = "ec-cube-boundary"
    schema_decision = {
        "request-transport": "HTTP formでは文字列として届くため、Resource/Semantic層の400応答を奪わないtransport契約としてmixedを許可する。",
        "ec-cube-boundary": "EC-CUBE採番IDとFake文字列IDが同じResource境界に現れるため、この境界だけ互換IDとして扱う。",
        "legacy-map-key": "option/map表示キーは旧EC-CUBE互換で文字列/整数が混在し得る。数値演算対象ではない。",
        "should-be-string": "token/key/tracking系は不透明文字列へ狭めるべき候補。",
        "should-be-db-id": "DB採番IDとしてintegerへ狭めるべき候補。",
    }[classification]
    return {
        "path": f"{file}:{path}",
        "name": name,
        "classification": classification,
        "reason": comment or "mixedBoundaryIdの理由が未記録。",
        "schemaDecision": schema_decision,
    }
