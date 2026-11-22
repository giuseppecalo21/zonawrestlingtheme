#!/usr/bin/env python3
"""Copia sottotitolo e valutazione (campi tagDiv Newspaper) dagli articoli del sito vero.

L'API pubblica non espone `td_post_theme_settings`, quindi i valori si leggono
dall'HTML della pagina pubblica (stesso slug) e si scrivono sul sito di prova
con l'endpoint /zw-deploy/v1/meta del plugin ZW Deploy (v1.1+).
"""
import os
import re
import sys
import time

import requests
from bs4 import BeautifulSoup

SRC = "https://zonawrestling.net/wp-json/wp/v2"
SITE = os.environ.get("ZW_TEST_SITE", "https://test.giuseppe.rocks/zonawrestling")
DST = SITE + "/wp-json"

src = requests.Session()
src.headers["User-Agent"] = "zw-theme-test-import"
dst = requests.Session()

FIELDS = {"rate_stars": "p_review_stars", "rate_percent": "p_review_percents", "rate_point": "p_review_points"}


def num(text):
    m = re.search(r"\d+(?:[.,]\d+)?", text or "")
    return float(m.group(0).replace(",", ".")) if m else None


def parse(html):
    soup = BeautifulSoup(html, "html.parser")
    settings = {}
    sub = soup.select_one(".td-post-sub-title")
    if sub and sub.get_text(strip=True):
        settings["td_subtitle"] = sub.get_text(" ", strip=True)

    box = soup.select_one(".td-review")
    review_key = None
    if box:
        footer = box.select_one(".td-review-footer")
        classes = " ".join(footer.get("class", [])) if footer else ""
        rtype = next((t for t in FIELDS if t in classes), None)
        rows = []
        for row in box.select(".td-review-row-bars, .td-review-row-stars"):
            desc = row.select_one(".td-review-desc")
            desc = desc.get_text(" ", strip=True) if desc else ""
            if rtype == "rate_stars" or row.select("i[class*=td-icon-star]"):
                full = len(row.select("i.td-icon-star"))
                half = len(row.select("i.td-icon-star-half"))
                rate = full + 0.5 * half
                rtype = rtype or "rate_stars"
            else:
                rate = num(row.select_one(".td-review-percent").get_text() if row.select_one(".td-review-percent") else "")
            if rate is not None:
                rows.append({"desc": desc, "rate": rate})
        if rtype and rows:
            settings["has_review"] = rtype
            settings[FIELDS[rtype]] = rows
            summary = box.select_one(".td-review-summary-content")
            if summary:
                settings["review"] = summary.get_text(" ", strip=True)
            mx = {"rate_stars": 5, "rate_percent": 100, "rate_point": 10}[rtype]
            review_key = round(sum(r["rate"] for r in rows) / len(rows) / mx * 5, 1)
    return settings, review_key


def main():
    page = 1
    while True:
        r = dst.get(f"{DST}/wp/v2/posts", params={"per_page": 50, "page": page, "_fields": "id,slug"}, timeout=60)
        if r.status_code == 400:
            break
        r.raise_for_status()
        posts = r.json()
        if not posts:
            break
        for p in posts:
            live = src.get(f"{SRC}/posts", params={"slug": p["slug"], "_fields": "link"}, timeout=60).json()
            if not live:
                continue
            html = src.get(live[0]["link"], timeout=60).text
            settings, review_key = parse(html)
            if not settings:
                print(f"- {p['slug']}: niente da copiare")
                continue
            meta = {"td_post_theme_settings": settings}
            if review_key is not None:
                meta["td_review_key"] = review_key
            w = dst.post(f"{DST}/zw-deploy/v1/meta", json={"post": p["id"], "meta": meta}, timeout=60)
            if not w.ok:
                print(f"! {p['slug']}: {w.status_code} {w.text[:200]}", file=sys.stderr)
                continue
            print(f"+ {p['slug']}: {', '.join(k for k in settings)}")
            time.sleep(0.3)
        page += 1


if __name__ == "__main__":
    main()
