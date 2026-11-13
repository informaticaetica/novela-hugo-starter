#!/usr/bin/env bash
# Impacchetta il plugin "Contemporary Art — veste grafica" in wordpress/contemporary-art-veste.zip,
# da caricare in WordPress con Plugin → Aggiungi nuovo → Carica plugin.
# Ricorda di alzare il numero di versione in contemporary-art-veste.php a ogni aggiornamento.
set -euo pipefail
qui="$(cd "$(dirname "$0")" && pwd)"
rm -f "$qui/contemporary-art-veste.zip"
(cd "$qui" && zip -qr contemporary-art-veste.zip contemporary-art-veste)
echo "Creato $qui/contemporary-art-veste.zip"
