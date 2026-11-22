# Sito di prova

Strumenti per provare il tema su **https://test.giuseppe.rocks/zonawrestling/** (WordPress su cPanel, escluso dai motori di ricerca). Questa cartella non finisce nello zip del tema.

## Accesso

- L'utente amministratore del sito di prova ha una **password per le applicazioni**, salvata come segreto di rete dell'ambiente cloud (host `test.giuseppe.rocks`): il proxy la aggiunge da solo alle richieste, non va mai scritta nel codice o in chat.
- Sul sito è attivo il plugin **ZW Deploy** (`dev/zw-deploy/`, oggi v1.3), che aggiunge:
  - `POST /wp-json/zw-deploy/v1/theme?activate=1` — carica lo zip del tema;
  - `POST /wp-json/zw-deploy/v1/plugin?slug=…&activate=1` — installa o aggiorna un plugin (anche se stesso);
  - `GET|POST /wp-json/zw-deploy/v1/meta` — legge e scrive campi personalizzati di un post;
  - nelle sue richieste il tema non viene caricato: anche con un tema rotto si può sempre ricaricarne uno buono.
- Sul sito è attivo anche **ZW Events (sostituto)** (`dev/zw-events-stub/`): registra `zw_event`, `zw_federation` e i campi `_zw_event_*` come il plugin vero ZW Events Calendar, e aggiunge al testo i riquadri "Dettagli evento" / "Tutte le news" con lo stesso HTML. In produzione resta il plugin vero.

## Comandi

```bash
./dev/deploy.sh            # controlla sintassi e file, carica il tema, verifica il sito; se si rompe rimette dist/last-good.zip
./dev/build-zip.sh         # solo gli zip (dist/zonawrestling.zip, dist/zw-deploy.zip)
python3 dev/import-posts.py   # copia articoli recenti da zonawrestling.net (salta quelli già presenti)
python3 dev/import-meta.py    # copia sottotitoli e valutazioni (td_post_theme_settings) dalle pagine pubbliche
python3 dev/import-events.py  # copia gli eventi pubblici e gli articoli con i tag collegati
```

Dopo aver modificato classi Tailwind nei template: `npm run build` prima del deploy.

## Contenuti di prova

Circa 140 articoli veri di zonawrestling.net (sezioni della home, sottocategorie di News e Show Analysis, articoli collegati agli eventi), 10 eventi. Inizio in Italia (`_zw_event_start_it`) impostato solo dove il testo dell'evento lo riporta: Survivor Series 2026 (29/11 00:00) e NXT TLC 2026 (13/12 01:00).

## Da sapere

- Durata e visualizzazioni dei video YouTube richiedono la chiave della YouTube Data API v3 (Personalizza → Zona Wrestling → Video): dai server di hosting YouTube blocca le altre strade (`LOGIN_REQUIRED`). Titolo e canale arrivano comunque dall'oEmbed.
- In cPanel può essere rimasta la cartella `wp-content/themes/zonawrestling-off` (vecchia copia rotta): si può eliminare.
