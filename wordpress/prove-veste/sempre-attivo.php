<?php
/* Sul sito di prova la pagina «del vecchio blog» è Gym: il plugin, da solo, esclude gli ID del sito vero. */
add_filter(
	'caa_pagine_escluse',
	function () {
		$ids = get_option( 'prova_ids' );
		return isset( $ids['gym'] ) ? array( (int) $ids['gym'] ) : array();
	}
);
