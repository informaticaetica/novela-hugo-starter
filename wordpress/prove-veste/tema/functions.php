<?php
/* Le stesse posizioni di menu del tema Koji. */
add_action(
	'after_setup_theme',
	function () {
		register_nav_menus( array( 'primary-menu' => 'Principale', 'mobile-menu' => 'Telefono' ) );
		add_theme_support( 'title-tag' );
	}
);
