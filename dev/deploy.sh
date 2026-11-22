#!/usr/bin/env bash
# Carica il tema sul sito di prova (serve il plugin ZW Deploy e la password applicazione).
set -euo pipefail
cd "$(dirname "$0")/.."
SITE="${ZW_TEST_SITE:-https://test.giuseppe.rocks/zonawrestling}"
./dev/build-zip.sh >/dev/null
curl -sS --fail-with-body --max-time 120 -X POST -H "Content-Type: application/zip" \
  --data-binary @dist/zonawrestling.zip \
  "$SITE/wp-json/zw-deploy/v1/theme?activate=1&ref=$(git rev-parse --short HEAD)"
echo
