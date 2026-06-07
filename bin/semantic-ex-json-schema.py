#!/usr/bin/env python3
"""Semantic-Ex JSON Schema generator/auditor for BeMart.

Sources of truth, in order:
1. alps.json descriptor meaning
2. be/var/fake JSON/JSONL corpus observations
3. existing generated schema shapes from Resource bodies/parameters

This script intentionally rewrites var/json_schema and var/json_validate with
meaningful constraints instead of broad pass-through unions.
"""
from __future__ import annotations

import argparse
import json
import math
import re
from collections import Counter, defaultdict
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

from semantic_ex_json_schema.audit import dynamic_row_entry, form_boundary_entry, mixed_boundary_entry
from semantic_ex_json_schema.ledger import (
    write_dynamic_row_ledger,
    write_form_boundary_ledger,
    write_mixed_boundary_ledger,
)

ROOT = Path(__file__).resolve().parents[1]
SCHEMA_DIRS = [ROOT / "var/json_schema", ROOT / "var/json_validate"]
REPORT_DIR = ROOT / "docs/api"
OBSERVATION_MD = REPORT_DIR / "semantic-ex-observation.md"
QUALITY_JSON = REPORT_DIR / "schema-quality.json"
ALPS_GAP_MD = REPORT_DIR / "alps-gap-ledger.md"
FORM_BOUNDARY_MD = REPORT_DIR / "form-boundary-ledger.md"
DYNAMIC_ROW_MD = REPORT_DIR / "dynamic-row-ledger.md"
MIXED_BOUNDARY_MD = REPORT_DIR / "mixed-boundary-ledger.md"

BROAD_ESCAPE = ["string", "integer", "number", "boolean", "null"]

DYNAMIC_OBJECT_NAMES = {
    "form", "searchForm", "fields", "errors", "info", "Mail", "payment", "product", "category", "delivery", "order",
    "customer", "staticContent", "submitTo", "links", "_links", "arrFileList", "trustedHosts", "authKey",
}
DYNAMIC_MAP_NAMES = {
    "productStatusOptions", "orderStatusOptions", "authorityOptions", "rules", "masterTypes", "outputColumns",
    "notOutputColumns", "sections", "recommendedPlugins", "orderStatuses", "tradeLawRows",
}
PAGINATION_NAMES = {
    "page", "limit", "offset", "pageno", "disp_number", "orderby", "pager", "previous", "next",
    "pageCount", "current", "totalItemCount", "historyLimit", "orderLimit", "nameKeyword", "emailKeyword",
}
TRANSPORT_PAYLOAD_NAMES = {"csv", "pdf", "css", "js", "log", "value", "file_name", "fileName", "size"}
PRESENTATION_NAMES = {
    "csvTitle", "skeletonRoute", "tplNowDir", "tplParentDir", "tplIsTopDir", "colorKey",
    "descriptionKey", "nameKey", "customerNameKey", "displayOrderCountKey", "table",
    "className1", "className2", "classCategoryName1", "classCategoryName2",
    "mainImage", "mainListImage", "imagePath", "defaultShippingAddress", "body", "content",
    "name", "title", "label", "color", "version",
}
OPTIONAL_BY_NAME = {
    "description", "descriptionList", "descriptionDetail", "searchWord", "note", "productNote", "freeArea",
    "companyName", "kana01", "kana02", "birth", "sex", "job", "addr02", "phoneNumber", "postalCode", "pref",
    "imagePath", "mainImage", "mainListImage", "categoryNames", "tagNames", "classNames", "stock", "saleLimit",
    "ruleMin", "ruleMax", "previous", "next", "returnTo", "message", "errors", "form", "fields", "searchForm",
    "csrfToken", "links", "submitTo", "staticContent", "Mail", "currentPassword", "changePasswordFirst",
    "changePasswordSecond", "adminAllowHosts", "adminDenyHosts", "frontAllowHosts", "frontDenyHosts", "trustedHosts",
}

BOOL_NAMES = {
    "visible", "enabled", "installed", "alreadyInstalled", "wasInstalled", "alreadyDeleted", "alreadyAbsent",
    "alreadyExisted", "changed", "cleared", "accepted", "imported", "deleted", "skipped", "success", "available",
    "stockUnlimited", "canCheckout", "isMaintenance", "isSecureRequest", "phpinfoEnabled", "tplIsTopDir",
    "wasLoggedIn", "linkMethod", "hasError", "isDeletable", "doAddMultipleShippingAddress",
}
COUNT_NAMES = {
    "count", "totalItemCount", "cartCount", "favoriteCount", "orderCount", "recentOrderCount", "itemCount",
    "lineCount", "rowCount", "changedCount", "requestedCount", "addedCount", "skippedCount", "allocationCount",
    "countCustomers", "countProducts", "countNonStockProducts", "salesToday", "salesYesterday", "salesThisMonth",
    "totalSpent", "length", "pageCount", "current", "offset", "limit", "historyLimit", "orderLimit", "displayOrderCountKey",
}
INTEGER_NAMES = COUNT_NAMES | {
    "quantity", "requestedQuantity", "adjustedQuantity", "stock", "price", "price01", "price02", "unitPrice",
    "totalPrice", "deliveryFee", "deliveryFeeTotal", "charge", "discount", "usePoint", "initialPoint",
    "paymentMethodId", "saleTypeId", "productStatus", "customerStatus", "orderStatus", "previousStatus",
    "pref", "sortNo", "rank", "priority", "taxRate", "roundingType", "cartIndex", "taxType", "taxRuleId",
    "ruleMin", "ruleMax", "authority", "holiday", "id", "rowId", "calendarId", "shippingAddressId",
}
MONEY_NAMES = {"price", "price01", "price02", "unitPrice", "totalPrice", "deliveryFee", "deliveryFeeTotal", "charge", "discount", "salesToday", "salesYesterday", "salesThisMonth", "totalSpent"}

# ID semantics are intentionally split. Earlier schema generations used
# string|integer|null for most IDs; that made validation pass but did not tell
# clients which boundary they were crossing.
OPAQUE_ID_NAMES = {
    "customerId", "adminId", "addressId", "loginId", "preOrderId", "categoryId", "deliveryId",
    "classNameId", "classCategoryId", "newsId", "pageId", "blockId", "layoutId", "templateId",
    "tagId", "taxRuleId", "ticketId", "resetKey", "authKey", "secretKey", "deviceToken",
    "parentId",
}
DB_ID_NAMES = {"paymentMethodId", "saleTypeId", "shippingAddressId", "memberId", "mailTemplateId", "productId", "productClassId"}
MIXED_ID_NAMES = {"paymentId", "rowId", "calendarId", "id"}

# Contextual ALPS aliases: Resource properties often use presentation names that
# are semantically the same as an ALPS descriptor.
ALPS_ALIASES = {
    "customerName": "name01",
    "customerId": "customerId",
    "category_id": "categoryId",
    "mainImage": "imagePath",
    "mainListImage": "imagePath",
    "description": "descriptionDetail",
    "note": "productNote",
    "price": "price02",
    "unitPrice": "price02",
    "paymentMethodName": "paymentMethod",
    "paymentMethodId": "paymentMethod",
    "routeName": "pageUrl",
    "returnTo": "pageUrl",
    "href": "pageUrl",
    "method": "httpMethod",
    "rel": "transitionId",
}

# Exact/contextual Japanese titles for properties that are too generic or too
# presentation-specific to inherit an ALPS title blindly.  The goal is not to
# hide camelCase; it is to name the business role at the Resource boundary.
SEMANTIC_TITLE_BY_NAME = {
    "submitTo": "フォーム送信先リンク",
    "links": "ALPS遷移リンク集合",
    "_links": "ALPS遷移リンク集合",
    "method": "HTTPメソッド",
    "rel": "リンク関係",
    "href": "リンクURI参照",
    "filters": "検索条件",
    "pager": "ページャ",
    "previous": "前ページリンク",
    "next": "次ページリンク",
    "limit": "表示件数",
    "offset": "開始位置",
    "pageno": "ページ番号",
    "disp_number": "表示件数指定",
    "orderby": "並び順",
    "pageCount": "総ページ数",
    "current": "現在ページ",
    "totalItemCount": "総件数",
    "items": "明細一覧",
    "item": "明細",
    "rows": "行データ",
    "row": "行",
    "columns": "CSV列定義",
    "column": "CSV列",
    "sections": "静的コンテンツセクション一覧",
    "section": "静的コンテンツセクション",
    "content": "本文",
    "body": "本文",
    "staticContent": "静的コンテンツ",
    "fields": "表示フィールド集合",
    "field": "表示フィールド",
    "errors": "検証エラー",
    "error": "検証エラー",
    "form": "入力フォーム",
    "searchForm": "検索フォーム",
    "message": "処理メッセージ",
    "nameKeyword": "名前検索キーワード",
    "emailKeyword": "メール検索キーワード",
    "parentId": "親カテゴリID",
    "calendarId": "カレンダーID",
    "shippingAddressId": "配送先ID",
    "paymentMethodId": "支払方法ID",
    "productId": "商品ID",
    "productClassId": "商品規格ID",
    "memberId": "管理メンバーID",
    "mailTemplateId": "メールテンプレートID",
    "rowId": "行ID",
    "id": "ID",
    "paymentId": "支払方法ID",
    "authKey": "二要素認証キー",
    "resetKey": "パスワードリセットキー",
    "secretKey": "メール認証キー",
    "deviceToken": "二要素認証デバイストークン",
    "trackingNumber": "荷物追跡番号",
    "alreadyDeleted": "既削除フラグ",
    "alreadyAbsent": "既不存在フラグ",
    "alreadyExisted": "既存在フラグ",
    "alreadyInstalled": "既インストールフラグ",
    "wasInstalled": "インストール済み結果",
    "wasLoggedIn": "ログイン済み結果",
    "hasError": "エラー有無",
    "isMaintenance": "メンテナンス中フラグ",
    "isSecureRequest": "セキュアリクエスト判定",
    "phpinfoEnabled": "PHP情報表示可否",
    "isDeletable": "削除可能フラグ",
    "stockFind": "在庫検索フラグ",
    "blockDeletable": "ブロック削除可能フラグ",
    "displayOrderScreen": "表示順画面フラグ",
    "tplIsTopDir": "テンプレートルートディレクトリ判定",
    "arrFileList": "ファイル一覧",
    "tplNowDir": "現在テンプレートディレクトリ",
    "tplParentDir": "親テンプレートディレクトリ",
    "notOutputColumns": "非出力CSV列",
    "outputColumns": "出力CSV列",
    "csvTitle": "CSVタイトル",
    "skeletonRoute": "スケルトンルート",
    "selectedMaster": "選択中マスタ",
    "masterTypes": "マスタ種別一覧",
    "masterType": "マスタ種別",
    "table": "マスタテーブル",
    "file_name": "ファイル名",
    "fileName": "ファイル名",
    "size": "ペイロードサイズ",
    "length": "本文長",
    "initialPoint": "初期ポイント",
    "totalSpent": "累計購入金額",
    "salesToday": "本日売上",
    "salesYesterday": "昨日売上",
    "salesThisMonth": "当月売上",
    "countCustomers": "会員数",
    "countProducts": "商品数",
    "countNonStockProducts": "在庫切れ商品数",
    "mainImage": "メイン画像URI",
    "mainListImage": "一覧メイン画像URI",
    "imagePath": "画像URI",
    "categoryNames": "カテゴリ名一覧",
    "tagNames": "タグ名一覧",
    "classNames": "規格名一覧",
    "className": "規格名",
    "className1": "第1規格名",
    "className2": "第2規格名",
    "classCategoryName": "規格分類名",
    "classCategoryName1": "第1規格分類名",
    "classCategoryName2": "第2規格分類名",
    "classCategories": "規格分類一覧",
    "ruleMin": "下限金額",
    "ruleMax": "上限金額",
    "requestedQuantity": "要求数量",
    "adjustedQuantity": "調整後数量",
    "orderNos": "注文番号一覧",
    "orderStatuses": "注文ステータス一覧",
    "mailTemplates": "メールテンプレート一覧",
    "mailHistories": "メール履歴一覧",
    "recommendedPlugins": "推奨プラグイン一覧",
    "tradeLawRows": "特定商取引法表示行",
    "displayOrderCountKey": "表示順件数キー",
    "displayOrderScreenKey": "表示順画面キー",
    "customerNameKey": "顧客名キー",
    "nameKey": "名称キー",
    "colorKey": "色キー",
    "descriptionKey": "説明キー",
    "operation": "操作種別",
    "accepted": "受理件数",
    "imported": "取込件数",
    "deleted": "削除件数",
    "skipped": "スキップ件数",
    "holiday": "休日指定",
    "Mail": "メールテンプレート詳細",
}

COLLECTION_TITLE_BY_NAME = {
    "products": "商品一覧",
    "product": "商品詳細",
    "carts": "カート一覧",
    "cartItems": "カート明細一覧",
    "orders": "注文一覧",
    "order": "注文詳細",
    "orderItems": "受注明細一覧",
    "shippings": "配送一覧",
    "allocations": "配送割当一覧",
    "customers": "会員一覧",
    "customer": "会員詳細",
    "addresses": "住所一覧",
    "favorites": "お気に入り商品一覧",
    "recentOrders": "最近の注文一覧",
    "classes": "商品規格一覧",
    "categories": "カテゴリ一覧",
    "category": "カテゴリ詳細",
    "tags": "タグ一覧",
    "payments": "支払方法一覧",
    "payment": "支払方法詳細",
    "deliveries": "配送方法一覧",
    "delivery": "配送方法詳細",
    "taxRules": "税ルール一覧",
    "calendars": "カレンダー一覧",
    "news": "ニュース一覧",
    "pages": "ページ一覧",
    "blocks": "ブロック一覧",
    "layouts": "レイアウト一覧",
    "members": "管理メンバー一覧",
    "templates": "テンプレート一覧",
    "plugins": "プラグイン一覧",
    "entries": "ログイン履歴一覧",
    "log": "ログ行一覧",
    "info": "システム情報一覧",
    "rules": "権限ルール一覧",
    "authorityOptions": "権限選択肢一覧",
    "masterTypes": "マスタ種別一覧",
    "rows": "行データ",
    "columns": "CSV列定義",
    "sections": "静的コンテンツセクション一覧",
    "recommendedPlugins": "推奨プラグイン一覧",
    "orderStatuses": "注文ステータス一覧",
    "tradeLawRows": "特定商取引法表示行",
}

DYNAMIC_ROW_NAMES = {"rows", "columns", "masterTypes", "recommendedPlugins", "orderStatuses", "tradeLawRows", "rules", "authorityOptions"}
REQUEST_TRANSPORT_MIXED_NAMES = {
    "quantity", "requestedQuantity", "adjustedQuantity", "price02", "charge", "taxRate", "productStatus",
    "paymentMethodId", "shippingAddressId", "calendarId", "paymentId", "rowId", "id", "mailTemplateId",
    "productId", "productClassId", "memberId", "holiday", "pageno", "disp_number",
}
APPROVED_MIXED_RESPONSE_NAMES = {"id", "paymentId", "calendarId", "rowId", "holiday", "pageno", "disp_number"}

@dataclass
class Stat:
    seen: int = 0
    nulls: int = 0
    types: Counter[str] = field(default_factory=Counter)
    string_lengths: list[int] = field(default_factory=list)
    numbers: list[float] = field(default_factory=list)
    values: Counter[str] = field(default_factory=Counter)
    sources: Counter[str] = field(default_factory=Counter)

    def add(self, value: Any, source: str) -> None:
        self.seen += 1
        self.sources[source] += 1
        if value is None:
            self.nulls += 1
            self.types["null"] += 1
            return
        if isinstance(value, bool):
            self.types["boolean"] += 1
            self.values[str(value).lower()] += 1
            return
        if isinstance(value, int):
            self.types["integer"] += 1
            self.numbers.append(value)
            self.values[str(value)] += 1
            return
        if isinstance(value, float):
            self.types["number"] += 1
            self.numbers.append(value)
            self.values[str(value)] += 1
            return
        if isinstance(value, str):
            self.types["string"] += 1
            self.string_lengths.append(len(value))
            if len(self.values) < 120:
                self.values[value] += 1
            return
        if isinstance(value, list):
            self.types["array"] += 1
            self.numbers.append(len(value))
            return
        if isinstance(value, dict):
            self.types["object"] += 1
            return


def load_json(path: Path) -> Any:
    return json.loads(path.read_text())


def dump_json(path: Path, data: Any) -> None:
    path.write_text(json.dumps(data, ensure_ascii=False, indent=2) + "\n")


def load_alps() -> dict[str, dict[str, Any]]:
    alps = load_json(ROOT / "alps.json")
    result: dict[str, dict[str, Any]] = {}
    for desc in alps.get("alps", {}).get("descriptor", []):
        if isinstance(desc, dict) and desc.get("id"):
            result[str(desc["id"])] = desc
    return result


def read_jsonl(path: Path) -> list[Any]:
    rows = []
    for line in path.read_text().splitlines():
        line = line.strip()
        if line:
            rows.append(json.loads(line))
    return rows


def observe_fake() -> dict[str, Stat]:
    stats: dict[str, Stat] = defaultdict(Stat)

    def walk(value: Any, source: str) -> None:
        if isinstance(value, dict):
            for key, child in value.items():
                stats[key].add(child, source)
                walk(child, source)
        elif isinstance(value, list):
            for child in value:
                walk(child, source)

    fake_root = ROOT / "be/var/fake"
    for path in sorted(fake_root.glob("*.json")):
        data = load_json(path)
        walk(data, path.name)
    for path in sorted((fake_root / "query").glob("*.json")):
        walk(load_json(path), "query/" + path.name)
    for path in sorted((fake_root / "query").glob("*.jsonl")):
        for row in read_jsonl(path):
            walk(row, "query/" + path.name)
    return stats


def camel_words(name: str) -> str:
    s = re.sub(r"[_-]+", " ", name)
    s = re.sub(r"(?<!^)(?=[A-Z])", " ", s)
    return s.strip()


def alps_id_for(name: str, alps: dict[str, dict[str, Any]]) -> str | None:
    if name in alps:
        return name
    if name in ALPS_ALIASES and ALPS_ALIASES[name] in alps:
        return ALPS_ALIASES[name]
    # Strip common presentation suffixes/prefixes.
    for suffix in ("Options", "Rows", "List", "Count", "Key", "Name", "Id", "Ids"):
        if name.endswith(suffix):
            candidate = name[: -len(suffix)]
            if candidate in alps:
                return candidate
    return None


def classify_non_alps_property(name: str, alps: dict[str, dict[str, Any]]) -> str | None:
    """Classify properties not directly named in ALPS.

    The quality gate is not "every JSON key must literally exist in ALPS";
    Resource bodies also contain hypermedia affordances, pagination, framework
    form state, export payloads, and presentation collections. This classifier
    keeps those exceptions explicit and leaves truly unknown names visible.
    """
    if alps_id_for(name, alps) or name in DYNAMIC_OBJECT_NAMES or name in DYNAMIC_MAP_NAMES:
        return "alps-or-dynamic"
    if name in {"method", "rel", "transitionId"} or re.match(r"^(go|do)[A-Z]", name):
        return "hypermedia"
    if name in PAGINATION_NAMES:
        return "pagination"
    if name in BOOL_NAMES or name.startswith(("is", "has", "can", "was", "already")):
        return "runtime-flag"
    if name in COUNT_NAMES or name.endswith("Count"):
        return "counter"
    if name in TRANSPORT_PAYLOAD_NAMES:
        return "transport-payload"
    if name.endswith(("Id", "Ids", "Key", "Code", "Codes", "Token")) or name in {"id", "authKey", "deviceToken"}:
        return "identifier"
    if name.endswith("s") or name in {"items", "rows", "columns", "entries"}:
        return "collection-or-row"
    if name in PRESENTATION_NAMES or name.endswith(("Name", "Names", "Title", "Route")):
        return "presentation"
    if name in OPTIONAL_BY_NAME or name in {"currentPassword", "changePasswordFirst", "changePasswordSecond", "dummyEmail", "originalEmail"}:
        return "form-context"
    if name in {"accepted", "changed", "deleted", "imported", "skipped", "cleared", "operation"}:
        return "operation-result"
    if name in {"ruleMin", "ruleMax", "initialPoint", "stockFind", "rowCount", "lineCount", "requestedCount", "changedCount", "addedCount", "skippedCount", "allocationCount", "requestedQuantity", "adjustedQuantity", "length", "previousAuthority", "previousStatus", "newProductName", "masterType", "selectedMaster", "holiday", "timestamp"}:
        return "domain-derived"
    return None


def route_from_title(schema_title: str) -> str:
    m = re.match(r"^(GET|POST|PUT|PATCH|DELETE)\s+(.+?)\s+(response|request parameters)$", schema_title)
    return m.group(2) if m else schema_title


def domain_from_file(file_name: str) -> str:
    if "-csv" in file_name or "csv-" in file_name or "-export-" in file_name or "-import-" in file_name:
        return "CSV"
    if "forgot" in file_name or "reset" in file_name:
        return "パスワード再設定"
    if "action-redirect" in file_name:
        return "リダイレクト"
    if "unsupported-route" in file_name:
        return "未対応ルート"
    if "two-factor" in file_name:
        return "二要素認証"
    if "-order" in file_name or "shopping" in file_name:
        return "注文"
    if "-product" in file_name or "products" in file_name:
        return "商品"
    if "-customer" in file_name or "mypage" in file_name or "entry" in file_name:
        return "会員"
    if "-payment" in file_name:
        return "支払方法"
    if "-delivery" in file_name:
        return "配送方法"
    if "-tax-rule" in file_name:
        return "税ルール"
    if "-master-data" in file_name:
        return "マスタデータ"
    return "処理"


def is_mechanical_title(title: str) -> bool:
    return bool(re.search(r"\b[a-z]+ [A-Z][A-Za-z]+", title))


def is_generic_fallback_description(desc: str) -> bool:
    return bool(re.search(r" における [^。]+。$", desc))


def property_name_from_path(path: str) -> str:
    if not path:
        return "$root"
    parts = [p for p in path.split(".") if p and not p.startswith("$defs")]
    if not parts:
        return "$root"
    if parts[-1] == "items":
        if len(parts) >= 2:
            return f"{parts[-2]}Item"
        return "itemsItem"
    if parts[-1] == "additionalProperties":
        if len(parts) >= 2:
            return f"{parts[-2]}Value"
        return "additionalProperty"
    return parts[-1]


def collection_name_for_item(name: str, parent: str, prop_path: str) -> str:
    if parent:
        return parent
    if name.endswith("Item"):
        stem = name[:-4]
        return stem or "items"
    parts = [p for p in prop_path.split(".") if p]
    if parts and parts[-1] == "items" and len(parts) >= 2:
        return parts[-2]
    return name


def collection_item_title(collection_name: str, domain: str) -> str:
    item_titles = {
        "products": "商品概要",
        "product": "商品",
        "carts": "カート",
        "cartItems": "カート明細",
        "items": "明細",
        "orders": "注文概要",
        "order": "注文",
        "orderItems": "受注明細",
        "shippings": "配送",
        "allocations": "配送割当",
        "customers": "会員概要",
        "customer": "会員",
        "addresses": "住所",
        "favorites": "お気に入り商品",
        "recentOrders": "最近の注文",
        "classes": "商品規格",
        "categories": "カテゴリ",
        "category": "カテゴリ",
        "classNames": "規格名",
        "classCategories": "規格分類",
        "tags": "タグ",
        "payments": "支払方法",
        "payment": "支払方法",
        "deliveries": "配送方法",
        "delivery": "配送方法",
        "taxRules": "税ルール",
        "calendars": "カレンダー行",
        "news": "ニュース",
        "pages": "ページ",
        "blocks": "ブロック",
        "layouts": "レイアウト",
        "members": "管理メンバー",
        "templates": "テンプレート",
        "plugins": "プラグイン",
        "entries": "ログイン履歴",
        "log": "ログ行",
        "info": "システム情報",
        "rules": "権限ルール",
        "authorityOptions": "権限選択肢",
        "masterTypes": "マスタ種別",
        "rows": "行",
        "columns": "CSV列",
        "sections": "静的コンテンツセクション",
        "recommendedPlugins": "推奨プラグイン",
        "orderStatuses": "注文ステータス",
        "tradeLawRows": "特定商取引法表示行",
        "mailTemplates": "メールテンプレート",
        "mailHistories": "メール履歴",
    }
    return item_titles.get(collection_name, f"{domain}行")


def semantic_title_for(
    name: str,
    schema_title: str,
    alps: dict[str, dict[str, Any]],
    *,
    file_name: str = "",
    prop_path: str = "",
    parent: str = "",
    fallback: bool = False,
) -> str:
    """Context-aware title; never falls back to mechanical camel splitting."""
    domain = domain_from_file(file_name)
    if name == "$root" or not name:
        return ""
    if re.match(r"^(go|do)[A-Z]", name):
        return "ALPS遷移リンク"
    if name == "title":
        if parent in {"sectionsItem", "sections"} or "help-" in file_name:
            return "セクション見出し"
        if "calendar" in file_name:
            return "カレンダー表示名"
        if parent in {"pagesItem", "page"}:
            return "ページタイトル"
        if parent in {"newsItem", "news"}:
            return "ニュースタイトル"
        return f"{domain}タイトル"
    if name == "name":
        if "product" in file_name or parent in {"productsItem", "product"}:
            return "商品名"
        if parent in {"masterTypesItem", "masterTypes"}:
            return "マスタ種別名"
        if parent in {"infoItem", "info"}:
            return "情報名"
        if parent in {"paymentsItem", "payment"}:
            return "支払方法名"
        if parent in {"deliveriesItem", "delivery"}:
            return "配送方法名"
        return f"{domain}表示名"
    if name == "label":
        return "表示ラベル"
    if name == "value":
        if "export" in file_name:
            return "CSVエクスポート本文"
        if parent in {"masterTypesItem", "masterTypes"} or "master-data" in file_name:
            return "マスタ値"
        if parent in {"infoItem", "info"}:
            return "システム情報値"
        return "表示値"
    if name.endswith("Item"):
        return collection_item_title(collection_name_for_item(name, parent, prop_path), domain)
    if name in SEMANTIC_TITLE_BY_NAME:
        return SEMANTIC_TITLE_BY_NAME[name]
    if name in COLLECTION_TITLE_BY_NAME:
        return COLLECTION_TITLE_BY_NAME[name]
    if not fallback:
        return ""
    cls = classify_non_alps_property(name, alps)
    if name.endswith(("Id", "Ids")) or cls == "identifier":
        return f"{domain}識別子"
    if name.endswith(("Key", "Token", "Code", "Codes")):
        return f"{domain}キー"
    if name.endswith("Count") or cls == "counter":
        return f"{domain}件数"
    if name.endswith("s") or cls == "collection-or-row":
        return f"{domain}一覧"
    if cls == "hypermedia":
        return "ハイパーメディア項目"
    if cls == "pagination":
        return "一覧制御項目"
    if cls == "runtime-flag":
        return "処理状態フラグ"
    if cls == "transport-payload":
        return "輸送ペイロード"
    if cls == "presentation":
        return "表示項目"
    if cls == "form-context":
        return "フォーム文脈項目"
    if cls == "operation-result":
        return "操作結果"
    if cls == "domain-derived":
        return f"{domain}派生項目"
    return f"{domain}項目"


def semantic_description_for(
    name: str,
    schema_title: str,
    alps: dict[str, dict[str, Any]],
    *,
    file_name: str = "",
    prop_path: str = "",
    parent: str = "",
) -> str:
    route = route_from_title(schema_title)
    domain = domain_from_file(file_name)
    is_request = file_name.endswith(".param.json")
    io = "入力" if is_request else "レスポンス"
    title = semantic_title_for(name, schema_title, alps, file_name=file_name, prop_path=prop_path, parent=parent, fallback=True)
    if name in {"submitTo"}:
        return f"{route} のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。"
    if name in {"links", "_links"}:
        return f"{route} の{io}から利用できるALPS遷移リンク集合。property名がrel、値が遷移先URIを表す。"
    if re.match(r"^(go|do)[A-Z]", name):
        return f"ALPS `{name}` 遷移のリンク先URI。Resource状態から次に実行できるsafe/unsafe操作を示す。"
    if name == "method":
        return f"{route} のリンクまたはフォーム送信で使うHTTPメソッド。GET/POST等の遷移方法を表す。"
    if name == "rel":
        return f"{route} のリンク関係名。ALPS descriptor idと対応し、クライアントが遷移意味を識別する。"
    if name == "href":
        return f"{route} の遷移先URI。相対URIまたは絶対URIとして解決される。"
    if name in {"form", "searchForm"}:
        return f"{route} の{io}で保持するフォーム文脈。Aura/WebForm由来の内部構造は別境界の責務で、ここではResource上の役割を示す。"
    if name in {"filters", "limit", "offset", "pageno", "disp_number", "orderby", "pager", "previous", "next", "pageCount", "current", "totalItemCount"}:
        return f"{route} の一覧表示を制御するページング/検索条件。件数、開始位置、並び順、前後リンクをクライアントが再現するための値。"
    if name.endswith("Item"):
        collection = collection_name_for_item(name, parent, prop_path)
        return f"{route} の{io}に含まれる{title}。親コレクション `{collection}` の1行を表し、固定できる業務列はschema propertyで明示する。"
    if name in COLLECTION_TITLE_BY_NAME or name in {"items", "rows", "columns", "sections"}:
        return f"{route} の{io}で扱う{title}。配列要素はALPS意味とFake観察に基づき、固定できない動的列は例外理由を台帳化する。"
    if name in {"title", "name", "label", "value", "content", "body", "staticContent", "fields"}:
        return f"{route} の{io}で表示またはテンプレート描画に使う{title}。同名propertyでも親文脈 `{parent or 'root'}` によって意味を分ける。"
    if name in {"authKey", "resetKey", "secretKey", "deviceToken", "trackingNumber"} or name.endswith(("Key", "Token")):
        return f"{route} の{io}で扱う{title}。数値演算対象ではなく、照合・URL・配送追跡などに使う不透明な文字列識別子。"
    if name.endswith(("Id", "Ids")) or name in {"id", "rowId", "paymentId", "calendarId"}:
        return f"{route} の{io}で対象を識別する{title}。DB採番ID、Fake文字列ID、互換境界IDのどれに該当するかをschemaの型とコメントで分ける。"
    if name in BOOL_NAMES or name.startswith(("is", "has", "can", "was", "already")):
        return f"{route} の処理状態を示す{title}。画面表示や冪等処理結果の分岐に使う真偽値。"
    if name in COUNT_NAMES or name.endswith("Count"):
        return f"{route} の{io}で返す{title}。一覧、集計、CSV処理結果の規模を表す非負の数値。"
    if name in TRANSPORT_PAYLOAD_NAMES:
        return f"{route} の{io}で運ぶ{title}。CSV/PDF/ログ等の内部形式は専用境界で扱い、JSON Schemaでは輸送上の型とサイズを契約する。"
    cls = classify_non_alps_property(name, alps)
    if cls == "presentation":
        return f"{route} の画面表示に使う{title}。業務エンティティそのものではなくテンプレート/一覧表示の補助値。"
    if cls == "form-context":
        return f"{route} のフォーム文脈で使う{title}。入力保持、初期値、再表示に必要な補助値。"
    if cls == "operation-result":
        return f"{route} のunsafe操作結果を表す{title}。成功時の差分、処理件数、冪等状態をクライアントに返す。"
    if cls == "domain-derived":
        return f"{route} の{domain}文脈から派生した{title}。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。"
    if cls == "hypermedia":
        return f"{route} のハイパーメディア制御に使う{title}。ALPS遷移とResourceリンクを接続する。"
    return f"{route} の{io}で扱う{title}。ALPS、Fake観察、Resource境界の形状から導いた業務契約。"


def context_title_description(file_name: str, schema_title: str, prop_path: str, name: str, parent: str) -> tuple[str, str] | None:
    """Return route-aware labels for generic property names.

    ALPS descriptors are intentionally reused, but JSON Schema properties such
    as message/value/count/name need a Resource context to be useful.
    """
    route = route_from_title(schema_title)
    domain = domain_from_file(file_name)
    is_request = file_name.endswith(".param.json")
    io = "入力" if is_request else "レスポンス"

    if name == "message":
        return (
            f"{domain}メッセージ",
            f"{route} の{io}に含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。",
        )
    if name == "nameKeyword":
        return ("名前検索キーワード", f"{route} の検索条件。商品名・会員名・管理者名など、この一覧画面で名前として扱う表示名を部分一致検索する。")
    if name == "emailKeyword":
        return ("メール検索キーワード", f"{route} の検索条件。会員または管理者のメールアドレス/ログイン識別子を部分一致検索する。")
    if name == "count" or name.endswith("Count"):
        label = {
            "totalItemCount": "総件数", "favoriteCount": "お気に入り件数", "orderCount": "注文件数",
            "recentOrderCount": "最近の注文件数", "cartCount": "カート件数", "itemCount": "明細件数",
            "lineCount": "CSV行数", "rowCount": "行数",
        }.get(name, "件数")
        return (label, f"{route} の{io}で返す{label}。一覧・集計・処理結果の規模を表す非負整数。")
    if name == "value":
        if "export" in file_name:
            return ("CSVエクスポート本文", f"{route} が返すCSV本文。列意味はCSV互換サービス側、JSON境界では文字列として契約する。")
        if parent in {"masterTypesItem", "masterTypes"} or "master-data" in file_name:
            return ("マスタ値", f"{route} のマスタ種別またはマスタ行に表示される値。選択肢の表示/保存単位として扱う。")
        if parent in {"infoItem", "info"}:
            return ("システム情報値", f"{route} のシステム情報行に表示される値。PHP/環境/設定などの表示用文字列。")
        return ("表示値", f"{route} の{io}で表示または選択肢として使う値。親コンテキスト `{parent}` に属する。")
    if name == "page":
        return ("ページ識別子", f"{route} の静的コンテンツで表示対象ページを識別する値。ページ番号ではない。")
    if name == "fields":
        return ("静的表示フィールド", f"{route} でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。")
    if name == "staticContent":
        return ("静的コンテンツ", f"{route} で表示する規約・ヘルプ・エラー等の静的ページ本文とセクション情報。")
    if name == "rows":
        return ("行データ", f"{route} のマスタ/CSV行データ。列集合は対象マスタにより変わるため、既知列を優先して契約する。")
    if name == "columns":
        return ("CSV列定義", f"{route} のCSV列設定。各要素は出力対象フィールドと表示名を表す。")
    if name == "items":
        return ("明細一覧", f"{route} の親オブジェクト `{parent}` に含まれる明細配列。商品・カート・受注明細の文脈で解釈する。")
    if name == "name" and ("product" in file_name or parent in {"productsItem", "product"}):
        return ("商品名", f"{route} で表示する商品名。検索・一覧・詳細でユーザーに見せる販売名。")
    return None


def doc_value(desc: dict[str, Any] | None) -> str:
    if not desc:
        return ""
    doc = desc.get("doc")
    if isinstance(doc, dict):
        return str(doc.get("value", ""))
    if isinstance(doc, str):
        return doc
    return ""


def contextual_title(name: str, schema_title: str, alps: dict[str, dict[str, Any]]) -> str:
    semantic = semantic_title_for(name, schema_title, alps)
    aid = alps_id_for(name, alps)
    base = semantic or (str(alps.get(aid, {}).get("title", "")) if aid else "")
    if not base:
        base = semantic_title_for(name, schema_title, alps, fallback=True)
    # Add light context only when it materially changes meaning.
    if "request parameters" in schema_title:
        return f"{base}（入力）"
    if name in {"price", "unitPrice"}:
        return f"{base}（表示/計算用）"
    if name in {"href", "returnTo"}:
        return f"{base}（URI参照）"
    return base


def description_for(name: str, schema_title: str, alps: dict[str, dict[str, Any]], stats: dict[str, Stat]) -> str:
    aid = alps_id_for(name, alps)
    parts = []
    if aid:
        d = doc_value(alps[aid])
        if d:
            parts.append(d)
    contextual = {
        "csrfToken": "フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。",
        "transitionId": "このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。",
        "cartKey": "販売種別ごとにカートを分離するキー。ALPSのcartKeyはセッション接頭辞と販売種別IDから構成される。",
        "productCode": "商品を識別するSKU。Fake corpusではASCII英数・ハイフン中心で、受注明細/カート明細の結合キーになる。",
        "email": "ログインIDを兼ねるメールアドレス。会員登録・ログイン・通知で共通に使う。",
        "postalCode": "日本の郵便番号。入力フォームではハイフン有無をどちらも受け入れる。",
        "phoneNumber": "日本の電話番号。Fake corpusはハイフンなし中心だが、入力ではハイフン付きも許容する。",
        "pref": "都道府県ID。住所フォームの未選択状態では0、確定住所では1〜47を使う。",
        "pdf": "PDFバイナリをJSON検査可能なUTF-8文字列へ正規化した表現。内容自体のPDF構造は別境界の責務。",
        "csv": "CSVインポート/エクスポート本文。列構造の詳細はCSV互換サービス境界で検査する。",
        "form": "Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。",
    }
    if name in contextual:
        parts.append(contextual[name])
    st = stats.get(name)
    if st and (st.string_lengths or st.numbers or st.values):
        obs = observation_sentence(name, st, short=True)
        if obs:
            parts.append(obs)
    if not parts:
        parts.append(semantic_description_for(name, schema_title, alps))
    return " ".join(dict.fromkeys(parts))


def nullable(existing: dict[str, Any], name: str) -> bool:
    t = existing.get("type")
    has_null = (isinstance(t, list) and "null" in t) or t == "null"
    return has_null or name in OPTIONAL_BY_NAME


def with_null(schema: dict[str, Any], allow_null: bool) -> dict[str, Any]:
    if not allow_null:
        return schema
    t = schema.get("type")
    if isinstance(t, str):
        schema["type"] = [t, "null"]
    elif isinstance(t, list) and "null" not in t:
        schema["type"] = [*t, "null"]
    return schema


def ref(name: str, allow_null: bool = False) -> dict[str, Any]:
    if allow_null:
        return {"anyOf": [{"$ref": f"#/$defs/{name}"}, {"type": "null"}]}
    return {"$ref": f"#/$defs/{name}"}


def common_defs(alps: dict[str, dict[str, Any]]) -> dict[str, Any]:
    def desc(id_: str, extra: str = "") -> str:
        base = doc_value(alps.get(id_))
        return (base + (" " + extra if extra else "")).strip()

    return {
        "productCode": {
            "title": "商品コード",
            "description": desc("productCode", "SKUとして在庫・カート・受注明細を接続する。Fake観察ではASCII英数とハイフン中心。"),
            "type": "string", "minLength": 1, "maxLength": 64, "pattern": "^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$",
            "example": "sample-001",
        },
        "email": {
            "title": "メールアドレス",
            "description": desc("email", "ログインID/通知先として使うためRFC準拠のemail形式。"),
            "type": "string", "format": "email", "minLength": 3, "maxLength": 254, "example": "alice@example.com",
        },
        "postalCode": {
            "title": "郵便番号",
            "description": desc("postalCode", "日本国内住所向け。ハイフンなし7桁またはハイフン付き8桁。"),
            "type": "string", "pattern": "^\\d{3}-?\\d{4}$", "example": "1500001",
        },
        "phoneNumber": {
            "title": "電話番号",
            "description": desc("phoneNumber", "日本国内電話番号。Fakeはハイフンなし中心、入力ではハイフン付きも許容。"),
            "type": "string", "pattern": "^0\\d{1,4}-?\\d{1,4}-?\\d{3,4}$", "minLength": 10, "maxLength": 13, "example": "0312345678",
        },
        "price": {
            "title": "金額",
            "description": "EC-CUBEの商品価格・送料・手数料・売上金額。日本円の整数金額として扱う。",
            "type": "integer", "minimum": 0, "maximum": 999999999, "example": 1200,
        },
        "quantity": {
            "title": "数量",
            "description": desc("quantity", "購入/調整数量。入力は1以上、調整後や集計では0以上を許容する場合がある。"),
            "type": "integer", "minimum": 1, "maximum": 999, "example": 2,
        },
        "nonNegativeInteger": {
            "title": "非負整数",
            "description": "件数、在庫、ページ番号、集計値など0以上の整数。",
            "type": "integer", "minimum": 0, "maximum": 2147483647, "example": 1,
        },
        "opaqueId": {
            "title": "不透明ID",
            "description": "BeMart/Fake/アプリ層で外部に公開する文字列ID。DB採番値としての数値演算には使わない。",
            "type": "string", "minLength": 1, "maxLength": 128, "pattern": "^[A-Za-z0-9._:@/-]+$", "example": "customer-001",
        },
        "dbId": {
            "title": "DB採番ID",
            "description": "EC-CUBEマスタや内部行を指す非負整数ID。表示やフォーム境界では文字列化される場合があるが、意味は採番ID。",
            "type": "integer", "minimum": 0, "maximum": 2147483647, "example": 1,
        },
        "mixedBoundaryId": {
            "title": "互換境界ID",
            "description": "同一ResourceでFake文字列IDとEC-CUBE整数IDの両方が観察される互換境界専用ID。恒久的なドメイン型ではない。",
            "type": ["string", "integer"], "minLength": 0, "maxLength": 128, "example": "pay-cod",
            "$comment": "mixedBoundaryIdは移行互換のための例外。可能ならopaqueIdまたはdbIdへ分解する。",
        },
        "pref": {
            "title": "都道府県ID",
            "description": desc("pref", "確定住所は1〜47。入力フォームの未選択初期値として0を許容する。"),
            "type": "integer", "minimum": 0, "maximum": 47, "example": 13,
        },
        "productStatus": {
            "title": "商品ステータス",
            "description": desc("productStatus"),
            "type": "integer", "enum": [1, 2, 3], "example": 1,
        },
        "customerStatus": {
            "title": "会員ステータス",
            "description": desc("customerStatus"),
            "type": "integer", "enum": [1, 2, 3], "example": 2,
        },
        "orderStatus": {
            "title": "注文ステータス",
            "description": desc("orderStatus", "EC-CUBE受注状態。Fake/管理画面で扱う状態ID。"),
            "type": "integer", "minimum": 1, "maximum": 9, "example": 1,
        },
        "cartKey": {
            "title": "カートキー",
            "description": desc("cartKey"),
            "type": "string", "minLength": 3, "maxLength": 128, "pattern": "^.+_[0-9]+$", "example": "session-prefix-1_1",
        },
        "csrfToken": {
            "title": "CSRFトークン",
            "description": "フォーム送信元を検証するトークン。Fake環境では deterministic な値を使う。",
            "type": "string", "minLength": 8, "maxLength": 160, "pattern": "^[A-Za-z0-9_.:-]+$", "example": "fake-csrf-token-bemart-2026",
        },
        "uriReference": {
            "title": "URI参照",
            "description": "画面遷移・リダイレクト・リンク先を表す相対または絶対URI。",
            "type": "string", "format": "uri-reference", "minLength": 1, "maxLength": 2048, "example": "/products",
        },
        "transitionId": {
            "title": "ALPS遷移ID",
            "description": "alps.json に定義された safe/unsafe descriptor のID。",
            "type": "string", "minLength": 2, "maxLength": 96, "pattern": "^(go|do)[A-Z][A-Za-z0-9]*$", "example": "doAddCartItem",
        },
        "date": {"title": "日付", "description": "業務上の日付。", "type": "string", "format": "date", "example": "2026-01-01"},
        "dateTime": {"title": "日時", "description": "業務イベント発生日時。", "type": "string", "format": "date-time", "example": "2026-01-01T00:00:00+09:00"},
        "link": {
            "title": "ハイパーメディアリンク",
            "description": "ALPS遷移に対応するリンク。hrefはURI参照。",
            "type": "object", "required": ["href"], "additionalProperties": False,
            "properties": {
                "href": {"$ref": "#/$defs/uriReference"},
                "rel": {"type": "string", "minLength": 1, "maxLength": 96},
                "method": {"type": "string", "enum": ["get", "post", "put", "patch", "delete", "GET", "POST", "PUT", "PATCH", "DELETE"]},
            },
        },
    }


def scalar_schema(name: str, existing: dict[str, Any], file_name: str, schema_title: str, alps: dict[str, dict[str, Any]], stats: dict[str, Stat]) -> dict[str, Any]:
    allow_null = nullable(existing, name)
    lower = name.lower()
    title = contextual_title(name, schema_title, alps)
    description = description_for(name, schema_title, alps, stats)

    # Direct semantic refs.
    if name in {"productCode", "newProductCode"}:
        s = ref("productCode", allow_null)
    elif name in {"email", "originalEmail", "dummyEmail", "shopEmail01"} or lower.endswith("email"):
        s = ref("email", allow_null)
    elif name == "postalCode":
        s = ref("postalCode", allow_null)
    elif name == "phoneNumber":
        s = ref("phoneNumber", allow_null)
    elif name == "pref":
        s = ref("pref", allow_null)
    elif name in MONEY_NAMES:
        s = ref("price", allow_null)
    elif name == "quantity":
        s = ref("quantity", allow_null)
    elif name in {"requestedQuantity", "adjustedQuantity"}:
        s = ref("nonNegativeInteger", allow_null)
    elif name == "productStatus":
        s = ref("productStatus", allow_null)
    elif name == "customerStatus":
        s = ref("customerStatus", allow_null)
    elif name in {"orderStatus", "previousStatus"}:
        s = ref("orderStatus", allow_null)
    elif name == "cartKey":
        s = ref("cartKey", allow_null)
    elif name == "csrfToken":
        s = ref("csrfToken", allow_null)
    elif name in {"href", "returnTo", "pageUrl", "url", "imagePath", "mainImage", "mainListImage"} or lower.endswith("url"):
        s = ref("uriReference", allow_null)
    elif name == "transitionId" or re.match(r"^(go|do)[A-Z]", name):
        s = ref("transitionId", allow_null)
    elif name in BOOL_NAMES or lower.startswith(("is", "has", "can", "was", "already")):
        s = {"type": "boolean"}
        s = with_null(s, allow_null)
    elif name in INTEGER_NAMES or lower.endswith(("count", "id", "ids", "no", "number")) and name not in {"orderNo", "phoneNumber", "postalCode"}:
        s = {"type": "integer", "minimum": 0, "maximum": 2147483647}
        if name in {"paymentMethodId", "saleTypeId", "authority", "roundingType"}:
            s["minimum"] = 1
            s["maximum"] = 99
        if name.endswith("Id") and name not in {"customerId"}:
            s["minimum"] = 0
        s = with_null(s, allow_null)
    elif name in {"orderNo"}:
        s = {"type": "string", "minLength": 1, "maxLength": 64, "pattern": "^[A-Za-z0-9._:-]+$"}
        s = with_null(s, allow_null)
    elif name in {"customerId", "preOrderId", "resetKey", "ticketId", "secretKey", "deviceToken", "authKey", "loginId", "pluginCode", "templateId", "pageId", "blockId", "categoryId", "classNameId", "classCategoryId", "deliveryId", "paymentId", "memberId", "newsId", "tagId"}:
        s = {"type": "string", "minLength": 1, "maxLength": 128, "pattern": "^[A-Za-z0-9._:@-]+$"}
        s = with_null(s, allow_null)
    elif name in {"birth", "applyDate", "holiday"}:
        # holiday in admin calendar may be a date string or 0/1-ish input in legacy forms.
        if name == "holiday":
            s = {"type": ["string", "integer"], "maxLength": 32}
            s = with_null(s, allow_null)
        else:
            s = ref("date", allow_null)
    elif name in {"timestamp", "createDate", "updateDate", "orderDate", "paymentDate", "shippingDate"} or lower.endswith("date"):
        s = ref("dateTime", allow_null)
    elif name == "method":
        s = {"type": "string", "enum": ["get", "post", "put", "patch", "delete", "GET", "POST", "PUT", "PATCH", "DELETE"]}
        s = with_null(s, allow_null)
    elif name == "operation":
        vals = ["add", "update", "delete", "remove", "clear", "append", "checkout"]
        s = {"type": "string", "minLength": 1, "maxLength": 64, "examples": vals[:3]}
        s = with_null(s, allow_null)
    elif name in {"csv", "pdf", "css", "js", "log"}:
        max_len = 5_000_000 if name in {"pdf", "csv"} else 1_000_000
        s = {"type": "string", "minLength": 0, "maxLength": max_len}
        if name == "pdf":
            s["$comment"] = "PDFバイナリはResourceでUTF-8正規化してからJSON Schema検査する。PDF内部構造はOrderPdfCompatibility境界の責務。"
        if name == "csv":
            s["$comment"] = "CSV列の業務妥当性はCSV互換サービスで検査する。ここではJSON境界上の文字列サイズを契約する。"
        s = with_null(s, allow_null)
    else:
        # String is the safest semantic default for names/labels/messages/textual form values.
        max_len = max_length_from_observation(name, stats.get(name), fallback=255)
        min_len = 0 if allow_null or name in OPTIONAL_BY_NAME else 1
        s = {"type": "string", "minLength": min_len, "maxLength": max_len}
        if name in {"kana01", "kana02"}:
            s["pattern"] = "^[ァ-ヶー　 ]*$"
        if name in {"name01", "name02"}:
            s["maxLength"] = max(s["maxLength"], 80)
        if name in {"password", "currentPassword", "changePasswordFirst", "changePasswordSecond"}:
            s["minLength"] = 8
            s["maxLength"] = 128
        s = with_null(s, allow_null)

    # Attach contextual title/description to non-$ref wrappers and to wrappers as siblings.
    s.setdefault("title", title)
    s.setdefault("description", description)
    if "default" in existing:
        s["default"] = existing["default"]
    ex = example_for(name, stats.get(name), s)
    if ex is not None and "example" not in s:
        s["example"] = ex
    return s


def max_length_from_observation(name: str, st: Stat | None, fallback: int) -> int:
    if name in {"description", "descriptionDetail", "newsDescription", "freeArea", "shopMessage", "message", "contents", "contactContents"}:
        fallback = 2000
    if name in {"searchWord", "note", "productNote"}:
        fallback = 1000
    if name in {"nameKeyword", "emailKeyword", "paymentMethodName", "deliveryName"}:
        fallback = 255
    if name in {"fileName", "blockFileName", "pageFileName", "templateId"}:
        fallback = 255
    if not st or not st.string_lengths:
        return fallback
    observed = max(st.string_lengths)
    # Semantic-Ex: observed max × 1.5〜2, but keep practical ceilings.
    proposed = max(fallback if observed > fallback else 0, math.ceil(observed * 2))
    if observed <= 16:
        proposed = max(proposed, 32)
    elif observed <= 64:
        proposed = max(proposed, 128)
    else:
        proposed = max(proposed, min(4000, math.ceil(observed * 1.5)))
    return min(max(proposed, 1), 5_000_000)


def example_for(name: str, st: Stat | None, schema: dict[str, Any]) -> Any | None:
    if name == "csrfToken": return "fake-csrf-token-bemart-2026"
    if name == "productCode": return "sample-001"
    if name == "email": return "alice@example.com"
    if name == "postalCode": return "1500001"
    if name == "phoneNumber": return "0312345678"
    if name == "cartKey": return "session-prefix-1_1"
    if name == "transitionId": return "doAddCartItem"
    if st and st.values:
        v = st.values.most_common(1)[0][0]
        t = schema.get("type")
        if t == "integer" or (isinstance(t, list) and "integer" in t and v.isdigit()):
            try: return int(v)
            except ValueError: pass
        if t == "boolean" and v in {"true", "false"}:
            return v == "true"
        if v not in {"", "None", "null"}:
            return v
    return None


def typed_extension_comment(name: str) -> str:
    return f"`{name}` は表示/管理画面ごとに追加列が増える可能性があるため、既知propertyを契約しつつ未知列は後続のSemantic-Ex観察で昇格する。"


def object_item(title: str, description: str, properties: dict[str, Any]) -> dict[str, Any]:
    return {
        "type": "object",
        "title": title,
        "description": description,
        "properties": properties,
        "additionalProperties": False,
        "$comment": typed_extension_comment(title),
    }


def collection_item_schema(name: str, file_name: str, schema_title: str) -> dict[str, Any] | None:
    """Known collection shapes for schemas whose original item shape was empty.

    These are intentionally conservative: they document stable fields observed
    in Fake/Resource contexts, while not using additionalProperties:true.
    """
    str_short = {"type": ["string", "null"], "minLength": 0, "maxLength": 255}
    int_count = {"type": ["integer", "null"], "minimum": 0, "maximum": 2147483647}
    money = {"type": ["integer", "null"], "minimum": 0, "maximum": 999999999}
    opaque = {"type": ["string", "null"], "minLength": 0, "maxLength": 128, "pattern": "^[A-Za-z0-9._:@/-]*$"}

    if name in {"favorites", "products", "recentOrders"} and "order" not in name.lower():
        return object_item("商品概要", f"{schema_title} の商品概要行。商品コード、表示名、価格、画像を一覧表示に使う。", {
            "productCode": {"type": ["string", "null"], "minLength": 0, "maxLength": 64, "pattern": "^[A-Za-z0-9._-]*$"},
            "name": {"type": ["string", "null"], "title": "商品名", "minLength": 0, "maxLength": 255},
            "productName": {"type": ["string", "null"], "title": "商品名", "minLength": 0, "maxLength": 255},
            "price02": money,
            "mainImage": {"type": ["string", "null"], "format": "uri-reference", "minLength": 0, "maxLength": 2048},
        })
    if name in {"orders", "recentOrders"}:
        return object_item("注文概要", f"{schema_title} の注文概要行。注文番号、状態、日時、支払合計を一覧表示に使う。", {
            "orderNo": {"type": ["string", "null"], "minLength": 0, "maxLength": 64},
            "orderStatus": {"type": ["integer", "null"], "minimum": 1, "maximum": 9},
            "orderDate": {"type": ["string", "null"], "minLength": 0, "maxLength": 32},
            "paymentTotal": money,
            "total": money,
            "itemCount": int_count,
        })
    if name in {"addresses"}:
        return object_item("住所概要", f"{schema_title} の住所行。配送先・会員住所録で共通に使う宛名と所在地。", {
            "addressId": opaque,
            "shippingAddressId": int_count,
            "name01": str_short, "name02": str_short,
            "postalCode": {"type": ["string", "null"], "pattern": "^\\d{3}-?\\d{4}$"},
            "pref": {"type": ["integer", "null"], "minimum": 0, "maximum": 47},
            "addr01": str_short, "addr02": str_short,
            "phoneNumber": {"type": ["string", "null"], "minLength": 0, "maxLength": 13},
        })
    if name in {"cartItems", "items"} and ("cart" in file_name or "shopping" in file_name):
        return object_item("カート明細", f"{schema_title} のカート明細。商品、数量、単価、カートキーを購入フローで使う。", {
            "productCode": {"type": ["string", "null"], "minLength": 0, "maxLength": 64},
            "productId": int_count,
            "productClassId": int_count,
            "productName": str_short,
            "quantity": {"type": ["integer", "null"], "minimum": 0, "maximum": 999},
            "price": money, "price02": money, "totalPrice": money,
            "cartKey": {"type": ["string", "null"], "minLength": 0, "maxLength": 128},
        })
    if name in {"classes"}:
        return object_item("商品規格", f"{schema_title} の商品規格行。規格名、規格分類、在庫、販売価格を表す。", {
            "classNameId": opaque, "classCategoryId": opaque, "className": str_short, "classCategoryName": str_short,
            "productCode": {"type": ["string", "null"], "minLength": 0, "maxLength": 64},
            "stock": int_count, "price02": money,
        })
    if name in {"payments"}:
        return object_item("支払方法", f"{schema_title} の支払方法行。管理画面で表示・編集する決済マスタ。", {
            "paymentId": {"type": ["string", "integer", "null"], "minLength": 0, "maxLength": 128},
            "paymentMethodName": str_short, "charge": money, "sortNo": int_count,
        })
    if name in {"deliveries"}:
        return object_item("配送方法", f"{schema_title} の配送方法行。配送マスタの表示名、送料、表示順を表す。", {
            "deliveryId": opaque, "deliveryName": str_short, "deliveryFee": money, "sortNo": int_count,
        })
    if name in {"taxRules"}:
        return object_item("税ルール", f"{schema_title} の税ルール行。税率と適用範囲を管理する。", {
            "taxRuleId": opaque, "taxRate": {"type": ["number", "null"], "minimum": 0, "maximum": 100},
            "applyDate": {"type": ["string", "null"], "minLength": 0, "maxLength": 32},
        })
    if name in {"calendars"}:
        return object_item("カレンダー行", f"{schema_title} の営業日/休日カレンダー行。日付、休日フラグ、表示状態を表す。", {
            "id": {"type": ["string", "integer", "null"], "minLength": 0, "maxLength": 128},
            "calendarId": {"type": ["string", "integer", "null"], "minLength": 0, "maxLength": 128},
            "holiday": {"type": ["string", "integer", "null"], "minLength": 0, "maxLength": 32},
            "title": str_short, "hasError": {"type": ["boolean", "null"]},
        })
    if name in {"news"}:
        return object_item("ニュース行", f"{schema_title} のニュース行。公開日、タイトル、URL、本文概要を表す。", {
            "newsId": opaque, "newsTitle": str_short, "newsUrl": {"type": ["string", "null"], "minLength": 0, "maxLength": 2048},
            "publishDate": {"type": ["string", "null"], "minLength": 0, "maxLength": 32},
        })
    if name in {"pages", "blocks", "members", "templates", "plugins", "mailTemplates"}:
        return object_item("管理行", f"{schema_title} の管理一覧行。ID、名称、表示順、状態を中心に契約する。", {
            "id": opaque, "pageId": opaque, "blockId": opaque, "memberId": int_count, "adminId": opaque,
            "templateId": opaque, "pluginCode": str_short, "name": str_short, "title": str_short,
            "sortNo": int_count, "enabled": {"type": ["boolean", "null"]}, "visible": {"type": ["boolean", "null"]},
        })
    if name in {"orderItems"}:
        return object_item("受注明細", f"{schema_title} の受注明細行。商品、数量、単価、税額を表す。", {
            "productCode": {"type": ["string", "null"], "minLength": 0, "maxLength": 64},
            "productName": str_short, "quantity": int_count, "price": money, "unitPrice": money, "tax": money,
        })
    if name in {"shippings", "allocations"}:
        return object_item("配送割当", f"{schema_title} の配送/割当行。配送先とカート明細の割当数を表す。", {
            "shippingAddressId": int_count, "addressId": opaque, "cartKey": {"type": ["string", "null"], "minLength": 0, "maxLength": 128},
            "productCode": {"type": ["string", "null"], "minLength": 0, "maxLength": 64},
            "quantity": int_count,
        })
    if name in {"mailHistories"}:
        return object_item("メール履歴", f"{schema_title} のメール送信履歴行。件名、送信日時、宛先を表す。", {
            "subject": str_short, "sendDate": {"type": ["string", "null"], "minLength": 0, "maxLength": 32},
            "email": {"type": ["string", "null"], "format": "email", "minLength": 0, "maxLength": 254},
        })
    if name in {"recommendedPlugins"}:
        return {
            "type": "object",
            "title": "推奨プラグイン",
            "description": f"{schema_title} の推奨プラグイン行。外部プラグインカタログ由来のため追加列は許容しつつ、表示に必要な安定列を契約する。",
            "properties": {
                "pluginCode": {"type": ["string", "null"], "title": "プラグインコード", "minLength": 0, "maxLength": 128, "pattern": "^[A-Za-z0-9._:@/+-]*$"},
                "name": {"type": ["string", "null"], "title": "プラグイン名", "minLength": 0, "maxLength": 255},
                "version": {"type": ["string", "null"], "title": "プラグインバージョン", "minLength": 0, "maxLength": 64},
                "enabled": {"type": ["boolean", "null"], "title": "有効状態"},
            },
            "$comment": "外部プラグインカタログは店舗環境で列が増えるため、既知表示列を契約し追加列はカタログ境界として許容する。",
        }
    if name in {"orderStatuses"}:
        return {
            "type": "object",
            "title": "注文ステータス",
            "description": f"{schema_title} の注文ステータス行。管理ダッシュボードとステータス管理で共通に使う状態ID、名称、色、件数を表す。",
            "properties": {
                "name": {"type": ["string", "null"], "title": "注文ステータス名", "minLength": 0, "maxLength": 255},
                "color": {"type": ["string", "null"], "title": "表示色", "minLength": 0, "maxLength": 32, "pattern": "^$|^#[0-9A-Fa-f]{6}$|^[A-Za-z0-9_-]+$"},
                "count": {"type": ["integer", "null"], "title": "注文件数", "minimum": 0, "maximum": 2147483647},
            },
            "$comment": "ダッシュボードでは集計列、ステータス管理では表示順キーが加わるため追加列を許容する。安定列はschema propertyとして明示する。",
        }
    if name in {"sections"}:
        return object_item("静的コンテンツセクション", f"{schema_title} の静的本文セクション。見出しと本文を表す。", {
            "title": str_short, "body": {"type": ["string", "null"], "minLength": 0, "maxLength": 10000},
            "content": {"type": ["string", "null"], "minLength": 0, "maxLength": 10000},
        })
    return None


def augment_array_item_properties(collection_name: str, props: dict[str, Any]) -> None:
    """Add stable runtime fields observed after fixing collection closure.

    These additions are schema work, not PHP changes: Resource responses may
    expose fields that the original generated item shape missed.  When
    additionalProperties is false we must promote such fields into the
    contract with explicit meaning.
    """
    money = {
        "type": ["integer", "null"],
        "title": "単価",
        "description": "商品・お気に入り・カート明細で表示する日本円の単価。価格表示と小計計算に使う。",
        "minimum": 0,
        "maximum": 999999999,
        "example": 1200,
    }
    image_file = {
        "type": ["string", "null"],
        "title": "画像ファイル名",
        "description": "商品画像としてテンプレートで参照するファイル名または相対パス。画像URIとは別にEC-CUBE互換の表示値として扱う。",
        "minLength": 0,
        "maxLength": 255,
        "pattern": "^[^\\r\\n]*$",
        "example": "sample.jpg",
    }
    if collection_name in {"favorites", "products", "cartItems"}:
        props.setdefault("unitPrice", money)
        props.setdefault("fileName", image_file)


def array_item_schema(name: str, existing: dict[str, Any], file_name: str, schema_title: str, alps: dict[str, dict[str, Any]], stats: dict[str, Stat]) -> dict[str, Any]:
    item_existing = existing.get("items") if isinstance(existing.get("items"), dict) else {}
    if item_existing and item_existing.get("properties"):
        return transform_node(item_existing, name + "Item", file_name, schema_title, alps, stats, in_array=True)
    scalar_arrays = {
        "categoryNames": {"type": "string", "title": "カテゴリ名", "minLength": 0, "maxLength": 128},
        "tagNames": {"type": "string", "title": "タグ名", "minLength": 0, "maxLength": 128},
        "classNames": {"type": "string", "title": "規格名", "minLength": 0, "maxLength": 128},
        "productCodes": ref("productCode"),
        "skippedProductCodes": ref("productCode"),
        "orderNos": {"type": "string", "title": "注文番号", "minLength": 1, "maxLength": 64, "pattern": "^[A-Za-z0-9._:-]+$"},
        "cartKeys": ref("cartKey"),
        "pages": {"type": "integer", "title": "ページ番号", "minimum": 1, "maximum": 10000},
        "fields": {"type": "string", "title": "表示フィールド", "minLength": 0, "maxLength": 255},
        "errors": {"type": "string", "title": "エラーメッセージ", "minLength": 0, "maxLength": 1000},
    }
    if name in scalar_arrays:
        return scalar_arrays[name]
    shaped = collection_item_schema(name, file_name, schema_title)
    if shaped is not None:
        return shaped
    if name in {"allocations", "orderItems", "rows", "columns", "orderStatuses"}:
        item_title = collection_item_title(name, domain_from_file(file_name))
        dynamic_props: dict[str, Any] = {}
        if name == "columns":
            dynamic_props = {
                "name": {"type": ["string", "null"], "title": "CSV列名", "minLength": 0, "maxLength": 255},
                "label": {"type": ["string", "null"], "title": "CSV列表示名", "minLength": 0, "maxLength": 255},
                "value": {"type": ["string", "null"], "title": "CSV列値", "minLength": 0, "maxLength": 255},
                "enabled": {"type": ["boolean", "null"], "title": "出力対象フラグ"},
            }
        elif name == "rows":
            dynamic_props = {
                "name": {"type": ["string", "null"], "title": "行名称", "minLength": 0, "maxLength": 255},
                "value": {"type": ["string", "integer", "boolean", "null"], "title": "行値", "maxLength": 255, "$comment": "マスタ種別ごとに値型が異なるため、表示/保存境界の互換値として扱う。"},
                "sortNo": {"type": ["integer", "null"], "title": "表示順", "minimum": 0, "maximum": 2147483647},
                "enabled": {"type": ["boolean", "null"], "title": "有効状態"},
            }
        return {
            "type": "object",
            "title": item_title,
            "description": f"{schema_title} の {contextual_title(name, schema_title, alps)} 要素。具体的な列はALPS/Fake観察に基づく動的行として扱う。",
            **({"properties": dynamic_props} if dynamic_props else {}),
            "$comment": "行/明細配列は画面・マスタ種別ごとに列が変わるため、配列要素はobjectで契約し詳細は該当サービス境界で検査する。追加列は不透明構造として許容する。",
        }
    # Collections of domain objects whose current schema did not expose item properties.
    if name.endswith("s") or name in {"items", "favorites", "orders", "addresses", "cartItems", "classes", "payments", "plugins", "templates", "calendars", "news"}:
        item_title = collection_item_title(name, domain_from_file(file_name))
        return {
            "type": "object",
            "title": item_title,
            "description": f"{schema_title} に含まれる {contextual_title(name, schema_title, alps)} の要素。Fake観察ではコレクション要素として扱われる。",
            "$comment": "既存Resource schemaに要素プロパティが露出していないコレクション。今後のFake観察追加で詳細化する。追加キーは互換境界として許容する。",
        }
    return {"type": "string", "title": contextual_title(name + "Item", schema_title, alps), "minLength": 0, "maxLength": 255}


def transform_node(existing: dict[str, Any], name: str, file_name: str, schema_title: str, alps: dict[str, dict[str, Any]], stats: dict[str, Stat], in_array: bool = False) -> dict[str, Any]:
    if name in {"form", "searchForm"}:
        return {
            "type": ["object", "array", "null"] if nullable(existing, name) else ["object", "array"],
            "title": contextual_title(name, schema_title, alps),
            "description": description_for(name, schema_title, alps, stats),
            "$comment": "Aura/WebForm由来の不透明フォーム表現。Resource境界ではフォームの存在とコンテキストだけを契約し、内部構造はフレームワーク境界に委ねるため追加キー制約を置かない。",
        }
    t = existing.get("type")
    types = set(t if isinstance(t, list) else [t])
    if "array" in types:
        s = {
            "type": "array",
            "title": contextual_title(name, schema_title, alps),
            "description": description_for(name, schema_title, alps, stats),
            "items": array_item_schema(name, existing, file_name, schema_title, alps, stats),
        }
        if name not in {"fields", "errors"}:
            s["minItems"] = 0
        if "default" in existing:
            s["default"] = existing["default"]
        return with_null(s, nullable(existing, name))
    if "object" in types or existing.get("properties"):
        props = existing.get("properties") if isinstance(existing.get("properties"), dict) else {}
        if props:
            new_props = {k: transform_node(v if isinstance(v, dict) else {}, k, file_name, schema_title, alps, stats) for k, v in props.items()}
            collection_name = name[:-4] if name.endswith("Item") else name
            if in_array:
                augment_array_item_properties(collection_name, new_props)
            req = [] if in_array else [k for k, v in new_props.items() if should_require(k, v, file_name, schema_title)]
            s = {
                "type": "object",
                "title": contextual_title(name, schema_title, alps),
                "description": description_for(name, schema_title, alps, stats),
                "properties": new_props,
            }
            if in_array:
                if collection_name in DYNAMIC_ROW_NAMES:
                    s["$comment"] = "配列要素はマスタ/CSV/option mapの動的行であり、列集合が対象種別により変わるため固定shape化しない。既知propertyは契約し、追加列は該当サービス境界で扱う。"
                else:
                    s["additionalProperties"] = False
                    s["$comment"] = "配列要素はFake/Resourceで観察された既知propertyに固定する。新しい列が必要になった場合はSemantic-Ex観察に追加してschemaを更新する。"
            else:
                s["additionalProperties"] = False
            if req:
                s["required"] = req
            return with_null(s, nullable(existing, name))
        if name in DYNAMIC_OBJECT_NAMES or name in DYNAMIC_MAP_NAMES:
            return {
                "type": ["object", "null"] if nullable(existing, name) else "object",
                "title": contextual_title(name, schema_title, alps),
                "description": description_for(name, schema_title, alps, stats),
                "$comment": "フレームワーク由来または動的mapのため、JSON境界ではobjectであることと意味を契約し、内部キーは別境界で扱う。追加キーは不透明構造として許容する。",
            }
        return scalar_schema(name, existing, file_name, schema_title, alps, stats)
    return scalar_schema(name, existing, file_name, schema_title, alps, stats)


def should_require(name: str, schema: dict[str, Any], file_name: str, schema_title: str) -> bool:
    if name in OPTIONAL_BY_NAME:
        return False
    if "default" in schema:
        return False
    if file_name.endswith(".param.json"):
        # Search/list filters and navigation defaults are optional inputs.
        if name in {"limit", "offset", "pageno", "disp_number", "orderby", "name", "nameKeyword", "emailKeyword", "returnTo", "routeName"}:
            return False
    if name in DYNAMIC_OBJECT_NAMES and name not in {"customer", "order", "product"}:
        return False
    return True


def enhance_schema(path: Path, alps: dict[str, dict[str, Any]], stats: dict[str, Stat]) -> None:
    data = load_json(path)
    title = data.get("title", path.stem)
    props = data.get("properties", {}) if isinstance(data.get("properties"), dict) else {}
    new_props = {k: transform_node(v if isinstance(v, dict) else {}, k, path.name, str(title), alps, stats) for k, v in props.items()}
    req = [k for k, v in new_props.items() if should_require(k, v, path.name, str(title))]
    new = {
        "$schema": data.get("$schema", "http://json-schema.org/draft-07/schema#"),
        "$id": data.get("$id", path.name),
        "title": title,
        "description": schema_description(path, str(title)),
        "type": "object",
        "properties": new_props,
        "additionalProperties": False,
        "$defs": common_defs(alps),
        "$comment": "Generated by bin/semantic-ex-json-schema.py from ALPS meaning, be/var/fake observation, and Resource schema shape.",
    }
    if req:
        new["required"] = req
    expand_nullable_refs(new)
    semantic_runtime_adjustments(new, path)
    dump_json(path, new)



def expand_nullable_refs(schema: dict[str, Any]) -> None:
    """ApiDoc requires each property to expose type or $ref directly.

    JSON Schema nullable refs are first produced as anyOf([$ref, null]) for
    validation semantics. This pass expands those refs inline and adds null to
    the concrete type, preserving title/description/example from the contextual
    property wrapper.
    """
    defs = schema.get("$defs", {}) if isinstance(schema.get("$defs"), dict) else {}

    def resolve(ref_value: str) -> dict[str, Any] | None:
        prefix = "#/$defs/"
        if not ref_value.startswith(prefix):
            return None
        target = defs.get(ref_value[len(prefix):])
        return dict(target) if isinstance(target, dict) else None

    def add_null_type(node: dict[str, Any]) -> None:
        t = node.get("type")
        if isinstance(t, str):
            node["type"] = [t, "null"]
        elif isinstance(t, list) and "null" not in t:
            node["type"] = [*t, "null"]

    def walk(node: Any) -> Any:
        if isinstance(node, dict):
            any_of = node.get("anyOf")
            if isinstance(any_of, list) and len(any_of) == 2:
                ref_part = next((x for x in any_of if isinstance(x, dict) and isinstance(x.get("$ref"), str)), None)
                null_part = next((x for x in any_of if isinstance(x, dict) and x.get("type") == "null"), None)
                if ref_part is not None and null_part is not None:
                    expanded = resolve(ref_part["$ref"])
                    if expanded is not None:
                        for key in ("title", "description", "example", "examples", "default", "$comment"):
                            if key in node:
                                expanded[key] = node[key]
                        add_null_type(expanded)
                        node.clear()
                        node.update(expanded)
            if isinstance(node.get("$ref"), str):
                expanded = resolve(node["$ref"])
                if expanded is not None:
                    for key in ("title", "description", "example", "examples", "default", "$comment"):
                        if key in node:
                            expanded[key] = node[key]
                    node.clear()
                    node.update(expanded)
            for key, value in list(node.items()):
                node[key] = walk(value)
        elif isinstance(node, list):
            return [walk(v) for v in node]
        return node

    walk(schema)

def schema_description(path: Path, title: str) -> str:
    kind = "request parameter" if path.parent.name == "json_validate" else "response body"
    return f"{title} の {kind} schema。ALPSの意味、be/var/fakeの観察値、Resource境界の実形状から導いたSemantic-Ex制約。"


def observation_sentence(name: str, st: Stat, short: bool = False) -> str:
    parts = []
    if st.string_lengths:
        parts.append(f"Fake観察文字長 {min(st.string_lengths)}〜{max(st.string_lengths)}")
    if st.numbers:
        nums = [n for n in st.numbers if isinstance(n, (int, float))]
        if nums:
            parts.append(f"Fake観察数値 {min(nums):g}〜{max(nums):g}")
    if st.values and len(st.values) <= 12:
        vals = ", ".join(repr(v) for v, _ in st.values.most_common(8))
        parts.append(f"観察値 {vals}")
    if st.nulls:
        parts.append(f"null {st.nulls}/{st.seen}")
    if not parts:
        return ""
    return ("; ".join(parts) + "。") if not short else ("; ".join(parts) + "。")



def semantic_runtime_adjustments(schema: dict[str, Any], path: Path) -> None:
    """Keep schemas meaningful while matching current Resource boundaries.

    These are not no-error escape hatches: each rule encodes a discovered
    BeMart context, e.g. EC-CUBE IDs are often string keys, ALPS link object
    properties hold URI references, and request schemas must allow invalid
    business values to reach the Resource/Semantic layer that returns 400.
    """
    is_request = path.parent.name == "json_validate" or path.name.endswith(".param.json")
    defs = schema.get("$defs", {}) if isinstance(schema.get("$defs"), dict) else {}
    runtime_alps = load_alps()

    def nullable_type(node: dict[str, Any]) -> bool:
        t = node.get("type")
        return t == "null" or (isinstance(t, list) and "null" in t)

    def set_type(node: dict[str, Any], typ: str | list[str], keep_null: bool = True) -> None:
        allow_null = keep_null and nullable_type(node)
        if isinstance(typ, list):
            vals = list(dict.fromkeys(typ + (["null"] if allow_null and "null" not in typ else [])))
            node["type"] = vals
        else:
            node["type"] = [typ, "null"] if allow_null else typ
        node.pop("$ref", None)
        node.pop("anyOf", None)

    def make_identifier(node: dict[str, Any], name: str) -> None:
        # Split ID contexts instead of defaulting to string|integer|null.
        if name in MIXED_ID_NAMES:
            set_type(node, ["string", "integer"])
            node.pop("minimum", None); node.pop("maximum", None); node.pop("pattern", None)
            node["minLength"] = 0
            node["maxLength"] = max(int(node.get("maxLength", 0) or 0), 128)
            node["title"] = node.get("title") or "互換境界ID"
            node["$comment"] = "Fake文字列IDとEC-CUBE整数IDの両方が観察されるため、この境界だけmixedBoundaryIdとして扱う。"
            return
        if name in DB_ID_NAMES:
            set_type(node, ["integer", "null"] if nullable_type(node) else "integer")
            node["minimum"] = 0
            node["maximum"] = 2147483647
            node.pop("minLength", None); node.pop("maxLength", None); node.pop("pattern", None)
            node["$comment"] = node.get("$comment") or "EC-CUBE側の採番IDとして扱う。"
            return
        set_type(node, ["string", "null"] if nullable_type(node) else "string")
        node.pop("minimum", None); node.pop("maximum", None)
        node["minLength"] = 0
        node["maxLength"] = max(int(node.get("maxLength", 0) or 0), 128)
        node["pattern"] = "^[A-Za-z0-9._:@/-]*$"
        node["$comment"] = node.get("$comment") or "BeMart/Fake境界で観察される不透明な文字列ID。DB採番値としての数値演算には使わない。"

    def loosen_request(node: dict[str, Any], name: str) -> None:
        if not is_request:
            return
        # Request schemas document the semantic contract but must not preempt
        # Resource/Semantic validators that intentionally return 400/403/404.
        for k in ("format", "pattern", "enum", "minimum", "exclusiveMinimum", "maximum", "exclusiveMaximum"):
            node.pop(k, None)
        if node.get("minLength", 0) > 0:
            node["minLength"] = 0
        node["$comment"] = (node.get("$comment", "") + " Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation.").strip()

    def fix_link_map(node: dict[str, Any]) -> None:
        props = node.get("properties")
        if not isinstance(props, dict):
            return
        for rel, child in props.items():
            if isinstance(child, dict) and re.match(r"^(go|do)[A-Z]", rel):
                child.clear()
                child.update({
                    "$ref": "#/$defs/uriReference",
                    "title": contextual_title(rel, str(schema.get("title", "")), runtime_alps),
                    "description": f"ALPS `{rel}` 遷移のリンク先URI。property名がrel、値がhrefを表す。",
                })

    def extra_property(name: str) -> dict[str, Any]:
        title = camel_words(name)
        base: dict[str, Any] = {"title": title, "description": f"Resource実レスポンスで観察された `{name}`。ALPS/Fake文脈に基づく追加schema property。"}
        if name in {"changed", "alreadyDeleted"}:
            base.update({"type": "boolean", "example": True})
            if name == "alreadyDeleted":
                base.update({
                    "title": "既削除フラグ",
                    "description": "管理者削除操作で、対象が既に削除済みだったことを示す冪等処理フラグ。",
                })
        elif name in {"orderNos", "rows", "columns"}:
            base.update({"type": "array", "items": {"type": "object", "additionalProperties": True, "$comment": "動的行データ。具体列は該当サービス境界で扱う。"}, "minItems": 0})
            if name == "orderNos": base["items"] = {"type": "string", "minLength": 0, "maxLength": 64}
        elif name == "productCodes":
            base.update({
                "type": "array",
                "title": "取込商品コード一覧",
                "description": "商品CSV取込で処理対象になったSKU一覧。各要素は商品コード制約に従う。",
                "items": {"$ref": "#/$defs/productCode"},
                "minItems": 0,
            })
        elif name in {"subtotal", "deliveryFeeTotal", "charge", "discount", "tax", "total", "paymentTotal"}:
            titles = {
                "subtotal": "小計",
                "deliveryFeeTotal": "送料合計",
                "charge": "決済手数料",
                "discount": "値引き額",
                "tax": "税額",
                "total": "受注合計",
                "paymentTotal": "支払合計",
            }
            base.update({
                "type": ["integer", "null"],
                "title": titles[name],
                "description": f"受注作成レスポンスに含まれる{titles[name]}。日本円の非負整数金額として扱う。",
                "minimum": 0,
                "maximum": 999999999,
                "example": 0,
            })
        elif name == "addPoint":
            base.update({
                "type": ["integer", "null"],
                "title": "付与ポイント",
                "description": "受注作成時に見積もられる会員付与ポイント。ポイント未使用/対象外では0。",
                "minimum": 0,
                "maximum": 2147483647,
                "example": 0,
            })
        elif name == "itemCount":
            base.update({
                "type": ["integer", "null"],
                "title": "受注明細件数",
                "description": "受注に含まれる商品明細の件数。空の受注は作成しないため通常は1以上だが、境界互換として0も許容する。",
                "minimum": 0,
                "maximum": 10000,
                "example": 1,
            })
        elif name == "orderStatus":
            base.update({
                "type": ["integer", "null"],
                "title": "注文ステータス",
                "description": "受注作成後のEC-CUBE注文状態ID。ALPSのorderStatusに対応する。",
                "minimum": 1,
                "maximum": 9,
                "example": 1,
            })
        elif name == "paymentMethodId":
            base.update({
                "type": ["integer", "null"],
                "title": "支払方法ID",
                "description": "受注に紐づく支払方法マスタID。未選択/未確定境界ではnullを許容する。",
                "minimum": 0,
                "maximum": 2147483647,
                "example": 1,
            })
        elif name == "orderDate":
            base.update({
                "type": ["string", "null"],
                "title": "注文日時",
                "description": "受注作成日時。Fake/Resource境界では日付または日時文字列として返る。",
                "pattern": r"^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2}([+-]\d{2}:?\d{2}|Z)?)?$",
                "example": "2026-01-01 10:00:00",
            })
        elif name in {"pdf", "fileName", "value", "masterType", "selectedMaster", "newProductName"}:
            base.update({"type": "string", "minLength": 0, "maxLength": 5000000 if name == "pdf" else 255})
        elif name in {"price02", "stock", "size", "count", "csvType", "authority", "previousAuthority", "sortNo"}:
            base.update({"type": ["integer", "number", "null"] if name in {"price02"} else ["integer", "null"], "minimum": 0})
        elif name in {"transitionId"}:
            base.update({"type": "string", "minLength": 1, "maxLength": 96, "pattern": "^(go|do)[A-Z][A-Za-z0-9]*$"})
        elif name.endswith("Id") or name in {"loginId", "orderNo", "productCode", "newProductCode", "email"}:
            base.update({"type": ["string", "integer", "null"], "minLength": 0, "maxLength": 128})
            if name == "email": base.update({"type": ["string", "null"], "format": "email", "maxLength": 254})
        else:
            base.update({"type": ["string", "integer", "boolean", "null"], "maxLength": 255, "$comment": "追加観察property。型はResource互換のため複数許容し、title/descriptionで意味を固定する。"})
        return base

    extra_by_file = {
        "delete-admin-block-block.json": ["blockId"],
        "delete-admin-member.json": ["adminId", "loginId", "alreadyDeleted"],
        "delete-admin-page-page.json": ["pageId"],
        "delete-admin-template-template-list.json": ["transitionId", "templateId"],
        "get-admin-class-category-class-category-export.json": ["value"],
        "get-admin-class-name-class-name-export.json": ["value"],
        "get-admin-order-export-order-pdf.json": ["orderNo", "orderNos", "pdf", "size", "fileName"],
        "post-admin-authority-role.json": ["adminId", "loginId", "previousAuthority", "authority", "changed"],
        "post-admin-csv-config.json": ["csvType", "columns", "count"],
        "post-admin-customer-resend-activation-mail.json": ["customerId", "email"],
        "post-admin-product-csv.json": ["transitionId", "count", "productCodes"],
        "post-admin-product-copy.json": ["productCode", "newProductCode", "newProductName", "price02", "stock"],
        "post-admin-order-create.json": ["orderNo", "customerId", "paymentMethodId", "subtotal", "deliveryFeeTotal", "charge", "discount", "tax", "total", "paymentTotal", "addPoint", "itemCount", "orderStatus", "orderDate"],
        "post-admin-template-template-list.json": ["value"],
        "post-admin-two-factor-auth.json": ["transitionId", "loginId"],
        "post-mypage-change.json": ["customerId", "name01", "name02"],
        "put-admin-master-data.json": ["transitionId", "selectedMaster", "rows"],
        "put-admin-master-data-edit.json": ["transitionId", "masterType", "count"],
        "put-admin-sort-no-move.json": ["masterType", "rowId", "sortNo"],
        "put-admin-template-template-list.json": ["transitionId", "templateId"],
        "put-admin-two-factor-auth-set.json": ["transitionId", "loginId"],
    }
    if path.name in extra_by_file:
        props = schema.setdefault("properties", {})
        if isinstance(props, dict):
            for prop_name in extra_by_file[path.name]:
                props.setdefault(prop_name, extra_property(prop_name))

    def walk(node: Any, name: str = "", parent: str = "") -> None:
        if not isinstance(node, dict):
            return
        if parent == "links":
            if re.match(r"^(go|do)[A-Z]", name):
                node.clear(); node.update({
                    "$ref": "#/$defs/uriReference",
                    "title": contextual_title(name, str(schema.get("title", "")), runtime_alps),
                    "description": f"ALPS `{name}` 遷移のリンク先URI。property名がrel、値がhrefを表す。",
                })
        if parent in {"outputColumns", "notOutputColumns"}:
            set_type(node, "string"); node.pop("format", None); node.pop("pattern", None); node.pop("minimum", None); node.pop("maximum", None); node["minLength"] = 0; node["maxLength"] = 255
            node["$comment"] = "CSV設定の列ラベル。property名は業務フィールドだが値は出力列名の表示文字列。"
        if name in {"links", "_links"}:
            fix_link_map(node)
        if name in {"customerId", "adminId", "addressId", "loginId", "preOrderId", "categoryId", "deliveryId", "paymentId", "classNameId", "classCategoryId", "newsId", "pageId", "blockId", "layoutId", "templateId", "tagId", "taxRuleId", "rowId", "id", "ticketId", "shippingAddressId", "memberId"} or (name.endswith("Id") and name not in {"paymentMethodId", "saleTypeId", "transitionId"}):
            make_identifier(node, name)
        if name == "transitionId":
            set_type(node, "string")
            node["minLength"] = 2
            node["maxLength"] = 96
            node["pattern"] = "^(go|do)[A-Z][A-Za-z0-9]*$"
            node["title"] = "ALPS遷移ID"
            node["description"] = "このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。"
            node.setdefault("example", "doAddCartItem")
        if name in {"pluginCode"}:
            set_type(node, "string"); node["minLength"] = 0; node["maxLength"] = 128; node["pattern"] = "^[A-Za-z0-9._:@/+-]*$"
        if name in {"orderNo", "productCode", "newProductCode", "authKey"}:
            set_type(node, "string"); node["minLength"] = 0; node["maxLength"] = max(int(node.get("maxLength", 0) or 0), 64); node.pop("pattern", None)
        if name == "paymentMethodId":
            set_type(node, ["integer", "null"])
            node["minimum"] = 0
            node["maximum"] = 2147483647
            node["title"] = "支払方法ID"
            node["description"] = "受注に紐づく支払方法マスタID。Fake/EC-CUBE境界ではDB採番値として扱う。"
            node.pop("minLength", None); node.pop("maxLength", None); node.pop("pattern", None); node.pop("format", None)
        if name in {"productStatus", "price02", "charge", "salesToday", "salesYesterday", "salesThisMonth", "shopEmail01", "newsUrl", "pageUrl", "publishDate", "contactEmail"}:
            t = node.get("type")
            if isinstance(t, str): node["type"] = [t, "null"]
            elif isinstance(t, list) and "null" not in t: node["type"].append("null")
        if name in {"authority"}:
            node["minimum"] = 0; node["maximum"] = 9
        if name in {"subtotal", "deliveryFeeTotal", "charge", "discount", "tax", "total", "paymentTotal", "addPoint"}:
            set_type(node, ["integer", "null"])
            node["minimum"] = 0
            node["maximum"] = 999999999 if name != "addPoint" else 2147483647
            node.pop("minLength", None); node.pop("maxLength", None); node.pop("pattern", None); node.pop("format", None)
        if name in {"sex", "job", "work", "deviceType", "pageEditType", "previousAuthority", "csvType", "size", "accepted", "deleted", "imported"}:
            set_type(node, ["integer", "boolean"] if name in {"accepted", "deleted", "imported"} else "integer")
            node["minimum"] = 0
            node.pop("minLength", None); node.pop("maxLength", None); node.pop("pattern", None); node.pop("format", None)
        if name == "skipped":
            set_type(node, ["integer", "boolean", "null"])
            node["minimum"] = 0
            node.pop("minLength", None); node.pop("maxLength", None); node.pop("pattern", None); node.pop("format", None)
            node["title"] = "スキップ件数"
            node["description"] = "CSV取込で業務的に処理対象外となった行数。旧境界では真偽値も返るため互換的に許容する。"
            node["example"] = 0
            node["$comment"] = "CSV import response contextでは skipped はbooleanではなく件数を表す。旧Fake/Resource境界のboolean互換だけ残す。"
        if name == "productCodes":
            node["type"] = "array"
            node["title"] = "取込商品コード一覧"
            node["description"] = "商品CSV取込で処理対象になったSKU一覧。各要素は商品コード制約に従う。"
            node["items"] = dict(defs.get("productCode", {"type": "string", "minLength": 1, "maxLength": 64}))
            node["minItems"] = 0
            node.pop("minLength", None); node.pop("maxLength", None); node.pop("pattern", None); node.pop("format", None)
        if name == "value" and "export" in path.name:
            set_type(node, "string")
            node["title"] = "CSVエクスポート本文"
            node["description"] = "管理画面CSVエクスポートの本文。行数・列数に応じて255文字を超えるため、JSON境界ではCSV文字列の輸送サイズを契約する。"
            node["minLength"] = 0
            node["maxLength"] = 5_000_000
            node["$comment"] = "CSV列の意味検査はCSV互換サービスで扱い、ここではレスポンス本文としての文字列サイズを検査する。"
        if name == "name" and ("product" in path.name or parent in {"productsItem", "product"}):
            set_type(node, ["string", "null"] if nullable_type(node) else "string")
            node["title"] = "商品名"
            node["description"] = "フロント商品一覧/商品詳細で表示する商品名。Fakeの一覧表示名は短いが、管理画面で作成される実商品名は長い日本語名を許容する。"
            node["minLength"] = 0
            node["maxLength"] = max(int(node.get("maxLength", 0) or 0), 255)
        if name in {"deliveryName", "paymentMethodName"}:
            set_type(node, ["string", "null"] if nullable_type(node) else "string")
            node["minLength"] = 0
            node["maxLength"] = max(int(node.get("maxLength", 0) or 0), 255)
            if name == "deliveryName":
                node["title"] = "配送方法名"
                node["description"] = "管理画面で登録・更新する配送方法の表示名。実運用では店舗独自の長い名称を許容する。"
        if name == "itemCount":
            set_type(node, ["integer", "null"])
            node["minimum"] = 0
            node["maximum"] = 10000
            node["title"] = "受注明細件数"
            node["description"] = "受注に含まれる商品明細の件数。空の受注は作成しないため通常は1以上だが、境界互換として0も許容する。"
            node.setdefault("example", 1)
            node.pop("minLength", None); node.pop("maxLength", None); node.pop("pattern", None); node.pop("format", None)
        if name in {"alreadyDeleted", "visible", "enabled", "installed", "changed", "cleared", "success", "available"} and node.get("type") == "boolean":
            node.setdefault("example", False)
        if name in {"newsDescription", "descriptionDetail", "freeArea", "shopMessage", "contents", "contactContents"}:
            set_type(node, ["string", "null"] if nullable_type(node) else "string")
            node["minLength"] = 0
            node["maxLength"] = max(int(node.get("maxLength", 0) or 0), 2000)
        if name in {"searchWord", "note", "productNote"}:
            set_type(node, ["string", "null"] if nullable_type(node) else "string")
            node["minLength"] = 0
            node["maxLength"] = max(int(node.get("maxLength", 0) or 0), 1000)
        if name in {"nameKeyword", "emailKeyword", "paymentMethodName"}:
            set_type(node, ["string", "null"] if nullable_type(node) else "string")
            node["minLength"] = 0
            node["maxLength"] = max(int(node.get("maxLength", 0) or 0), 255)
        if name in {"stockFind", "blockDeletable", "displayOrderScreen"}:
            set_type(node, "boolean")
        if name in {"paymentDate", "orderDate", "applyDate", "timestamp", "createDate", "updateDate", "sendDate", "deliveryDate", "publishDate"} or name.endswith("Date"):
            set_type(node, "string"); node.pop("format", None)
            date_pattern = r"^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2}([+-]\d{2}:?\d{2}|Z)?)?$"
            if name in {"paymentDate", "shippingDate", "deliveryDate", "sendDate", "publishDate", "applyDate"}:
                date_pattern = r"^$|" + date_pattern[1:]
                node["$comment"] = "未入金・未発送・未公開など未確定日時はEC-CUBE境界で空文字として現れるため、日付/日時文字列に加えて空文字を許容する。"
            node["pattern"] = date_pattern
        if name == "taxRate":
            set_type(node, ["number", "null"])
            node["minimum"] = 0
            node["maximum"] = 100
            node["title"] = "税率"
            node["description"] = "税ルールに適用する税率。8.0や10.0のような小数表現を含む百分率。"
            node.pop("minLength", None); node.pop("maxLength", None); node.pop("pattern", None); node.pop("format", None)
        if name in {"disp_number", "pageno", "holiday"}:
            set_type(node, ["string", "integer"]); node.pop("minimum", None); node["minLength"] = 0; node["maxLength"] = 64
            node["$comment"] = node.get("$comment") or "EC-CUBE互換のフォーム/一覧境界で数値と文字列の両方が観察される。業務解釈はResource/Semantic層で行う。"
        if name == "trackingNumber" or (name.endswith("Key") and name not in {"cartKey"}):
            set_type(node, "string"); node.pop("minimum", None); node.pop("maximum", None); node["minLength"] = 0; node["maxLength"] = 128
            node["$comment"] = node.get("$comment") or "キー/追跡番号は照合用の不透明文字列で、数値演算対象ではない。"
        if name == "fileName":
            set_type(node, "string"); node["minLength"] = 1; node["maxLength"] = 255
        if parent in {"outputColumns", "notOutputColumns"}:
            set_type(node, "string"); node.pop("format", None); node.pop("pattern", None); node.pop("minimum", None); node.pop("maximum", None); node["minLength"] = 0; node["maxLength"] = 255
            node["$comment"] = "CSV設定の列ラベル。property名は業務フィールドだが値は出力列名の表示文字列。"
        if name in {"product", "payment", "delivery", "category", "order", "customer"}:
            t = node.get("type")
            vals = t if isinstance(t, list) else [t] if t else []
            for extra in ["object", "array", "null"]:
                if extra not in vals: vals.append(extra)
            node["type"] = vals
            node.setdefault("$comment", "単一詳細画面では未選択/初期表示に空配列、取得済み状態にobjectが現れる。不透明な詳細構造は既知propertyを優先し、追加キーは互換境界として許容する。")
        if is_request and (name in DB_ID_NAMES or name in MIXED_ID_NAMES):
            set_type(node, ["string", "integer", "null"])
            node.pop("minimum", None); node.pop("maximum", None); node.pop("pattern", None)
            node["minLength"] = 0
            node["maxLength"] = max(int(node.get("maxLength", 0) or 0), 128)
            node["$comment"] = f"{node.get('title', name)}は業務上IDだが、HTTPフォームでは文字列として届く。Resource/Semantic層の検証を通すためtransport schemaではstring|integerを許容する。"
        if is_request and name in {"email", "contactEmail", "shopEmail01"}:
            set_type(node, ["string", "null"]); node.pop("format", None); node["minLength"] = 0; node["maxLength"] = 254
        if is_request and name in {"productStatus", "price02", "charge", "quantity", "taxRate"}:
            if name == "taxRate":
                set_type(node, ["number", "string", "null"])
            else:
                set_type(node, ["integer", "string", "null"])
            node.pop("enum", None); node.pop("minimum", None); node.pop("maximum", None)
            node["$comment"] = f"{node.get('title', name)}は本来数値/列挙の業務値だが、HTTPフォームでは文字列として届く。Resource/Semantic層の400応答を奪わないためtransport schemaでは文字列入力を許容する。"
        loosen_request(node, name)
        typ = node.get("type")
        typ_set = set(typ if isinstance(typ, list) else [typ])
        ex = node.get("example")
        if "integer" in typ_set and "string" not in typ_set and isinstance(ex, str) and re.fullmatch(r"-?\d+", ex):
            node["example"] = int(ex)
        if "number" in typ_set and "string" not in typ_set and isinstance(ex, str):
            try:
                node["example"] = float(ex)
            except ValueError:
                pass
        for k, v in (node.get("properties") or {}).items() if isinstance(node.get("properties"), dict) else []:
            walk(v, k, name)
        if isinstance(node.get("items"), dict): walk(node["items"], name + "Item", name)
        if isinstance(node.get("additionalProperties"), dict): walk(node["additionalProperties"], name + "Value", name)

    def contextualize(node: Any, prop_path: str = "", name: str = "", parent: str = "") -> None:
        if not isinstance(node, dict):
            return
        override = context_title_description(path.name, str(schema.get("title", "")), prop_path, name, parent)
        if override:
            node["title"], node["description"] = override
        else:
            current_title = str(node.get("title", ""))
            current_desc = str(node.get("description", ""))
            needs_title = (
                not current_title
                or is_mechanical_title(current_title)
                or is_generic_fallback_description(current_desc)
                or current_title in {name, camel_words(name)}
            )
            needs_desc = not current_desc or is_generic_fallback_description(current_desc)
            semantic_title = semantic_title_for(
                name,
                str(schema.get("title", "")),
                runtime_alps,
                file_name=path.name,
                prop_path=prop_path,
                parent=parent,
                fallback=True,
            )
            if needs_title and semantic_title:
                node["title"] = semantic_title + ("（入力）" if is_request and "（入力）" not in semantic_title else "")
            if needs_desc:
                node["description"] = semantic_description_for(
                    name,
                    str(schema.get("title", "")),
                    runtime_alps,
                    file_name=path.name,
                    prop_path=prop_path,
                    parent=parent,
                )
        # Remove known ALPS-overreach on generic result messages even if the
        # exact route was not covered above.
        if name == "message" and "顧客が注文時に入力するお問い合わせ欄" in str(node.get("description", "")):
            node["title"] = f"{domain_from_file(path.name)}メッセージ"
            node["description"] = f"{route_from_title(str(schema.get('title', '')))} の処理結果メッセージ。注文時お問い合わせ欄ではない。"
        if name in {"nameKeyword", "emailKeyword"} and " Keyword" in str(node.get("title", "")):
            node["title"] = "名前検索キーワード" if name == "nameKeyword" else "メール検索キーワード"
        for k, v in (node.get("properties") or {}).items() if isinstance(node.get("properties"), dict) else []:
            child_path = f"{prop_path}.{k}" if prop_path else k
            contextualize(v, child_path, k, name)
        if isinstance(node.get("items"), dict):
            contextualize(node["items"], f"{prop_path}.items" if prop_path else "items", f"{name}Item", name)
        if isinstance(node.get("additionalProperties"), dict):
            contextualize(node["additionalProperties"], f"{prop_path}.additionalProperties" if prop_path else "additionalProperties", f"{name}Value", name)

    walk(schema)
    contextualize(schema)


def constraint_summary(s: dict[str, Any]) -> str:
    bits: list[str] = []
    t = s.get("type")
    if t:
        bits.append("type=" + ("/".join(t) if isinstance(t, list) else str(t)))
    for key in ("$ref", "enum", "pattern", "format", "minLength", "maxLength", "minimum", "maximum", "minItems", "maxItems"):
        if key in s:
            value = s[key]
            if isinstance(value, list):
                value = "[" + ",".join(map(str, value[:8])) + (",..." if len(value) > 8 else "") + "]"
            bits.append(f"{key}={value}")
    if isinstance(s.get("properties"), dict):
        bits.append(f"properties={len(s['properties'])}")
    if isinstance(s.get("items"), dict):
        bits.append("items=defined")
    return "; ".join(bits) or "object boundary only"


def is_approved_mixed_boundary(name: str, file: str, path: str, node: dict[str, Any]) -> bool:
    if name.endswith("Item") or name.endswith("Value"):
        return False
    if name in APPROVED_MIXED_RESPONSE_NAMES or name in MIXED_ID_NAMES:
        return True
    if name in {"disp_number", "pageno", "holiday"}:
        return True
    if "var/json_validate/" in file and name in REQUEST_TRANSPORT_MIXED_NAMES:
        return bool(node.get("$comment"))
    if "var/json_validate/" in file and (name in DB_ID_NAMES or name.endswith("Id")):
        return bool(node.get("$comment"))
    return False


def should_count_dynamic_row(name: str, path: str) -> bool:
    base = name[:-4] if name.endswith("Item") else name
    return base in DYNAMIC_ROW_NAMES or any(f".{n}." in f".{path}." or path.startswith(n + ".") for n in DYNAMIC_ROW_NAMES)




def quality_report(alps: dict[str, dict[str, Any]], stats: dict[str, Stat]) -> dict[str, Any]:
    report: dict[str, Any] = {
        "schemaFiles": {},
        "totals": Counter(),
        "broadEscapeTypes": [],
        "additionalPropertiesTrue": [],
        "scalarWithoutConstraints": [],
        "propertiesWithoutAlps": Counter(),
        "classifiedPropertiesWithoutAlps": Counter(),
        "unclassifiedPropertiesWithoutAlps": Counter(),
        "alpsGapLedger": {},
        "suspiciousDescriptions": [],
        "genericFallbackDescriptions": [],
        "genericFallbackDescriptionsByClass": Counter(),
        "mechanicalTitles": [],
        "mechanicalTitlesByClass": Counter(),
        "wideTransportUnions": [],
        "mixedBoundaryIds": [],
        "approvedMixedBoundaryIds": [],
        "unapprovedMixedBoundaryIds": [],
        "mixedBoundaryLedger": [],
        "stringTokenMixedIds": [],
        "dbIdMixedWithoutTransportReason": [],
        "openCollectionItemObjects": [],
        "opaqueFormObjects": [],
        "formBoundaryLedger": [],
        "shapeableOpaqueFormObjects": [],
        "opaqueFormObjectsWithoutReason": [],
        "dynamicRowsAccepted": [],
        "dynamicRowLedger": [],
        "dynamicRowsWithoutReason": [],
        "dynamicRowsWithNoProperties": [],
    }

    def has_constraint(s: dict[str, Any]) -> bool:
        return any(k in s for k in ("$ref", "anyOf", "enum", "pattern", "format", "minLength", "maxLength", "minimum", "maximum", "minItems", "maxItems", "properties", "items", "example", "examples"))

    def walk(s: Any, path: str, file: str) -> None:
        if not isinstance(s, dict):
            return
        metric_name = property_name_from_path(path)
        cls_for_metric = classify_non_alps_property(metric_name[:-4] if metric_name.endswith("Item") else metric_name, alps) or "alps-or-dynamic"
        t = s.get("type")
        if t == BROAD_ESCAPE or (isinstance(t, list) and sorted(t) == sorted(BROAD_ESCAPE)):
            report["broadEscapeTypes"].append(f"{file}:{path}")
        if isinstance(t, list) and set(t) == {"integer", "number", "string", "null"}:
            report["wideTransportUnions"].append(f"{file}:{path}")
        if isinstance(t, list) and set(t) == {"string", "integer", "null"}:
            entry = f"{file}:{path}"
            mixed = mixed_boundary_entry(metric_name, file, path, s, REQUEST_TRANSPORT_MIXED_NAMES, DB_ID_NAMES)
            report["mixedBoundaryIds"].append(entry)
            report["mixedBoundaryLedger"].append(mixed)
            if mixed["classification"] == "should-be-string":
                report["stringTokenMixedIds"].append(entry)
            if mixed["classification"] == "should-be-db-id":
                report["dbIdMixedWithoutTransportReason"].append(entry)
            if is_approved_mixed_boundary(metric_name, file, path, s) and mixed["classification"] not in {"should-be-string", "should-be-db-id"}:
                report["approvedMixedBoundaryIds"].append({"path": entry, "reason": s.get("$comment", "互換境界として明示承認"), "classification": mixed["classification"]})
            else:
                report["unapprovedMixedBoundaryIds"].append(entry)
        if s.get("additionalProperties") is True:
            report["additionalPropertiesTrue"].append({"path": f"{file}:{path}", "comment": s.get("$comment", "")})
        if metric_name in {"form", "searchForm"} and (t == "object" or (isinstance(t, list) and "object" in t)):
            form_entry = form_boundary_entry(file, path, s)
            report["opaqueFormObjects"].append({"path": f"{file}:{path}", "comment": s.get("$comment", ""), "classification": form_entry["classification"]})
            report["formBoundaryLedger"].append(form_entry)
            if form_entry["classification"] == "shapeable-form-context":
                report["shapeableOpaqueFormObjects"].append(form_entry["path"])
            if not form_entry["reason"] or form_entry["reason"].endswith("未記録。"):
                report["opaqueFormObjectsWithoutReason"].append(form_entry["path"])
        if path.endswith(".items") and (t == "object" or (isinstance(t, list) and "object" in t)):
            item_entry = {"path": f"{file}:{path}", "title": s.get("title", ""), "comment": s.get("$comment", ""), "properties": len(s.get("properties", {})) if isinstance(s.get("properties"), dict) else 0}
            if should_count_dynamic_row(metric_name, path):
                dyn_entry = dynamic_row_entry(file, path, s)
                item_entry.update({"classification": dyn_entry["classification"], "reason": dyn_entry["reason"]})
                report["dynamicRowsAccepted"].append(item_entry)
                report["dynamicRowLedger"].append(dyn_entry)
                if not dyn_entry["reason"] or dyn_entry["reason"].endswith("未記録。"):
                    report["dynamicRowsWithoutReason"].append(dyn_entry["path"])
                if dyn_entry["properties"] == 0 and dyn_entry["classification"] not in {"external-catalog-row"}:
                    report["dynamicRowsWithNoProperties"].append(dyn_entry["path"])
            elif s.get("additionalProperties") is not False or not isinstance(s.get("properties"), dict) or not s.get("properties"):
                report["openCollectionItemObjects"].append(item_entry)
        if isinstance(t, str) and t in {"string", "integer", "number", "boolean"} and not has_constraint(s):
            report["scalarWithoutConstraints"].append(f"{file}:{path}")
        title = str(s.get("title", ""))
        desc = str(s.get("description", ""))
        if "顧客が注文時に入力するお問い合わせ欄" in desc:
            report["suspiciousDescriptions"].append(f"{file}:{path}:{title}")
        if is_generic_fallback_description(desc):
            report["genericFallbackDescriptions"].append(f"{file}:{path}:{title}")
            report["genericFallbackDescriptionsByClass"][cls_for_metric] += 1
        if is_mechanical_title(title):
            report["mechanicalTitles"].append(f"{file}:{path}:{title}")
            report["mechanicalTitlesByClass"][cls_for_metric] += 1
        for k, v in s.get("properties", {}).items() if isinstance(s.get("properties"), dict) else []:
            if not alps_id_for(k, alps) and k not in DYNAMIC_OBJECT_NAMES and k not in DYNAMIC_MAP_NAMES:
                report["propertiesWithoutAlps"][k] += 1
                cls = classify_non_alps_property(k, alps)
                if cls:
                    report["classifiedPropertiesWithoutAlps"][cls] += 1
                else:
                    report["unclassifiedPropertiesWithoutAlps"][k] += 1
                ledger = report["alpsGapLedger"].setdefault(k, {
                    "classification": cls or "ALPS-candidate",
                    "count": 0,
                    "examples": [],
                    "aliasCandidate": ALPS_ALIASES.get(k, ""),
                    "schemaDecision": "",
                    "schemaConstraint": constraint_summary(v if isinstance(v, dict) else {}),
                    "shapeFixedCandidate": cls in {"collection-or-row", "domain-derived", "presentation"},
                    "exceptionReason": "",
                    "shouldReturnToAlps": cls is None,
                })
                ledger["count"] += 1
                if len(ledger["examples"]) < 6:
                    ledger["examples"].append(f"{file}:{path}.{k}" if path else f"{file}:{k}")
            walk(v, f"{path}.{k}" if path else k, file)
        if isinstance(s.get("items"), dict):
            walk(s["items"], f"{path}.items", file)
        if isinstance(s.get("additionalProperties"), dict):
            walk(s["additionalProperties"], f"{path}.additionalProperties", file)
        for v in s.get("$defs", {}).values() if isinstance(s.get("$defs"), dict) else []:
            walk(v, f"{path}.$defs", file)
        for key in ("anyOf", "oneOf", "allOf"):
            if isinstance(s.get(key), list):
                for i, v in enumerate(s[key]):
                    walk(v, f"{path}.{key}[{i}]", file)

    for d in SCHEMA_DIRS:
        files = sorted(d.glob("*.json"))
        report["schemaFiles"][d.name] = len(files)
        for p in files:
            data = load_json(p)
            report["totals"]["files"] += 1
            report["totals"]["requiredSchemas"] += 1 if data.get("required") else 0
            walk(data, "", str(p.relative_to(ROOT)))
    report["totals"] = dict(report["totals"])
    report["propertiesWithoutAlps"] = dict(report["propertiesWithoutAlps"].most_common())
    report["classifiedPropertiesWithoutAlps"] = dict(report["classifiedPropertiesWithoutAlps"].most_common())
    report["unclassifiedPropertiesWithoutAlps"] = dict(report["unclassifiedPropertiesWithoutAlps"].most_common())
    report["genericFallbackDescriptionsByClass"] = dict(report["genericFallbackDescriptionsByClass"].most_common())
    report["mechanicalTitlesByClass"] = dict(report["mechanicalTitlesByClass"].most_common())
    for name, row in report["alpsGapLedger"].items():
        row["schemaDecision"] = {
            "hypermedia": "ALPS遷移/link relationとして扱う。",
            "pagination": "一覧検索のページング/検索条件として扱う。",
            "identifier": "不透明ID/採番ID/互換IDのいずれかとしてschemaで明示する。",
            "counter": "非負整数の件数・集計値として扱う。",
            "runtime-flag": "Resource処理状態を表すboolean flagとして扱う。",
            "collection-or-row": "配列/行データとして親Resource文脈でshapeを定義する。",
            "transport-payload": "CSV/PDF/ログ等の輸送payloadとしてサイズ・文字列性を契約する。",
            "presentation": "画面表示/テンプレート用のpresentation値として扱う。",
            "form-context": "フォーム入力/表示文脈の補助値として扱う。",
            "operation-result": "unsafe操作の処理結果として扱う。",
            "domain-derived": "ALPS基礎語からResource文脈で派生した業務値として扱う。",
        }.get(row["classification"], "ALPS descriptorへ戻す候補として扱う。")
        row["exceptionReason"] = {
            "hypermedia": "ALPS遷移relationやURI mapであり、業務フィールドではない。",
            "pagination": "一覧制御の横断項目として複数Resourceで共有される。",
            "identifier": "Resource境界でID型を分解し、ALPS候補は別途検討する。",
            "counter": "集計値・処理件数であり永続属性とは限らない。",
            "runtime-flag": "処理結果/画面状態の一時フラグ。",
            "collection-or-row": "親Resource文脈でshapeを固定し、ALPSへ戻す候補は台帳で追跡する。",
            "transport-payload": "CSV/PDF/log等の輸送境界。",
            "presentation": "表示・テンプレート補助値。",
            "form-context": "フォーム再表示・フレームワーク文脈。",
            "operation-result": "unsafe操作の戻り値。",
            "domain-derived": "ALPS基礎語から派生したResource固有値。",
            "alps-or-dynamic": "ALPS aliasまたは動的mapとして明示管理する。",
        }.get(row["classification"], "未分類ではなくALPS改善候補として次工程へ渡す。")
    report["alpsGapLedger"] = dict(sorted(report["alpsGapLedger"].items(), key=lambda kv: (-kv[1]["count"], kv[0])))
    return report


def write_observation(alps: dict[str, dict[str, Any]], stats: dict[str, Stat], report: dict[str, Any]) -> None:
    lines = []
    lines.append("# Semantic-Ex JSON Schema Observation\n")
    lines.append("この文書は `alps.json` の意味、`be/var/fake` の観察、Resource境界のschema形状から導いたJSON Schema制約の根拠です。\n")
    lines.append("## Quality Baseline / Gate\n")
    lines.append(f"- Response schema files: {len(list((ROOT/'var/json_schema').glob('*.json')))}")
    lines.append(f"- Request schema files: {len(list((ROOT/'var/json_validate').glob('*.json')))}")
    lines.append(f"- Broad escape union occurrences: {len(report['broadEscapeTypes'])}")
    lines.append(f"- additionalProperties=true occurrences: {len(report['additionalPropertiesTrue'])}")
    lines.append(f"- Scalar properties without semantic constraints: {len(report['scalarWithoutConstraints'])}")
    lines.append(f"- Suspicious descriptions: {len(report['suspiciousDescriptions'])}")
    lines.append(f"- Generic fallback descriptions: {len(report['genericFallbackDescriptions'])}")
    lines.append(f"- Mechanical titles: {len(report['mechanicalTitles'])}")
    lines.append(f"- Wide transport unions: {len(report['wideTransportUnions'])}")
    lines.append(f"- Mixed boundary IDs/usages: {len(report['mixedBoundaryIds'])} (approved: {len(report['approvedMixedBoundaryIds'])}, unapproved: {len(report['unapprovedMixedBoundaryIds'])})")
    lines.append(f"- Open collection item objects (monitor): {len(report['openCollectionItemObjects'])}")
    lines.append(f"- Opaque form objects (monitor): {len(report['opaqueFormObjects'])}")
    lines.append(f"- Dynamic rows accepted (monitor): {len(report['dynamicRowsAccepted'])}")
    lines.append(f"- Shapeable opaque form objects: {len(report['shapeableOpaqueFormObjects'])}")
    lines.append(f"- Opaque form objects without reason: {len(report['opaqueFormObjectsWithoutReason'])}")
    lines.append(f"- Dynamic rows without reason: {len(report['dynamicRowsWithoutReason'])}")
    lines.append(f"- Dynamic rows with no properties and no exception: {len(report['dynamicRowsWithNoProperties'])}")
    lines.append(f"- String-token mixed IDs: {len(report['stringTokenMixedIds'])}")
    lines.append(f"- DB-ID mixed without transport reason: {len(report['dbIdMixedWithoutTransportReason'])}")
    lines.append(f"- ALPS未直結property（分類済み）: {sum(report['classifiedPropertiesWithoutAlps'].values())}")
    lines.append(f"- ALPS未直結property（未分類）: {sum(report['unclassifiedPropertiesWithoutAlps'].values())}")
    lines.append("")
    lines.append("必須ゼロ品質ゲート: broad escape union / additionalProperties=true / scalarWithoutConstraints / suspiciousDescriptions / wideTransportUnions / unclassified ALPS gap / genericFallbackDescriptions / mechanicalTitles / unapprovedMixedBoundaryIds / shapeableOpaqueFormObjects / dynamicRowsWithoutReason / stringTokenMixedIds / dbIdMixedWithoutTransportReason。")
    lines.append("")
    lines.append("## Verification Boundary")
    lines.append("このschema資産はPHP/ALPS/phpunit差分から隔離して管理します。Runtime検査とApiDoc 234/154/234の統合検証には、別WIPで導入済みのResource `#[JsonSchema]`/`#[Alps]` 属性と `JsonSchemaModule` のインストールが必要です。")
    lines.append("clean schema worktree単独ではPHP属性を持たないため、schema品質ゲートを直接検証し、統合テストは既存PHP WIPへschema資産だけを重ねた一時検証コピーで確認します。")
    lines.append("")
    lines.append("## Core Semantic Constraints\n")
    for key in ["productCode", "email", "postalCode", "phoneNumber", "pref", "price02", "quantity", "productStatus", "customerStatus", "orderStatus", "cartKey"]:
        aid = alps_id_for(key, alps) or key
        desc = alps.get(aid, {})
        st = stats.get(key)
        title = desc.get("title", key) if desc else key
        lines.append(f"### {key} — {title}")
        d = doc_value(desc)
        if d:
            lines.append(f"- ALPS meaning: {d}")
        if st:
            lines.append(f"- Fake observation: {observation_sentence(key, st) or 'object/array shape observed.'}")
            lines.append(f"- Types: {dict(st.types)}; sources: {', '.join(k for k,_ in st.sources.most_common(5))}")
        lines.append("- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.")
        lines.append("")
    lines.append("## Exceptions\n")
    lines.append("- `form`, framework form objects: object with documented `additionalProperties: true`; internal Aura/WebForm state is not an application contract.")
    lines.append("- Dynamic option maps such as `productStatusOptions`: object maps with constrained value type where the numeric keys would break ApiDoc term rendering if expressed as literal property names.")
    lines.append("- `csv` and `pdf`: JSON boundary validates transport shape/size; CSV/PDF internal structure belongs to compatibility services.")
    lines.append("- Collections whose Resource schema currently exposes no item properties are marked as object arrays with a `$comment`; these are explicit TODOs for future Fake expansion, not silent pass-throughs.")
    lines.append("")
    lines.append("## ALPS未直結propertyの分類")
    lines.append("ALPS descriptor名と完全一致しないpropertyは、無視せず次の例外クラスへ分類して監査対象にしています。未分類が0であることを品質ゲートにします。")
    for cls, count in report["classifiedPropertiesWithoutAlps"].items():
        lines.append(f"- {cls}: {count}")
    OBSERVATION_MD.write_text("\n".join(lines) + "\n")


def write_alps_gap_ledger(report: dict[str, Any]) -> None:
    lines = []
    lines.append("# ALPS未直結property台帳\n")
    lines.append("この台帳は、JSON Schema property名が `alps.json` のdescriptor idと直接一致しないものを、無視せず分類・監査するためのものです。")
    lines.append("`ALPS-candidate` は将来ALPSへ戻す候補ですが、この改善では `alps.json` は変更しません。\n")
    lines.append("| property | count | classification | alias candidate | should return to ALPS | fixed shape candidate | examples | schema constraint | schema decision | exception reason |")
    lines.append("|---|---:|---|---|---|---|---|---|---|---|")
    for name, row in report.get("alpsGapLedger", {}).items():
        examples = "<br>".join(row.get("examples", []))
        alias = row.get("aliasCandidate", "")
        should = "yes" if row.get("shouldReturnToAlps") else "no"
        fixed = "yes" if row.get("shapeFixedCandidate") else "no"
        decision = row.get("schemaDecision", "")
        constraint = str(row.get("schemaConstraint", "")).replace("|", "\\|")
        reason = str(row.get("exceptionReason", "")).replace("|", "\\|")
        lines.append(f"| `{name}` | {row.get('count', 0)} | {row.get('classification', '')} | `{alias}` | {should} | {fixed} | {examples} | {constraint} | {decision} | {reason} |")
    ALPS_GAP_MD.write_text("\n".join(lines) + "\n")


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--audit-only", action="store_true")
    args = parser.parse_args()
    alps = load_alps()
    stats = observe_fake()
    if not args.audit_only:
        for d in SCHEMA_DIRS:
            for path in sorted(d.glob("*.json")):
                enhance_schema(path, alps, stats)
    report = quality_report(alps, stats)
    # Convert nested Counters if any.
    QUALITY_JSON.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n")
    write_observation(alps, stats, report)
    write_alps_gap_ledger(report)
    write_form_boundary_ledger(FORM_BOUNDARY_MD, report)
    write_dynamic_row_ledger(DYNAMIC_ROW_MD, report)
    write_mixed_boundary_ledger(MIXED_BOUNDARY_MD, report)
    print(json.dumps({
        "schemas": report["schemaFiles"],
        "broadEscapeTypes": len(report["broadEscapeTypes"]),
        "additionalPropertiesTrue": len(report["additionalPropertiesTrue"]),
        "scalarWithoutConstraints": len(report["scalarWithoutConstraints"]),
        "propertiesWithoutAlps": len(report["propertiesWithoutAlps"]),
        "unclassifiedPropertiesWithoutAlps": len(report["unclassifiedPropertiesWithoutAlps"]),
        "suspiciousDescriptions": len(report["suspiciousDescriptions"]),
        "genericFallbackDescriptions": len(report["genericFallbackDescriptions"]),
        "mechanicalTitles": len(report["mechanicalTitles"]),
        "wideTransportUnions": len(report["wideTransportUnions"]),
        "mixedBoundaryIds": len(report["mixedBoundaryIds"]),
        "approvedMixedBoundaryIds": len(report["approvedMixedBoundaryIds"]),
        "unapprovedMixedBoundaryIds": len(report["unapprovedMixedBoundaryIds"]),
        "openCollectionItemObjects": len(report["openCollectionItemObjects"]),
        "opaqueFormObjects": len(report["opaqueFormObjects"]),
        "dynamicRowsAccepted": len(report["dynamicRowsAccepted"]),
        "shapeableOpaqueFormObjects": len(report["shapeableOpaqueFormObjects"]),
        "opaqueFormObjectsWithoutReason": len(report["opaqueFormObjectsWithoutReason"]),
        "dynamicRowsWithoutReason": len(report["dynamicRowsWithoutReason"]),
        "dynamicRowsWithNoProperties": len(report["dynamicRowsWithNoProperties"]),
        "stringTokenMixedIds": len(report["stringTokenMixedIds"]),
        "dbIdMixedWithoutTransportReason": len(report["dbIdMixedWithoutTransportReason"]),
    }, ensure_ascii=False, indent=2))
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
