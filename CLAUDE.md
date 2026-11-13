# contemporaryart.app

Da questo repository si gestiscono il sito WordPress contemporaryart.app (cartella `wordpress/`, più gli strumenti WordPress.com) e il gioco «Notte in galleria» (`static/galleria/`).

## Regole date dal proprietario

- **Scopo del sito**: promuovere la conoscenza dell'arte internazionale (13 nov 2026).
- **Ogni articolo ha la versione italiana e quella inglese, nella stessa pagina**, con cura per la SEO (13 nov 2026: scelta esplicita, preferita a due articoli separati). Lo schema è quello della guida «Investire in arte contemporanea nel 2026» e dell'articolo su Kandinsky:
  - titolo «Titolo italiano | English title»;
  - in cima un link alla parte inglese: `<p class="caa-altra-lingua"><a href="#english">Read this article in English</a></p>`;
  - prima l'italiano, poi l'inglese dentro un blocco Gruppo con ancora `english` (il plugin gli dichiara `lang="en"`), con il titolo inglese in H2 e le sezioni in H3;
  - riassunto (excerpt) sempre compilato: diventa la descrizione per i motori di ricerca;
  - traduzione fedele: stessi dati e stessi link dell'italiano.
- **Il gioco online non si aggiorna** (sul sito il plugin «Notte in galleria» è alla 1.0.0) finché il proprietario non lo dice (12 nov 2026).
- Sul sito, niente lunghe serie di modifiche una per una: il proprietario ha interrotto l'aggiornamento di 56 articoli uno alla volta. (Interpretazione mia: preferire una sola modifica al plugin.)

## Dove stanno le cose

- `wordpress/contemporary-art-veste/`: plugin del sito (veste grafica, «solo arte», descrizioni per i motori). Si impacchetta con `wordpress/costruisci-veste.sh` e si prova con `wordpress/prove-veste/esegui.sh` prima di caricarlo.
- `wordpress/pagine/`: contenuto delle pagine pubblicate a mano (Artisti).
- `wordpress/notte-in-galleria/` e `wordpress/costruisci-plugin.sh`: plugin del gioco.
