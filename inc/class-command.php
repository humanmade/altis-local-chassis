<?php
/**
 * Altis Local Chassis Composer Command.
 *
 * @package altis/local-chassis
 */

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
		$this->setAliases( [ 'local-chassis' ] );
		$this->setDescription( 'Set up and run Chassis' );
		$this->addArgument(
			'subcommand',
			InputArgument::REQUIRED,
			'Subcommand to run'
		);
		$this->addArgument(
			'options',
			InputArgument::IS_ARRAY
		);
		$this->setHelp(
			<<<EOT
To set up Local Chassis:
    init
Start the server:
    start
Stop the server:
    stop
View status of the server:
    status
Restart the server:
    restart
Install HTTPS certificate:
    secure
Apply configuration changes:
    provision
Run any shell command on the VM:
    exec -- <command>             eg: exec -- vendor/bin/phpcs
Open a shell:
    shell
    ssh
Destroy the VM:
    destroy
Upgrade Local Chassis to the latest version:
    upgrade
EOT
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
	 * Wrapper command to dispatch subcommands.
	 *
	 * @param InputInterface $input Command input.
	 * @param OutputInterface $output Command output.
	 * @return int Status code to return.
	 * @throws CommandNotFoundException Thrown if the specified subcommand is not found.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		$command = $input->getArgument( 'subcommand' );
		switch ( $command ) {
			case 'init':
				return $this->init( $input, $output );

			case 'up':
			case 'start':
				return $this->start( $input, $output );

			case 'status':
				return $this->status( $input, $output );

			case 'restart':
			case 'reload':
				return $this->restart( $input, $output );

			case 'halt':
			case 'stop':
				return $this->stop( $input, $output );

			case 'secure':
				return $this->secure( $input, $output );

			case 'shell':
			case 'ssh':
				return $this->shell( $input, $output );

			case 'exec':
				return $this->exec( $input, $output );

			case 'destroy':
				return $this->destroy( $input, $output );

			case 'provision':
				return $this->provision( $input, $output );

			case 'update':
			case 'upgrade':
				return $this->upgrade( $input, $output );

			default:
				throw new CommandNotFoundException( sprintf( 'Subcommand "%s" is not defined.', $command ) );
		}
	}

	/**
	 * Command to initialize a Chassis install.
	 *
	 * @param InputInterface $input Command input.
	 * @param OutputInterface $output Command output.
	 * @return int Status code to return.
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
			$output->writeln( '<error>Could not clone Chassis successfully</error>' );
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

		$status = $this->start( $input, $output );
		if ( $status !== 0 ) {
			$output->writeln( '<error>Virtual machine could not be installed or launched.</>' );
			return $status;
		}

		// And run the initial setup, if the user wants to.
		$question = new ConfirmationQuestion( 'Install HTTPS certificate? [Y/n] ', true );
		if ( ! $questioner->ask( $input, $output, $question ) ) {
			return;
		}

		$status = $this->secure( $input, $output );
		if ( $status !== 0 ) {
			$output->writeln( '<error>HTTPS certificate could not be installed.</>' );
			return $status;
		}

		return;
	}

	/**
	 * Run a command in the Chassis directory.
	 *
	 * @param string $command Command to execute.
	 * @return int Status returned from the command.
	 * @throws Exception If chassis has not yet been downloaded.
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
	 *
	 * @param InputInterface $input Command input.
	 * @param OutputInterface $output Command output.
	 */
	protected function start( InputInterface $input, OutputInterface $output ) {
		$hosts = $this->get_project_hosts();
		$status = $this->run_command( 'vagrant up' );

		if ( $status === 0 ) {
			$output->writeln( '<info>Start up complete!</>' );
			$output->writeln( '<info>To access your site\'s admin visit:</> <comment>http://' . $hosts[0] . '/wp-admin/</>' );
			$output->writeln( '<info>WP Username:</> <comment>admin</>' );
			$output->writeln( '<info>WP Password:</> <comment>password</>' );
		}

		return $status;
	}

	/**
	 * Command to check the virtual machine's status.
	 *
	 * @param InputInterface $input Command input.
	 * @param OutputInterface $output Command output.
	 */
	protected function status( InputInterface $input, OutputInterface $output ) {
		return $this->run_command( 'vagrant status' );
	}

	/**
	 * Command to restart the virtual machine.
	 *
	 * @param InputInterface $input Command input.
	 * @param OutputInterface $output Command output.
	 */
	protected function restart( InputInterface $input, OutputInterface $output ) {
		return $this->run_command( 'vagrant reload' );
	}

	/**
	 * Command to stop the virtual machine.
	 *
	 * @param InputInterface $input Command input.
	 * @param OutputInterface $output Command output.
	 */
	protected function stop( InputInterface $input, OutputInterface $output ) {
		return $this->run_command( 'vagrant halt' );
	}

	/**
	 * Command to install the generated HTTPS cert.
	 *
	 * @param InputInterface $input Command input.
	 * @param OutputInterface $output Command output.
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
	 *
	 * @param InputInterface $input Command input.
	 * @param OutputInterface $output Command output.
	 */
	protected function shell( InputInterface $input, OutputInterface $output ) {
		return $this->run_command( 'vagrant ssh' );
	}

	/**
	 * Command to destroy the virtual machine.
	 *
	 * @param InputInterface $input Command input.
	 * @param OutputInterface $output Command output.
	 */
	protected function destroy( InputInterface $input, OutputInterface $output ) {
		return $this->run_command( 'vagrant destroy' );
	}

	/**
	 * Command to pass a command in to the virtual machine.
	 *
	 * @param InputInterface $input Command input.
	 * @param OutputInterface $output Command output.
	 */
	protected function exec( InputInterface $input, OutputInterface $output ) {
		$command = implode( ' ', $input->getArgument( 'options' ) );
		$command = escapeshellarg( "cd /chassis && $command" );

		return $this->run_command( sprintf( 'vagrant ssh -c %s', $command ) );
	}

	/**
	 * Command to update the config.local.yaml file and re-provision.
	 *
	 * @param InputInterface $input Command input object.
	 * @param OutputInterface $output Command output object.
	 * @return int
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
	 * Command to upgrade chassis and re provision the VM.
	 *
	 * @param InputInterface $input Command input.
	 * @param OutputInterface $output Command output.
	 * @return int Status code to return.
	 */
	protected function upgrade( InputInterface $input, OutputInterface $output ) {
		$output->writeln( '<info>Updating chassis...</>' );

		// Update chassis.
		$failed = $this->run_command( 'git pull origin master' );
		if ( $failed ) {
			$output->writeln( '<error>Could not pull latest master from GitHub. Make sure you have a clean checkout of chassis on the master branch.</>' );
			return $failed;
		}
		$failed = $this->run_command( 'git submodule update --init --recursive' );
		if ( $failed ) {
			$output->writeln( '<error>Could not update submodules.</>' );
			return $failed;
		}

		// Clean extensions directory.
		$os = php_uname();
		if ( strpos( $os, 'Darwin' ) !== false ) {
			$failed = $this->run_command( 'rm -rf extensions' );
		} elseif ( strpos( $os, 'Windows' ) !== false ) {
			$failed = $this->run_command( 'rmdir extensions' );
		}
		if ( $failed ) {
			$output->writeln( '<error>Unable to clean existing extensions.</>' );
			return $failed;
		}
		$failed = $this->run_command( 'git checkout -- .' );
		if ( $failed ) {
			$output->writeln( '<error>Unable to restore default extension, try running `cd chassis && git checkout reset --hard HEAD`.</>' );
			return $failed;
		}

		// Bring up the machine and re-provision it.
		$this->write_config_file();
		$failed = $this->run_command( 'vagrant reload --provision' );
		if ( $failed ) {
			$output->writeln( '<error>There was a problem re provisioning your VM. Please check the output above for specific errors.</>' );
			return $failed;
		}

		$output->writeln( '<info>Success!</>' );

		return 0;
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
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$json = file_get_contents( $this->get_root_dir() . DIRECTORY_SEPARATOR . 'composer.json' );
		$composer_json = json_decode( $json, true );

		return (array) ( $composer_json['extra']['altis']['modules']['local-chassis'] ?? [] );
	}

	/**
	 * Gets sanitised project host names with a default fallback based
	 * on the project directory name.
	 *
	 * @return array
	 */
	protected function get_project_hosts() : array {
		$config = $this->get_config();

		// Add a default host if none set.
		if ( ! isset( $config['hosts'] ) ) {
			$hosts = [ basename( $this->get_root_dir() ) ];
		} else {
			$hosts = $config['hosts'];
		}

		// Sanitise hosts.
		$hosts = array_map( function ( $host ) {
			// Ensure .local suffixes.
			if ( ! preg_match( '/\.local$/', $host ) ) {
				$host = "{$host}.local";
			}
			// Sanitize host name.
			$host = preg_replace( '/[^a-z0-9\-\.]/i', '', $host );
			return $host;
		}, $hosts );

		return $hosts;
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
			'multisite' => true,
			'extensions' => [
				'humanmade/platform_chassis_extension',
			],
			'elasticsearch' => [
				'repo_version' => '6',
				'version' => '6.3.1',
				'plugins' => [
					'analysis-icu',
					'analysis-kuromoji',
					'analysis-phonetic',
					'analysis-smartcn',
					'analysis-stempel',
					'analysis-ukrainian',
					'ingest-attachment',
					'ingest-user-agent',
					'mapper-size',
					'mapper-murmur3',
				],
			],
			'database' => [
				'name' => 'wordpress',
				'user' => 'wordpress',
				'password' => 'vagrantpassword',
				'prefix' => 'wp_',
			],
		];

		// Merge config from composer.json.
		$overrides = $this->get_config();
		$config = $this->merge_config( $config, $overrides );

		// Set hosts.
		$config['hosts'] = $this->get_project_hosts();

		// Set the machine name.
		$config['machine_name'] = $config['hosts'][0];

		// Remove the enabled setting.
		unset( $config['enabled'] );

		// Push comment on to front of file.
		$config = array_merge( [
			'notice' => implode( "\n", [
				'# THIS FILE IS MANAGED BY ALTIS, ANY CHANGES MADE MAY BE LOST',
				'#',
				'# Find out how to modify this file through config here:',
				'# https://www.altis-dxp.com/resources/docs/local-chassis/',
			] ),
		], $config );

		// Convert to YAML.
		$yaml = Yaml::dump( $config, 2, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK );

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
					$merged[ $key ] = $this->merge_config( $merged[ $key ] ?? [], $value );
				} else {
					// Overwrite scalar values directly.
					$merged[ $key ] = $value;
				}
			} else {
				// Merge numerically keyed arrays directly and remove empty/duplicate items.
				$merged = array_merge( $merged, (array) $overrides );
				$merged = array_filter( $merged );
				$merged = array_unique( $merged );
				break;
			}
		}
		return $merged;
	}

}
