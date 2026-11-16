#!/usr/bin/env bash
set -euo pipefail

SLUG="hi-theme-toolkit"
SVN_URL="https://plugins.svn.wordpress.org/${SLUG}"
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SVN_DIR="${SVN_DIR:-${PLUGIN_DIR}/../${SLUG}-svn}"
MAIN_FILE="${PLUGIN_DIR}/wordpress-toolkit-plugin.php"
ASSETS_SRC="${PLUGIN_DIR}/screenshots"
SVN_USER="${1:-${SVN_USERNAME:-}}"

die() { echo "Error: $*" >&2; exit 1; }

command -v svn >/dev/null || die "svn not found (brew install subversion)"
command -v rsync >/dev/null || die "rsync not found"

VERSION="$(grep -m1 -E '^\s*\*\s*Version:' "$MAIN_FILE" | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
STABLE_TAG="$(grep -m1 -E '^Stable tag:' "${PLUGIN_DIR}/readme.txt" | sed -E 's/^Stable tag:[[:space:]]*//' | tr -d '[:space:]')"
CONST_VERSION="$(grep -m1 "HITHTO_VERSION'," "$MAIN_FILE" | sed -E "s/.*HITHTO_VERSION',[[:space:]]*'([^']+)'.*/\1/")"

[ -n "$VERSION" ] || die "cannot read Version from $(basename "$MAIN_FILE")"
[ "$VERSION" = "$STABLE_TAG" ] || die "Version ($VERSION) != readme.txt Stable tag ($STABLE_TAG)"
[ "$VERSION" = "$CONST_VERSION" ] || die "Version ($VERSION) != HITHTO_VERSION ($CONST_VERSION)"

if [ -n "$(git -C "$PLUGIN_DIR" status --porcelain)" ]; then
	read -r -p "Git working tree is not clean. Continue anyway? [y/N] " answer
	[ "$answer" = "y" ] || exit 1
fi

echo "Deploying ${SLUG} ${VERSION}"
echo "SVN working copy: ${SVN_DIR}"

if [ -d "${SVN_DIR}/.svn" ]; then
	svn update "$SVN_DIR"
else
	svn checkout "$SVN_URL" "$SVN_DIR"
fi

[ ! -d "${SVN_DIR}/tags/${VERSION}" ] || die "tag ${VERSION} already exists in SVN, bump the version first"

mkdir -p "${SVN_DIR}/trunk" "${SVN_DIR}/assets" "${SVN_DIR}/tags"

rsync -a --delete \
	--exclude='.git' \
	--exclude='.gitignore' \
	--exclude='.DS_Store' \
	--exclude='.zip-staging' \
	--exclude='*.zip' \
	--exclude='node_modules' \
	--exclude='package.json' \
	--exclude='package-lock.json' \
	--exclude='bin' \
	--exclude='docs' \
	--exclude='screenshots' \
	--exclude='CLAUDE.md' \
	--exclude='.claude' \
	"${PLUGIN_DIR}/" "${SVN_DIR}/trunk/"

if [ -d "$ASSETS_SRC" ]; then
	for file in "$ASSETS_SRC"/screenshot*.*; do
		[ -e "$file" ] || continue
		name="$(basename "$file")"
		num="$(echo "$name" | sed -E 's/^screenshot-?0*([0-9]+)\..*/\1/')"
		ext="${name##*.}"
		cp "$file" "${SVN_DIR}/assets/screenshot-${num}.${ext}"
	done
	for file in "$ASSETS_SRC"/banner-* "$ASSETS_SRC"/icon-*; do
		[ -e "$file" ] || continue
		cp "$file" "${SVN_DIR}/assets/"
	done
fi

cd "$SVN_DIR"

{ svn status | grep -E '^\?' || true; } | cut -c9- | while IFS= read -r path; do svn add --parents "$path" >/dev/null; done
{ svn status | grep -E '^!' || true; } | cut -c9- | while IFS= read -r path; do svn delete "$path" >/dev/null; done

for file in assets/*; do
	[ -e "$file" ] || continue
	case "$file" in
		*.png) svn propset svn:mime-type image/png "$file" >/dev/null ;;
		*.jpg|*.jpeg) svn propset svn:mime-type image/jpeg "$file" >/dev/null ;;
		*.gif) svn propset svn:mime-type image/gif "$file" >/dev/null ;;
		*.svg) svn propset svn:mime-type image/svg+xml "$file" >/dev/null ;;
	esac
done

svn copy trunk "tags/${VERSION}" >/dev/null

svn status
echo
read -r -p "Commit release ${VERSION} to ${SVN_URL}? [y/N] " answer
[ "$answer" = "y" ] || { echo "Aborted. Changes are left in ${SVN_DIR} (undo with: cd ${SVN_DIR} && svn revert -R . && rm -rf tags/${VERSION})."; exit 1; }

if [ -n "$SVN_USER" ]; then
	svn commit -m "Release ${VERSION}" --username "$SVN_USER"
else
	svn commit -m "Release ${VERSION}"
fi

echo "Released ${SLUG} ${VERSION}"
