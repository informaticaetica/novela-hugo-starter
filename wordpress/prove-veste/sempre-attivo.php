<?php
/* Le prove girano con «versioni inglesi separate» accesa (sul sito è spenta): è lì che c'è più da controllare. Il modo «una-lingua» la spegne. */
add_filter(
	'pre_option_caa_opzioni',
	function ( $valore ) {
		return is_array( $valore ) ? $valore : array( 'bilingue' => 1 );
	},
	5
);

/* Sul sito di prova la pagina «del vecchio blog» è Gym: il plugin, da solo, esclude gli ID del sito vero. */
add_filter(
	'caa_pagine_escluse',
	function () {
		$ids = get_option( 'prova_ids' );
		return isset( $ids['gym'] ) ? array( (int) $ids['gym'] ) : array();
	}
);
