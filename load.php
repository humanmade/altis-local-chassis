<?php

namespace Altis\Local_Chassis;

use function Altis\get_environment_architecture;
use function Altis\register_module;

// Load database configuration on Chassis installs.
if ( file_exists( '/vagrant/local-config-db.php' ) ) {
	define( 'HM_ENV_ARCHITECTURE', 'chassis' );
	require_once '/vagrant/local-config-db.php';
	require_once '/vagrant/local-config-extensions.php';
}

// Don't self-initialize if this is not an Altis execution.
if ( ! function_exists( 'add_action' ) ) {
	return;
}

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled' => get_environment_architecture() === 'chassis',
	];

	register_module( 'local-chassis', __DIR__, 'Local Chassis', $default_settings );
} );
