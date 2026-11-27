#!/usr/bin/env bash
# Crea dist/zonawrestling.zip (tema), dist/zw-deploy.zip (plugin per il sito di prova) e dist/zw-wwe-shop.zip.
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p dist && rm -rf dist/stage dist/zonawrestling.zip && mkdir -p dist/stage/zonawrestling
git ls-files --cached --others --exclude-standard | grep -vE '^(dev/|plugins/|src/|package(-lock)?\.json|tailwind\.config\.js|\.gitignore|README\.md|CLAUDE\.md)' \
  | while read -r f; do mkdir -p "dist/stage/zonawrestling/$(dirname "$f")"; cp "$f" "dist/stage/zonawrestling/$f"; done
(cd dist/stage && zip -qr ../zonawrestling.zip zonawrestling)
(cd dev && zip -qr ../dist/zw-deploy.zip zw-deploy)
(cd plugins && rm -f ../dist/zw-wwe-shop.zip && zip -qr ../dist/zw-wwe-shop.zip zw-wwe-shop)
rm -rf dist/stage
ls -la dist
