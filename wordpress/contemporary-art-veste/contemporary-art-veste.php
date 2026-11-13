<?php
/**
 * Plugin Name:       Contemporary Art — veste grafica
 * Description:       Veste "white cube" sopra il tema Koji e interruttore che mostra al pubblico solo i contenuti d'arte. Si regola da Impostazioni → Contemporary Art; disattivando il plugin il sito torna com'era.
 * Version:           1.1.0
 * Requires at least: 5.6
 * Requires PHP:      7.0
 * License:           MIT
 * Text Domain:       contemporary-art-veste
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CAA_VERSIONE', '1.1.0' );

/** Le due opzioni, entrambe accese finché non si spengono da Impostazioni → Contemporary Art. */
function caa_opzione( $nome ) {
	$salvate = get_option( 'caa_opzioni', array() );
	return isset( $salvate[ $nome ] ) ? (int) $salvate[ $nome ] : 1;
}

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
	if ( ! caa_opzione( 'veste' ) && ! caa_opzione( 'solo_arte' ) ) {
		return;                                         // le regole sono tutte sotto body.caa-veste o body.caa-solo-arte
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
	return $classi;
}
add_filter( 'body_class', 'caa_classi_body' );

/** Niente immagine di riserva del vecchio blog: un articolo senza copertina diventa un cartello tipografico. */
function caa_senza_immagine_di_riserva( $valore ) {
	return caa_opzione( 'veste' ) ? true : $valore;
}
add_filter( 'theme_mod_koji_disable_fallback_image', 'caa_senza_immagine_di_riserva' );

/* ---------- solo arte ---------- */

function caa_filtro_attivo() {
	if ( ! caa_opzione( 'solo_arte' ) ) {
		return false;
	}
	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() ) {
		return false;                                   // l'editor, Jetpack e gli strumenti di gestione vedono sempre tutto
	}
	if ( is_admin() ) {
		return wp_doing_ajax() && isset( $_REQUEST['action'] ) && 'koji_ajax_load_more' === $_REQUEST['action'];   // phpcs:ignore -- solo il "carica altri" del tema
	}
	return true;
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

/** Elenchi di articoli (home, archivi, ricerca, feed, "articoli recenti", correlati del tema): fuori quelli nascosti. */
function caa_segna_richiesta( $q ) {
	if ( ! caa_filtro_attivo() ) {
		return;
	}
	if ( $q->is_singular() || $q->get( 'p' ) || $q->get( 'name' ) || $q->get( 'page_id' ) || $q->get( 'pagename' ) || $q->get( 'post__in' ) ) {
		return;                                         // chi ha il link diretto continua a leggere l'articolo
	}
	$tipo = $q->get( 'post_type' );
	if ( $tipo && 'any' !== $tipo && 'post' !== $tipo && ! ( is_array( $tipo ) && in_array( 'post', $tipo, true ) ) ) {
		return;                                         // menu, pagine, immagini: non si toccano
	}
	$via = caa_articoli_nascosti();
	if ( $q->is_search() ) {
		$via = array_merge( $via, caa_pagine_escluse() );
	}
	if ( $via ) {
		$q->set( 'post__not_in', array_merge( (array) $q->get( 'post__not_in' ), $via ) );
	}
}
add_action( 'pre_get_posts', 'caa_segna_richiesta' );

/** Ricerca di Relevanssi: seconda sicura, nel caso un altro plugin riscriva la richiesta. */
function caa_relevanssi( $va_bene, $id ) {
	if ( $va_bene && caa_filtro_attivo() && ( in_array( (int) $id, caa_articoli_nascosti(), true ) || in_array( (int) $id, caa_pagine_escluse(), true ) ) ) {
		return false;
	}
	return $va_bene;
}
add_filter( 'relevanssi_post_ok', 'caa_relevanssi', 20, 2 );

/** "Articolo precedente / successivo" in fondo a un articolo. */
function caa_vicini( $dove ) {
	$via = caa_filtro_attivo() ? caa_articoli_nascosti() : array();
	return $via ? $dove . ' AND p.ID NOT IN (' . implode( ',', $via ) . ')' : $dove;
}
add_filter( 'get_previous_post_where', 'caa_vicini' );
add_filter( 'get_next_post_where', 'caa_vicini' );

/** "Correlati" di Jetpack: si chiede di escluderli e, per sicurezza, si tolgono anche dalla risposta. */
function caa_correlati_esclusi( $esclusi ) {
	return caa_filtro_attivo() ? array_merge( (array) $esclusi, caa_articoli_nascosti() ) : $esclusi;
}
add_filter( 'jetpack_relatedposts_filter_exclude_post_ids', 'caa_correlati_esclusi' );

function caa_correlati( $risultati ) {
	if ( ! is_array( $risultati ) || ! caa_filtro_attivo() ) {
		return $risultati;
	}
	$via    = caa_articoli_nascosti();
	$tenuti = array();
	foreach ( $risultati as $uno ) {
		if ( ! is_array( $uno ) || ! isset( $uno['id'] ) || ! in_array( (int) $uno['id'], $via, true ) ) {
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

/** Voci di menu ed elenchi di pagine: via quelle che parlano d'altro. */
function caa_menu( $voci ) {
	if ( ! caa_filtro_attivo() ) {
		return $voci;
	}
	$escluse = caa_pagine_escluse();
	foreach ( $voci as $chiave => $voce ) {
		if ( 'page' === $voce->object && in_array( (int) $voce->object_id, $escluse, true ) ) {
			unset( $voci[ $chiave ] );
		}
	}
	return $voci;
}
add_filter( 'wp_nav_menu_objects', 'caa_menu' );

function caa_elenco_pagine( $pagine ) {
	if ( ! caa_filtro_attivo() ) {
		return $pagine;
	}
	$escluse = caa_pagine_escluse();
	$tenute  = array();
	foreach ( (array) $pagine as $pagina ) {
		if ( ! in_array( (int) $pagina->ID, $escluse, true ) ) {
			$tenute[] = $pagina;
		}
	}
	return $tenute;
}
add_filter( 'get_pages', 'caa_elenco_pagine' );

/* ---------- pagina delle impostazioni ---------- */

function caa_pulisci_opzioni( $valori ) {
	return array(
		'veste'     => empty( $valori['veste'] ) ? 0 : 1,
		'solo_arte' => empty( $valori['solo_arte'] ) ? 0 : 1,
	);
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
					<th scope="row">Solo arte</th>
					<td>
						<label><input type="checkbox" name="caa_opzioni[solo_arte]" value="1" <?php checked( caa_opzione( 'solo_arte' ) ); ?>> Mostra al pubblico solo i contenuti d'arte</label>
						<p class="description">Nasconde da home, archivi, ricerca, feed, correlati, indici e menu gli articoli scritti prima del 2026 (salvo quelli nelle categorie d'arte), le loro categorie e le pagine del vecchio blog. Nulla viene cancellato: tutto resta qui nel pannello e raggiungibile dal proprio indirizzo.</p>
					</td>
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
