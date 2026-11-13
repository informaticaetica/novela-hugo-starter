<?php
/**
 * Plugin Name:       Contemporary Art — veste grafica
 * Description:       Veste "white cube" sopra il tema Koji, interruttore che mostra al pubblico solo i contenuti d'arte, articoli in italiano e in inglese. Si regola da Impostazioni → Contemporary Art; disattivando il plugin il sito torna com'era.
 * Version:           1.4.0
 * Requires at least: 5.6
 * Requires PHP:      7.0
 * License:           MIT
 * Text Domain:       contemporary-art-veste
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CAA_VERSIONE', '1.4.0' );

/**
 * Le spunte di Impostazioni → Contemporary Art. Veste, logo_testo, solo_arte e fuori_dai_motori sono accese finché
 * non si spengono; «bilingue» (versioni inglesi come articoli separati) è spenta finché non si accende, perché sul
 * sito ogni articolo porta italiano e inglese nella stessa pagina.
 */
function caa_opzione( $nome ) {
	$salvate = get_option( 'caa_opzioni', array() );
	return isset( $salvate[ $nome ] ) ? (int) $salvate[ $nome ] : ( 'bilingue' === $nome ? 0 : 1 );
}

/** Le due frasi che dicono a chi cerca che cos'è il sito: finiscono nella descrizione per i motori di ricerca della home italiana e di quella inglese. */
function caa_testi_predefiniti() {
	return array(
		'descrizione_it' => 'Contemporary Art promuove la conoscenza dell’arte internazionale: mostre, artisti e opere raccontati con chiarezza, in italiano e in inglese.',
		'descrizione_en' => 'Contemporary Art promotes knowledge of international art: exhibitions, artists and artworks explained clearly, in English and Italian.',
	);
}
function caa_testo( $nome ) {
	$salvate = get_option( 'caa_opzioni', array() );
	if ( isset( $salvate[ $nome ] ) && is_string( $salvate[ $nome ] ) ) {
		return $salvate[ $nome ];                      // anche vuoto: vuol dire «lascia la descrizione di prima»
	}
	$predefiniti = caa_testi_predefiniti();
	return isset( $predefiniti[ $nome ] ) ? $predefiniti[ $nome ] : '';
}

require_once __DIR__ . '/lingue.php';

/** Pagine che parlano d'altro (vecchio blog): con "solo arte" spariscono da menu ed elenchi, ma restano raggiungibili dal loro indirizzo. */
function caa_pagine_escluse() {
	// Mucca Gialla, Analfabetismo funzionale, Gym, Laboratorio digitale a Sapri, Cilento, APPO, Bravi cittadini, Psicologia aperta, Da scoprire, Eccellenze.
	return array_map( 'intval', apply_filters( 'caa_pagine_escluse', array( 47, 109, 192, 357, 294, 319, 322, 380, 119, 122 ) ) );
}

/** Gli articoli scritti da questa data in poi sono quelli d'arte; quelli più vecchi si vedono solo se stanno in una categoria d'arte. */
function caa_inizio_arte() {
	return apply_filters( 'caa_inizio_arte', '2026-01-01 00:00:00' );
}
function caa_categorie_arte() {
	return (array) apply_filters( 'caa_categorie_arte', array( 'mostre', 'arte', 'artisti', 'opere', 'musei', 'gallerie' ) );
}

/* ---------- veste grafica ---------- */

function caa_stile() {
	if ( ! caa_opzione( 'veste' ) && ! caa_opzione( 'solo_arte' ) && ! caa_bilingue() ) {
		return;                                         // le regole sono tutte sotto body.caa-veste o body.caa-solo-arte, più i link fra le lingue
	}
	$dopo = wp_style_is( 'koji-style', 'registered' ) ? array( 'koji-style' ) : array();
	wp_enqueue_style( 'caa-veste', plugins_url( 'stile.css', __FILE__ ), $dopo, CAA_VERSIONE );
}
add_action( 'wp_enqueue_scripts', 'caa_stile', 20 );

function caa_classi_body( $classi ) {
	if ( caa_opzione( 'veste' ) ) {
		$classi[] = 'caa-veste';
	}
	if ( caa_opzione( 'solo_arte' ) ) {
		$classi[] = 'caa-solo-arte';
	}
	if ( caa_in_inglese() ) {
		$classi[] = 'caa-en';
	}
	return $classi;
}
add_filter( 'body_class', 'caa_classi_body' );

/** Niente immagine di riserva del vecchio blog: un articolo senza copertina diventa un cartello tipografico. */
function caa_senza_immagine_di_riserva( $valore ) {
	return caa_opzione( 'veste' ) ? true : $valore;
}
add_filter( 'theme_mod_koji_disable_fallback_image', 'caa_senza_immagine_di_riserva' );

/** Al posto dell'immagine del logo il tema scrive il nome del sito, che la veste compone in caratteri. Il logo resta salvato: nel pannello non cambia nulla. */
function caa_senza_logo( $logo ) {
	if ( is_admin() || is_customize_preview() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! caa_opzione( 'veste' ) || ! caa_opzione( 'logo_testo' ) ) {
		return $logo;
	}
	return false;
}
add_filter( 'theme_mod_custom_logo', 'caa_senza_logo' );

/* ---------- solo arte ---------- */

/** Vero quando si compone una pagina per il pubblico. Pannello, editor, Jetpack, cron e riga di comando vedono sempre tutto. */
function caa_pubblico() {
	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() ) {
		return false;
	}
	if ( is_admin() ) {
		return wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'koji_ajax_load_more' === $_REQUEST['action'];   // phpcs:ignore -- solo il "carica altri" del tema
	}
	return true;
}

function caa_filtro_attivo() {
	return caa_opzione( 'solo_arte' ) && caa_pubblico();
}

/**
 * Gli articoli da tenere fuori dagli elenchi: scritti prima di caa_inizio_arte() e fuori dalle categorie d'arte.
 * Una sola lettura per richiesta. Si lavora per elenco di ID (e non ritoccando l'SQL) perché post__not_in è l'unica
 * esclusione rispettata da tutti: WordPress, get_posts() del tema, articoli in evidenza e la ricerca di Relevanssi.
 */
function caa_articoli_nascosti() {
	static $nascosti = null;
	if ( null === $nascosti ) {
		global $wpdb;
		$sql  = $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type = 'post' AND p.post_date < %s", caa_inizio_arte() );
		$slug = array_filter( array_map( 'sanitize_title', caa_categorie_arte() ) );
		if ( $slug ) {
			$sql .= " AND p.ID NOT IN ( SELECT tr.object_id FROM {$wpdb->term_relationships} tr"
				. " JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id JOIN {$wpdb->terms} t ON t.term_id = tt.term_id"
				. " WHERE tt.taxonomy = 'category' AND t.slug IN ('" . implode( "','", array_map( 'esc_sql', $slug ) ) . "') )";
		}
		$nascosti = array_map( 'intval', $wpdb->get_col( $sql ) );   // phpcs:ignore WordPress.DB.PreparedSQL -- preparata qui sopra
	}
	return $nascosti;
}

/** Elenchi di articoli (home, archivi, ricerca, feed, "articoli recenti", correlati del tema): fuori quelli nascosti e quelli nell'altra lingua. */
function caa_segna_richiesta( $q ) {
	if ( ! caa_pubblico() ) {
		return;
	}
	if ( $q->is_singular() || $q->get( 'p' ) || $q->get( 'name' ) || $q->get( 'page_id' ) || $q->get( 'pagename' ) || $q->get( 'post__in' ) ) {
		return;                                         // chi ha il link diretto continua a leggere l'articolo
	}
	$tipo = $q->get( 'post_type' );
	if ( $tipo && 'any' !== $tipo && 'post' !== $tipo && ! ( is_array( $tipo ) && in_array( 'post', $tipo, true ) ) ) {
		return;                                         // menu, pagine, immagini: non si toccano
	}
	$via = array();
	if ( caa_opzione( 'solo_arte' ) ) {
		$via = caa_articoli_nascosti();
		if ( $q->is_search() ) {
			$via = array_merge( $via, caa_pagine_escluse() );
		}
	}
	if ( caa_bilingue() ) {
		if ( $q->get( 'caa_en' ) || caa_in_inglese() ) {
			// Si legge in inglese: solo ciò che è in inglese. post__in ha la precedenza su post__not_in, che quindi si sottrae a mano.
			$solo = array_diff( caa_inglesi(), $via, array_map( 'intval', (array) $q->get( 'post__not_in' ) ) );
			$q->set( 'post__in', $solo ? array_values( $solo ) : array( 0 ) );
			$q->set( 'ignore_sticky_posts', true );
			return;
		}
		$via = array_merge( $via, caa_inglesi() );
	}
	if ( $via ) {
		$q->set( 'post__not_in', array_merge( (array) $q->get( 'post__not_in' ), $via ) );
	}
}
add_action( 'pre_get_posts', 'caa_segna_richiesta' );

/** Ricerca di Relevanssi: seconda sicura, nel caso un altro plugin riscriva la richiesta. */
function caa_relevanssi( $va_bene, $id ) {
	if ( ! $va_bene || ! caa_pubblico() ) {
		return $va_bene;
	}
	$id = (int) $id;
	if ( caa_opzione( 'solo_arte' ) && ( in_array( $id, caa_articoli_nascosti(), true ) || in_array( $id, caa_pagine_escluse(), true ) ) ) {
		return false;
	}
	if ( caa_bilingue() && caa_in_inglese() !== caa_e_inglese( $id ) ) {
		return false;
	}
	return $va_bene;
}
add_filter( 'relevanssi_post_ok', 'caa_relevanssi', 20, 2 );

/** "Articolo precedente / successivo" in fondo a un articolo. */
function caa_vicini( $dove ) {
	if ( ! caa_pubblico() ) {
		return $dove;
	}
	$via = caa_opzione( 'solo_arte' ) ? caa_articoli_nascosti() : array();
	if ( caa_bilingue() ) {
		$inglesi = caa_inglesi();
		if ( caa_in_inglese() ) {
			return $dove . ' AND p.ID IN (' . implode( ',', $inglesi ? $inglesi : array( 0 ) ) . ')';
		}
		$via = array_merge( $via, $inglesi );
	}
	return $via ? $dove . ' AND p.ID NOT IN (' . implode( ',', $via ) . ')' : $dove;
}
add_filter( 'get_previous_post_where', 'caa_vicini' );
add_filter( 'get_next_post_where', 'caa_vicini' );

/** "Correlati" di Jetpack: si chiede di escluderli e, per sicurezza, si tolgono anche dalla risposta. */
function caa_correlati_esclusi( $esclusi ) {
	if ( ! caa_pubblico() ) {
		return $esclusi;
	}
	$esclusi = (array) $esclusi;
	if ( caa_opzione( 'solo_arte' ) ) {
		$esclusi = array_merge( $esclusi, caa_articoli_nascosti() );
	}
	if ( caa_bilingue() && ! caa_in_inglese() ) {
		$esclusi = array_merge( $esclusi, caa_inglesi() );
	}
	return $esclusi;
}
add_filter( 'jetpack_relatedposts_filter_exclude_post_ids', 'caa_correlati_esclusi' );

function caa_correlati( $risultati ) {
	if ( ! is_array( $risultati ) || ! caa_pubblico() ) {
		return $risultati;
	}
	$via     = caa_opzione( 'solo_arte' ) ? caa_articoli_nascosti() : array();
	$due     = caa_bilingue();
	$inglese = $due && caa_in_inglese();
	$tenuti  = array();
	foreach ( $risultati as $uno ) {
		$id = ( is_array( $uno ) && isset( $uno['id'] ) ) ? (int) $uno['id'] : 0;
		if ( ! $id || ( ! in_array( $id, $via, true ) && ( ! $due || caa_e_inglese( $id ) === $inglese ) ) ) {
			$tenuti[] = $uno;
		}
	}
	return $tenuti;
}
add_filter( 'jetpack_relatedposts_returned_results', 'caa_correlati' );

/** Le categorie che hanno ancora almeno un articolo visibile (più le loro categorie madri). */
function caa_categorie_vive() {
	static $vive = null;
	if ( null === $vive ) {
		global $wpdb;
		$sql = "SELECT DISTINCT tt.term_id FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id"
			. " JOIN {$wpdb->posts} p ON p.ID = tr.object_id WHERE tt.taxonomy = 'category' AND p.post_type = 'post' AND p.post_status = 'publish'";
		$via = caa_articoli_nascosti();
		if ( $via ) {
			$sql .= ' AND p.ID NOT IN (' . implode( ',', $via ) . ')';
		}
		$dirette = array_map( 'intval', $wpdb->get_col( $sql ) );   // phpcs:ignore WordPress.DB.PreparedSQL -- solo numeri interi
		$vive    = $dirette;
		foreach ( $dirette as $id ) {
			$vive = array_merge( $vive, get_ancestors( $id, 'category' ) );
		}
	}
	return $vive;
}

/** Elenchi di categorie (indice della pagina Info, widget): via quelle rimaste senza articoli visibili. Le categorie di un singolo articolo non si toccano. */
function caa_categorie( $termini, $tassonomie, $args ) {
	if ( ! is_array( $termini ) || ! $termini || ! empty( $args['object_ids'] ) || ! in_array( 'category', (array) $tassonomie, true ) || ! caa_filtro_attivo() ) {
		return $termini;
	}
	$vive    = caa_categorie_vive();
	$a_lista = array_keys( $termini ) === range( 0, count( $termini ) - 1 );
	$tenuti  = array();
	foreach ( $termini as $chiave => $termine ) {
		if ( ! ( $termine instanceof WP_Term ) || 'category' !== $termine->taxonomy || in_array( (int) $termine->term_id, $vive, true ) ) {
			$tenuti[ $chiave ] = $termine;
		}
	}
	return $a_lista ? array_values( $tenuti ) : $tenuti;
}
add_filter( 'get_terms', 'caa_categorie', 10, 3 );

/* ---------- fuori dai motori di ricerca ---------- */

/** Vero sulle pagine che i motori non devono più indicizzare: articoli e pagine nascosti, archivi rimasti vuoti. */
function caa_da_non_indicizzare() {
	if ( ! caa_opzione( 'solo_arte' ) || ! caa_opzione( 'fuori_dai_motori' ) ) {
		return false;
	}
	if ( is_singular() ) {
		$id = (int) get_queried_object_id();
		return in_array( $id, caa_articoli_nascosti(), true ) || in_array( $id, caa_pagine_escluse(), true );
	}
	global $wp_query;
	return ( is_category() || is_tag() || is_date() || is_author() ) && $wp_query && 0 === (int) $wp_query->post_count;
}

function caa_robots( $robots ) {
	if ( is_array( $robots ) && caa_da_non_indicizzare() ) {
		unset( $robots['index'] );
		$robots['noindex'] = true;
		if ( empty( $robots['nofollow'] ) ) {
			$robots['follow'] = true;
		}
	}
	return $robots;
}
add_filter( 'wp_robots', 'caa_robots', 999 );

/** Yoast SEO compone da sé l'etichetta per i motori: gli si dà la stessa risposta. */
function caa_robots_yoast( $robots ) {
	return caa_da_non_indicizzare() ? 'noindex, follow' : $robots;
}
add_filter( 'wpseo_robots', 'caa_robots_yoast', 999 );

/** …e si tolgono gli stessi contenuti dalla mappa del sito di Yoast. */
function caa_fuori_dalla_mappa( $esclusi ) {
	if ( ! caa_opzione( 'solo_arte' ) || ! caa_opzione( 'fuori_dai_motori' ) ) {
		return $esclusi;
	}
	return array_values( array_unique( array_merge( array_map( 'intval', (array) $esclusi ), caa_articoli_nascosti(), caa_pagine_escluse() ) ) );
}
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', 'caa_fuori_dalla_mappa' );

/** Il tema stampa due volte gli stessi widget quando l'elenco salvato contiene doppioni: se ne tiene uno. */
function caa_senza_doppioni( $aree ) {
	if ( is_admin() || ! is_array( $aree ) || ! caa_opzione( 'veste' ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $aree;
	}
	foreach ( $aree as $nome => $widget ) {
		if ( is_array( $widget ) ) {
			$aree[ $nome ] = array_values( array_unique( $widget ) );
		}
	}
	return $aree;
}
add_filter( 'sidebars_widgets', 'caa_senza_doppioni' );

/** Voci di menu: via le pagine che parlano d'altro; leggendo in inglese, ogni pagina prende titolo e indirizzo della sua versione inglese. */
function caa_menu( $voci ) {
	if ( ! caa_pubblico() ) {
		return $voci;
	}
	$escluse = caa_opzione( 'solo_arte' ) ? caa_pagine_escluse() : array();
	$due     = caa_bilingue();
	$inglese = $due && caa_in_inglese();
	foreach ( $voci as $chiave => $voce ) {
		if ( 'page' !== $voce->object && 'post' !== $voce->object ) {
			continue;
		}
		$id = (int) $voce->object_id;
		if ( in_array( $id, $escluse, true ) || ( $due && caa_e_inglese( $id ) ) ) {
			unset( $voci[ $chiave ] );                  // le versioni inglesi non hanno una voce loro: prendono il posto dell'originale
			continue;
		}
		$versione = $inglese ? caa_gemello( $id ) : 0;
		if ( $versione ) {
			$voce->title = get_the_title( $versione );
			$voce->url   = get_permalink( $versione );
			if ( is_singular() && (int) get_queried_object_id() === $versione ) {
				$voce->current   = true;
				$voce->classes[] = 'current-menu-item';
			}
		}
	}
	return $voci;
}
add_filter( 'wp_nav_menu_objects', 'caa_menu' );

/**
 * Elenchi di pagine (indice della pagina Info, menu del telefono): via quelle che parlano d'altro; le versioni inglesi
 * non compaiono da sole ma, leggendo in inglese, prendono il posto dell'originale.
 */
function caa_elenco_pagine( $pagine ) {
	if ( ! caa_pubblico() ) {
		return $pagine;
	}
	$escluse = caa_opzione( 'solo_arte' ) ? caa_pagine_escluse() : array();
	$due     = caa_bilingue();
	$inglese = $due && caa_in_inglese();
	$tenute  = array();
	foreach ( (array) $pagine as $pagina ) {
		if ( in_array( (int) $pagina->ID, $escluse, true ) || ( $due && caa_e_inglese( $pagina->ID ) ) ) {
			continue;
		}
		$versione = $inglese ? caa_gemello( $pagina->ID ) : 0;   // attenzione: get_post( 0 ) restituirebbe l'articolo corrente
		$versione = $versione ? get_post( $versione ) : null;
		$tenute[] = $versione ? $versione : $pagina;
	}
	return $tenute;
}
add_filter( 'get_pages', 'caa_elenco_pagine' );

/* ---------- per i motori di ricerca ---------- */

/**
 * Descrizione della pagina (Yoast SEO): sulla home, la frase che dice che cos'è il sito, nella lingua della pagina;
 * su articoli e pagine senza una descrizione scritta a mano, il riassunto.
 */
function caa_descrizione( $testo ) {
	if ( is_home() && ! is_paged() ) {
		$mia = caa_testo( caa_in_inglese() ? 'descrizione_en' : 'descrizione_it' );
		return '' !== $mia ? $mia : $testo;
	}
	if ( '' === trim( (string) $testo ) && is_singular() && ! post_password_required( get_queried_object_id() ) ) {
		$riassunto = trim( wp_strip_all_tags( (string) get_post_field( 'post_excerpt', get_queried_object_id() ) ) );
		if ( '' !== $riassunto ) {
			return $riassunto;
		}
	}
	return $testo;
}
add_filter( 'wpseo_metadesc', 'caa_descrizione' );
add_filter( 'wpseo_opengraph_desc', 'caa_descrizione' );
add_filter( 'wpseo_twitter_description', 'caa_descrizione' );

/**
 * Articoli con le due lingue nella stessa pagina: la parte inglese comincia dall'elemento con ancora «english».
 * Gli si dichiara la lingua, così lettori di schermo e motori di ricerca sanno che da lì in poi il testo è in inglese.
 */
function caa_lingua_delle_parti( $contenuto ) {
	if ( false === strpos( $contenuto, 'id="english"' ) ) {
		return $contenuto;
	}
	return preg_replace( '/<([a-z0-9]+)\b((?:(?!\blang=)[^>])*?\bid="english"(?:(?!\blang=)[^>])*)>/i', '<$1$2 lang="en">', $contenuto, 1 );
}
add_filter( 'the_content', 'caa_lingua_delle_parti', 15 );

/** Le pagine si leggono in https: anche l'indirizzo canonico deve dirlo (la home e alcuni articoli lo dichiaravano ancora in http). */
function caa_indirizzo_sicuro( $url ) {
	return ( is_string( $url ) && '' !== $url && is_ssl() ) ? set_url_scheme( $url, 'https' ) : $url;
}
add_filter( 'wpseo_canonical', 'caa_indirizzo_sicuro', 5 );
add_filter( 'wpseo_opengraph_url', 'caa_indirizzo_sicuro', 5 );

/* ---------- pagina delle impostazioni ---------- */

function caa_pulisci_opzioni( $valori ) {
	$pulite = array();
	foreach ( array( 'veste', 'logo_testo', 'solo_arte', 'fuori_dai_motori', 'bilingue' ) as $nome ) {
		$pulite[ $nome ] = empty( $valori[ $nome ] ) ? 0 : 1;
	}
	foreach ( array_keys( caa_testi_predefiniti() ) as $nome ) {
		if ( isset( $valori[ $nome ] ) ) {
			$pulite[ $nome ] = mb_substr( sanitize_text_field( $valori[ $nome ] ), 0, 320 );
		}
	}
	return $pulite;
}
function caa_registra_opzioni() {
	register_setting( 'caa', 'caa_opzioni', array( 'type' => 'array', 'sanitize_callback' => 'caa_pulisci_opzioni' ) );
}
add_action( 'admin_init', 'caa_registra_opzioni' );

function caa_voce_impostazioni() {
	add_options_page( 'Contemporary Art', 'Contemporary Art', 'manage_options', 'caa', 'caa_pagina_impostazioni' );
}
add_action( 'admin_menu', 'caa_voce_impostazioni' );

function caa_pagina_impostazioni() {
	?>
	<div class="wrap">
		<h1>Contemporary Art</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'caa' ); ?>
			<input type="hidden" name="caa_opzioni[presente]" value="1">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Veste grafica</th>
					<td><label><input type="checkbox" name="caa_opzioni[veste]" value="1" <?php checked( caa_opzione( 'veste' ) ); ?>> Usa la veste «white cube» (fondo bianco, testo nero, accento blu)</label></td>
				</tr>
				<tr>
					<th scope="row">Nome al posto del logo</th>
					<td>
						<label><input type="checkbox" name="caa_opzioni[logo_testo]" value="1" <?php checked( caa_opzione( 'logo_testo' ) ); ?>> Scrivi il nome del sito in caratteri al posto dell'immagine del logo</label>
						<p class="description">Vale solo con la veste accesa. Il logo resta salvato nel tema: togliendo la spunta ricompare.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Solo arte</th>
					<td>
						<label><input type="checkbox" name="caa_opzioni[solo_arte]" value="1" <?php checked( caa_opzione( 'solo_arte' ) ); ?>> Mostra al pubblico solo i contenuti d'arte</label>
						<p class="description">Nasconde da home, archivi, ricerca, feed, correlati, indici e menu gli articoli scritti prima del 2026 (salvo quelli nelle categorie d'arte), le loro categorie e le pagine del vecchio blog. Nulla viene cancellato: tutto resta qui nel pannello e raggiungibile dal proprio indirizzo.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Fuori dai motori di ricerca</th>
					<td>
						<label><input type="checkbox" name="caa_opzioni[fuori_dai_motori]" value="1" <?php checked( caa_opzione( 'fuori_dai_motori' ) ); ?>> Chiedi a Google e agli altri motori di non indicizzare ciò che «Solo arte» nasconde</label>
						<p class="description">Vale solo con «Solo arte» acceso. Gli indirizzi continuano a funzionare per chi li ha; togliendo la spunta tornano indicizzabili, ma ai motori servono alcune settimane per accorgersene.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Versioni inglesi separate</th>
					<td>
						<label><input type="checkbox" name="caa_opzioni[bilingue]" value="1" <?php checked( caa_opzione( 'bilingue' ) ); ?>> Tieni la versione inglese di ogni articolo in un articolo a sé</label>
						<p class="description">Spenta: ogni articolo porta italiano e inglese nella stessa pagina. Accesa: la versione inglese è un articolo abbinato all'originale (campo <code>caa_versione_inglese_di</code>); in italiano si vedono solo gli articoli italiani, quelli inglesi stanno sotto <code>/en/</code> e le due versioni si rimandano l'una all'altra, anche per i motori di ricerca (hreflang).</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="caa-descrizione-it">Che cos'è il sito (italiano)</label></th>
					<td>
						<input type="text" class="large-text" id="caa-descrizione-it" name="caa_opzioni[descrizione_it]" maxlength="320" value="<?php echo esc_attr( caa_testo( 'descrizione_it' ) ); ?>">
						<p class="description">La frase che i motori di ricerca mostrano sotto il nome del sito. Meglio entro 155 caratteri.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="caa-descrizione-en">Che cos'è il sito (inglese)</label></th>
					<td><input type="text" class="large-text" id="caa-descrizione-en" name="caa_opzioni[descrizione_en]" maxlength="320" value="<?php echo esc_attr( caa_testo( 'descrizione_en' ) ); ?>"></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

function caa_link_impostazioni( $link ) {
	array_unshift( $link, '<a href="' . esc_url( admin_url( 'options-general.php?page=caa' ) ) . '">Impostazioni</a>' );
	return $link;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'caa_link_impostazioni' );

/** Disattivando il plugin spariscono anche gli indirizzi sotto /en/: WordPress ricostruisce le sue regole alla prima visita. */
function caa_alla_disattivazione() {
	delete_option( 'caa_regole' );
	delete_option( 'rewrite_rules' );
}
register_deactivation_hook( __FILE__, 'caa_alla_disattivazione' );
