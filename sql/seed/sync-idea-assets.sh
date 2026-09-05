#!/usr/bin/env bash
#
# sync-idea-assets.sh — vendor IdeaStore design assets into BeMart.
#
# Copies the IdeaStore storefront assets (CSS, product/category/hero/editorial
# images) and brand logos into public/assets/idea-store/, the path the storefront
# templates reference (/assets/idea-store/...). Run this whenever the upstream
# IdeaStore design assets change. Idempotent.
#
# All current assets are already committed under public/assets/idea-store/; this
# script documents how they got there and re-syncs future updates.
#
# Source defaults to the sibling IdeaStore checkout; override with IDEA_REPO.
#
#   sql/seed/sync-idea-assets.sh
#   IDEA_REPO=/path/to/IdeaStore sql/seed/sync-idea-assets.sh
#
set -euo pipefail
cd "$(dirname "$0")/../.."

IDEA_REPO="${IDEA_REPO:-$(pwd)/../IdeaStore}"
SRC_ASSETS="$IDEA_REPO/assets"
SRC_LOGO="$IDEA_REPO/brand/logo"
DEST="public/assets/idea-store"

[[ -d "$SRC_ASSETS" ]] || { echo "sync-idea-assets: not found: $SRC_ASSETS (set IDEA_REPO)"; exit 1; }

echo "sync-idea-assets: source = $IDEA_REPO"
mkdir -p "$DEST/logo"

# CSS + images (product/category/hero/editorial). Note: idea-admin.css is
# BeMart-authored and lives under $DEST/css too — preserve it.
cp -R "$SRC_ASSETS/." "$DEST/"

# Brand logos live in IdeaStore/brand/logo, not assets/.
if [[ -d "$SRC_LOGO" ]]; then
    cp "$SRC_LOGO"/*.svg "$DEST/logo/" 2>/dev/null || true
fi

echo "sync-idea-assets: done. $(find "$DEST" -type f | wc -l | tr -d ' ') files, $(du -sh "$DEST" | cut -f1) under $DEST"
echo "sync-idea-assets: review with 'git status $DEST' and commit intentionally."
