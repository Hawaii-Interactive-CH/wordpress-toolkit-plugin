#!/usr/bin/env bash
# Install/update Toolkit Claude skills into ~/.claude/commands/toolkit/
# Run from anywhere: bash /path/to/wordpress-toolkit-plugin/bin/install-skills.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_DIR="$SCRIPT_DIR/../.claude/commands"
TARGET_DIR="$HOME/.claude/commands/toolkit"

if [ ! -d "$SOURCE_DIR" ]; then
    echo "Error: source directory not found: $SOURCE_DIR" >&2
    exit 1
fi

mkdir -p "$TARGET_DIR"

count=0
for src in "$SOURCE_DIR"/*.md; do
    [ -e "$src" ] || continue
    filename="$(basename "$src")"
    dest="$TARGET_DIR/$filename"
    cp "$src" "$dest"
    echo "  installed: toolkit/$filename"
    count=$((count + 1))
done

echo ""
echo "$count skill(s) installed to $TARGET_DIR"
