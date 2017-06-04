<?php
/**
 * RAFCOCreator - Admin Module
 *
 * Contains admin related functions
 *
 * @package RAFCOCreator
 */
/*  Copyright 2015 Reaktiv Studios

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License (GPL v2) only.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! class_exists( 'RAFCOCreator_Admin' ) ) {

// Start up the engine
class RAFCOCreator_Admin
{

	/**
	 * This is our constructor
	 *
	 * @return RAFCOCreator_Admin
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts',        array( $this, 'scripts_styles'      ),  10      );
		add_action( 'add_meta_boxes',               array( $this, 'rafco_metabox'      ),  11      );
		add_action( 'save_post',                    array( $this, 'rafco_keyword'      )           );
		add_action( 'save_post',                    array( $this, 'rafco_on_save'      )           );
		add_action( 'manage_posts_custom_column',   array( $this, 'display_columns'     ),  10, 2   );
		add_filter( 'manage_posts_columns',         array( $this, 'register_columns'    )           );
		add_filter( 'post_row_actions',             array( $this, 'rafco_row_action'   ),  10, 2   );
		add_filter( 'page_row_actions',             array( $this, 'rafco_row_action'   ),  10, 2   );
	}

	/**
	 * scripts and stylesheets
	 *
	 * @param  [type] $hook [description]
	 * @return [type]       [description]
	 */
	public function scripts_styles( $hook ) {

		// bail if not on the right part
		if ( ! in_array( $hook, array( 'settings_page_rafco-settings', 'edit.php', 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		// set our JS and CSS prefixes
		$css_sx = defined( 'WP_DEBUG' ) && WP_DEBUG ? '.css' : '.min.css';
		$js_sx  = defined( 'WP_DEBUG' ) && WP_DEBUG ? '.js' : '.min.js';

		// load the password stuff on just the settings page
		if ( $hook == 'settings_page_rafco-settings' ) {
			wp_enqueue_script( 'hideshow', plugins_url( '/js/hideShowPassword' . $js_sx, __FILE__ ) , array( 'jquery' ), '2.0.3', true );
		}

		// load our files
		wp_enqueue_style( 'rafco-admin', plugins_url( '/css/yourls-admin' . $css_sx, __FILE__ ), array(), RAFCO_VER, 'all' );
		wp_enqueue_script( 'rafco-admin', plugins_url( '/js/yourls-admin' . $js_sx, __FILE__ ) , array( 'jquery' ), RAFCO_VER, true );
		wp_localize_script( 'rafco-admin', 'yourlsAdmin', array(
			'shortSubmit'   => '<a onclick="prompt(\'URL:\', jQuery(\'#shortlink\').val()); return false;" class="button button-small" href="#">' . __( 'Get Shortlink' ) . '</a>',
			'defaultError'  => __( 'There was an error with your request.' )
		));
	}

	/**
	 * call the metabox if on an appropriate
	 * post type and post status
	 *
	 * @return [type] [description]
	 */
	public function rafco_metabox() {

		// fetch the global post object
		global $post;

		// make sure we're working with an approved post type
		if ( ! in_array( $post->post_type, RAFCOCreator_Helper::get_yourls_types() ) ) {
			return;
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = RAFCOCreator_Helper::get_rafco_api_data() ) {
			return;
		}

		// only fire if user has the option
		if(	false === $check = RAFCOCreator_Helper::check_rafco_cap() ) {
			return;
		}

		// now add the meta box
		add_meta_box( 'rafco-post-display', __( 'RAFCO Shortlink', 'wpyourls' ), array( __class__, 'rafco_post_display' ), $post->post_type, 'side', 'high' );
	}

	/**
	 * Display RAFCO shortlink if present
	 *
	 * @param  [type] $post [description]
	 * @return [type]       [description]
	 */
	public static function yourls_post_display( $post ) {

		// cast our post ID
		$post_id    = absint( $post->ID );

		// check for a link and click counts
		$link   = RAFCOCreator_Helper::get_rafco_meta( $post_id, '_rafco_url' );

		// if we have no link, display our box
		if ( empty( $link ) ) {

			// display the box
			echo RAFCOCreator_Helper::get_rafco_subbox( $post_id );

			// and return
			return;
		}

		// we have a shortlink. show it along with the count
		if( ! empty( $link ) ) {

			// get my count
			$count  = RAFCOCreator_Helper::get_rafco_meta( $post_id, '_rafco_clicks', '0' );

			// and echo the box
			echo RAFCOCreator_Helper::get_rafco_linkbox( $link, $post_id, $count );
		}
	}

	/**
	 * our check for a custom RAFCO keyword
	 *
	 * @param  integer $post_id [description]
	 *
	 * @return void
	 */
	public function rafco_keyword( $post_id ) {

		// run various checks to make sure we aren't doing anything weird
		if ( RAFCOCreator_Helper::meta_save_check( $post_id ) ) {
			return;
		}

		// make sure we're working with an approved post type
		if ( ! in_array( get_post_type( $post_id ), RAFCOCreator_Helper::get_rafco_types() ) ) {
			return;
		}

		// we have a keyword and we're going to store it
		if( ! empty( $_POST['rafco-keyw'] ) ) {

			// sanitize it
			$keywd  = RAFCOCreator_Helper::prepare_api_keyword( $_POST['rafco-keyw'] );

			// update the post meta
			update_post_meta( $post_id, '_rafco_keyword', $keywd );
		} else {
			// delete it if none was passed
			delete_post_meta( $post_id, '_rafco_keyword' );
		}
	}

	/**
	 * Create Rafco link on publish if one doesn't exist
	 *
	 * @param  integer $post_id [description]
	 *
	 * @return void
	 */
	public function rafco_on_save( $post_id ) {

		// bail if this is an import since it'll potentially mess up the process
		if ( ! empty( $_POST['import_id'] ) ) {
			return;
		}

		// run various checks to make sure we aren't doing anything weird
		if ( RAFCOCreator_Helper::meta_save_check( $post_id ) ) {
			return;
		}

		// bail if we aren't working with a published or scheduled post
		if ( ! in_array( get_post_status( $post_id ), RAFCOCreator_Helper::get_rafco_status( 'save' ) ) ) {
			return;
		}

		// make sure we're working with an approved post type
		if ( ! in_array( get_post_type( $post_id ), RAFCOCreator_Helper::get_rafco_types() ) ) {
			return;
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = RAFCOCreator_Helper::get_rafco_api_data() ) {
			return;
		}

		// bail if user hasn't checked the box
		if ( false === $onsave = RAFCOCreator_Helper::get_rafco_option( 'sav' ) ) {
		   	return;
		}

		// check for a link and bail if one exists
		if ( false !== $exist = RAFCOCreator_Helper::get_rafco_meta( $post_id ) ) {
			return;
		}

		// get my post URL and title
		$url    = RAFCOCreator_Helper::prepare_api_link( $post_id );
		$title  = get_the_title( $post_id );

		// and optional keyword
		$keywd  = ! empty( $_POST['rafco-keyw'] ) ? RAFCOCreator_Helper::prepare_api_keyword( $_POST['rafco-keyw'] ) : '';

		// set my args for the API call
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );

		// make the API call
		$build  = RAFCOCreator_Helper::run_rafco_api_call( 'shorturl', $args );

		// bail if empty data or error received
		if ( empty( $build ) || false === $build['success'] ) {
			return;
		}

		// we have done our error checking and we are ready to go
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// get my short URL
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// update the post meta
			update_post_meta( $post_id, '_rafco_url', $shorturl );
			update_post_meta( $post_id, '_rafco_clicks', '0' );

			// do the action after saving
			do_action( 'yourls_after_url_save', $post_id, $shorturl );
		}
	}

	/**
	 * the custom display columns for click counts
	 *
	 * @param  [type] $column_name [description]
	 * @param  [type] $post_id     [description]
	 * @return [type]              [description]
	 */
	public function display_columns( $column, $post_id ) {

		// start my column output
		switch ( $column ) {

		case 'rafco-click':

			echo '<span>' . RAFCOCreator_Helper::get_yourls_meta( $post_id, '_rafco_clicks', '0' ) . '</span>';

			break;

		// end all case breaks
		}
	}

	/**
	 * register and display columns
	 *
	 */
	public function register_columns( $columns ) {

		// call the global post type object
		global $post_type_object;

		// make sure we're working with an approved post type
		if ( ! in_array( $post_type_object->name, RAFCOCreator_Helper::get_rafco_types() ) ) {
			return $columns;
		}

		// get display for column icon
		$columns['rafco-click'] = '<span title="' . __( 'RAFCO Clicks', 'wpyourls' ) . '" class="dashicons dashicons-editor-unlink"></span>';

		// return the columns
		return $columns;
	}

	/**
	 * the action row link based on the status
	 *
	 * @param  [type] $actions [description]
	 * @param  [type] $post    [description]
	 * @return [type]          [description]
	 */
	public function rafco_row_action( $actions, $post ) {

		// make sure we're working with an approved post type
		if ( ! in_array( $post->post_type, RAFCOCreator_Helper::get_rafco_types() ) ) {
			return $actions;
		}

		// bail if we aren't working with a published or scheduled post
		if ( ! in_array( get_post_status( $post->ID ), RAFCOCreator_Helper::get_rafco_status() ) ) {
			return $actions;
		}

		// check for existing and add our new action
		if ( false === $exist = RAFCOCreator_Helper::get_rafco_meta( $post->ID ) ) {
			$actions['create-rafco'] = RAFCOCreator_Helper::create_row_action( $post->ID );
		} else {
			$actions['update-rafco'] = RAFCOCreator_Helper::update_row_action( $post->ID );
		}

		// return the actions
		return $actions;
	}

// end class
}

// end exists check
}

// Instantiate our class
new RAFCOCreator_Admin();

