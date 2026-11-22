#!/usr/bin/env bash
# Carica il tema sul sito di prova (serve il plugin ZW Deploy e la password applicazione).
# Prima controlla la sintassi PHP e che ogni file richiesto sia nello zip; dopo il
# caricamento verifica che home e un articolo rispondano: se no, rimette l'ultima
# versione funzionante (dist/last-good.zip).
set -euo pipefail
cd "$(dirname "$0")/.."
SITE="${ZW_TEST_SITE:-https://test.giuseppe.rocks/zonawrestling}"
./dev/build-zip.sh >/dev/null

# Controlli prima di caricare.
TMP=$(mktemp -d)
unzip -q dist/zonawrestling.zip -d "$TMP"
for f in $(find "$TMP" -name '*.php'); do php -l "$f" >/dev/null || { echo "Errore di sintassi in $f"; exit 1; }; done
for req in $(grep -ohE "require ZW_DIR \. '[^']+'" "$TMP/zonawrestling/functions.php" | sed -E "s/.*'(.*)'/\1/"); do
  [ -f "$TMP/zonawrestling$req" ] || { echo "Manca nello zip: $req"; exit 1; }
done
rm -r "$TMP"

upload() {
  curl -sS --fail-with-body --max-time 120 -X POST -H "Content-Type: application/zip" \
    --data-binary @"$1" "$SITE/wp-json/zw-deploy/v1/theme?activate=1&ref=$2"
  echo
}
check() {
  local ok=1
  for u in "$SITE/" "$SITE/?p=$(curl -sS "$SITE/wp-json/wp/v2/posts?per_page=1&_fields=id" | grep -oE '[0-9]+' | head -1)"; do
    code=$(curl -sS -o /dev/null -w '%{http_code}' --max-time 60 "$u")
    [ "$code" = "200" ] || [ "$code" = "301" ] || { echo "ERRORE: $u risponde $code"; ok=0; }
  done
  [ $ok = 1 ]
}

upload dist/zonawrestling.zip "$(git rev-parse --short HEAD)"
if check; then
  cp dist/zonawrestling.zip dist/last-good.zip
  echo "Sito OK."
else
  if [ -f dist/last-good.zip ]; then
    echo "Ripristino l'ultima versione funzionante..."
    upload dist/last-good.zip rollback
  fi
  exit 1
fi
