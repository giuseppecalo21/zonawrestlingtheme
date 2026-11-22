#!/usr/bin/env python3
"""Copia articoli recenti da zonawrestling.net al sito di prova.

Legge l'API pubblica del sito vero e ricrea sul sito di prova categorie,
tag, autori, immagini in evidenza e articoli (stesso slug e stessa data).
Se un articolo con lo stesso slug esiste già, lo salta: si può rilanciare.

Autenticazione verso il sito di prova: password per le applicazioni,
aggiunta dal proxy dell'ambiente (lo script non la conosce).
"""
import os
import secrets
import sys
import time
from datetime import datetime, timedelta

import requests

SRC = "https://zonawrestling.net/wp-json/wp/v2"
DST = os.environ.get("ZW_TEST_SITE", "https://test.giuseppe.rocks/zonawrestling") + "/wp-json/wp/v2"

# Quanti articoli prendere per categoria (slug -> numero).
PLAN = {
    "featured": 8, "risultati": 6, "show-analysis": 6, "editoriali": 6,
    "gallery": 4, "videonews": 4, "podcast": 4,
    "wwe": 4, "aew": 4, "njpw": 3, "tna": 3, "stardom": 3, "cmll": 3, "aaa": 3, "italia": 4,
}
LATEST = 20  # più gli ultimi N articoli in assoluto

# Categorie madri di cui si usano le sottocategorie (gli articoli stanno lì):
# slug madre -> (più recenti fra tutte le figlie, più recenti per ogni figlia).
# Per ogni figlia si tengono solo articoli dell'ultimo anno (salta i programmi chiusi).
CHILDREN = {"news": (40, 3), "show-analysis": (12, 2)}
RECENT_DAYS = 365

src = requests.Session()
src.headers["User-Agent"] = "zw-theme-test-import"
dst = requests.Session()

cat_map, tag_map, user_map, media_map = {}, {}, {}, {}


def get_json(session, url, **params):
    r = session.get(url, params=params, timeout=60)
    r.raise_for_status()
    return r.json()


def dst_find(endpoint, slug):
    res = get_json(dst, f"{DST}/{endpoint}", slug=slug, context="edit", status="any" if endpoint == "posts" else None)
    return res[0] if res else None


def ensure_category(src_id):
    if src_id in cat_map:
        return cat_map[src_id]
    c = get_json(src, f"{SRC}/categories/{src_id}")
    parent = ensure_category(c["parent"]) if c["parent"] else 0
    found = dst_find("categories", c["slug"])
    if found:
        cat_map[src_id] = found["id"]
    else:
        r = dst.post(f"{DST}/categories", json={
            "name": c["name"], "slug": c["slug"], "description": c["description"], "parent": parent,
        }, timeout=60)
        r.raise_for_status()
        cat_map[src_id] = r.json()["id"]
    return cat_map[src_id]


def ensure_tags(terms):
    ids = []
    for t in terms:
        if t["id"] not in tag_map:
            found = dst_find("tags", t["slug"])
            if found:
                tag_map[t["id"]] = found["id"]
            else:
                r = dst.post(f"{DST}/tags", json={"name": t["name"], "slug": t["slug"]}, timeout=60)
                r.raise_for_status()
                tag_map[t["id"]] = r.json()["id"]
        ids.append(tag_map[t["id"]])
    return ids


def ensure_author(a):
    slug = a.get("slug") or "redazione"
    if slug in user_map:
        return user_map[slug]
    found = get_json(dst, f"{DST}/users", slug=slug, context="edit")
    if found:
        user_map[slug] = found[0]["id"]
    else:
        r = dst.post(f"{DST}/users", json={
            "username": slug, "slug": slug, "name": a.get("name") or slug,
            "nickname": a.get("name") or slug, "description": a.get("description", ""),
            "email": f"{slug}@example.invalid", "password": secrets.token_urlsafe(24),
            "roles": ["author"],
        }, timeout=60)
        r.raise_for_status()
        user_map[slug] = r.json()["id"]
    return user_map[slug]


def ensure_media(m, author_id):
    if not m or "source_url" not in m:
        return 0
    if m["id"] in media_map:
        return media_map[m["id"]]
    sizes = (m.get("media_details") or {}).get("sizes") or {}
    url = m["source_url"]  # originale: WordPress rigenera i formati del tema
    img = src.get(url, timeout=120)
    img.raise_for_status()
    name = url.rsplit("/", 1)[-1].split("?")[0]
    r = dst.post(f"{DST}/media", data=img.content, headers={
        "Content-Disposition": f'attachment; filename="{name}"',
        "Content-Type": img.headers.get("Content-Type", "image/jpeg"),
    }, timeout=180)
    r.raise_for_status()
    mid = r.json()["id"]
    dst.post(f"{DST}/media/{mid}", json={
        "alt_text": m.get("alt_text", ""), "caption": (m.get("caption") or {}).get("rendered", ""),
        "author": author_id,
    }, timeout=60)
    media_map[m["id"]] = mid
    return mid


def collect():
    posts = {}
    for p in get_json(src, f"{SRC}/posts", per_page=LATEST, _embed=1):
        posts[p["id"]] = p
    for slug, n in PLAN.items():
        cats = get_json(src, f"{SRC}/categories", slug=slug)
        if not cats:
            print(f"! categoria {slug} non trovata", file=sys.stderr)
            continue
        for p in get_json(src, f"{SRC}/posts", categories=cats[0]["id"], per_page=n, _embed=1):
            posts.setdefault(p["id"], p)
        time.sleep(0.3)
    cutoff = (datetime.now() - timedelta(days=RECENT_DAYS)).isoformat()
    for slug, (top, per_child) in CHILDREN.items():
        parent = get_json(src, f"{SRC}/categories", slug=slug)[0]["id"]
        kids = get_json(src, f"{SRC}/categories", parent=parent, per_page=100)
        ids = ",".join(str(k["id"]) for k in kids)
        for p in get_json(src, f"{SRC}/posts", categories=ids, per_page=top, _embed=1):
            posts.setdefault(p["id"], p)
        for k in kids:
            for p in get_json(src, f"{SRC}/posts", categories=k["id"], per_page=per_child, _embed=1, after=cutoff):
                posts.setdefault(p["id"], p)
            time.sleep(0.2)
    return sorted(posts.values(), key=lambda p: p["date"])


def main():
    posts = collect()
    print(f"{len(posts)} articoli da copiare")
    for i, p in enumerate(posts, 1):
        if dst_find("posts", p["slug"]):
            print(f"[{i}] già presente: {p['slug']}")
            continue
        emb = p.get("_embedded", {})
        author = ensure_author((emb.get("author") or [{}])[0])
        cats = [ensure_category(c) for c in p["categories"]]
        tags = []
        for group in emb.get("wp:term", []):
            tags += [t for t in group if t.get("taxonomy") == "post_tag"]
        media = ensure_media((emb.get("wp:featuredmedia") or [None])[0], author)
        r = dst.post(f"{DST}/posts", json={
            "title": p["title"]["rendered"], "slug": p["slug"], "status": "publish",
            "date": p["date"], "content": p["content"]["rendered"], "excerpt": p["excerpt"]["rendered"],
            "author": author, "categories": cats, "tags": ensure_tags(tags),
            "featured_media": media, "format": p.get("format", "standard"),
            "comment_status": "closed",
        }, timeout=120)
        if not r.ok:
            print(f"[{i}] ERRORE {r.status_code} {p['slug']}: {r.text[:200]}", file=sys.stderr)
            continue
        print(f"[{i}] ok: {p['title']['rendered'][:70]}")
        time.sleep(0.3)


if __name__ == "__main__":
    main()
