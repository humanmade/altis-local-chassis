<?php

namespace Altis\Local_Chassis;

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
 * @param array $map Map of guest path => host path
 * @return array Adjusted mapping of folders
 */
function set_file_path_map( array $map ) : array {
	if ( ! file_exists( '/etc/chassis-constants' ) ) {
		return $map;
	}
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
