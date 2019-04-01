<?php

if ( file_exists( '/vagrant/local-config-db.php' ) ) {
	define( 'HM_ENV_ARCHITECTURE', 'chassis' );
	require_once '/vagrant/local-config-db.php';
	require_once '/vagrant/local-config-extensions.php';
}
