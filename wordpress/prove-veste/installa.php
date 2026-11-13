<?php
/* php installa.php <cartella del sito di prova>
   Installa WordPress e crea il contenuto delle prove: articoli vecchi e nuovi, categorie, due pagine, widget ripetuti. */
$_SERVER['HTTP_HOST']       = 'prova.test';
$_SERVER['REQUEST_URI']     = '/';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
define( 'WP_INSTALLING', true );
require $argv[1] . '/wp-load.php';
require ABSPATH . 'wp-admin/includes/upgrade.php';

wp_install( 'Prova', 'admin', 'prova@example.test', true, '', 'prova-prova-1' );
update_option( 'template', 'vuoto' );
update_option( 'stylesheet', 'vuoto' );
update_option( 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );
update_option( 'active_plugins', array( 'contemporary-art-veste/contemporary-art-veste.php' ) );
foreach ( array( 1, 2, 3 ) as $esempio ) {
	wp_delete_post( $esempio, true );
}

$cat = array();
foreach ( array( 'mostre', 'marketing', 'ho-imparato', 'eventi' ) as $slug ) {
	$t            = wp_insert_term( ucfirst( $slug ), 'category', array( 'slug' => $slug ) );
	$cat[ $slug ] = (int) $t['term_id'];
}
$t               = wp_insert_term( 'Biennali', 'category', array( 'slug' => 'biennali', 'parent' => $cat['eventi'] ) );
$cat['biennali'] = (int) $t['term_id'];

$ids      = array();
$articoli = array(   // nome => data, categorie. La prima lettera è la sigla usata nelle prove.
	'A-vecchio-marketing'   => array( '2025-10-01 10:00:00', array( 'marketing' ) ),
	'B-vecchio-in-evidenza' => array( '2025-11-01 10:00:00', array() ),
	'C-vecchio-ma-mostre'   => array( '2025-03-01 10:00:00', array( 'mostre' ) ),
	'D-nuovo-merz'          => array( '2026-10-14 10:00:00', array( 'mostre' ) ),
	'E-nuovo-kandinsky'     => array( '2026-11-10 10:00:00', array( 'mostre' ) ),
	'F-vecchio-dicembre'    => array( '2025-12-03 10:00:00', array( 'ho-imparato', 'marketing' ) ),
	'G-nuovo-biennale'      => array( '2026-05-02 10:00:00', array( 'biennali' ) ),
);
foreach ( $articoli as $nome => $d ) {
	$categorie = array( 1 );
	if ( $d[1] ) {
		$categorie = array();
		foreach ( $d[1] as $slug ) {
			$categorie[] = $cat[ $slug ];
		}
	}
	$ids[ $nome[0] ] = wp_insert_post(
		array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_title'    => $nome,
			'post_name'     => strtolower( $nome ),
			'post_content'  => 'testo comune arte ' . $nome,
			'post_date'     => $d[0],
			'post_author'   => 1,
			'post_category' => $categorie,
		)
	);
}
$ids['gym']  = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Gym', 'post_content' => 'testo comune arte palestra' ) );
$ids['info'] = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Info', 'post_content' => 'testo comune arte info' ) );

update_option( 'sticky_posts', array( $ids['B'] ) );
update_option( 'sidebars_widgets', array( 'wp_inactive_widgets' => array(), 'sidebar' => array( 'block-12', 'block-2', 'block-3', 'block-8', 'block-2', 'block-3', 'block-8' ), 'array_version' => 3 ) );
set_theme_mod( 'custom_logo', 123 );   // un logo qualsiasi, per provare «nome al posto del logo»
update_option( 'prova_ids', $ids );
echo 'installato: ', json_encode( $ids ), "\n";
