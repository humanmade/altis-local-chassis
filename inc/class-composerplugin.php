<?php

namespace Altis\Local_Chassis;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class ComposerPlugin implements PluginInterface, Capable {
	public function activate( Composer $composer, IOInterface $io ) {
	}

	public function getCapabilities() {
		return [
			'Composer\\Plugin\\Capability\\CommandProvider' => __NAMESPACE__ . '\\CommandProvider',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function deactivate( Composer $composer, IOInterface $io) {
	}

	/**
	 * {@inheritDoc}
	 */
	public function uninstall( Composer $composer, IOInterface $io ) {
	}
}
