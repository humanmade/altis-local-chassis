<?php

// Set the architecture constant when on the VM.
if ( file_exists( '/vagrant/local-config-db.php' ) ) {
	define( 'HM_ENV_ARCHITECTURE', 'chassis' ); // phpcs:ignore
}
