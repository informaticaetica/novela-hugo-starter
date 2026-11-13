<?php
/* php prova.php <cartella del sito di prova> [pubblico|admin|spento|una-lingua]
   Controlla che cosa mostra il plugin: a un visitatore (pubblico), al pannello di amministrazione (admin),
   con «solo arte» spento (spento) e con «due lingue» spento (una-lingua).
   Le sigle sono quelle di installa.php: A B F articoli vecchi, C vecchio ma in «mostre», D E G nuovi,
   H I versioni inglesi di D ed E; gym pagina esclusa, info pagina italiana, about la sua versione inglese. */
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
if ( 'una-lingua' === $modo ) {
	add_filter( 'pre_option_caa_opzioni', function () { return array( 'bilingue' => 0 ); } );
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
	printf( "%s %-62s %s%s\n", $ok ? 'ok ' : 'NO ', $cosa, var_export( $avuto, true ), $ok ? '' : '   (atteso ' . var_export( $atteso, true ) . ')' );
}
function ids_di( $richiesta ) {
	return sigle( wp_list_pluck( $richiesta->posts, 'ID' ) );
}
/** Finge di essere su una pagina: da qui in poi è questa la richiesta principale. */
function vai_a( $richiesta ) {
	global $wp_query, $wp_the_query;
	$wp_query     = new WP_Query();
	$wp_the_query = $wp_query;
	$wp_query->query( $richiesta );
	return $wp_query;
}
/** Sceglie il valore atteso secondo il modo: pubblico, admin, spento, una-lingua. */
function per_modo( $pubblico, $admin, $spento, $una_lingua ) {
	global $modo;
	$tutti = array( 'pubblico' => $pubblico, 'admin' => $admin, 'spento' => $spento, 'una-lingua' => $una_lingua );
	return $tutti[ $modo ];
}
$pub = 'pubblico' === $modo;

/* ===== in italiano ===== */
vai_a( array() );
verifica( 'home (con un vecchio articolo in evidenza)', ids_di( $wp_query ), per_modo( 'C D E G', 'A B C D E F G H I', 'A B C D E F G', 'C D E G H I' ) );
$q = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => -1, 'fields' => 'ids', 'ignore_sticky_posts' => true ) );
verifica( 'WP_Query articoli', sigle( $q->posts ), per_modo( 'C D E G', 'A B C D E F G H I', 'A B C D E F G', 'C D E G H I' ) );
verifica( 'get_posts() del tema (suppress_filters)', sigle( wp_list_pluck( get_posts( array( 'numberposts' => -1 ) ), 'ID' ) ), per_modo( 'C D E G', 'A B C D E F G H I', 'A B C D E F G', 'C D E G H I' ) );
verifica( 'get_posts() con esclusione propria (correlati del tema)', sigle( wp_list_pluck( get_posts( array( 'numberposts' => -1, 'orderby' => 'rand', 'post__not_in' => array( $I['E'] ) ) ), 'ID' ) ), per_modo( 'C D G', 'A B C D F G H I', 'A B C D F G', 'C D G H I' ) );
$mese = new WP_Query();
$mese->query( array( 'year' => 2025, 'monthnum' => 10 ) );
verifica( 'archivio ottobre 2025', ids_di( $mese ), per_modo( '', 'A', 'A', '' ) );
$s = new WP_Query( array( 's' => 'comune', 'posts_per_page' => -1 ) );
verifica( 'ricerca (articoli e pagine)', ids_di( $s ), per_modo( 'C D E G info', 'A B C D E F G H I about gym info', 'A B C D E F G gym info', 'C D E G H I about info' ) );

/* chi ha l'indirizzo esatto legge ancora */
verifica( 'link diretto a un articolo nascosto (p=)', ids_di( new WP_Query( array( 'p' => $I['A'] ) ) ), 'A' );
verifica( 'link diretto per nome', ids_di( new WP_Query( array( 'name' => 'f-vecchio-dicembre' ) ) ), 'F' );
verifica( 'get_posts include (correlati già scelti)', sigle( wp_list_pluck( get_posts( array( 'include' => array( $I['A'], $I['D'], $I['H'] ) ) ), 'ID' ) ), 'A D H' );

/* categorie */
$cm = new WP_Query();
$cm->query( array( 'category_name' => 'marketing' ) );
verifica( 'archivio categoria marketing', ids_di( $cm ), per_modo( '', 'A F', 'A F', '' ) );
verifica( '  la pagina della categoria resta (vuota), senza errori', (bool) $cm->get_queried_object(), true );
$cmo = new WP_Query();
$cmo->query( array( 'category_name' => 'mostre' ) );
verifica( 'archivio categoria mostre', ids_di( $cmo ), per_modo( 'C D E', 'C D E H I', 'C D E', 'C D E H I' ) );
$cat = wp_list_pluck( get_categories(), 'slug' );
sort( $cat );
verifica( 'get_categories() (indice della pagina Info)', implode( ' ', $cat ), per_modo( 'biennali eventi mostre', 'biennali eventi ho-imparato marketing mostre uncategorized', 'biennali eventi ho-imparato marketing mostre uncategorized', 'biennali eventi mostre' ) );
verifica( 'get_terms fields=ids: intatto', count( get_terms( array( 'taxonomy' => 'category', 'fields' => 'ids', 'hide_empty' => false ) ) ), 6 );
verifica( 'categorie di un articolo nascosto: intatte', implode( ' ', wp_list_pluck( get_the_category( $I['F'] ), 'slug' ) ), 'ho-imparato marketing' );
verifica( 'nome della categoria, in italiano', get_category_by_slug( 'mostre' )->name, 'Mostre' );
verifica( 'link della categoria, in italiano', wp_parse_url( get_term_link( 'mostre', 'category' ), PHP_URL_PATH ), '/category/mostre/' );
verifica( 'tag (altra tassonomia): intatti', is_array( get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) ) ), true );

/* pagine e menu */
verifica( 'richiesta di sole pagine: intatta', ids_di( new WP_Query( array( 'post_type' => 'page', 'posts_per_page' => -1 ) ) ), 'about gym info' );
verifica( 'get_pages (indice e menu del telefono)', sigle( wp_list_pluck( get_pages(), 'ID' ) ), per_modo( 'info', 'about gym info', 'gym info', 'about info' ) );
$menu = wp_nav_menu( array( 'container' => '', 'items_wrap' => '%3$s', 'theme_location' => 'primary-menu', 'echo' => false ) );
verifica( 'menu principale', trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( str_replace( '</li>', ' | ', $menu ) ) ), ' |' ), per_modo( 'Info | English', 'Gym | Info | About', 'Gym | Info | English', 'Info | About' ) );
verifica( '  la voce «English» porta a /en/', (bool) preg_match( '~<li class="menu-item caa-lingua"><a href="http://prova.test/en/" hreflang="en" lang="en">English</a>~', $menu ), per_modo( true, false, true, false ) );

/* articolo precedente / successivo */
vai_a( array( 'p' => $I['D'] ) );
$GLOBALS['post'] = get_post( $I['D'] );
$prec            = get_previous_post();
$succ            = get_next_post();
verifica( 'articolo precedente di D (ott 2026)', $prec ? sigle( array( $prec->ID ) ) : '', 'G' );
verifica( 'articolo successivo di D', $succ ? sigle( array( $succ->ID ) ) : '', per_modo( 'E', 'H', 'E', 'H' ) );
$GLOBALS['post'] = get_post( $I['G'] );
$prec            = get_previous_post();
verifica( 'articolo precedente di G (mag 2026)', $prec ? sigle( array( $prec->ID ) ) : '', per_modo( 'C', 'F', 'F', 'C' ) );

/* Jetpack e Relevanssi non sono installati qui: si provano i loro filtri, stando sull'articolo D */
verifica( 'Jetpack: esclusi dai correlati', sigle( apply_filters( 'jetpack_relatedposts_filter_exclude_post_ids', array(), $I['D'] ) ), per_modo( 'A B F H I about', '', 'H I about', 'A B F' ) );
$risposta = array( array( 'id' => $I['E'] ), array( 'id' => $I['A'] ), array( 'id' => $I['C'] ), array( 'id' => $I['I'] ) );
verifica( 'Jetpack: risposta ripulita', sigle( wp_list_pluck( apply_filters( 'jetpack_relatedposts_returned_results', $risposta, $I['D'] ), 'id' ) ), per_modo( 'C E', 'A C E I', 'A C E', 'C E I' ) );
verifica( 'Relevanssi: articolo nascosto', apply_filters( 'relevanssi_post_ok', true, (string) $I['A'] ), per_modo( false, true, true, false ) );
verifica( 'Relevanssi: pagina esclusa', apply_filters( 'relevanssi_post_ok', true, $I['gym'] ), per_modo( false, true, true, false ) );
verifica( 'Relevanssi: articolo d\'arte', apply_filters( 'relevanssi_post_ok', true, $I['D'] ), true );
verifica( 'Relevanssi: articolo inglese, cercando in italiano', apply_filters( 'relevanssi_post_ok', true, $I['H'] ), per_modo( false, true, false, true ) );
verifica( 'Relevanssi: già negato resta negato', apply_filters( 'relevanssi_post_ok', false, $I['D'] ), false );

/* le due versioni si rimandano l'una all'altra */
ob_start();
caa_hreflang();
$hreflang = ob_get_clean();
verifica( 'hreflang sull\'articolo italiano D', substr_count( $hreflang, 'hreflang=' ) . ( false !== strpos( $hreflang, 'hreflang="x-default" href="' . get_permalink( $I['H'] ) ) ? ' x-default→H' : '' ), per_modo( '3 x-default→H', '3 x-default→H', '3 x-default→H', '0' ) );
verifica( 'altra versione di D / di H / di A', caa_gemello( $I['D'] ) . ' ' . caa_gemello( $I['H'] ) . ' ' . caa_gemello( $I['A'] ), $I['H'] . ' ' . $I['D'] . ' 0' );

/* ===== in inglese: l'indice /en/ ===== */
vai_a( array( 'caa_en' => 1 ) );
$en = per_modo( true, false, true, false );   // nel pannello e a «due lingue» spento /en/ non esiste
verifica( '/en/: articoli', ids_di( $wp_query ), per_modo( 'H I', 'A B C D E F G H I', 'H I', 'C D E G H I' ) );
verifica( '/en/: si legge in inglese', caa_in_inglese(), $en );
verifica( '/en/: get_posts() del tema', sigle( wp_list_pluck( get_posts( array( 'numberposts' => -1 ) ), 'ID' ) ), per_modo( 'H I', 'A B C D E F G H I', 'H I', 'C D E G H I' ) );
verifica( '/en/: correlati del tema, escluso quello che si sta leggendo', sigle( wp_list_pluck( get_posts( array( 'numberposts' => -1, 'post__not_in' => array( $I['I'] ) ) ), 'ID' ) ), per_modo( 'H', 'A B C D E F G H', 'H', 'C D E G H' ) );
verifica( '/en/: nome della categoria', get_category_by_slug( 'mostre' )->name, $en ? 'Exhibitions' : 'Mostre' );
verifica( '/en/: link della categoria', wp_parse_url( get_term_link( 'mostre', 'category' ), PHP_URL_PATH ), $en ? '/en/category/mostre/' : '/category/mostre/' );
verifica( '/en/: get_pages (About al posto di Info)', sigle( wp_list_pluck( get_pages(), 'ID' ) ), per_modo( 'about', 'about gym info', 'about gym', 'about info' ) );
$menu = wp_nav_menu( array( 'container' => '', 'items_wrap' => '%3$s', 'theme_location' => 'primary-menu', 'echo' => false ) );
verifica( '/en/: menu principale', trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( str_replace( '</li>', ' | ', $menu ) ) ), ' |' ), per_modo( 'About | Italiano', 'Gym | Info | About', 'Gym | About | Italiano', 'Info | About' ) );
verifica( '/en/: Relevanssi tiene solo l\'inglese', ( apply_filters( 'relevanssi_post_ok', true, $I['H'] ) ? 'H' : '' ) . ( apply_filters( 'relevanssi_post_ok', true, $I['D'] ) ? 'D' : '' ), per_modo( 'H', 'HD', 'H', 'HD' ) );
$cen = vai_a( array( 'caa_en' => 1, 'category_name' => 'mostre' ) );
verifica( '/en/category/mostre/', ids_di( $cen ), per_modo( 'H I', 'C D E H I', 'H I', 'C D E H I' ) );
$sen = vai_a( array( 'caa_en' => 1, 's' => 'comune', 'posts_per_page' => -1 ) );
verifica( '/en/?s= (ricerca in inglese)', ids_di( $sen ), per_modo( 'H I about', 'A B C D E F G H I about gym info', 'H I about', 'C D E G H I about info' ) );

/* ===== in inglese: un articolo ===== */
vai_a( array( 'p' => $I['I'] ) );
$GLOBALS['post'] = get_post( $I['I'] );
verifica( 'articolo inglese I: si legge in inglese', caa_in_inglese(), $en );
$prec = get_previous_post();
verifica( 'articolo inglese I: il precedente è inglese', $prec ? sigle( array( $prec->ID ) ) : '', per_modo( 'H', 'E', 'H', 'E' ) );
verifica( 'articolo inglese I: elenchi nella pagina', sigle( wp_list_pluck( get_posts( array( 'numberposts' => -1 ) ), 'ID' ) ), per_modo( 'H I', 'A B C D E F G H I', 'H I', 'C D E G H I' ) );
verifica( 'articolo inglese I: Jetpack tiene solo l\'inglese', sigle( wp_list_pluck( apply_filters( 'jetpack_relatedposts_returned_results', $risposta, $I['I'] ), 'id' ) ), per_modo( 'I', 'A C E I', 'I', 'C E I' ) );

/* ===== il campo si scrive dall'API REST, come fa chi crea la versione inglese da fuori ===== */
wp_set_current_user( 1 );
$richiesta = new WP_REST_Request( 'POST', '/wp/v2/posts' );
$richiesta->set_body_params( array( 'title' => 'Versione inglese via REST', 'status' => 'publish', 'content' => 'x', 'meta' => array( 'caa_versione_inglese_di' => $I['G'] ) ) );
$esito = rest_do_request( $richiesta );
$nuovo = $esito->get_data();
verifica( 'REST: articolo creato con il campo che lo abbina', $esito->get_status() . ' ' . ( isset( $nuovo['id'] ) ? (int) get_post_meta( $nuovo['id'], 'caa_versione_inglese_di', true ) : 0 ), '201 ' . $I['G'] );
if ( isset( $nuovo['id'] ) ) {
	wp_delete_post( $nuovo['id'], true );
}
wp_set_current_user( 0 );

/* ===== veste ===== */
vai_a( array() );
$w = wp_get_sidebars_widgets();
verifica( 'widget senza doppioni', implode( ' ', $w['sidebar'] ), 'admin' === $modo ? 'block-12 block-2 block-3 block-8 block-2 block-3 block-8' : 'block-12 block-2 block-3 block-8' );
verifica( 'classi del body', implode( ' ', array_values( array_intersect( get_body_class(), array( 'caa-veste', 'caa-solo-arte', 'caa-en' ) ) ) ), 'spento' === $modo ? 'caa-veste' : 'caa-veste caa-solo-arte' );
do_action( 'wp_enqueue_scripts' );
verifica( 'foglio di stile in coda', wp_style_is( 'caa-veste', 'enqueued' ), true );
verifica( 'nome al posto del logo (il pannello vede il logo)', (int) get_theme_mod( 'custom_logo' ), 'admin' === $modo ? 123 : 0 );

/* ===== per i motori di ricerca ===== */
function noindex_su( $richiesta ) {
	vai_a( $richiesta );
	$robots = apply_filters( 'wp_robots', array( 'max-image-preview' => 'large' ) );
	return ! empty( $robots['noindex'] ) && 'noindex, follow' === apply_filters( 'wpseo_robots', 'index, follow' );
}
$acceso = 'spento' !== $modo;
verifica( 'noindex: articolo nascosto', noindex_su( array( 'p' => $I['A'] ) ), $acceso );
verifica( 'noindex: pagina esclusa', noindex_su( array( 'page_id' => $I['gym'] ) ), $acceso );
verifica( 'noindex: archivio rimasto vuoto', noindex_su( array( 'category_name' => 'marketing' ) ), per_modo( true, false, false, true ) );
verifica( 'indicizzabile: articolo d\'arte', noindex_su( array( 'p' => $I['D'] ) ), false );
verifica( 'indicizzabile: versione inglese', noindex_su( array( 'p' => $I['H'] ) ), false );
verifica( 'indicizzabile: vecchio articolo in «mostre»', noindex_su( array( 'p' => $I['C'] ) ), false );
verifica( 'indicizzabile: pagina Info', noindex_su( array( 'page_id' => $I['info'] ) ), false );
verifica( 'indicizzabile: categoria mostre', noindex_su( array( 'category_name' => 'mostre' ) ), false );
verifica( 'indicizzabile: home', noindex_su( array() ), false );
verifica( 'mappa del sito di Yoast: esclusi', sigle( apply_filters( 'wpseo_exclude_from_sitemap_by_post_ids', array() ) ), $acceso ? 'A B F gym' : '' );

vai_a( array() );
$_SERVER['HTTPS'] = 'on';   // il sito vero si legge in https anche se l'indirizzo salvato è in http
verifica( 'in https: casa, canonico, link alla versione inglese', caa_casa() . ' ' . apply_filters( 'wpseo_canonical', 'http://prova.test/x/' ) . ' ' . wp_parse_url( get_permalink( $I['H'] ), PHP_URL_SCHEME ), 'https://prova.test/ https://prova.test/x/ https' );
unset( $_SERVER['HTTPS'] );
verifica( 'descrizione della home italiana', (bool) preg_match( '/promuove la conoscenza dell’arte internazionale/', apply_filters( 'wpseo_metadesc', 'vecchia' ) ), 'admin' !== $modo );   // nel pannello non c'è una home
vai_a( array( 'caa_en' => 1 ) );
verifica( 'descrizione della home inglese', (bool) preg_match( '/promotes knowledge of international art/', apply_filters( 'wpseo_metadesc', 'vecchia' ) ), $en );
verifica( 'canonico di /en/ resta sotto /en/', apply_filters( 'wpseo_canonical', 'http://prova.test/' ), $en ? 'http://prova.test/en/' : 'http://prova.test/' );
wp_update_post( array( 'ID' => $I['D'], 'post_excerpt' => 'Riassunto <b>scritto</b> a mano.' ) );
vai_a( array( 'p' => $I['D'] ) );
verifica( 'descrizione di un articolo: il riassunto, se manca', apply_filters( 'wpseo_metadesc', '' ), 'Riassunto scritto a mano.' );
verifica( 'descrizione di un articolo: quella scritta a mano resta', apply_filters( 'wpseo_metadesc', 'Scritta a mano' ), 'Scritta a mano' );
wp_update_post( array( 'ID' => $I['D'], 'post_excerpt' => '' ) );

echo $ko ? "\n$ko PROVE FALLITE ($modo)\n" : "\ntutte superate ($modo)\n";
exit( $ko ? 1 : 0 );
