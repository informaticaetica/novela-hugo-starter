# contemporaryart.app

Da questo repository si gestiscono il sito WordPress contemporaryart.app (cartella `wordpress/`, più gli strumenti WordPress.com) e il gioco «Notte in galleria» (`static/galleria/`).

## Regole date dal proprietario

- **Scopo del sito**: promuovere la conoscenza dell'arte internazionale (13 nov 2026).
- **Ogni articolo ha due versioni, italiana e inglese, come articoli separati e abbinati**, con cura per la SEO (14 nov 2026: scelta del proprietario, dopo aver provato per un giorno le due lingue nella stessa pagina). Vale anche per le pagine (Info/About, Artisti/Artists, Contatti/Contact, Comincia da qui/Start here).
  - L'articolo inglese è un articolo a sé, creato con `meta: {"caa_versione_inglese_di": <ID dell'articolo italiano>}`: da quel campo il plugin ricava tutto il resto (elenchi separati, indice inglese sotto `/en/`, link fra le versioni, hreflang, menu nella lingua giusta).
  - Titolo, indirizzo (slug) e riassunto in inglese; stessa categoria e stessi tag dell'italiano; data un minuto dopo l'originale. Il riassunto (excerpt) va sempre compilato, in entrambe le versioni: diventa la descrizione per i motori di ricerca.
  - Traduzione fedele: stessi dati e stessi link dell'italiano.
  - Se arriva un articolo con le due lingue nella stessa pagina, va diviso: la parte inglese diventa l'articolo abbinato, quella italiana resta parola per parola.
  - Scelta mia, non del proprietario: le versioni inglesi si pubblicano con `_jetpack_dont_email_post_to_subs: true` e `jetpack_publicize_feature_enabled: false`, per non mandare due volte lo stesso articolo a iscritti e social.
- **Il gioco online non si aggiorna** (sul sito il plugin «Notte in galleria» è alla 1.0.0) finché il proprietario non lo dice (12 nov 2026).
- Sul sito, niente lunghe serie di modifiche una per una: il proprietario ha interrotto l'aggiornamento di 56 articoli uno alla volta. (Interpretazione mia: preferire una sola modifica al plugin.)

## Dove stanno le cose

- `wordpress/contemporary-art-veste/`: plugin del sito (veste grafica, «solo arte», due lingue, descrizioni per i motori). Si impacchetta con `wordpress/costruisci-veste.sh` e si prova con `wordpress/prove-veste/esegui.sh` prima di caricarlo.
- `wordpress/pagine/`: contenuto di pagine pubblicate a mano. `artisti.html` è la pagina Artisti; `manifesto-precedente.html` è la pagina «1 Start here» com'era prima della riscrittura del 14 nov 2026, da usare per ripristinarla.
- `wordpress/notte-in-galleria/` e `wordpress/costruisci-plugin.sh`: plugin del gioco.
