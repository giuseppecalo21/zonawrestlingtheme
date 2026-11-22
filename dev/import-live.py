#!/usr/bin/env python3
"""Copia gli aggiornamenti delle dirette (plugin WP Wrestling Live Pro) dal sito vero.

Gli aggiornamenti non stanno nel testo dell'articolo: il plugin li salva a parte,
quindi import-posts.py non li porta. Qui si leggono dall'API pubblica del plugin
sul sito vero (/zw-live/v1/updates/<id>) e si riscrivono sul sito di prova con la
stessa API (scrittura autenticata), nell'ordine in cui sono stati pubblicati.
Gli articoli si abbinano per slug. Le dirette che hanno già aggiornamenti sul sito
di prova vengono saltate. Gli orari degli aggiornamenti diventano quelli
dell'importazione: il plugin non permette di impostarli.
"""
import os
import sys
import time

import requests
from bs4 import BeautifulSoup

SRC = "https://zonawrestling.net/wp-json"
SITE = os.environ.get("ZW_TEST_SITE", "https://test.giuseppe.rocks/zonawrestling")
DST = SITE + "/wp-json"

src = requests.Session()
src.headers["User-Agent"] = "zw-theme-test-import"
dst = requests.Session()


def card_body(item):
    """HTML del corpo dell'aggiornamento (mantiene i paragrafi, a differenza di raw_text)."""
    soup = BeautifulSoup(item.get("html_card") or "", "html.parser")
    body = soup.select_one(".zw-live-card-body")
    if body and body.decode_contents().strip():
        return body.decode_contents().strip()
    return item.get("raw_text", "")


def live_posts():
    posts, page = [], 1
    while True:
        r = dst.get(DST + "/wp/v2/posts", params={"search": "DIRETTA", "per_page": 100, "page": page, "_fields": "id,slug,title"})
        if r.status_code != 200 or not r.json():
            break
        posts += [p for p in r.json() if p["title"]["rendered"].upper().startswith("DIRETTA")]
        if page >= int(r.headers.get("X-WP-TotalPages", 1)):
            break
        page += 1
    return posts


def main():
    for post in live_posts():
        have = dst.get(f"{DST}/zw-live/v1/updates/{post['id']}").json()
        if have.get("new_items"):
            print(f"= {post['slug']}: già importata")
            continue
        found = src.get(SRC + "/wp/v2/posts", params={"slug": post["slug"], "_fields": "id"}).json()
        if not found:
            print(f"? {post['slug']}: non trovata sul sito vero")
            continue
        data = src.get(f"{SRC}/zw-live/v1/updates/{found[0]['id']}").json()
        items = sorted(data.get("new_items", []), key=lambda i: (i.get("iso_date", ""), i["id"]))
        if not items:
            print(f"- {post['slug']}: nessun aggiornamento")
            continue
        for item in items:
            payload = {k: item.get(k, "") for k in ("title", "type", "winner", "label", "source", "ref")}
            payload["content"] = card_body(item)
            r = dst.post(f"{DST}/zw-live/v1/updates/{post['id']}", json=payload)
            if r.status_code >= 300:
                print(f"! {post['slug']}: errore {r.status_code} {r.text[:200]}")
                sys.exit(1)
            time.sleep(0.3)
        status = data.get("status") or "ended"
        dst.post(f"{DST}/zw-live/v1/status/{post['id']}", json={"status": status, "enabled": "1"})
        print(f"+ {post['slug']}: {len(items)} aggiornamenti, stato {status}")


if __name__ == "__main__":
    main()
