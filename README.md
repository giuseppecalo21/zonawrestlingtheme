# Tema Zona Wrestling

Tema WordPress di [ZonaWrestling.net](https://zonawrestling.net/). Tema classico (PHP), senza page builder e senza jQuery: un solo foglio di stile e un solo script, caricato in differita.

## Installazione

1. Comprimi questa cartella in uno zip (senza `node_modules`) e caricala da **Aspetto → Temi → Aggiungi → Carica tema**, oppure copiala in `wp-content/themes/zonawrestling`.
2. Prova prima su una copia del sito (staging). Quando attivi il tema, Newspaper si disattiva: i contenuti non vengono toccati.
3. **Aspetto → Personalizza → Zona Wrestling**: controlla gli slug delle categorie usate dalle sezioni e i link social/Google (i valori predefiniti sono quelli attuali del sito).
4. **Aspetto → Menu**: assegna un menu a «Menu principale (header)» e, se vuoi, a «Menu footer». Senza menu, l'header mostra le sezioni principali.
5. **Aspetto → Widget**: WordPress può spostare da solo i vecchi widget di Newspaper nelle due aree del tema («Sidebar articolo» e «Sidebar home»). Lascia solo quelli che servono (per esempio i banner).
6. **Articoli → Categorie**: nelle categorie mostrate come stories (WWE, AEW…) puoi caricare il logo che compare nel cerchio.

## Cosa resta compatibile

| Funzione | Come |
| --- | --- |
| Valutazioni delle analisi | Lette e scritte negli stessi campi di tagDiv Newspaper: `td_post_theme_settings` (`has_review`, `p_review_stars` / `p_review_percents` / `p_review_points`, `review`) e `td_review_key`. Nessuna migrazione. |
| Sottotitolo degli articoli | Campo `td_subtitle` di Newspaper. |
| Eventi | Plugin ZW Events Calendar: tipo `zw_event`, tassonomia `zw_federation`, campi `_zw_event_date`, `_zw_event_venue`, `_zw_event_city`, `_zw_event_related_tags`. |
| Stelline dei singoli match | Plugin Universal Star Rating (shortcode nel testo): resta attivo com'è. |
| Commenti | Plugin Disqus: il tema chiama il normale modulo commenti, che Disqus sostituisce. |
| Profili social degli autori | Stessi campi del profilo utente usati da Newspaper. |

## Da sistemare al passaggio da Newspaper

- **Banner inseriti dal pannello di Newspaper** (es. il riquadro in mezzo al testo): spariscono con il tema. Vanno ricreati in Ad Inserter. Il tema offre questi agganci (`do_action`): `zw_ad_after_header`, `zw_ad_home_after_hero`, `zw_ad_before_content`, `zw_ad_after_content`, `zw_ad_archive_inline`.
- **Vecchio riquadro «Seguici su Google»** (`zw-google-banner`): il tema ha già i due pulsanti discreti a fine articolo, quindi il vecchio blocco va disattivato dove è inserito oggi.
- **Home**: il tema usa `front-page.php`, quindi la pagina costruita con tagDiv Composer non viene più mostrata.
- **Dirette**: riconosciute dal titolo che inizia con «DIRETTA LIVE» (prefisso modificabile; filtro `zw_is_live`).

## Sviluppo

Gli stili sono scritti con Tailwind e compilati in `assets/css/main.css` (il file compilato è nel repository: sul server non serve Node).

```bash
npm install
```

```bash
npm run build
```

`npm run watch` ricompila a ogni modifica. Se aggiungi classi Tailwind nuove nei file PHP, ricompila.

## Struttura

- `front-page.php` + `template-parts/home/` — sezioni della home
- `single.php` + `template-parts/single/` — articolo (blocco evento, autore, pulsanti Google)
- `single-zw_event.php`, `archive-zw_event.php` — eventi
- `archive.php`, `search.php`, `index.php`, `page.php`, `404.php`
- `inc/` — logica: impostazioni, valutazioni, eventi, stories, più lette, feed
