<?php
/**
 * Altis Local Chassis.
 *
 * @package altis/local-chassis
 */

namespace Altis\Local_Chassis;

/**
 * Bootstrap.
 */
function bootstrap() {
	add_filter( 'qm/output/file_path_map', __NAMESPACE__ . '\\set_file_path_map', 1 );
	load_chassis();
}

/**
 * Enables Query Monitor to map paths to their original values on the host.
 *
 * @param array $map Map of guest path => host path.
 * @return array Adjusted mapping of folders
 */
function set_file_path_map( array $map ) : array {
	if ( ! file_exists( '/etc/chassis-constants' ) ) {
		return $map;
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$json_string = file_get_contents( '/etc/chassis-constants' );
	$data = json_decode( $json_string, true );
	if ( empty( $data ) ) {
		return $map;
	}
	if ( empty( $data['synced_folders'] ) ) {
		return $map;
	}
	foreach ( $data['synced_folders'] as $guest => $host ) {
		$map[ $guest ] = $host;
	}

	return $map;
}

/**
 * Configure and bootstrap the Chassis environment.
 */
function load_chassis() {
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
		define( 'DB_NAME', 'wordpress' ); // phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
		define( 'DB_USER', 'wordpress' ); // phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
		define( 'DB_PASSWORD', 'vagrantpassword' );
		define( 'DB_HOST', 'localhost' );

		defined( 'ABSPATH' ) or define( 'ABSPATH', '/chassis/wordpress/' );
		defined( 'WP_CONTENT_DIR' ) or define( 'WP_CONTENT_DIR', '/chassis/content' );
	} else {
		require_once '/vagrant/local-config-db.php';
	}

	require_once '/vagrant/local-config-extensions.php';

	// When Chassis is configured with subdomains for hosts, it will attempt to load
	// a file via /vagrant/local-config-extensions.php that will register a filter
	// directly on the wp_filter global. This will break loading with Altis, because
	// the plugin API is loaded very early. This will results in a $GLOBALS['wp_filter']
	// that has already been "upgraded" to WP_Hook objects, so the direct below hooked
	// added by Chassis will cause a fatal error.
	//
	// As a workaround, we remove the "direct" added hook via the PHP global, and just call
	// add_action on the function that was registered.
	if ( isset( $GLOBALS['wp_filter']['muplugins_loaded'][10]['chassis-hosts']['function'] ) ) {
		$function = $GLOBALS['wp_filter']['muplugins_loaded'][10]['chassis-hosts']['function'];
		unset( $GLOBALS['wp_filter']['muplugins_loaded'] );
		add_action( 'muplugins_loaded', $function );
	}
}
