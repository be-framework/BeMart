#!/usr/bin/env python3
"""Generate harness scenarios for all remaining EC-CUBE test areas."""
import json
from pathlib import Path

ITEMS = json.load(open(Path("/Users/akihito/git/BeMart/docs/eccube-spec-coverage/all_items.json")))

# URL mapping learned from EF03
EF_URLS = {
    "TOP": "/",
    "商品一覧": "/products",
    "商品詳細": "/product?productCode=CODE000002",
    "別商品": "/product?productCode=CODE000004",
    "カート": "/cart",
    "ログイン": "/login",
    "会員登録": "/entry",
    "マイページ": "/mypage",
    "ご注文履歴": "/mypage/history",
    "お気に入り": "/mypage/favorite",
    "お届け先": "/mypage/address",
    "退会": "/mypage/withdraw",
    "お問い合わせ": "/contact",
    "パスワード再発行": "/forgot",
    "当サイトについて": "/help/about",
    "プライバシーポリシー": "/help/privacy",
    "特定商取引法": "/help/tradelaw",
    "ご利用ガイド": "/help/guide",
    "利用規約": "/help/agreement",
}

ADMIN_URLS = {
    "admin_index": "/admin/index",
    "admin_product_list": "/admin/product-list",
    "admin_product_new": "/admin/product/new",
    "admin_order_list": "/admin/order-list",
    "admin_customer_list": "/admin/customer-list",
    "admin_content": "/admin/content",
    "admin_setting_shop": "/admin/setting/shop",
    "admin_system": "/admin/system",
    "admin_log": "/admin/log",
    "admin_security": "/admin/security",
    "admin_delivery": "/admin/setting/shop/delivery",
    "admin_tax": "/admin/tax-rule",
    "admin_member": "/admin/member",
    "admin_authority": "/admin/authority",
    "admin_masterdata": "/admin/masterdata",
    "admin_mail": "/admin/mail",
    "admin_csv": "/admin/csv",
    "admin_calendar": "/admin/calendar",
    "admin_payment": "/admin/payment",
}

LOGIN_STEPS = [
    {"action": "goto", "url": "/login"},
    {"action": "fill", "sel": "input[name=\"email\"]", "value": "login-test@example.com"},
    {"action": "fill", "sel": "input[name=\"password\"]", "value": "login-test-password-2026"},
    {"action": "submitFollow", "sel": "form[name=\"login_mypage\"] button[type=\"submit\"]"},
]

def build_ef_scenario(item):
    steps_text = " ".join(item["steps"])
    steps = []
    
    # Login-required areas
    if item["area"] in ["EF05"]:
        steps = LOGIN_STEPS.copy()
    else:
        steps = []
    
    # Page mapping
    if "TOP" in item["area"] or "TOPページ" in steps_text:
        steps.append({"action": "goto", "url": EF_URLS["TOP"]})
    elif "商品一覧" in steps_text or "ソート" in steps_text or "表示件数" in steps_text or "ページング" in steps_text:
        steps.append({"action": "goto", "url": EF_URLS["商品一覧"]})
    elif "商品詳細" in steps_text or "カートに入れる" in steps_text:
        steps.append({"action": "goto", "url": EF_URLS["商品詳細"]})
        if "カートに入れる" in steps_text or "投入" in steps_text:
            steps.append({"action": "submitFollow", "sel": "button.add-cart"})
    elif "カート" in steps_text:
        steps.append({"action": "goto", "url": EF_URLS["カート"]})
    elif "会員登録" in steps_text or "利用規約" in steps_text:
        if "利用規約" in steps_text:
            steps.append({"action": "goto", "url": EF_URLS["利用規約"]})
        else:
            steps.append({"action": "goto", "url": EF_URLS["会員登録"]})
    elif "ログイン" in steps_text or "ログアウト" in steps_text:
        if "ログアウト" in steps_text:
            steps.append({"action": "goto", "url": "/logout"})
        elif item["area"] != "EF05":
            steps.append({"action": "goto", "url": EF_URLS["ログイン"]})
    elif "マイページ" in steps_text or "ご注文履歴" in steps_text or "お気に入り" in steps_text or "お届け先" in steps_text or "退会" in steps_text or "会員情報" in steps_text:
        if "ご注文履歴" in steps_text:
            steps.append({"action": "goto", "url": EF_URLS["ご注文履歴"]})
        elif "お気に入り" in steps_text:
            steps.append({"action": "goto", "url": EF_URLS["お気に入り"]})
        elif "お届け先" in steps_text:
            steps.append({"action": "goto", "url": EF_URLS["お届け先"]})
        elif "退会" in steps_text:
            steps.append({"action": "goto", "url": EF_URLS["退会"]})
        else:
            steps.append({"action": "goto", "url": EF_URLS["マイページ"]})
    elif "お問い合わせ" in steps_text:
        steps.append({"action": "goto", "url": EF_URLS["お問い合わせ"]})
    elif "パスワード再発行" in steps_text or "パスワード" in steps_text:
        steps.append({"action": "goto", "url": EF_URLS["パスワード再発行"]})
    elif "当サイトについて" in steps_text:
        steps.append({"action": "goto", "url": EF_URLS["当サイトについて"]})
    elif "プライバシー" in steps_text:
        steps.append({"action": "goto", "url": EF_URLS["プライバシーポリシー"]})
    elif "特定商取引" in steps_text:
        steps.append({"action": "goto", "url": EF_URLS["特定商取引法"]})
    elif "ご利用ガイド" in steps_text:
        steps.append({"action": "goto", "url": EF_URLS["ご利用ガイド"]})
    elif "購入" in steps_text or "税率" in steps_text:
        # EF07 tax items - cart + checkout flow
        steps.append({"action": "goto", "url": EF_URLS["商品詳細"]})
        steps.append({"action": "submitFollow", "sel": "button.add-cart"})
        steps.append({"action": "goto", "url": EF_URLS["カート"]})
    else:
        steps.append({"action": "goto", "url": EF_URLS["TOP"]})
    
    return steps

def build_ea_scenario(item):
    steps_text = " ".join(item["steps"])
    steps = []
    url = ADMIN_URLS["admin_index"]
    
    if item["area"] == "EA01":
        url = ADMIN_URLS["admin_index"]
    elif item["area"] == "EA02":
        url = "/admin/login"
    elif item["area"] == "EA03":
        if "検索" in steps_text:
            url = ADMIN_URLS["admin_product_list"]
        elif "CSV" in steps_text:
            url = ADMIN_URLS["admin_product_list"]
        elif "登録" in steps_text:
            url = ADMIN_URLS["admin_product_new"]
        else:
            url = ADMIN_URLS["admin_product_list"]
    elif item["area"] == "EA04":
        url = ADMIN_URLS["admin_order_list"]
    elif item["area"] == "EA05":
        url = ADMIN_URLS["admin_customer_list"]
    elif item["area"] == "EA06":
        url = ADMIN_URLS["admin_content"]
    elif item["area"] == "EA07":
        if "配送" in steps_text:
            url = ADMIN_URLS["admin_delivery"]
        elif "税率" in steps_text:
            url = ADMIN_URLS["admin_tax"]
        elif "支払" in steps_text:
            url = ADMIN_URLS["admin_payment"]
        elif "メール" in steps_text:
            url = ADMIN_URLS["admin_mail"]
        elif "CSV" in steps_text:
            url = ADMIN_URLS["admin_csv"]
        elif "特定商取引" in steps_text:
            url = "/admin/tradelaw"
        else:
            url = ADMIN_URLS["admin_setting_shop"]
    elif item["area"] == "EA08":
        if "システム" in steps_text:
            url = ADMIN_URLS["admin_system"]
        elif "メンバー" in steps_text:
            url = ADMIN_URLS["admin_member"]
        elif "セキュリティ" in steps_text:
            url = ADMIN_URLS["admin_security"]
        elif "権限" in steps_text:
            url = ADMIN_URLS["admin_authority"]
        elif "ログ" in steps_text:
            url = ADMIN_URLS["admin_log"]
        elif "マスタ" in steps_text:
            url = ADMIN_URLS["admin_masterdata"]
        else:
            url = ADMIN_URLS["admin_system"]
    elif item["area"] == "EA09":
        url = ADMIN_URLS["admin_order_list"]
    elif item["area"] == "EA10":
        if "税率" in steps_text:
            url = ADMIN_URLS["admin_tax"]
        elif "CSV" in steps_text:
            url = ADMIN_URLS["admin_csv"]
        else:
            url = ADMIN_URLS["admin_product_list"]
    
    steps.append({"action": "goto", "url": url})
    
    # Admin search operations
    if "検索" in steps_text and "検索結果" in steps_text:
        if "0件" not in steps_text:
            # Try to submit the search form
            steps.append({"action": "submitFollow"})
    
    return steps

# Build all scenarios
all_scenarios = []

for item in ITEMS:
    if item["area"] == "EF03":
        continue  # Already done
    
    iid = item["item_id"]
    sp_title = item["title"].replace('"', "'")
    
    if item["area"].startswith("EF"):
        steps = build_ef_scenario(item)
        auth = "none"
        # Exception: EA02 needs real TOTP
    elif item["area"] == "EA02":
        steps = [{"action": "goto", "url": "/admin/login"}]
        auth = "none"  # real TOTP, handled separately
    else:
        steps = build_ea_scenario(item)
        auth = "admin"
    
    all_scenarios.append({
        "area": item["area"],
        "item_id": iid,
        "title": sp_title,
        "auth": auth,
        "steps": steps,
    })

# Save
out = Path("/Users/akihito/git/BeMart/docs/eccube-spec-coverage/harness/all-remaining.json")
json.dump(all_scenarios, open(out, "w"), ensure_ascii=False, indent=2)

# Count by area
from collections import Counter
c = Counter(s["area"] for s in all_scenarios)
for a in sorted(c):
    print(f"{a}: {c[a]} items")
print(f"Total: {len(all_scenarios)} scenarios -> {out}")
