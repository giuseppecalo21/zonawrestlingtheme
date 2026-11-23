# Tema Zona Wrestling

Tema WordPress di [ZonaWrestling.net](https://zonawrestling.net/). Tema classico (PHP), senza page builder e senza jQuery: un solo foglio di stile e un solo script, caricato in differita.

## Installazione

1. Comprimi questa cartella in uno zip (senza `node_modules`) e caricala da **Aspetto → Temi → Aggiungi → Carica tema**, oppure copiala in `wp-content/themes/zonawrestling`.
2. Prova prima su una copia del sito (staging). Quando attivi il tema, Newspaper si disattiva: i contenuti non vengono toccati.
3. **Aspetto → Personalizza → Zona Wrestling**: controlla gli slug delle categorie usate dalle sezioni e i link social/Google (i valori predefiniti sono quelli attuali del sito).
4. **Aspetto → Personalizza → Identità del sito → Logo**: carica il logo (versione per sfondo scuro, come l'attuale bianco e rosso, possibilmente senza margini vuoti). Compare in header e footer; senza logo resta la scritta «ZONAWRESTLING.NET».
5. **Aspetto → Menu**: assegna un menu a «Menu principale (header)» e, se vuoi, a «Menu footer». Senza menu, l'header mostra le sezioni principali.
6. **Aspetto → Widget**: WordPress può spostare da solo i vecchi widget di Newspaper nelle due aree del tema («Sidebar articolo» e «Sidebar home»). Lascia solo quelli che servono (per esempio i banner).
7. **Aspetto → Stories in home**: crea un blocco per ogni cerchio delle stories (categoria, nome, immagine, eventuale periodo di visibilità) e mettili nell'ordine voluto. L'immagine può essere un logo PNG trasparente (resta intero) o una foto piena (ritagliata nel cerchio); il testo alternativo è sempre il nome della categoria. Gli stessi blocchi diventano i filtri di "Ultime notizie" e i link del footer. Finché non crei blocchi, si usano gli slug indicati in Personalizza e il logo caricato in **Articoli → Categorie**.

## Cosa resta compatibile

| Funzione | Come |
| --- | --- |
| Valutazioni delle analisi | Lette e scritte negli stessi campi di tagDiv Newspaper: `td_post_theme_settings` (`has_review`, `p_review_stars` / `p_review_percents` / `p_review_points`, `review` per le considerazioni finali, il «Sommario» di Newspaper) e `td_review_key`. Nessuna migrazione. |
| Sottotitolo degli articoli | Campo `td_subtitle` di Newspaper. Se è vuoto non compare nulla (nessun ripiego sul riassunto). |
| Eventi | Plugin ZW Events Calendar: tipo `zw_event`, tassonomia `zw_federation`, campi `_zw_event_date`, `_zw_event_venue`, `_zw_event_city`, `_zw_event_related_tags`. Nella pagina evento il tema impagina da sé scheda e notizie (i riquadri del plugin vengono tolti) e mette la guida all'evento prima dell'elenco delle notizie. |
| Stelline dei singoli match | Plugin Universal Star Rating (shortcode nel testo): resta attivo com'è. |
| Commenti | Plugin Disqus: il tema chiama il normale modulo commenti, che Disqus sostituisce. I suoi script partono solo quando il lettore si avvicina ai commenti. |
| Profili social degli autori | Stessi campi del profilo utente usati da Newspaper. |

## Da sistemare al passaggio da Newspaper

- **Banner inseriti dal pannello di Newspaper** (es. il riquadro in mezzo al testo): spariscono con il tema. Vanno ricreati in Ad Inserter. Il tema offre questi agganci (`do_action`): `zw_ad_after_header`, `zw_ad_home_after_hero`, `zw_ad_before_content`, `zw_ad_after_content`, `zw_ad_archive_inline`.
- **Vecchio riquadro «Seguici su Google»** (`zw-google-banner`): il tema ha già i due pulsanti discreti a fine articolo, quindi il vecchio blocco va disattivato dove è inserito oggi.
- **Home**: il tema usa `front-page.php`, quindi la pagina costruita con tagDiv Composer non viene più mostrata.
- **Dirette**: riconosciute dal titolo che inizia con «DIRETTA LIVE» (prefisso modificabile; filtro `zw_is_live`).
- **SEO e dati strutturati**: il tema stampa meta description, Open Graph, X e JSON-LD (NewsArticle, testata, autori) **solo se mancano** nell'`<head>`: con il plugin SEO del sito attivo non crea doppioni. Spegnendo Newspaper spariscono i suoi Open Graph doppi; il BreadcrumbList lo stampa il tema.
- **Altezza riservata ai banner** (CLS): in Ad Inserter, nello stile di ogni blocco agganciato a `zw_ad_after_header` e `zw_ad_before_content` (sopra la piega), impostare un `min-height` pari all'altezza del formato più alto servito su quel dispositivo (es. 100px per 320×100, 250px per 300×250), con blocchi separati mobile/desktop.
- **Sidebar dell'articolo**: su mobile è nascosta. I banner messi nell'area «Sidebar articolo» vanno impostati in Ad Inserter come «solo desktop» con inserimento lato client (*client-side insert*, non *show/hide*), altrimenti vengono caricati anche su mobile senza essere visti.
- **Anteprima privata sul dominio vero**: il cookie usato per vedere il nuovo tema va escluso dalla cache di WP Rocket (*Never cache cookies*) **e** da Varnish dell'hosting (chiedere a managedserver.it), prima di condividere il link.
- **Cartella del tema**: `wp-content/themes/zonawrestling` (lo zip di `dev/build-zip.sh` ha già questo nome ed esclude i file di sviluppo). Cambiare nome alla cartella in futuro farebbe perdere menu, widget e impostazioni del Personalizza.
- **Tagli delle immagini**: il tema registra `zw-card` (560 px) e `zw-wide` (1200 px), senza ritaglio. Valgono per i nuovi caricamenti; rigenerare le miniature dell'archivio non è necessario (le immagini vecchie usano i tagli già presenti, compresi quelli di Newspaper).

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
- `inc/` — logica: impostazioni, valutazioni, eventi, stories, più lette, feed, SEO e dati strutturati
- `assets/fonts/` — font del tema (Sofia Sans, Sofia Sans Condensed, Chivo; licenza SIL OFL), niente Google Fonts
