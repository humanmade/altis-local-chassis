<?php
/**
 * Altis Local Chassis Command Provider.
 *
 * @package altis/local-chassis
 */

namespace Altis\Local_Chassis;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * Altis Local Chassis Command Provider.
 *
 * @package altis/local-chassis
 */
class CommandProvider implements CommandProviderCapability {
	/**
	 * Return available commands.
	 *
	 * @return array
	 */
	public function getCommands() {
		return [
			new Command(),
		];
	}
}
