#!/usr/bin/env python3
"""Carica il catalogo WWE Shop (Impact) nel plugin ZW WWE Shop del sito di prova.

Il sito di prova non ha le credenziali di Impact: le ha solo il proxy dell'ambiente
cloud (host api.impact.com). Lo script scarica qui i due cataloghi, quello in euro
(prezzi, link, foto) e quello britannico (nomi inglesi), e li invia a
POST /wp-json/zw-shop/v1/import. In produzione il plugin li scarica da solo ogni notte.

Uso: python3 dev/import-shop.py [catalogo_prezzi] [catalogo_nomi]   (default 10418 10416)
"""
import os
import re
import sys
import time

import requests

API = "https://api.impact.com"
SITE = os.environ.get("ZW_TEST_SITE", "https://test.giuseppe.rocks/zonawrestling")
OFFERS = sys.argv[1] if len(sys.argv) > 1 else "10418"
NAMES = sys.argv[2] if len(sys.argv) > 2 else "10416"
KEEP = ("CatalogItemId", "Name", "Category", "SubCategory", "CurrentPrice", "OriginalPrice",
        "Currency", "Url", "ImageUrl", "StockAvailability", "AgeGroup")

imp = requests.Session()
imp.headers["Accept"] = "application/json"
wp = requests.Session()


def account_sid():
    # Con un SID sbagliato Impact risponde indicando l'account autenticato.
    r = imp.get(f"{API}/Mediapartners/x/Catalogs", timeout=60)
    m = re.search(r"user\((\w+)\)", r.text)
    if not m:
        sys.exit(f"Impossibile ricavare l'account Impact: {r.status_code} {r.text[:200]}")
    return m.group(1)


def pages(sid, catalog):
    uri = f"/Mediapartners/{sid}/Catalogs/{catalog}/Items?PageSize=1000"
    while uri:
        r = imp.get(API + uri, timeout=120)
        r.raise_for_status()
        d = r.json()
        yield [{k: it.get(k, "") for k in KEEP} for it in d.get("Items", [])]
        uri = d.get("@nextpageuri")


def send(payload):
    for attempt in range(4):
        r = wp.post(f"{SITE}/wp-json/zw-shop/v1/import", json=payload, timeout=180)
        if r.ok:
            return r.json()
        print(f"  tentativo {attempt + 1}: HTTP {r.status_code} {r.text[:200]}", file=sys.stderr)
        time.sleep(3 * (attempt + 1))
    sys.exit("Invio fallito")


def main():
    sid = account_sid()
    run = time.strftime("%Y%m%d%H%M%S", time.gmtime())
    count = names = 0
    for items in pages(sid, OFFERS):
        count += send({"phase": "offers", "run": run, "items": items})["stored"]
        print(f"prezzi: {count}")
    for items in pages(sid, NAMES):
        names += send({"phase": "names", "run": run, "items": items})["updated"]
        print(f"nomi: {names}")
    print(send({"phase": "finish", "run": run, "count": count, "names": names}))


if __name__ == "__main__":
    main()
