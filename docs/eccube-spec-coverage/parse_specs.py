#!/usr/bin/env python3
"""EC-CUBE integration test spec parser."""
import json, re, sys, os
from pathlib import Path

SPEC_DIR = Path("/tmp/eccube-spec/IntegrationTest")
OUT_FILE = Path("/Users/akihito/git/BeMart/docs/eccube-spec-coverage/all_items.json")


def parse_spec_file(filepath: Path) -> list[dict]:
    """Parse a single spec file into test items."""
    text = filepath.read_text(encoding="utf-8")
    area = filepath.stem[:4]  # e.g., "EF01", "EA03"

    items = []
    # Split by ## headings
    sections = re.split(r'\n(?=## )', text)
    for section in sections:
        m = re.match(r'##\s+(\S+)_(.+)', section)
        if not m:
            continue
        item_id = m.group(1)
        title = m.group(2).strip()

        # Extract numbered steps
        steps = re.findall(r'^\d+\.\s+(.+)$', section, re.MULTILINE)
        items.append({
            "area": area,
            "item_id": item_id,
            "title": title,
            "steps": steps,
        })
    return items


def main():
    all_items = []
    for fp in sorted(SPEC_DIR.glob("*.md")):
        items = parse_spec_file(fp)
        all_items.extend(items)
        print(f"{fp.stem}: {len(items)} items")

    OUT_FILE.parent.mkdir(parents=True, exist_ok=True)
    OUT_FILE.write_text(json.dumps(all_items, ensure_ascii=False, indent=2))
    print(f"\nTotal: {len(all_items)} items written to {OUT_FILE}")


if __name__ == "__main__":
    main()
