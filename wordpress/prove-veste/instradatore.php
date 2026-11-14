<?php
/* Per il server di prova di PHP (php -S): i file che esistono si servono così come sono, tutto il resto passa da WordPress. */
$percorso = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
if ( '/' !== $percorso && is_file( $_SERVER['DOCUMENT_ROOT'] . $percorso ) ) {
	return false;
}
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['DOCUMENT_ROOT'] . '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
