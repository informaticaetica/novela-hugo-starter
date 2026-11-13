#!/usr/bin/env bash
# Prova il plugin "Contemporary Art — veste grafica" su un WordPress usa e getta: scarica WordPress
# con Composer, lo installa su SQLite, crea articoli vecchi e nuovi e controlla che cosa vede il
# pubblico, che cosa vede il pannello e che cosa succede a interruttore spento.
# Uso: wordpress/prove-veste/esegui.sh        (BANCO=/una/cartella per riusare ciò che è già scaricato)
set -euo pipefail
qui="$(cd "$(dirname "$0")" && pwd)"
banco="${BANCO:-$(mktemp -d)}"
cp "$qui/composer.json" "$banco/"
(cd "$banco" && composer install --no-interaction --no-progress --quiet)
sito="$banco/site"
rm -rf "$sito"
cp -r "$banco/vendor/johnpbloch/wordpress-core" "$sito"
mkdir -p "$sito/wp-content/plugins" "$sito/wp-content/themes/vuoto" "$sito/wp-content/database"
cp "$banco/vendor/aaemnnosttv/wp-sqlite-db/src/db.php" "$sito/wp-content/db.php"
cp -r "$qui/../contemporary-art-veste" "$sito/wp-content/plugins/"
printf '/*\nTheme Name: Vuoto\n*/\n' > "$sito/wp-content/themes/vuoto/style.css"
echo '<?php' > "$sito/wp-content/themes/vuoto/index.php"
cp "$qui/wp-config.php" "$sito/wp-config.php"
php "$qui/installa.php" "$sito" 2>/dev/null
esito=0
for modo in pubblico admin spento; do
	echo "=== $modo"
	php "$qui/prova.php" "$sito" "$modo" || esito=1
done
exit $esito
