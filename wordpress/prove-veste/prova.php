<?php
/* php prova.php <cartella del sito di prova> [pubblico|admin|spento]
   Controlla che cosa mostra il plugin a un visitatore, al pannello di amministrazione e a interruttore «solo arte» spento.
   Le sigle sono quelle di installa.php: A B F articoli vecchi, C vecchio ma in «mostre», D E G nuovi, gym pagina esclusa. */
$modo                       = isset( $argv[2] ) ? $argv[2] : 'pubblico';
$_SERVER['HTTP_HOST']       = 'prova.test';
$_SERVER['REQUEST_URI']     = '/';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
if ( 'admin' === $modo ) {
	define( 'WP_ADMIN', true );
}
require $argv[1] . '/wp-load.php';

$I = get_option( 'prova_ids' );
add_filter( 'caa_pagine_escluse', function () use ( $I ) { return array( $I['gym'] ); } );
if ( 'spento' === $modo ) {
	add_filter( 'pre_option_caa_opzioni', function () { return array( 'veste' => 1, 'solo_arte' => 0 ); } );
}
$nomi = array_flip( $I );
$ko   = 0;

function sigle( $ids ) {
	global $nomi;
	$o = array();
	foreach ( $ids as $i ) {
		$o[] = isset( $nomi[ (int) $i ] ) ? $nomi[ (int) $i ] : '#' . $i;
	}
	sort( $o );
	return implode( ' ', $o );
}
function verifica( $cosa, $avuto, $atteso ) {
	global $ko;
	$ok = $avuto === $atteso;
	if ( ! $ok ) {
		$ko++;
	}
	printf( "%s %-58s %s%s\n", $ok ? 'ok ' : 'NO ', $cosa, var_export( $avuto, true ), $ok ? '' : '   (atteso ' . var_export( $atteso, true ) . ')' );
}
function ids_di( $richiesta ) {
	return sigle( wp_list_pluck( $richiesta->posts, 'ID' ) );
}

$tutto    = 'pubblico' !== $modo;   // nel pannello, o a interruttore spento, si deve vedere tutto
$visibili = $tutto ? 'A B C D E F G' : 'C D E G';

/* elenchi di articoli */
$q = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => -1, 'fields' => 'ids', 'ignore_sticky_posts' => true ) );
verifica( 'WP_Query articoli', sigle( $q->posts ), $visibili );
verifica( 'get_posts() del tema (suppress_filters)', sigle( wp_list_pluck( get_posts( array( 'numberposts' => -1 ) ), 'ID' ) ), $visibili );
verifica( 'get_posts() con esclusione propria (correlati del tema)', sigle( wp_list_pluck( get_posts( array( 'numberposts' => -1, 'orderby' => 'rand', 'post__not_in' => array( $I['E'] ) ) ), 'ID' ) ), $tutto ? 'A B C D F G' : 'C D G' );
$home = new WP_Query();
$home->query( array() );
verifica( 'home con articolo in evidenza', ids_di( $home ), $visibili );
$mese = new WP_Query();
$mese->query( array( 'year' => 2025, 'monthnum' => 10 ) );
verifica( 'archivio ottobre 2025', ids_di( $mese ), $tutto ? 'A' : '' );
$s = new WP_Query( array( 's' => 'comune', 'posts_per_page' => -1 ) );
verifica( 'ricerca (articoli e pagine)', ids_di( $s ), $tutto ? 'A B C D E F G gym info' : 'C D E G info' );

/* chi ha l'indirizzo esatto legge ancora */
verifica( 'link diretto a un articolo nascosto (p=)', ids_di( new WP_Query( array( 'p' => $I['A'] ) ) ), 'A' );
verifica( 'link diretto per nome', ids_di( new WP_Query( array( 'name' => 'f-vecchio-dicembre' ) ) ), 'F' );
verifica( 'get_posts include (correlati già scelti)', sigle( wp_list_pluck( get_posts( array( 'include' => array( $I['A'], $I['D'] ) ) ), 'ID' ) ), 'A D' );

/* categorie */
$cm = new WP_Query();
$cm->query( array( 'category_name' => 'marketing' ) );
verifica( 'archivio categoria marketing', ids_di( $cm ), $tutto ? 'A F' : '' );
verifica( '  la pagina della categoria resta (vuota), senza errori', (bool) $cm->get_queried_object(), true );
$cmo = new WP_Query();
$cmo->query( array( 'category_name' => 'mostre' ) );
verifica( 'archivio categoria mostre', ids_di( $cmo ), 'C D E' );
$cat = wp_list_pluck( get_categories(), 'slug' );
sort( $cat );
verifica( 'get_categories() (indice della pagina Info)', implode( ' ', $cat ), $tutto ? 'biennali eventi ho-imparato marketing mostre uncategorized' : 'biennali eventi mostre' );
verifica( 'get_terms fields=ids: intatto', count( get_terms( array( 'taxonomy' => 'category', 'fields' => 'ids', 'hide_empty' => false ) ) ), 6 );
verifica( 'categorie di un articolo nascosto: intatte', implode( ' ', wp_list_pluck( get_the_category( $I['F'] ), 'slug' ) ), 'ho-imparato marketing' );
verifica( 'get_term_by mostre', (bool) get_term_by( 'slug', 'mostre', 'category' ), true );
verifica( 'tag (altra tassonomia): intatti', is_array( get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) ) ), true );

/* pagine */
verifica( 'richiesta di sole pagine: intatta', ids_di( new WP_Query( array( 'post_type' => 'page', 'posts_per_page' => -1 ) ) ), 'gym info' );
verifica( 'get_pages (elenco pagine)', sigle( wp_list_pluck( get_pages(), 'ID' ) ), $tutto ? 'gym info' : 'info' );

/* articolo precedente / successivo */
$GLOBALS['post'] = get_post( $I['D'] );
$prec            = get_previous_post();
$succ            = get_next_post();
verifica( 'articolo precedente di D (ott 2026)', $prec ? sigle( array( $prec->ID ) ) : '', 'G' );
verifica( 'articolo successivo di D', $succ ? sigle( array( $succ->ID ) ) : '', 'E' );
$GLOBALS['post'] = get_post( $I['G'] );
$prec            = get_previous_post();
verifica( 'articolo precedente di G (mag 2026)', $prec ? sigle( array( $prec->ID ) ) : '', $tutto ? 'F' : 'C' );

/* Jetpack e Relevanssi: non sono installati qui, si provano i loro filtri */
verifica( 'Jetpack: esclusi dai correlati', sigle( apply_filters( 'jetpack_relatedposts_filter_exclude_post_ids', array(), $I['D'] ) ), $tutto ? '' : 'A B F' );
$risposta = array( array( 'id' => $I['E'] ), array( 'id' => $I['A'] ), array( 'id' => $I['C'] ) );
verifica( 'Jetpack: risposta ripulita', sigle( wp_list_pluck( apply_filters( 'jetpack_relatedposts_returned_results', $risposta, $I['D'] ), 'id' ) ), $tutto ? 'A C E' : 'C E' );
verifica( 'Relevanssi: articolo nascosto', apply_filters( 'relevanssi_post_ok', true, (string) $I['A'] ), $tutto );
verifica( 'Relevanssi: pagina esclusa', apply_filters( 'relevanssi_post_ok', true, $I['gym'] ), $tutto );
verifica( 'Relevanssi: articolo d\'arte', apply_filters( 'relevanssi_post_ok', true, $I['D'] ), true );
verifica( 'Relevanssi: già negato resta negato', apply_filters( 'relevanssi_post_ok', false, $I['D'] ), false );

/* veste */
$w = wp_get_sidebars_widgets();
verifica( 'widget senza doppioni', implode( ' ', $w['sidebar'] ), 'admin' === $modo ? 'block-12 block-2 block-3 block-8 block-2 block-3 block-8' : 'block-12 block-2 block-3 block-8' );
verifica( 'classi del body', implode( ' ', array_values( array_intersect( get_body_class(), array( 'caa-veste', 'caa-solo-arte' ) ) ) ), 'spento' === $modo ? 'caa-veste' : 'caa-veste caa-solo-arte' );
do_action( 'wp_enqueue_scripts' );
verifica( 'foglio di stile in coda', wp_style_is( 'caa-veste', 'enqueued' ), true );

verifica( 'nome al posto del logo (il pannello vede il logo)', (int) get_theme_mod( 'custom_logo' ), 'admin' === $modo ? 123 : 0 );

/* fuori dai motori di ricerca: si finge di essere su una pagina e si guarda l'etichetta per i motori */
function noindex_su( $richiesta ) {
	global $wp_query, $wp_the_query;
	$wp_query     = new WP_Query();
	$wp_the_query = $wp_query;
	$wp_query->query( $richiesta );
	$robots = apply_filters( 'wp_robots', array( 'max-image-preview' => 'large' ) );
	return ! empty( $robots['noindex'] ) && 'noindex, follow' === apply_filters( 'wpseo_robots', 'index, follow' );
}
$acceso = 'spento' !== $modo;
verifica( 'noindex: articolo nascosto', noindex_su( array( 'p' => $I['A'] ) ), $acceso );
verifica( 'noindex: pagina esclusa', noindex_su( array( 'page_id' => $I['gym'] ) ), $acceso );
verifica( 'noindex: archivio rimasto vuoto', noindex_su( array( 'category_name' => 'marketing' ) ), 'pubblico' === $modo );
verifica( 'indicizzabile: articolo d\'arte', noindex_su( array( 'p' => $I['D'] ) ), false );
verifica( 'indicizzabile: vecchio articolo in «mostre»', noindex_su( array( 'p' => $I['C'] ) ), false );
verifica( 'indicizzabile: pagina Info', noindex_su( array( 'page_id' => $I['info'] ) ), false );
verifica( 'indicizzabile: categoria mostre', noindex_su( array( 'category_name' => 'mostre' ) ), false );
verifica( 'indicizzabile: home', noindex_su( array() ), false );
verifica( 'mappa del sito di Yoast: esclusi', sigle( apply_filters( 'wpseo_exclude_from_sitemap_by_post_ids', array() ) ), $acceso ? 'A B F gym' : '' );

echo $ko ? "\n$ko PROVE FALLITE ($modo)\n" : "\ntutte superate ($modo)\n";
exit( $ko ? 1 : 0 );
