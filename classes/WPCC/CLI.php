<?php

// +----------------------------------------------------------------------+
// | Copyright 2015 10up Inc                                              |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

namespace WPCC;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

// do nothing if WP_CLI is not available
if ( ! class_exists( '\WP_CLI_Command' ) ) {
	return;
}

/**
 * Performs Codeception tests.
 *
 * @since 1.0.0
 * @category WPCC
 */
class CLI extends \WP_CLI_Command {

	/**
	 * Returns full path to the selenium executable file.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 * @return string The path to the selenium server executable file.
	 */
	protected function _get_selenium_executable() {
		return WPCC_ABSPATH . '/node_modules/selenium-server/bin/selenium';
	}

	/**
	 * Checks if selenium server is started and launches it if it isn't.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function _start_selenium_server() {
		$selenium = $this->_get_selenium_executable();
		$pids = explode( PHP_EOL, trim( shell_exec( "pgrep -f {$selenium}" ) ) );
		if ( is_executable( $selenium ) && count( $pids ) < 2 ) {
			shell_exec( "{$selenium} > /dev/null 2>/dev/null  &" );
			sleep( 2 ); // wait while selenium server starts properly
		}
	}

	/**
	 * Stops selenium server if it has been launched.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 */
	protected function _stop_selenium_server() {
		$selenium = $this->_get_selenium_executable();
		$pids = trim( shell_exec( "pgrep -f {$selenium}" ) );
		if ( ! empty( $pids ) ) {
			foreach ( explode( PHP_EOL, (string) $pids ) as $pid ) {
				shell_exec( "kill -15 {$pid} > /dev/null 2>/dev/null" );
			}
		}
	}

	/**
	 * Runs Codeception tests.
	 *
	 * ### OPTIONS
	 * 
	 * <suite>
	 * : The suite name to run. There are three types of suites available to
	 * use: unit, functional and acceptance, but currently only acceptance tests
	 * are supported.
	 *
	 * <test>
	 * : The test name to run.
	 *
	 * <steps>
	 * : Determines whether to show test steps in output or not.
	 *
	 * <debug>
	 * : Determines whether to show debug and scenario output or not.
	 *
	 * <keep-alive>
	 * : Determines whether to halt selenium server on shutdown or not.
	 *
	 * ### EXAMPLE
	 *
	 *     wp codeception run
	 *     wp codeception run acceptance
	 *     wp codeception run acceptance my_custom_test
	 *     wp codeception run --steps
	 *     wp codeception run --debug
	 *
	 * @synopsis [<suite>] [<test>] [--steps] [--debug] [--keep-alive]
	 *
	 * @since 1.0.0
	 * 
	 * @access public
	 * @global array $argv Global array of console arguments passed to script.
	 * @param array $args Unassociated array of arguments passed to this command.
	 * @param array $assoc_args Associated array of arguments passed to this command.
	 */
	public function run( $args, $assoc_args ) {
		global $argv;

		$argv_input = array_slice( (array) $argv, 1 );
		$keep_alive_index = array_search( '--keep-alive', $argv_input );
		if ( false !== $keep_alive_index ) {
			unset( $argv_input[ $keep_alive_index ] );
		}

		$this->_start_selenium_server();

		$app = new Application( 'Codeception', \Codeception\Codecept::VERSION );
		$app->setAutoExit( false );
		$app->add( new \Codeception\Command\Run( 'run' ) );
		$app->run( new ArgvInput( $argv_input ) );

		if ( empty( $assoc_args['keep-alive'] ) ) {
			$this->_stop_selenium_server();
		}
	}
	
}