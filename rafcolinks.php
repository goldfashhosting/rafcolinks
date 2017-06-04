<?php
/**
 * Plugin Name: RaFco Short Links
 * Plugin URI: https://github.com/goldfashhosting/rafcolinks/
 * Description: Creates a shortlink using RaFco Short Links and Saves it automatically for you!
 * Author: G.Tech Devs
 * Author http://god1st.tech
 * Version: 1.0
 * Text Domain: rafcolinks
 * Domain Path: languages
 * GitHub Plugin URI: https://github.com/goldfashhosting/rafcolinks/
 */
/*
 * Copyright 2017 Andrew Norcross
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
 *
 */

if( ! defined( 'RAFCO_LINKS' ) ) {
	define( 'RAFCO_LINKS', plugin_basename(__FILE__) );
}

if( ! defined( 'RAFCO_DIR' ) ) {
	define( 'RAFCO_DIR', plugin_dir_path( __FILE__ ) );
}

if( ! defined( 'RAFCO_VER' ) ) {
	define( 'RAFCO_VER', '1.0' );
}

// Start up the engine
class RAFCOCreator
{
	/**
	 * Static property to hold our singleton instance
	 * @var instance
	 */
	static $instance = false;

	/**
	 * This is our constructor. There are many like it, but this one is mine.
	 *
	 * @return void
	 */
	private function __construct() {
		add_action( 'plugins_loaded',               array( $this, 'textdomain'          )           );
		add_action( 'plugins_loaded',               array( $this, 'load_files'          )           );

		// handle the scheduling and removal of cron jobs
		add_action( 'plugins_loaded',               array( $this, 'schedule_crons'      )           );
		register_deactivation_hook      ( __FILE__, array( $this, 'remove_crons'        )           );
	}

	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return $instance
	 */
	public static function getInstance() {

		// load an instance if not already initalized
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		// return the instance
		return self::$instance;
	}

	/**
	 * Load textdomain for international goodness.
	 *
	 * @return textdomain
	 */
	public function textdomain() {
		load_plugin_textdomain( 'rafcolinks', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Call our files in the appropriate place.
	 *
	 * @return void
	 */
	public function load_files() {

		// Load our back end.
		if ( is_admin() ) {
			require_once( 'lib/admin.php' );
			require_once( 'lib/settings.php' );
			require_once( 'lib/ajax.php' );
		}

		// Load our front end.
		if ( ! is_admin() ) {
			require_once( 'lib/front.php' );
		}

		// Load our global.
		require_once( 'lib/global.php' );

		// Load our helper file.
		require_once( 'lib/helper.php' );

		// Load our template tag file.
		require_once( 'lib/display.php' );

		// Load our legacy file.
		require_once( 'lib/legacy.php' );
	}

	/**
	 * Add our scheduled cron jobs.
	 *
	 * @return void
	 */
	public function schedule_crons() {

		// Optional filter to disable this all together.
		if ( false === apply_filters( 'rafco_run_cron_jobs', true ) ) {
			return;
		}

		// Schedule the click check.
		if ( ! wp_next_scheduled( 'rafco_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'rafco_cron' );
		}

		// Schedule the API ping test.
		if ( ! wp_next_scheduled( 'rafco_test' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'rafco_test' );
		}
	}

	/**
	 * Remove the cron jobs on deactivation.
	 *
	 * @return void
	 */
	public function remove_crons() {

		// Fetch the timestamps/
		$click  = wp_next_scheduled( 'rafco_cron' );
		$check  = wp_next_scheduled( 'rafco_test' );

		// Remove the jobs.
		wp_unschedule_event( $click, 'rafco_cron', array() );
		wp_unschedule_event( $check, 'rafco_test', array() );
	}

} // End the class.

// Instantiate our class.
$RAFCOCreator = RAFCOCreator::getInstance();
