<?php

namespace Altis\Local_Chassis;

use Composer\Command\BaseCommand;
use Exception;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;

const CHASSIS_DIR = 'chassis';
const REPO = 'https://github.com/Chassis/Chassis.git';

/**
 * Chassis command for Composer.
 */
class Command extends BaseCommand {
	/**
	 * Configure the command.
	 */
	protected function configure() {
		$this->setName( 'chassis' );
		$this->setDescription( 'Set up and run Chassis' );
		$this->addArgument(
			'subcommand',
			InputArgument::REQUIRED,
			'Subcommand to run'
		);
	}

	/**
	 * Get the Chassis directory
	 *
	 * Finds the Chassis directory for the current Composer project.
	 *
	 * @return string Path to the Chassis directory
	 */
	protected function get_chassis_dir() {
		return $this->get_root_dir() . DIRECTORY_SEPARATOR . CHASSIS_DIR;
	}

	/**
	 * Wrapper command to dispatch subcommands
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int Status code to return
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$command = $input->getArgument( 'subcommand' );
		switch ( $command ) {
			case 'init':
				return $this->init( $input, $output );

			case 'start':
				return $this->start( $input, $output );

			case 'status':
				return $this->status( $input, $output );

			case 'stop':
				return $this->stop( $input, $output );

			case 'secure':
				return $this->secure( $input, $output );

			case 'shell':
				return $this->shell( $input, $output );

			case 'provision':
				return $this->provision( $input, $output );

			default:
				throw new CommandNotFoundException( sprintf( 'Subcommand "%s" is not defined.', $command ) );
		}
	}

	/**
	 * Command to initialize a Chassis install.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int Status code to return
	 */
	protected function init( InputInterface $input, OutputInterface $output ) {
		$questioner = $this->getHelper( 'question' );

		// Check we're not overwriting it.
		$chassis_dir = $this->get_chassis_dir();
		if ( file_exists( $chassis_dir ) ) {
			$output->writeln( sprintf( '<warning>The Chassis directory already exists at %s</warning>', $chassis_dir ) );
			return 1;
		}

		$output->writeln( sprintf( '<info>Installing Chassis into %s</info>', $chassis_dir ) );

		// First, clone down Chassis.
		$command = sprintf(
			'git clone --recursive %s %s',
			escapeshellarg( REPO ),
			escapeshellarg( $chassis_dir )
		);
		passthru( $command, $status );
		if ( $status !== 0 ) {
			return $status;
		}

		// Create the default config file.
		$success = $this->write_config_file();
		if ( ! $success ) {
			$output->writeln( '<error>Could not write Chassis config</error>' );
			return 1;
		}

		$output->writeln( '' );
		$output->writeln( '<info>Chassis downloaded and configured.</info>' );

		// And run the initial setup, if the user wants to.
		$question = new ConfirmationQuestion( 'Launch and install virtual machine? [Y/n] ', true );
		if ( ! $questioner->ask( $input, $output, $question ) ) {
			return;
		}

		$this->start( $input, $output );

		// And run the initial setup, if the user wants to.
		$question = new ConfirmationQuestion( 'Install HTTPS certificate? [Y/n] ', true );
		if ( ! $questioner->ask( $input, $output, $question ) ) {
			return;
		}

		$this->secure( $input, $output );

		return;
	}

	/**
	 * Run a command in the Chassis directory.
	 *
	 * @param string $command Command to execute
	 * @return int Status returned from the command
	 */
	protected function run_command( $command ) {
		$cwd = getcwd();
		$chassis_dir = $this->get_chassis_dir();
		if ( ! file_exists( $chassis_dir ) ) {
			throw new Exception( 'Chassis directory does not exist; run `composer chassis init` to get started' );
		}

		chdir( $chassis_dir );
		passthru( $command, $status );
		chdir( $cwd );

		return $status;
	}

	/**
	 * Command to start the virtual machine
	 */
	protected function start( InputInterface $input, OutputInterface $output ) {
		return $this->run_command( 'vagrant up' );
	}

	/**
	 * Command to check the virtual machine's status
	 */
	protected function status( InputInterface $input, OutputInterface $output ) {
		return $this->run_command( 'vagrant status' );
	}

	/**
	 * Command to stop the virtual machine
	 */
	protected function stop( InputInterface $input, OutputInterface $output ) {
		return $this->run_command( 'vagrant halt' );
	}

	/**
	 * Command to install the generated HTTPS cert.
	 */
	protected function secure( InputInterface $input, OutputInterface $output ) {
		$chassis_dir = $this->get_chassis_dir();
		$config_file = $chassis_dir . DIRECTORY_SEPARATOR . 'config.local.yaml';

		// Pre-flight checks.
		if ( ! file_exists( $config_file ) ) {
			$output->writeln( '<warning>The config file at chassis/config.local.yaml does not exist yet. Run `composer chassis init` first.</warning>' );
			return 1;
		}

		// Get certificate file path.
		$config = Yaml::parseFile( $config_file );
		$cert_file = $config['hosts'][0] . '.cert';
		$cert_path = $chassis_dir . DIRECTORY_SEPARATOR . $cert_file;

		if ( ! file_exists( $cert_path ) ) {
			$output->writeln( sprintf( '<warning>The HTTPS certificate file "%s" does not exist yet. Run `composer chassis start` first to provision the VM and generate the file.</warning>', $cert_file ) );
			return 1;
		}

		// Run OS specific commands.
		$os = php_uname();
		$status = false;

		if ( strpos( $os, 'Darwin' ) !== false ) {
			$status = $this->run_command( sprintf( 'sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain "%s"', $cert_path ) );
		} elseif ( strpos( $os, 'Windows' ) !== false ) {
			$status = $this->run_command( sprintf( 'certutil -enterprise -f -v -AddStore "Root" "%s"', $cert_path ) );
		}

		if ( $status === 0 ) {
			$output->writeln( '<info>The HTTPS certificate was installed successfully!</info>' );
			$output->writeln( sprintf( '<info>You can now browse to https://%s/</info>', $config['hosts'][0] ) );
			return $status;
		} elseif ( $status !== false ) {
			$output->writeln( '<error>The was an error adding the HTTPS certificate. You may need to do this manually or contact support for further assistance.</error>' );
			if ( strpos( $os, 'Windows' ) !== false ) {
				$output->writeln( '<error>You may need to run this command again with administrator privileges. Right click on your command prompt app and choose "Run as Administrator".</error>' );
			}
			return $status;
		}

		$output->writeln( sprintf( "<warning>This command is not currently supported on your OS:\n%s</warning>", $os ) );
		$output->writeln( 'Please check the documentation and if no solution is available you can log an issue to get suppport at https://github.com/humanmade/altis-local-chassis/issues' );
		return 1;
	}

	/**
	 * Command to ssh in to the virtual machine.
	 */
	protected function shell( InputInterface $input, OutputInterface $output ) {
		return $this->run_command( 'vagrant ssh' );
	}

	/**
	 * Command to update the config.local.yaml file and re-provision.
	 */
	protected function provision( InputInterface $input, OutputInterface $output ) {
		$success = $this->write_config_file();
		if ( ! $success ) {
			$output->writeln( '<error>Could not write Chassis config</error>' );
			return 1;
		}
		return $this->run_command( 'vagrant provision' );
	}

	/**
	 * Get the root directory path for the project.
	 *
	 * @return string
	 */
	protected function get_root_dir() : string {
		return dirname( $this->getComposer()->getConfig()->getConfigSource()->getName() );
	}

	/**
	 * Get the Local Chassis config from composer.json.
	 *
	 * @return array
	 */
	protected function get_config() : array {
		// @codingStandardsIgnoreLine
		$json = file_get_contents( $this->get_root_dir() . DIRECTORY_SEPARATOR . 'composer.json' );
		$composer_json = json_decode( $json, true );

		return (array) $composer_json['extra']['altis']['modules']['local-chassis'] ?? [];
	}

	/**
	 * Writes the config.local.yaml file with Altis customisations.
	 *
	 * @return bool Returns false if the file write fails.
	 */
	protected function write_config_file() : bool {
		// Write the default config.
		$config = [
			'php' => '7.2',
			'paths' => [
				'base' => '..',
				'wp' => 'wordpress',
				'content' => 'content',
			],
			'hosts' => [
				basename( $this->get_root_dir() ),
			],
			'multisite' => true,
			'extensions' => [
				'humanmade/platform_chassis_extension',
			],
			'elasticsearch' => [
				'plugins' => [
					'analysis-icu',
					'ingest-attachment',
				],
			],
		];

		// Merge config from composer.json.
		$overrides = $this->get_config();
		$config = $this->merge_config( $config, $overrides );

		// Sanitise hosts.
		$config['hosts'] = array_map( function ( $host ) {
			// Ensure .local suffixes.
			if ( ! preg_match( '/\.local$/', $host ) ) {
				$host = "{$host}.local";
			}
			// Sanitize host name.
			$host = preg_replace( '/[^a-z0-9\-\.]/i', '', $host );
			return $host;
		}, $config['hosts'] );

		// Set the machine name.
		$config['machine_name'] = $config['hosts'][0];

		// Remove the enabled setting.
		unset( $config['enabled'] );

		// Convert to YAML.
		$yaml = Yaml::dump( $config );

		// @codingStandardsIgnoreLine
		return file_put_contents( $this->get_chassis_dir() . DIRECTORY_SEPARATOR . 'config.local.yaml', $yaml );
	}

	/**
	 * Merges two configuration arrays together, overriding the first or adding
	 * to it with items from the second.
	 *
	 * @param array $config The default config array.
	 * @param array $overrides The config to merge in.
	 * @return array
	 */
	protected function merge_config( array $config, array $overrides ) : array {
		$merged = $config;
		foreach ( $overrides as $key => $value ) {
			if ( is_string( $key ) ) {
				if ( is_array( $value ) ) {
					// Recursively merge arrays.
					$merged[ $key ] = $this->merge_config( $merged[ $key ], $value );
				} else {
					// Overwrite scalar values directly.
					$merged[ $key ] = $value;
				}
			} else {
				// Merge numerically keyed arrays directly and remove empty/dupliocate items.
				$merged = array_merge( $merged, (array) $overrides );
				$merged = array_filter( $merged );
				$merged = array_unique( $merged );
				break;
			}
		}
		return $merged;
	}

}
