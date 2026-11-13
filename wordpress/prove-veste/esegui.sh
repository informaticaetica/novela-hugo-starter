#!/usr/bin/env bash
# Prova il plugin "Contemporary Art — veste grafica" su un WordPress usa e getta: scarica WordPress
# con Composer, lo installa su SQLite, crea articoli vecchi e nuovi e controlla che cosa vede il
# pubblico (in italiano e in inglese), che cosa vede il pannello e che cosa succede spegnendo le spunte.
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
cp "$qui"/tema/* "$sito/wp-content/themes/vuoto/"
mkdir -p "$sito/wp-content/mu-plugins"
cp "$qui/sempre-attivo.php" "$sito/wp-content/mu-plugins/"
cp "$qui/wp-config.php" "$sito/wp-config.php"
php "$qui/installa.php" "$sito" 2>/dev/null
esito=0
for modo in pubblico admin spento una-lingua; do
	echo "=== $modo"
	php "$qui/prova.php" "$sito" "$modo" || esito=1
done

# Le stesse cose viste da fuori: un server di prova e richieste HTTP vere (indirizzi sotto /en/, intestazioni, menu).
porta="${PORTA:-8765}"
export PROVA_HOME="http://127.0.0.1:$porta"
php -S "127.0.0.1:$porta" -t "$sito" "$qui/instradatore.php" > "$banco/server.log" 2>&1 &
server=$!
trap 'kill $server 2>/dev/null || true' EXIT
sleep 2
echo "=== via HTTP"
php "$qui/prova-http.php" "$sito" || esito=1
exit $esito
