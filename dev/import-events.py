#!/usr/bin/env python3
"""Copia gli eventi (zw_event) dal sito vero al sito di prova.

Per ogni evento copia federazione, campi _zw_event_*, testo e immagine, e gli
ultimi articoli con i tag collegati, così sul sito di prova si vede anche il
blocco evento dentro gli articoli. Serve il tipo zw_event sul sito di prova
(plugin vero o zw-events-stub). Si può rilanciare: salta ciò che esiste già.
"""
import importlib.util
import os
import sys

spec = importlib.util.spec_from_file_location("ip", os.path.join(os.path.dirname(__file__), "import-posts.py"))
ip = importlib.util.module_from_spec(spec)
spec.loader.exec_module(ip)

POSTS_PER_TAG = 3
fed_map = {}


def ensure_federation(src_id):
    if src_id in fed_map:
        return fed_map[src_id]
    t = ip.get_json(ip.src, f"{ip.SRC}/zw_federation/{src_id}")
    found = ip.get_json(ip.dst, f"{ip.DST}/zw_federation", slug=t["slug"])
    if found:
        fed_map[src_id] = found[0]["id"]
    else:
        r = ip.dst.post(f"{ip.DST}/zw_federation", json={"name": t["name"], "slug": t["slug"]}, timeout=60)
        r.raise_for_status()
        fed_map[src_id] = r.json()["id"]
    return fed_map[src_id]


def all_events():
    page, out = 1, []
    while True:
        r = ip.src.get(f"{ip.SRC}/zw_event", params={"per_page": 100, "page": page, "_embed": 1}, timeout=60)
        if r.status_code == 400:
            break
        r.raise_for_status()
        out += r.json()
        if page >= int(r.headers.get("X-WP-TotalPages", 1)):
            break
        page += 1
    return out


def main():
    events = all_events()
    print(f"{len(events)} eventi")
    for e in events:
        meta = {k: v for k, v in (e.get("meta") or {}).items() if k.startswith("_zw_event_")}
        found = ip.get_json(ip.dst, f"{ip.DST}/zw_event", slug=e["slug"], status="any", context="edit")
        if found:
            print(f"evento già presente: {e['slug']}")
        else:
            emb = e.get("_embedded", {})
            media = ip.ensure_media((emb.get("wp:featuredmedia") or [None])[0], 0)
            r = ip.dst.post(f"{ip.DST}/zw_event", json={
                "title": e["title"]["rendered"], "slug": e["slug"], "status": "publish", "date": e["date"],
                "content": e["content"]["rendered"], "excerpt": e["excerpt"]["rendered"],
                "featured_media": media, "meta": meta,
                "zw_federation": [ensure_federation(f) for f in e.get("zw_federation", [])],
            }, timeout=120)
            if not r.ok:
                print(f"ERRORE evento {e['slug']}: {r.status_code} {r.text[:200]}", file=sys.stderr)
                continue
            print(f"evento ok: {e['title']['rendered']} ({meta.get('_zw_event_date')})")
        # Articoli collegati tramite tag.
        for slug in [s.strip() for s in meta.get("_zw_event_related_tags", "").split(",") if s.strip()]:
            tags = ip.get_json(ip.src, f"{ip.SRC}/tags", slug=slug)
            if not tags:
                continue
            for p in ip.get_json(ip.src, f"{ip.SRC}/posts", tags=tags[0]["id"], per_page=POSTS_PER_TAG, _embed=1):
                ip.import_post(p, slug)


if __name__ == "__main__":
    main()
