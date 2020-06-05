<?php
/**
 * Altis Local Chassis Composer Plugin.
 *
 * @package altis/local-chassis
 */

namespace Altis\Local_Chassis;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

/**
 * Altis Local Chassis Composer Plugin.
 *
 * @package altis/local-chassis
 */
class ComposerPlugin implements PluginInterface, Capable {
	/**
	 * Plugin activation callback.
	 *
	 * @param Composer $composer Composer object.
	 * @param IOInterface $io Composer disk interface.
	 * @return void
	 */
	public function activate( Composer $composer, IOInterface $io ) {
	}

	/**
	 * Return plugin capabilities.
	 *
	 * @return array
	 */
	public function getCapabilities() {
		return [
			'Composer\\Plugin\\Capability\\CommandProvider' => __NAMESPACE__ . '\\CommandProvider',
		];
	}
}
