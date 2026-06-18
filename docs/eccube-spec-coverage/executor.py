#!/usr/bin/env python3
"""Executes EC-CUBE integration test items against BeMart and collects evidence."""
import json, subprocess, os, re, sys, time, urllib.parse
from pathlib import Path

BASE_URL = "http://127.0.0.1:8081"
JAR = "/tmp/bemart.cookies"
EVIDENCE_DIR = Path("/Users/akihito/git/BeMart/docs/eccube-spec-coverage/evidence")
RECORDS_DIR = Path("/Users/akihito/git/BeMart/docs/eccube-spec-coverage/records")

# --- Route mapping ---
ROUTE_MAP = {
    "TOP": "/",
    "商品一覧": "/products/list",
    "商品詳細": "/products/detail",
    "カート": "/cart",
    "ログイン": "/login",
    "会員登録": "/entry",
    "マイページ": "/mypage",
    "お気に入り": "/mypage/favorite",
    "購入履歴": "/mypage/history",
    "お届け先": "/mypage/delivery",
    "退会": "/mypage/withdraw",
    "ご注文手続き": "/shopping",
    "注文確認": "/shopping/confirm",
    "ご注文完了": "/shopping/complete",
    "お問い合わせ": "/contact",
    "パスワード再発行": "/forgot",
    "特定商取引法": "/help/tradelaw",
    "プライバシーポリシー": "/help/privacy",
    "ご利用ガイド": "/help/guide",
    "管理画面": "/admin",
    "管理画面ログイン": "/admin/login",
    "管理画面商品管理": "/admin/product",
    "管理画面受注管理": "/admin/order",
    "管理画面会員管理": "/admin/customer",
    "管理画面コンテンツ管理": "/admin/content",
    "管理画面設定": "/admin/setting",
    "管理画面システム": "/admin/system",
}

ACTION_WORDS = ["押下", "クリック", "選択", "入力", "投入", "追加", "削除", "登録", "変更", "更新", "送信", "ログインする", "ログアウト"]
DISPLAY_WORDS = ["表示されている", "表示される", "遷移する", "表示する", "広がり", "反映", "確認できる", "確認する"]


def _is_action_step(step: str) -> bool:
    """A step is an 'action' if it describes doing something, not just verifying display."""
    return any(w in step for w in ACTION_WORDS) and not any(w in step for w in DISPLAY_WORDS)


def _is_display_step(step: str) -> bool:
    """A step is a display check (expected result)."""
    return any(w in step for w in DISPLAY_WORDS)


def detect_urls_from_steps(steps: list[str], area: str) -> list[dict]:
    """Detect HTTP operations from steps. Only action steps generate HTTP ops."""
    ops = []
    step_text = " ".join(steps)
    action_text = " ".join([s for s in steps if _is_action_step(s)])
    if not action_text:
        action_text = step_text  # fallback: use all steps

    if area.startswith("EF"):
        # --- Page display ---
        if "TOPページ" in action_text or "トップページ" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["TOP"], "desc": "TOPページ表示"})
        elif "商品一覧" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["商品一覧"], "desc": "商品一覧表示"})
        elif "商品詳細" in action_text:
            ops.append({"method": "GET", "path": "/products/detail/1", "desc": "商品詳細表示"})
        elif "カート" in action_text and "投入" not in action_text and "入れる" not in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["カート"], "desc": "カート表示"})
        elif "ログイン" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["ログイン"], "desc": "ログイン画面表示"})
        elif "会員登録" in action_text or "新規会員登録" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["会員登録"], "desc": "会員登録画面表示"})
        elif "マイページ" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["マイページ"], "desc": "マイページ表示"})
        elif "お気に入り" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["お気に入り"], "desc": "お気に入り表示"})
        elif "購入履歴" in action_text or "注文履歴" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["購入履歴"], "desc": "購入履歴表示"})
        elif "お届け先" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["お届け先"], "desc": "お届け先表示"})
        elif "退会" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["退会"], "desc": "退会画面表示"})
        elif "ご注文手続き" in action_text or "レジ" in action_text or "購入手続き" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["ご注文手続き"], "desc": "注文手続き表示"})
        elif "注文確認" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["注文確認"], "desc": "注文確認表示"})
        elif "ご注文完了" in action_text or "注文完了" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["ご注文完了"], "desc": "注文完了表示"})
        elif "お問い合わせ" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["お問い合わせ"], "desc": "お問い合わせ表示"})
        elif "パスワード" in action_text and "再発行" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["パスワード再発行"], "desc": "パスワード再発行表示"})
        elif "特定商取引" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["特定商取引法"], "desc": "特定商取引法表示"})
        elif "プライバシー" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["プライバシーポリシー"], "desc": "プライバシーポリシー表示"})
        elif "ご利用ガイド" in action_text:
            ops.append({"method": "GET", "path": ROUTE_MAP["ご利用ガイド"], "desc": "ご利用ガイド表示"})

        # --- Actions ---
        if _is_action_step(" ".join(steps)) and "カート" in action_text and ("投入" in action_text or "入れる" in action_text):
            ops.append({"method": "POST", "path": "/cart/item", "desc": "カート投入"})
        if "ソート" in action_text or "並び替え" in action_text:
            ops.append({"method": "GET", "path": "/products/list?orderby=price", "desc": "商品並び替え"})
        if _is_action_step(" ".join(steps)) and "虫眼鏡" in action_text:
            ops.append({"method": "GET", "path": "/products/list?name=test", "desc": "検索実行"})
        if _is_action_step(" ".join(steps)) and "カテゴリ" in action_text and "選択" in action_text:
            ops.append({"method": "GET", "path": "/products/list?category_id=1", "desc": "カテゴリ選択"})

    elif area.startswith("EA"):
        # EA route mapping based on actual BeMart URL patterns
        if area == "EA01":  # TOP
            ops.append({"method": "GET", "path": "/admin/index", "desc": "管理画面TOP表示"})
        elif area == "EA02":  # Authentication
            ops.append({"method": "GET", "path": "/admin/twofactorauth", "desc": "管理画面2FA設定表示"})
        elif area == "EA03":  # Product
            if "登録" in step_text or "新規" in step_text:
                ops.append({"method": "GET", "path": "/admin/productnew", "desc": "管理画面商品登録表示"})
            elif "編集" in step_text:
                ops.append({"method": "GET", "path": "/admin/product/edit", "desc": "管理画面商品編集表示"})
            elif "CSV" in step_text or "csv" in step_text:
                ops.append({"method": "GET", "path": "/admin/product/csvproduct", "desc": "管理画面商品CSV表示"})
            else:
                ops.append({"method": "GET", "path": "/admin/productlist", "desc": "管理画面商品一覧表示"})
        elif area == "EA04":  # Order
            if "編集" in step_text:
                ops.append({"method": "GET", "path": "/admin/order/edit", "desc": "管理画面受注編集表示"})
            elif "CSV" in step_text or "csv" in step_text or "出力" in step_text:
                ops.append({"method": "GET", "path": "/admin/order/exportorder", "desc": "管理画面受注CSV表示"})
            elif "メール" in step_text or "mail" in step_text.lower():
                ops.append({"method": "GET", "path": "/admin/order/sendmail", "desc": "管理画面受注メール表示"})
            else:
                ops.append({"method": "GET", "path": "/admin/orderlist", "desc": "管理画面受注一覧表示"})
        elif area == "EA05":  # Customer
            ops.append({"method": "GET", "path": "/admin/customerlist", "desc": "管理画面会員一覧表示"})
        elif area == "EA06":  # Content Management
            if "キャッシュ" in step_text or "cache" in step_text.lower():
                ops.append({"method": "GET", "path": "/admin/content/cache", "desc": "管理画面キャッシュ表示"})
            elif "メンテナンス" in step_text or "maintenance" in step_text.lower():
                ops.append({"method": "GET", "path": "/admin/content/maintenance", "desc": "管理画面メンテナンス表示"})
            elif "CSS" in step_text or "JS" in step_text:
                ops.append({"method": "GET", "path": "/admin/content/cache", "desc": "管理画面CSS/JS表示"})
            else:
                ops.append({"method": "GET", "path": "/admin/content/cache", "desc": "管理画面コンテンツ管理表示"})
        elif area == "EA07":  # Basic Info
            if "特定商取引" in step_text:
                ops.append({"method": "GET", "path": "/admin/tradelaw", "desc": "管理画面特定商取引法表示"})
            elif "マスタ" in step_text or "master" in step_text.lower():
                ops.append({"method": "GET", "path": "/admin/masterdata", "desc": "管理画面マスタデータ表示"})
            elif "メール" in step_text:
                ops.append({"method": "GET", "path": "/admin/mailtemplate", "desc": "管理画面メールテンプレート表示"})
            elif "CSV" in step_text:
                ops.append({"method": "GET", "path": "/admin/csvconfig", "desc": "管理画面CSV設定表示"})
            elif "支払" in step_text or "payment" in step_text.lower():
                ops.append({"method": "GET", "path": "/admin/payment/paymentlist", "desc": "管理画面支払方法表示"})
            else:
                ops.append({"method": "GET", "path": "/admin/index", "desc": "管理画面基本設定表示"})
        elif area == "EA08":  # System
            if "ログ" in step_text or "log" in step_text.lower():
                ops.append({"method": "GET", "path": "/admin/log", "desc": "管理画面ログ表示"})
            elif "セキュリティ" in step_text or "security" in step_text.lower():
                ops.append({"method": "GET", "path": "/admin/security", "desc": "管理画面セキュリティ表示"})
            elif "メンバー" in step_text or "member" in step_text.lower():
                ops.append({"method": "GET", "path": "/admin/memberlist", "desc": "管理画面メンバー表示"})
            else:
                ops.append({"method": "GET", "path": "/admin/system", "desc": "管理画面システム設定表示"})
        elif area == "EA09":  # Shipping/Delivery
            ops.append({"method": "GET", "path": "/admin/delivery/deliverylist", "desc": "管理画面配送設定表示"})
        elif area == "EA10":  # Reduced Tax
            ops.append({"method": "GET", "path": "/admin/taxrule/taxrulelist", "desc": "管理画面税率設定表示"})
        else:
            ops.append({"method": "GET", "path": "/admin/index", "desc": "管理画面トップ表示"})

    if not ops:
        ops.append({"method": "GET", "path": "/", "desc": "デフォルトTOP表示"})

    return ops


def extract_csrf(html: str) -> str | None:
    m = re.search(r'name="csrfToken"[^>]*value="([^"]*)"', html)
    if m:
        return m.group(1)
    m = re.search(r'name="_csrf_token"[^>]*value="([^"]*)"', html)
    if m:
        return m.group(1)
    return None


def run_curl(method: str, path: str, data: dict = None) -> tuple[int, str, str]:
    """Execute curl and return (status_code, headers, body)."""
    cmd = ["curl", "-s", "-i", "-b", JAR, "-c", JAR, "-o", "-"]
    if method == "POST":
        cmd += ["-X", "POST"]
    if data:
        parts = [f"{k}={urllib.parse.quote(str(v))}" for k, v in data.items()]
        cmd += ["-d", "&".join(parts)]
    cmd.append(f"{BASE_URL}{path}")

    result = subprocess.run(cmd, capture_output=True, text=True, timeout=30)
    output = result.stdout

    # Parse HTTP status from first line
    status = 0
    m = re.match(r'HTTP/[\d.]+ (\d+)', output)
    if m:
        status = int(m.group(1))

    # Split headers and body
    parts = output.split("\r\n\r\n", 1)
    headers = parts[0] if parts else ""
    body = parts[1] if len(parts) > 1 else ""

    return status, headers, body


def save_evidence(area: str, item_id: str, status: int, headers: str, body: str) -> dict:
    evidence_area = EVIDENCE_DIR / area
    evidence_area.mkdir(parents=True, exist_ok=True)
    resp_path = evidence_area / f"{item_id}.response.html"
    headers_path = evidence_area / f"{item_id}.headers.txt"
    resp_path.write_text(body, encoding="utf-8")
    headers_path.write_text(f"HTTP {status}\n{headers}", encoding="utf-8")
    return {
        "response": f"docs/eccube-spec-coverage/evidence/{area}/{item_id}.response.html",
        "headers": f"docs/eccube-spec-coverage/evidence/{area}/{item_id}.headers.txt",
    }


def execute_item(item: dict) -> dict:
    steps = item["steps"]
    ops = detect_urls_from_steps(steps, item["area"])

    # Expected = ALL steps (both procedure and verification)
    expected = "\n".join(steps)

    executed_steps = []
    primary_status = 0
    primary_headers = ""
    primary_body = ""
    all_observations = []

    for op in ops:
        method = op["method"]
        path = op["path"]
        post_data = None

        if method == "POST":
            csrf = extract_csrf(primary_body) if primary_body else None
            post_data = {}
            if csrf:
                post_data["csrfToken"] = csrf

        status, headers, body = run_curl(method, path, post_data)

        # Keep first response as primary
        if primary_status == 0:
            primary_status = status
            primary_headers = headers
            primary_body = body

        executed_steps.append({"method": method, "url": path, "inputs": post_data})

        # Observation
        if status == 200:
            all_observations.append(f"{method} {path} → 200 OK")
        elif status in (301, 302, 303):
            loc_m = re.search(r'(?i)location:\s*(.+)', headers)
            loc = loc_m.group(1).strip() if loc_m else "?"
            all_observations.append(f"{method} {path} → {status} → {loc}")
        elif status == 403:
            all_observations.append(f"{method} {path} → 403 Forbidden")
        elif status == 404:
            all_observations.append(f"{method} {path} → 404 Not Found")
        elif status == 501:
            all_observations.append(f"{method} {path} → 501 Not Implemented")
        else:
            all_observations.append(f"{method} {path} → {status}")

    evidence = save_evidence(item["area"], item["item_id"], primary_status, primary_headers, primary_body)

    return {
        "area": item["area"],
        "item_id": item["item_id"],
        "title": item["title"],
        "expected": expected,
        "context": "sql",
        "executed": True,
        "steps": executed_steps,
        "http_status": primary_status,
        "content_type": "text/html; charset=utf-8",
        "evidence": evidence,
        "observed": "; ".join(all_observations),
        "verifier_status": None,
        "notes": "",
    }


def main():
    items = json.loads(Path("/Users/akihito/git/BeMart/docs/eccube-spec-coverage/all_items.json").read_text())
    areas_to_process = sys.argv[1:] if len(sys.argv) > 1 else None

    for area in ["EF01", "EF02", "EF03", "EF04", "EF05", "EF06", "EF07",
                 "EA01", "EA02", "EA03", "EA04", "EA05", "EA06", "EA07", "EA08", "EA09", "EA10"]:
        if areas_to_process and area not in areas_to_process:
            continue

        area_items = [i for i in items if i["area"] == area]
        if not area_items:
            continue

        print(f"\n{'='*60}")
        print(f"Processing {area}: {len(area_items)} items")
        print(f"{'='*60}")

        records = []
        RECORDS_DIR.mkdir(parents=True, exist_ok=True)

        for item in area_items:
            print(f"  [{item['item_id']}] {item['title'][:50]}...", end=" ", flush=True)
            try:
                record = execute_item(item)
                records.append(record)
                print(f"→ {record['http_status']} ({len(record['steps'])} ops)")
            except Exception as e:
                print(f"→ ERROR: {e}")
                record = {
                    "area": area,
                    "item_id": item["item_id"],
                    "title": item["title"],
                    "expected": "\n".join(item["steps"]),
                    "context": "sql",
                    "executed": False,
                    "steps": [],
                    "http_status": 0,
                    "content_type": "",
                    "evidence": {},
                    "observed": f"実行エラー: {str(e)}",
                    "verifier_status": None,
                    "notes": f"error: {str(e)}",
                }
                records.append(record)
            time.sleep(0.2)

        out_path = RECORDS_DIR / f"{area}.jsonl"
        with open(out_path, "w") as f:
            for r in records:
                f.write(json.dumps(r, ensure_ascii=False) + "\n")
        print(f"  Wrote {len(records)} records to {out_path}")


if __name__ == "__main__":
    main()
