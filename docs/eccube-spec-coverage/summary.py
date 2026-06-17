#!/usr/bin/env python3
"""Build final summary report for all areas."""
import json, os, glob, re
from collections import Counter

BASE = "docs/eccube-spec-coverage"
EVD = f"{BASE}/evidence-browser"

# Get all test items
items = json.load(open(f"{BASE}/all_items.json"))
area_counts = Counter(i["area"] for i in items)

# Count evidence files
area_pngs = Counter()
for f in glob.glob(f"{EVD}/*.png"):
    name = os.path.basename(f).replace(".png","")
    parts = name.split("-", 1)
    if len(parts) == 2:
        area_pngs[parts[0]] += 1

# Quick status check from DOM titles
print("=" * 60)
print("EC-CUBE 結合試験カバレッジ 実行結果サマリー")
print("=" * 60)
print(f"\n全 {sum(area_counts.values())} 項目 / {len(area_counts)} 領域")
print(f"証拠: {sum(area_pngs.values())} PNG")

print("\n--- 領域別 証拠数 / 項目数 ---")
for area in sorted(area_counts.keys()):
    png = area_pngs.get(area, 0)
    total = area_counts[area]
    ok = "OK" if png >= total else f"MISS {total-png}"
    print(f"  {area}: {png}/{total} PNG [{ok}]")

# Spot-check status codes
print("\n--- ステータス分布 (全証拠から抜粋) ---")
statuses = Counter()
for f in list(glob.glob(f"{EVD}/*.html"))[:50]:
    content = open(f).read()[:3000]
    m = re.search(r'<title>([^<]+)</title>', content)
    title = m.group(1) if m else "?"
    if "404" in title:
        statuses["404"] += 1
    elif "403" in title:
        statuses["403"] += 1
    elif "405" in title:
        statuses["405"] += 1
    elif "500" in title:
        statuses["500"] += 1
    elif "Not Found" in title:
        statuses["404"] += 1
    else:
        statuses["200"] += 1

# Check all HTML files for error pages
for f in glob.glob(f"{EVD}/*.html"):
    content = open(f).read()[:1000]
    m = re.search(r'<title>([^<]+)</title>', content)
    title = m.group(1) if m else "?"
    if "404" in title or "Not Found" in title:
        statuses["404"] += 1
    elif "403" in title or "Forbidden" in title:
        statuses["403"] += 1
    elif "405" in title or "Method Not Allowed" in title:
        statuses["405"] += 1
    elif "400" in title or "Bad Request" in title:
        statuses["400"] += 1

for s, c in sorted(statuses.items()):
    print(f"  {s}: {c}")

print("\n証拠パス: docs/eccube-spec-coverage/evidence-browser/")
print("JSONL: docs/eccube-spec-coverage/records/{AREA}.jsonl")
