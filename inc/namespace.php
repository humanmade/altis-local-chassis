<?php

namespace Altis\Chassis;

/**
 * Bootstrap.
 */
function bootstrap() {
	add_filter( 'qm/output/file_path_map', __NAMESPACE__ . '\\set_file_path_map', 1 );
}

/**
 * Enables Query Monitor to map paths to their
 * original values on the host.
 *
 * @param array $map an associative array of folders as keys, and their mappings as values
 * @return array the adjusted mapping of folders
 */
function set_file_path_map( array $map ) : array {
	// Chassis and Local Server
	if ( ! file_exists( '/etc/chassis-constants' ) ) {
		return $map;
	}
	$json_string = file_get_contents( '/etc/chassis-constants' );
	$data = json_decode( $json_string, true );
	if ( empty( $data['synced_folders'] ) ) {
		return $map;
	}
	foreach ( $data['synced_folders'] as $guest => $host ) {
		$map[ $guest ] = $host;
	}

	return $map;
}
