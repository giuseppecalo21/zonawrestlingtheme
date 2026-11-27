# Tema Zona Wrestling — note per Claude

Tema WordPress classico (PHP + Tailwind compilato) che sostituirà tagDiv Newspaper su zonawrestling.net.
Leggi anche `README.md` (compatibilità con Newspaper, checklist del passaggio) e `dev/README.md` (sito di prova).

## Come lavorare con Giuseppe

- Scrivi sempre in italiano naturale: niente calchi dall'inglese, niente frasi da traduttore automatico.
- Criteri giornalistici: nessun fatto inventato, nessuna supposizione presentata come certezza. Se una cosa non è verificata, dillo.
- Prima di dire che qualcosa funziona, provalo sullo staging (HTML, screenshot, Lighthouse). Se non hai potuto provarlo, dillo chiaramente.
- Sul sito di produzione non si scrive nulla senza chiedere.

## Stato (23 novembre 2026)

- Versione **1.2.0**, caricata sullo staging (commit `d73bd9a`).
- Branch principale su GitHub: `claude/compassionate-goldberg-e71zvm` (non esiste `main`). Contiene tutto il lavoro: i vecchi branch `claude/...` sono superati.
- **Prima di iniziare**: fai il merge del branch principale nel branch della sessione. **Prima di caricare sullo staging**: controlla con `GET https://test.giuseppe.rocks/zonawrestling/wp-json/zw-deploy/v1/theme` quale commit c'è (`deployed.ref`) e assicurati che il tuo branch lo contenga, per non sovrascrivere lavoro più recente.
- A fine lavoro: commit, push del branch della sessione e, se Giuseppe lo chiede, avanzamento (fast-forward, mai forzato) del branch principale.

## Staging

- https://test.giuseppe.rocks/zonawrestling/ (WordPress su cPanel, `noindex`). Credenziali già gestite dal proxy dell'ambiente cloud: non vanno mai scritte nel codice o in chat.
- `./dev/deploy.sh` controlla, carica e verifica il tema (ripristina l'ultima versione buona se qualcosa si rompe). `npm run build` prima, se cambiano classi Tailwind.
- La password per le applicazioni vale solo per le API REST: wp-admin non si apre. Per provare pagine di amministrazione si è usato un plugin temporaneo caricato con `zw-deploy/v1/plugin`, poi disattivato e cancellato con `/wp/v2/plugins`.
- Sullo staging la home è "ultimi articoli" e i commenti sono chiusi; Gravatar e Clickio non sono raggiungibili dall'ambiente cloud.

## Cosa si sa della produzione (dall'HTML pubblico)

- Hosting managedserver.it con nginx e **Varnish**, più WP Rocket. Le immagini `.jpg` vengono servite come WebP dal server (stesso indirizzo).
- Home = pagina statica. Avatar degli autori = file locali, non Gravatar.
- Il **plugin custom Gemini/SEO** stampa meta description, Open Graph, X e uno `NewsArticle`. Newspaper stampa Open Graph doppi (difettosi) e il BreadcrumbList. Il tema stampa questi tag solo se mancano nell'`<head>` (`inc/seo.php`, `inc/schema.php`).
- Disqus 3.1.6 (il tema ne fa partire gli script vicino ai commenti), consenso Clickio + ProjectADV, AdSense, Prebid.
- Giuseppe può dare accesso alla produzione: servirà per adattare il tema al plugin eventi vero (ZW Events Calendar), ai suoi campi e alle categorie reali. Oggi lo staging usa un plugin sostituto (`dev/zw-events-stub/`).

## Fatto di recente

- **Affiliazione WWE Shop** (27/11/2026): plugin `plugins/zw-wwe-shop/` (v0.1.0, sullo staging) con prodotti a metà articolo scelti dai tag; catalogo da Impact (campagna WWE Shop, contratto attivo). Credenziali API Impact salvate nell'ambiente cloud (host `api.impact.com`), mai nel codice. Dettagli nel README del plugin.

- Stories in home configurabili: Aspetto → Stories in home (`inc/stories.php`). Gli stessi blocchi guidano i filtri del feed e i link del footer.
- Audit pre-lancio:
  - LCP della home: il carosello delle analisi scattava al caricamento e bloccava la misura (`scroll-px-6` in `analyses.php`);
  - font locali in `assets/fonts`;
  - tagli immagine `zw-card` (560) e `zw-wide` (1200);
  - slide dell'hero caricate dopo la pagina;
  - niente blur pesanti;
  - `content-visibility` su gallery, video e footer;
  - copertina al posto degli iframe di YouTube nel testo;
  - pulizia dei transient.

## Aperto

- WWE Shop: link verso EuroShop in inglese con euro (verificato da Giuseppe). Spedizione in Italia cara (circa 10 €, 10 giorni; circa 30 € espressa): chiedere a Fanatics eventuali promozioni sulla spedizione. Pagina impostazioni del plugin non ancora provata in wp-admin.

- Da verificare in produzione: banner Clickio con il reset CSS di Tailwind; che il preload LCP di WP Rocket usi la stessa variante del `srcset`; esclusione del cookie di anteprima da WP Rocket **e** da Varnish.
- Da decidere con Giuseppe: `noindex` sui tag con pochi articoli; copertina leggera anche per gli embed di X/Instagram; creazione di `main` e pulizia dei branch vecchi.
- Segnalato, ma è del plugin Gemini: nello `NewsArticle` il titolo contiene `&#8217;` e il publisher non ha il logo.
- Banner (Ad Inserter): `min-height` sugli slot sopra la piega e sidebar dell'articolo "solo desktop" con inserimento lato client (dettagli nel README).
