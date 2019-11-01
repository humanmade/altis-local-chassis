<?php

namespace Altis\Local_Chassis;  // @codingStandardsIgnoreLine

use function Altis\get_environment_architecture;
use function Altis\register_module;

// Load database configuration on Chassis installs.
if ( file_exists( '/vagrant/local-config-extensions.php' ) ) {
	 // @codingStandardsIgnoreStart
	define( 'HM_ENV_ARCHITECTURE', 'chassis' );
	define( 'DB_NAME',     'wordpress' );
	define( 'DB_USER',     'wordpress' );
	define( 'DB_PASSWORD', 'vagrantpassword' );
	define( 'DB_HOST',     'localhost' );

	defined( 'ABSPATH' ) or define( 'ABSPATH', '/chassis/wordpress/' );
	defined( 'WP_CONTENT_DIR' ) or define( 'WP_CONTENT_DIR', '/chassis/content' );

	require_once '/vagrant/local-config-extensions.php';
	// @codingStandardsIgnoreEnd


	// When Chassis is configured with subdomains for hosts, it will attempt to load
	// a file via /vagrant/local-config-extensions.php that will register a filter
	// directly on the wp_filter global. This will break loading with Altis, because
	// the plugin API is loaded very early. This will results in a $GLOBALS['wp_filter']
	// that has already been "upgraded" to WP_Hook objects, so the direct below hooked
	// added by Chassis will cause a fatal error.
	//
	// As a workaround, we remove the "direct" added hook via the PHP global, and just call
	// add_action on the function that was registered.
	if ( is_array( $GLOBALS['wp_filter']['muplugins_loaded'] ) && isset( $GLOBALS['wp_filter']['muplugins_loaded'][10]['chassis-hosts'] ) ) {
		$function = $GLOBALS['wp_filter']['muplugins_loaded'][10]['chassis-hosts']['function'];
		unset( $GLOBALS['wp_filter']['muplugins_loaded'] );
		add_action( 'muplugins_loaded', $function );
	}
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
