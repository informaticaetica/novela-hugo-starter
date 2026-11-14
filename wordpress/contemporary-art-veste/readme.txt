=== Contemporary Art — veste grafica ===
Requires at least: 5.6
Tested up to: 6.9
Stable tag: 1.5.0
License: MIT

Veste "white cube" sopra il tema Koji, interruttore "solo arte", articoli in italiano e in inglese.

== Descrizione ==

Cinque spunte, regolabili da Impostazioni → Contemporary Art:

* Veste grafica: fondo bianco, testo nero, titoli grandi, linee sottili, accento blu Klein. Gli articoli senza copertina diventano cartelli tipografici.
* Solo arte: nasconde da home, archivi, ricerca (anche con Relevanssi), feed, correlati (del tema e di Jetpack), «articolo precedente/successivo», indici e menu gli articoli scritti prima del 2026 (salvo quelli nelle categorie mostre, arte, artisti, opere, musei, gallerie), le categorie rimaste senza articoli visibili e le pagine del vecchio blog. Chi ha l'indirizzo esatto di un vecchio articolo lo legge ancora.
* Nome al posto del logo: il nome del sito composto in caratteri prende il posto dell'immagine del logo (che resta salvata nel tema).
* Fuori dai motori di ricerca: ciò che «Solo arte» nasconde riceve l'indicazione «noindex» ed esce dalla mappa del sito di Yoast SEO; gli indirizzi continuano a funzionare.
* Due lingue: la versione inglese di un articolo o di una pagina è un contenuto a sé, abbinato all'originale italiano. In italiano si vedono solo i contenuti italiani; quelli inglesi stanno sotto /en/ (indice, categorie, tag, ricerca, feed). Le due versioni si rimandano l'una all'altra con un link in cima al testo, con la voce «English / Italiano» nel menu e, per i motori di ricerca, con i tag hreflang; le pagine inglesi dichiarano la loro lingua e mostrano l'interfaccia del tema in inglese.

Nelle stesse impostazioni si scrivono le due frasi (italiana e inglese) che dicono che cos'è il sito: diventano la descrizione della home per i motori di ricerca. Gli articoli senza una descrizione scritta a mano usano il riassunto.

== Come si abbina una versione inglese ==

L'articolo (o la pagina) in inglese porta nel campo personalizzato «caa_versione_inglese_di» l'ID dell'originale italiano. Il campo è registrato anche per l'API REST di WordPress: chi crea l'articolo da fuori lo passa in «meta». Basta questo: elenchi, link, menu e hreflang discendono da lì.

La classe «caa-indice» su un blocco Gruppo compone titoli e liste come un indice a colonne (pagina Artisti).

Non cancella e non modifica nessun contenuto. Disattivando il plugin il sito torna esattamente com'era.

== Changelog ==

= 1.5.0 =
* Le due lingue tornano accese in partenza: ogni articolo ha la sua versione inglese separata. Tolto il supporto alle due lingue nella stessa pagina.

= 1.4.0 =
* Le versioni inglesi separate diventano una scelta, spenta in partenza: gli articoli portano le due lingue nella stessa pagina.
* La parte inglese di un articolo (ancora «english») dichiara la sua lingua.

= 1.3.1 =
* Indirizzi canonici, hreflang e link fra le lingue sempre in https, anche se l'indirizzo salvato nelle impostazioni di WordPress è in http.

= 1.3.0 =
* Due lingue: versioni inglesi abbinate, indirizzi sotto /en/, hreflang, link fra le versioni, menu e interfaccia nella lingua della pagina.
* Descrizione per i motori di ricerca: frase sullo scopo del sito per la home (italiana e inglese), riassunto per gli articoli; indirizzo canonico sempre in https.

= 1.2.0 =
* Nome del sito in caratteri al posto del logo.
* Contenuti nascosti fuori dai motori di ricerca (noindex e mappa del sito).
* Stile per le pagine a indice.

= 1.1.0 =
* Solo arte: copre anche la ricerca di Relevanssi, i correlati del tema e di Jetpack, i link «precedente/successivo», gli articoli in evidenza e gli elenchi di categorie (indice della pagina Info).
* Tolti i widget ripetuti due volte nella colonna laterale.

= 1.0.0 =
* Prima versione.
