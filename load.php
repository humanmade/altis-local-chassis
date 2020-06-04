<?php

namespace Altis\Local_Chassis;

use function Altis\get_environment_architecture;
use function Altis\register_module;

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
