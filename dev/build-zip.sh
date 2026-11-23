#!/usr/bin/env bash
# Crea dist/zonawrestling.zip (tema) e dist/zw-deploy.zip (plugin per il sito di prova).
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p dist && rm -rf dist/stage dist/zonawrestling.zip && mkdir -p dist/stage/zonawrestling
git ls-files --cached --others --exclude-standard | grep -vE '^(dev/|src/|package(-lock)?\.json|tailwind\.config\.js|\.gitignore|README\.md|CLAUDE\.md)' \
  | while read -r f; do mkdir -p "dist/stage/zonawrestling/$(dirname "$f")"; cp "$f" "dist/stage/zonawrestling/$f"; done
(cd dist/stage && zip -qr ../zonawrestling.zip zonawrestling)
(cd dev && zip -qr ../dist/zw-deploy.zip zw-deploy)
rm -rf dist/stage
ls -la dist
