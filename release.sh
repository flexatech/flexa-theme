#!/usr/bin/env bash
#
# release.sh - Build a clean, distributable zip of the Flexa theme.
#
# Reads the version from style.css, copies the theme into a top-level
# `flexa/` folder (as WordPress expects), strips development files, and
# writes dist/flexa-<version>.zip.
#
# Usage: ./release.sh

set -euo pipefail

SLUG="flexa"
THEME_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DIST_DIR="${THEME_DIR}/dist"
BUILD_DIR="${DIST_DIR}/${SLUG}"

# Extract "Version:" from the style.css header.
VERSION="$(grep -iE '^\s*Version:' "${THEME_DIR}/style.css" | head -n1 | sed -E 's/.*Version:\s*//' | tr -d '[:space:]')"

if [[ -z "${VERSION}" ]]; then
	echo "Error: could not read Version from style.css" >&2
	exit 1
fi

ZIP_FILE="${DIST_DIR}/${SLUG}-${VERSION}.zip"

echo "Building ${SLUG} v${VERSION}..."

# Fresh build directory.
rm -rf "${BUILD_DIR}" "${ZIP_FILE}"
mkdir -p "${BUILD_DIR}"

# Copy the theme, excluding development and VCS files.
rsync -a --delete \
	--exclude='.git' \
	--exclude='.gitignore' \
	--exclude='.gitattributes' \
	--exclude='.svn' \
	--exclude='.claude' \
	--exclude='.DS_Store' \
	--exclude='node_modules' \
	--exclude='dist' \
	--exclude='docs' \
	--exclude='release.sh' \
	--exclude='*.map' \
	"${THEME_DIR}/" "${BUILD_DIR}/"

# Zip with a top-level flexa/ folder.
( cd "${DIST_DIR}" && zip -rq "${ZIP_FILE}" "${SLUG}" )

# Clean up the staged folder, keep only the zip.
rm -rf "${BUILD_DIR}"

echo "Created ${ZIP_FILE}"
