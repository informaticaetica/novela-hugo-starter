#!/usr/bin/env bash
# Assembla il plugin WordPress "Notte in galleria": il codice PHP di questa cartella
# più i file del gioco presi da static/galleria. Produce wordpress/notte-in-galleria.zip,
# da caricare in WordPress con Plugin → Aggiungi nuovo → Carica plugin.
# Ricorda di alzare il numero di versione in notte-in-galleria.php a ogni aggiornamento.
set -euo pipefail
qui="$(cd "$(dirname "$0")" && pwd)"
gioco="$qui/../static/galleria"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
mkdir -p "$tmp/notte-in-galleria/gioco/quadri"
cp "$qui/notte-in-galleria/notte-in-galleria.php" "$qui/notte-in-galleria/readme.txt" "$tmp/notte-in-galleria/"
cp "$gioco/index.html" "$gioco/three.min.js" "$gioco/three.post.js" "$gioco/three.LICENSE.txt" "$tmp/notte-in-galleria/gioco/"
cp "$gioco"/quadri/*.jpg "$gioco/quadri/CREDITI.md" "$tmp/notte-in-galleria/gioco/quadri/"
rm -f "$qui/notte-in-galleria.zip"
(cd "$tmp" && zip -qr "$qui/notte-in-galleria.zip" notte-in-galleria)
echo "Creato $qui/notte-in-galleria.zip"
