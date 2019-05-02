<?php

namespace HM\Platform\Chassis;

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
		$root = dirname( $this->getComposer()->getConfig()->getConfigSource()->getName() );
		return $root . DIRECTORY_SEPARATOR . CHASSIS_DIR;
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

		// Write the default config.
		$config = [
			'paths' => [
				'base' => '..',
				'wp' => 'wordpress',
				'content' => 'content',
			],
			'hosts' => [
				'platform.local',
			],
			'multisite' => true,
			'extensions' => [
				'humanmade/platform-chassis-extension',
			],
		];
		$yaml = Yaml::dump( $config );
		$success = file_put_contents( $chassis_dir . DIRECTORY_SEPARATOR . 'config.local.yaml', $yaml );
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

		return $this->run_command( 'vagrant up' );
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
}
