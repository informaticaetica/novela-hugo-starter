<?php
/**
 * Versioni inglesi separate (spunta «bilingue», spenta finché non la si accende da Impostazioni → Contemporary Art).
 *
 * A spunta accesa, la versione inglese di un articolo (o di una pagina) è un articolo a sé, con il suo indirizzo, che porta
 * nel campo «caa_versione_inglese_di» l'ID dell'originale italiano. Da quel solo dato discende tutto il resto:
 * - gli elenchi separati: in italiano solo gli italiani; sotto /en/ (indice, categorie, tag, ricerca, feed) solo gli inglesi;
 * - i tag hreflang, la lingua dichiarata dalla pagina e l'interfaccia del tema in inglese;
 * - il link «Read this article in English / Leggi questo articolo in italiano» in cima al testo;
 * - il menu: le pagine prendono titolo e indirizzo della versione inglese, più la voce per cambiare lingua.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CAA_CAMPO_EN', 'caa_versione_inglese_di' );

function caa_bilingue() {
	return (bool) caa_opzione( 'bilingue' );
}

/* ---------- il campo che abbina le due versioni ---------- */

function caa_puo_abbinare( $consentito, $chiave, $id ) {
	return current_user_can( 'edit_post', $id );
}

/** Registrato anche per l'API REST, così la versione inglese si abbina nel momento stesso in cui la si crea. */
function caa_registra_campo_en() {
	foreach ( array( 'post', 'page' ) as $tipo ) {
		register_post_meta(
			$tipo,
			CAA_CAMPO_EN,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'description'       => 'ID dell\'articolo o della pagina in italiano di cui questo è la versione inglese.',
				'sanitize_callback' => 'absint',
				'auth_callback'     => 'caa_puo_abbinare',
			)
		);
	}
}
add_action( 'init', 'caa_registra_campo_en' );

/** Le coppie pubblicate, versione inglese => originale italiano. Una sola lettura per richiesta. */
function caa_coppie() {
	static $coppie = null;
	if ( null === $coppie ) {
		global $wpdb;
		$coppie = array();
		$righe  = $wpdb->get_results( $wpdb->prepare( "SELECT m.post_id, m.meta_value FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = %s AND p.post_status = 'publish' AND p.post_type IN ('post','page')", CAA_CAMPO_EN ) );
		foreach ( (array) $righe as $riga ) {
			if ( (int) $riga->meta_value > 0 ) {
				$coppie[ (int) $riga->post_id ] = (int) $riga->meta_value;
			}
		}
	}
	return $coppie;
}

function caa_inglesi() {
	return array_keys( caa_coppie() );
}

function caa_e_inglese( $id ) {
	$coppie = caa_coppie();
	return isset( $coppie[ (int) $id ] );
}

/** L'altra versione di un articolo o di una pagina: l'ID, oppure 0 se non c'è (o non è pubblicata). */
function caa_gemello( $id ) {
	$id     = (int) $id;
	$coppie = caa_coppie();
	if ( isset( $coppie[ $id ] ) ) {
		return 'publish' === get_post_status( $coppie[ $id ] ) ? $coppie[ $id ] : 0;
	}
	$inglese = array_search( $id, $coppie, true );
	return false === $inglese ? 0 : (int) $inglese;
}

/** Vero quando la pagina che si sta componendo è in inglese: un indirizzo sotto /en/ oppure un articolo o una pagina in inglese. */
function caa_in_inglese() {
	global $wp_the_query;
	if ( ! ( $wp_the_query instanceof WP_Query ) || ! caa_bilingue() || ! caa_pubblico() ) {
		return false;
	}
	if ( $wp_the_query->get( 'caa_en' ) ) {
		return true;
	}
	return $wp_the_query->is_singular() && ! empty( $wp_the_query->post ) && caa_e_inglese( $wp_the_query->post->ID );
}

/* ---------- gli indirizzi sotto /en/ ---------- */

function caa_variabile_en( $variabili ) {
	$variabili[] = 'caa_en';
	return $variabili;
}
add_filter( 'query_vars', 'caa_variabile_en' );

function caa_regole_en() {
	if ( ! caa_bilingue() ) {
		return;
	}
	add_rewrite_rule( '^en/?$', 'index.php?caa_en=1', 'top' );
	add_rewrite_rule( '^en/page/([0-9]+)/?$', 'index.php?caa_en=1&paged=$matches[1]', 'top' );
	add_rewrite_rule( '^en/feed/?$', 'index.php?caa_en=1&feed=rss2', 'top' );
	$basi = array(
		( get_option( 'category_base' ) ? trim( get_option( 'category_base' ), '/' ) : 'category' ) => 'category_name',
		( get_option( 'tag_base' ) ? trim( get_option( 'tag_base' ), '/' ) : 'tag' )                => 'tag',
	);
	foreach ( $basi as $base => $variabile ) {
		add_rewrite_rule( '^en/' . $base . '/(.+?)/page/([0-9]+)/?$', 'index.php?caa_en=1&' . $variabile . '=$matches[1]&paged=$matches[2]', 'top' );
		add_rewrite_rule( '^en/' . $base . '/(.+?)/?$', 'index.php?caa_en=1&' . $variabile . '=$matches[1]', 'top' );
	}
}
add_action( 'init', 'caa_regole_en', 20 );

/** Le regole si riscrivono una volta sola, quando cambia la versione del plugin o si accende/spegne «due lingue». */
function caa_aggiorna_regole() {
	$impronta = CAA_VERSIONE . ( caa_bilingue() ? '+en' : '' );
	if ( get_option( 'caa_regole' ) !== $impronta ) {
		flush_rewrite_rules( false );
		update_option( 'caa_regole', $impronta );
	}
}
add_action( 'wp_loaded', 'caa_aggiorna_regole' );

/**
 * L'indirizzo di casa senza passare dai filtri (vedi caa_casa_in_inglese), ma con lo schema della richiesta:
 * sul sito l'indirizzo salvato è ancora in http, mentre le pagine si leggono in https.
 */
function caa_casa() {
	return set_url_scheme( trailingslashit( get_option( 'home' ) ) );
}

/** Lo stesso indirizzo, sotto /en/. */
function caa_con_en( $url ) {
	$casa = caa_casa();
	$url  = set_url_scheme( $url, wp_parse_url( $casa, PHP_URL_SCHEME ) );
	if ( 0 === strpos( $url, $casa ) && 0 !== strpos( $url, $casa . 'en/' ) ) {
		$url = $casa . 'en/' . substr( $url, strlen( $casa ) );
	}
	return $url;
}

/** Dentro la pagina (non nell'intestazione, dove servono gli indirizzi veri) «casa» è l'indice inglese: insegna e modulo di ricerca restano in inglese. */
function caa_casa_in_inglese( $url, $percorso ) {
	return ( '/' === $percorso && did_action( 'wp_body_open' ) && caa_in_inglese() ) ? trailingslashit( $url ) . 'en/' : $url;
}
add_filter( 'home_url', 'caa_casa_in_inglese', 10, 2 );

/** Categorie e tag di un articolo in inglese portano all'elenco in inglese. */
function caa_link_termine_en( $link, $termine, $tassonomia ) {
	return ( in_array( $tassonomia, array( 'category', 'post_tag' ), true ) && caa_in_inglese() ) ? caa_con_en( $link ) : $link;
}
add_filter( 'term_link', 'caa_link_termine_en', 10, 3 );

/** Yoast SEO: l'indirizzo canonico di ciò che sta sotto /en/ resta sotto /en/. */
function caa_canonico_en( $url ) {
	return ( is_string( $url ) && '' !== $url && get_query_var( 'caa_en' ) && caa_in_inglese() ) ? caa_con_en( $url ) : $url;
}
add_filter( 'wpseo_canonical', 'caa_canonico_en', 20 );
add_filter( 'wpseo_opengraph_url', 'caa_canonico_en', 20 );

/* ---------- lingua della pagina ---------- */

/** Tema e plugin parlano inglese sulle pagine in inglese: date, «Search», «Related posts», modulo dei commenti. */
function caa_lingua_della_pagina() {
	if ( caa_in_inglese() ) {
		switch_to_locale( 'en_US' );
	}
}
add_action( 'wp', 'caa_lingua_della_pagina', 1 );

function caa_attributi_lingua( $attributi ) {
	return caa_in_inglese() ? preg_replace( '/lang="[^"]*"/', 'lang="en"', $attributi ) : $attributi;
}
add_filter( 'language_attributes', 'caa_attributi_lingua' );

/** I nomi italiani delle categorie, in inglese. */
function caa_nomi_inglesi() {
	return (array) apply_filters(
		'caa_nomi_inglesi',
		array(
			'mostre'   => 'Exhibitions',
			'arte'     => 'Art',
			'artisti'  => 'Artists',
			'opere'    => 'Artworks',
			'musei'    => 'Museums',
			'gallerie' => 'Galleries',
		)
	);
}

function caa_termine_in_inglese( $termine ) {
	if ( $termine instanceof WP_Term && 'category' === $termine->taxonomy && caa_in_inglese() ) {
		$nomi = caa_nomi_inglesi();
		if ( isset( $nomi[ $termine->slug ] ) ) {
			$termine->name = $nomi[ $termine->slug ];
		}
	}
	return $termine;
}
add_filter( 'get_term', 'caa_termine_in_inglese' );

/** Il modulo di ricerca nella colonna ha le scritte salvate in italiano. */
function caa_cerca_in_inglese( $blocco ) {
	if ( isset( $blocco['blockName'] ) && 'core/search' === $blocco['blockName'] && caa_in_inglese() ) {
		$blocco['attrs']['label']      = 'Search';
		$blocco['attrs']['buttonText'] = 'Search';
	}
	return $blocco;
}
add_filter( 'render_block_data', 'caa_cerca_in_inglese' );

/* ---------- da una lingua all'altra ---------- */

/** Gli indirizzi italiano e inglese della pagina corrente, o un array vuoto se la pagina non ha le due versioni. */
function caa_due_indirizzi() {
	if ( ! caa_bilingue() ) {
		return array();
	}
	if ( is_singular() ) {
		$id    = (int) get_queried_object_id();
		$altro = caa_gemello( $id );
		if ( ! $altro ) {
			return array();
		}
		return caa_e_inglese( $id )
			? array( 'it' => get_permalink( $altro ), 'en' => get_permalink( $id ) )
			: array( 'it' => get_permalink( $id ), 'en' => get_permalink( $altro ) );
	}
	if ( is_home() && ! is_paged() && caa_inglesi() ) {
		return array( 'it' => caa_casa(), 'en' => caa_casa() . 'en/' );
	}
	return array();
}

/** hreflang: dice ai motori di ricerca che le due pagine sono la stessa cosa in due lingue. Chi non parla né l'una né l'altra riceve l'inglese. */
function caa_hreflang() {
	$due = caa_due_indirizzi();
	if ( ! $due ) {
		return;
	}
	foreach ( array( 'it' => $due['it'], 'en' => $due['en'], 'x-default' => $due['en'] ) as $lingua => $url ) {
		printf( '<link rel="alternate" hreflang="%s" href="%s" />' . "\n", esc_attr( $lingua ), esc_url( $url ) );
	}
}
add_action( 'wp_head', 'caa_hreflang', 2 );

/** In cima al testo, il link all'altra versione. */
function caa_link_altra_versione( $contenuto ) {
	if ( ! caa_bilingue() || ! is_singular() || ! in_the_loop() || ! is_main_query() || doing_filter( 'get_the_excerpt' ) || (int) get_the_ID() !== (int) get_queried_object_id() ) {
		return $contenuto;
	}
	$altro = caa_gemello( get_the_ID() );
	if ( ! $altro ) {
		return $contenuto;
	}
	$pagina = 'page' === get_post_type();
	if ( caa_e_inglese( get_the_ID() ) ) {
		$lingua = 'it';
		$testo  = $pagina ? 'Leggi questa pagina in italiano' : 'Leggi questo articolo in italiano';
	} else {
		$lingua = 'en';
		$testo  = $pagina ? 'Read this page in English' : 'Read this article in English';
	}
	return sprintf( '<p class="caa-altra-lingua"><a href="%1$s" hreflang="%2$s" lang="%2$s">%3$s</a></p>', esc_url( get_permalink( $altro ) ), $lingua, esc_html( $testo ) ) . $contenuto;
}
add_filter( 'the_content', 'caa_link_altra_versione', 20 );

/** La voce per cambiare lingua: porta all'altra versione della pagina, o all'indice dell'altra lingua. Vuota se non c'è ancora nulla in inglese. */
function caa_voce_lingua_html( $classe ) {
	if ( ! caa_bilingue() || ! caa_pubblico() || ! caa_inglesi() ) {
		return '';
	}
	$inglese = caa_in_inglese();
	$due     = caa_due_indirizzi();
	$lingua  = $inglese ? 'it' : 'en';
	$url     = $due ? $due[ $lingua ] : caa_casa() . ( $inglese ? '' : 'en/' );
	return sprintf( '<li class="%1$s caa-lingua"><a href="%2$s" hreflang="%3$s" lang="%3$s">%4$s</a></li>', esc_attr( $classe ), esc_url( $url ), $lingua, $inglese ? 'Italiano' : 'English' );
}

/** In fondo al menu principale… */
function caa_voce_lingua( $voci, $args ) {
	$menu = (array) apply_filters( 'caa_menu_con_lingua', array( 'primary-menu', 'mobile-menu' ) );
	return ( ! empty( $args->theme_location ) && in_array( $args->theme_location, $menu, true ) ) ? $voci . caa_voce_lingua_html( 'menu-item' ) : $voci;
}
add_filter( 'wp_nav_menu_items', 'caa_voce_lingua', 10, 2 );

/** …e in fondo all'elenco di pagine che il tema Koji usa come menu quando non gliene è stato assegnato uno (lo si riconosce dall'argomento «container»). */
function caa_voce_lingua_pagine( $html, $args ) {
	return ( is_array( $args ) && array_key_exists( 'container', $args ) ) ? $html . caa_voce_lingua_html( 'page_item' ) : $html;
}
add_filter( 'wp_list_pages', 'caa_voce_lingua_pagine', 10, 2 );
