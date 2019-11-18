<?php

namespace Altis\Local_Chassis;  // @codingStandardsIgnoreLine

use function Altis\get_environment_architecture;
use function Altis\register_module;

// Set the architecture constant when on the VM.
if ( file_exists( '/vagrant/local-config-db.php' ) ) {
	define( 'HM_ENV_ARCHITECTURE', 'chassis' );
}

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled' => get_environment_architecture() === 'chassis',
	];

	register_module(
		'local-chassis',
		__DIR__,
		'Local Chassis',
		$default_settings,
		__NAMESPACE__ . '\\bootstrap'
	);
} );
