<?php

namespace HM\Platform\Chassis;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\Capable;

class ComposerPlugin implements PluginInterface, Capable {
	public function activate( Composer $composer, IOInterface $io ) {
	}

	public function getCapabilities() {
		return [
			'Composer\\Plugin\\Capability\\CommandProvider' => __NAMESPACE__ . '\\CommandProvider',
		];
	}
}
