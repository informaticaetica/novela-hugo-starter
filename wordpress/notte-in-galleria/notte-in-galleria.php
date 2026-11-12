<?php
/**
 * Plugin Name:       Notte in galleria
 * Description:       Gioco 3D ambientato in una galleria d'arte con diciotto capolavori di pubblico dominio. Inserisci lo shortcode [notte_in_galleria] in una pagina o in un articolo.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * License:           MIT
 * Text Domain:       notte-in-galleria
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NIG_VERSIONE', '1.0.0' );

/**
 * Indirizzo del gioco: sono file statici dentro la cartella "gioco" di questo plugin.
 */
function nig_url_gioco() {
	return add_query_arg( 'v', NIG_VERSIONE, plugins_url( 'gioco/index.html', __FILE__ ) );
}

/**
 * Shortcode [notte_in_galleria larghezza="1200"]: mostra il gioco in un riquadro
 * che si adatta alla larghezza della pagina, con i pulsanti per lo schermo intero.
 */
function nig_shortcode( $atts ) {
	static $n = 0;
	$n++;

	$atts = shortcode_atts( array( 'larghezza' => '1200' ), $atts, 'notte_in_galleria' );
	$max  = max( 320, absint( $atts['larghezza'] ) );
	$id   = 'nig-gioco-' . $n;
	$url  = nig_url_gioco();

	$html  = '<div class="nig-gioco" style="max-width:' . $max . 'px;margin:1.5em auto;">';
	$html .= '<div style="position:relative;box-sizing:content-box;aspect-ratio:3/2;padding-bottom:46px;background:#0b0b17;">';
	$html .= '<iframe id="' . esc_attr( $id ) . '" src="' . esc_url( $url ) . '" title="Notte in galleria" loading="lazy" allow="fullscreen; autoplay" allowfullscreen style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;display:block;"></iframe>';
	$html .= '</div>';
	$html .= '<p style="text-align:center;font-size:.9em;margin:.6em 0 0;">';
	$html .= '<button type="button" onclick="var f=document.getElementById(\'' . esc_js( $id ) . '\');(f.requestFullscreen||f.webkitRequestFullscreen||function(){}).call(f);">Schermo intero</button>';
	$html .= ' &nbsp;·&nbsp; <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">Apri il gioco in una nuova scheda</a>';
	$html .= '</p></div>';

	return $html;
}
add_shortcode( 'notte_in_galleria', 'nig_shortcode' );
