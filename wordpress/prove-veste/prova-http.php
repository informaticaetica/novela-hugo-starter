<?php
/* php prova-http.php <cartella del sito di prova>      (con PROVA_HOME=http://127.0.0.1:porta e il server di prova acceso)
   Chiede le pagine come farebbe un visitatore e controlla lingua, elenchi, menu, link fra le versioni e hreflang. */
$_SERVER['HTTP_HOST']       = parse_url( getenv( 'PROVA_HOME' ), PHP_URL_HOST ) . ':' . parse_url( getenv( 'PROVA_HOME' ), PHP_URL_PORT );
$_SERVER['REQUEST_URI']     = '/';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
require $argv[1] . '/wp-load.php';

$I    = get_option( 'prova_ids' );
$casa = trailingslashit( getenv( 'PROVA_HOME' ) );
$nomi = array_flip( $I );
$ko   = 0;

function verifica( $cosa, $avuto, $atteso ) {
	global $ko;
	$ok = $avuto === $atteso;
	if ( ! $ok ) {
		$ko++;
	}
	printf( "%s %-58s %s%s\n", $ok ? 'ok ' : 'NO ', $cosa, var_export( $avuto, true ), $ok ? '' : '   (atteso ' . var_export( $atteso, true ) . ')' );
}
/** Una pagina del sito di prova: stato, intestazione Location, testo. L'indirizzo può essere un percorso o un indirizzo intero. */
function pagina( $indirizzo ) {
	global $casa;
	$url    = 0 === strpos( $indirizzo, 'http' ) ? $indirizzo : $casa . ltrim( $indirizzo, '/' );
	$testo  = @file_get_contents( $url, false, stream_context_create( array( 'http' => array( 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 30 ) ) ) );
	$stato  = isset( $http_response_header[0] ) ? (int) substr( $http_response_header[0], 9, 3 ) : 0;
	$verso  = '';
	foreach ( (array) $http_response_header as $riga ) {
		if ( 0 === stripos( $riga, 'Location:' ) ) {
			$verso = trim( substr( $riga, 9 ) );
		}
	}
	return array( 'stato' => $stato, 'verso' => $verso, 'testo' => (string) $testo );
}
function sigle_in( $testo, $modello ) {
	global $nomi;
	preg_match_all( $modello, $testo, $m );
	$o = array();
	foreach ( $m[1] as $id ) {
		$o[] = isset( $nomi[ (int) $id ] ) ? $nomi[ (int) $id ] : '#' . $id;
	}
	sort( $o );
	return implode( ' ', $o );
}
function articoli( $p ) {
	return sigle_in( $p['testo'], '/<article data-id="(\d+)"/' );
}
function recenti( $p ) {
	return sigle_in( $p['testo'], '/<i data-id="(\d+)"/' );
}
function lingua( $p ) {
	return preg_match( '/<html[^>]*\blang="([^"]*)"/', $p['testo'], $m ) ? $m[1] : '';
}
function pezzo( $p, $modello ) {
	return preg_match( $modello, $p['testo'], $m ) ? trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( str_replace( '</li>', ' | ', $m[1] ) ) ), ' |' ) : '(manca)';
}
function hreflang( $p ) {
	preg_match_all( '/<link rel="alternate" hreflang="([^"]+)" href="([^"]+)"/', $p['testo'], $m );
	return array_combine( $m[1], $m[2] );
}
function errori( $p ) {
	return preg_match( '/Fatal error|Warning<\/b>|Notice<\/b>|Deprecated<\/b>/', $p['testo'] ) ? 'ERRORI PHP' : '';
}

$D = get_permalink( $I['D'] );
$H = get_permalink( $I['H'] );
$Ie = get_permalink( $I['I'] );

/* --- home italiana --- */
$p = pagina( '/' );
verifica( '/ stato, lingua, errori', $p['stato'] . ' ' . lingua( $p ) . errori( $p ), '200 it-IT' );
verifica( '/ articoli', articoli( $p ), 'C D E G' );
verifica( '/ menu principale', pezzo( $p, '~<ul id="menu">(.*?)</ul>~s' ), 'Info | English' );
verifica( '/ menu del telefono (elenco di pagine)', pezzo( $p, '~<ul id="telefono">(.*?)</ul>~s' ), 'Info | English' );
verifica( '/ hreflang', hreflang( $p ), array( 'it' => $casa, 'en' => $casa . 'en/', 'x-default' => $casa . 'en/' ) );
verifica( '/ insegna', preg_match( '~<a id="insegna" href="([^"]*)"~', $p['testo'], $m ) ? $m[1] : '', $casa );

/* --- indice inglese --- */
$p = pagina( '/en' );
verifica( '/en porta a /en/', $p['stato'] . ' ' . $p['verso'], '301 ' . $casa . 'en/' );
$p = pagina( '/en/' );
verifica( '/en/ stato, lingua, errori', $p['stato'] . ' ' . lingua( $p ) . errori( $p ), '200 en' );
verifica( '/en/ articoli', articoli( $p ), 'H I' );
verifica( '/en/ elenco nella colonna', recenti( $p ), 'H I' );
verifica( '/en/ menu principale', pezzo( $p, '~<ul id="menu">(.*?)</ul>~s' ), 'About | Italiano' );
verifica( '/en/ menu del telefono', pezzo( $p, '~<ul id="telefono">(.*?)</ul>~s' ), 'About | Italiano' );
verifica( '/en/ «Italiano» porta alla home italiana', (bool) preg_match( '~<li class="menu-item caa-lingua"><a href="' . preg_quote( $casa, '~' ) . '" hreflang="it" lang="it">Italiano</a>~', $p['testo'] ), true );
verifica( '/en/ hreflang', hreflang( $p ), array( 'it' => $casa, 'en' => $casa . 'en/', 'x-default' => $casa . 'en/' ) );
verifica( '/en/ insegna e ricerca restano in inglese', ( preg_match( '~<a id="insegna" href="([^"]*)"~', $p['testo'], $m ) ? $m[1] : '' ) . ' ' . ( preg_match( '~<form[^>]*action="([^"]*)"~', $p['testo'], $m ) ? $m[1] : '' ), $casa . 'en/ ' . $casa . 'en/' );
verifica( '/en/ date in inglese', (bool) preg_match( '~<time>November 2026</time>~', $p['testo'] ), true );
verifica( '/en/ categoria in inglese, con link sotto /en/', (bool) preg_match( '~<a href="' . preg_quote( $casa, '~' ) . 'en/category/mostre/"[^>]*>Exhibitions</a>~', $p['testo'] ), true );

/* --- articolo inglese --- */
$p = pagina( $Ie );
verifica( 'articolo inglese: stato, lingua, errori', $p['stato'] . ' ' . lingua( $p ) . errori( $p ), '200 en' );
verifica( 'articolo inglese: link alla versione italiana', (bool) preg_match( '~<p class="caa-altra-lingua"><a href="' . preg_quote( get_permalink( $I['E'] ), '~' ) . '" hreflang="it" lang="it">Leggi questo articolo in italiano</a></p>~', $p['testo'] ), true );
verifica( 'articolo inglese: hreflang', hreflang( $p ), array( 'it' => get_permalink( $I['E'] ), 'en' => $Ie, 'x-default' => $Ie ) );
verifica( 'articolo inglese: precedente / successivo', pezzo( $p, '~<span class="prima">(.*?)</span>~s' ) . ' / ' . pezzo( $p, '~<span class="dopo">(.*?)</span>~s' ), 'H-english-merz / ' );
verifica( 'articolo inglese: elenco nella colonna', recenti( $p ), 'H I' );
verifica( 'articolo inglese: «Italiano» porta alla versione italiana', (bool) preg_match( '~caa-lingua"><a href="' . preg_quote( get_permalink( $I['E'] ), '~' ) . '" hreflang="it"~', $p['testo'] ), true );

/* --- articolo italiano --- */
$p = pagina( $D );
verifica( 'articolo italiano: stato, lingua, errori', $p['stato'] . ' ' . lingua( $p ) . errori( $p ), '200 it-IT' );
verifica( 'articolo italiano: link alla versione inglese', (bool) preg_match( '~<p class="caa-altra-lingua"><a href="' . preg_quote( $H, '~' ) . '" hreflang="en" lang="en">Read this article in English</a></p>~', $p['testo'] ), true );
verifica( 'articolo italiano: precedente / successivo', pezzo( $p, '~<span class="prima">(.*?)</span>~s' ) . ' / ' . pezzo( $p, '~<span class="dopo">(.*?)</span>~s' ), 'G-nuovo-biennale / E-nuovo-kandinsky' );
verifica( 'articolo italiano: elenco nella colonna', recenti( $p ), 'C D E G' );
verifica( 'articolo italiano: categoria in italiano', (bool) preg_match( '~<a href="' . preg_quote( $casa, '~' ) . 'category/mostre/"[^>]*>Mostre</a>~', $p['testo'] ), true );
$p = pagina( get_permalink( $I['G'] ) );
verifica( 'articolo senza versione inglese: nessun link, nessun hreflang', substr_count( $p['testo'], 'caa-altra-lingua' ) . ' ' . count( hreflang( $p ) ), '0 0' );

/* --- pagine --- */
$p = pagina( get_permalink( $I['about'] ) );
verifica( 'pagina About: lingua e menu', lingua( $p ) . ' · ' . pezzo( $p, '~<ul id="menu">(.*?)</ul>~s' ), 'en · About | Italiano' );
verifica( 'pagina About: voce del menu evidenziata', (bool) preg_match( '~<li[^>]*class="[^"]*current-menu-item[^"]*"[^>]*><a href="' . preg_quote( get_permalink( $I['about'] ), '~' ) . '"~', $p['testo'] ), true );
verifica( 'pagina About: link alla versione italiana', (bool) preg_match( '~hreflang="it" lang="it">Leggi questa pagina in italiano</a>~', $p['testo'] ), true );
$p = pagina( get_permalink( $I['info'] ) );
verifica( 'pagina Info: lingua, menu, link', lingua( $p ) . ' · ' . pezzo( $p, '~<ul id="menu">(.*?)</ul>~s' ) . ' · ' . ( false !== strpos( $p['testo'], 'Read this page in English' ) ? 'link' : 'senza link' ), 'it-IT · Info | English · link' );

/* --- categorie, ricerca, feed --- */
$p = pagina( '/en/category/mostre/' );
verifica( '/en/category/mostre/', $p['stato'] . ' ' . lingua( $p ) . ' ' . articoli( $p ), '200 en H I' );
$p = pagina( '/category/mostre/' );
verifica( '/category/mostre/', $p['stato'] . ' ' . lingua( $p ) . ' ' . articoli( $p ), '200 it-IT C D E' );
$p = pagina( '/en/?s=comune' );
verifica( '/en/?s=comune', $p['stato'] . ' ' . lingua( $p ) . ' ' . articoli( $p ), '200 en H I about' );
$p = pagina( '/?s=comune' );
verifica( '/?s=comune', $p['stato'] . ' ' . lingua( $p ) . ' ' . articoli( $p ), '200 it-IT C D E G info' );
$p = pagina( '/en/feed/' );
preg_match_all( '~<item>\s*<title>([^<]*)</title>~', $p['testo'], $m );
sort( $m[1] );
verifica( '/en/feed/', $p['stato'] . ' ' . implode( ' ', $m[1] ), '200 H-english-merz I-english-kandinsky' );
$p = pagina( '/feed/' );
preg_match_all( '~<item>\s*<title>([^<]*)</title>~', $p['testo'], $m );
verifica( '/feed/ senza articoli inglesi né nascosti', $p['stato'] . ' ' . count( $m[1] ) . ' ' . ( preg_grep( '/english|vecchio-(marketing|in-evidenza|dicembre)/', $m[1] ) ? 'intrusi' : 'pulito' ), '200 4 pulito' );

echo $ko ? "\n$ko PROVE FALLITE (via HTTP)\n" : "\ntutte superate (via HTTP)\n";
exit( $ko ? 1 : 0 );
