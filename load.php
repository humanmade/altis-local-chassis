<?php
/**
 * Altis Local Chassis Module.
 *
 * @package altis/local-chassis
 */

namespace Altis\Local_Chassis; // phpcs:ignore

use Altis;

add_action( 'altis.modules.init', function () {
	$default_settings = [
		'enabled' => Altis\get_environment_architecture() === 'chassis',
	];
	$options = [
		'defaults' => $default_settings,
	];
	Altis\register_module(
		'local-chassis',
		__DIR__,
		'Local Chassis',
		$options,
		__NAMESPACE__ . '\\bootstrap'
	);
} );
