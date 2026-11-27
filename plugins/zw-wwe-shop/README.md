# ZW WWE Shop

Plugin (non fa parte del tema) che mostra a metà articolo un riquadro con 4 prodotti ufficiali di WWE Shop, scelti in base ai tag. I link sono di affiliazione: campagna **WWE Shop** su **Impact** (8% su Stati Uniti, Canada, Regno Unito, Europa e resto del mondo; cookie di 7 giorni).

## Come funziona

- **Catalogo**: ogni notte (WP-Cron, 4:17) scarica da Impact due cataloghi della campagna:
  - quello in euro (predefinito Spagna, `10418`) per prezzo, link affiliato e foto;
  - quello britannico (`10416`) per il nome inglese dello stesso prodotto.

  Non esiste un catalogo italiano. Il lavoro è diviso in passi da 25 secondi; i prodotti spariti vengono cancellati. Tabella `wp_zw_shop_products`, circa 11.000 righe.
- **Abbinamento**: ogni prodotto di Impact ha il wrestler nel campo `SubCategory`. Il plugin lo confronta con i tag dell'articolo, senza maiuscole, accenti e virgolette, aggiungendo le corrispondenze manuali. L'ordine è questo: prima i wrestler nel titolo, poi per prima comparsa nel testo. Show ed eventi (Raw, Survivor Series...) si usano solo se non c'è nessun wrestler. Le notizie senza corrispondenze (AEW, TNA, NJPW...) non hanno riquadro.
- **Scelta**: i prodotti si prendono a turno dai wrestler, a caso ma con la stessa scelta per tutto il giorno, così la pagina in cache resta coerente. Si scartano i doppioni (stessa maglia uomo/donna o in altri colori) e i prodotti non disponibili.
- **Posizione**: a metà dei paragrafi "di primo livello", cioè non dentro citazioni, embed, tabelle o liste. Mai prima del secondo paragrafo e mai subito dopo un paragrafo che finisce con i due punti. Negli articoli con meno di 3 paragrafi va in fondo.
- **Link**: `rel="sponsored nofollow noopener"`, nuova scheda, avviso di affiliazione sotto i prodotti. Le foto arrivano dal server di Fanatics a 300/600 px, con `loading="lazy"`. Il CSS (circa 2 KB) è in linea, solo negli articoli.
- **Nell'editor**: riquadro laterale "Prodotti WWE Shop" per spegnerlo in un articolo o scegliere i wrestler a mano.

## Messa in produzione

1. Caricare lo zip (`dist/zw-wwe-shop.zip`, creato da `dev/build-zip.sh`) da Plugin → Aggiungi nuovo → Carica, e attivarlo.
2. Impostazioni → WWE Shop: inserire **Account SID** e **Auth Token** di Impact (token con lettura su Catalogs) e premere "Aggiorna il catalogo adesso". Il resto dell'aggiornamento prosegue con WP-Cron: se WP-Cron è disattivato a favore del cron di sistema, il cron di sistema deve girare almeno ogni minuto o due.
3. Controllare un articolo WWE e svuotare la cache (WP Rocket + Varnish).

## Sito di prova

Lo staging non ha le credenziali di Impact: il catalogo lo carica `python3 dev/import-shop.py` tramite `POST /wp-json/zw-shop/v1/import` (solo amministratori).
