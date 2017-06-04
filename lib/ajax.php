<?php
/**
 * RAFCO Link Creator - Ajax Module
 *
 * Contains our ajax related functions
 *
 * @package RAFCO Link Creator
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

if ( ! class_exists( 'RAFCOCreator_Ajax' ) ) {

// Start up the engine
class RAFCOCreator_Ajax
{

	/**
	 * This is our constructor
	 *
	 * @return RAFCOCreator_Ajax
	 */
	public function __construct() {
		add_action( 'wp_ajax_create_rafco',        array( $this, 'create_rafco'       )           );
		add_action( 'wp_ajax_delete_rafco',        array( $this, 'delete_rafco'       )           );
		add_action( 'wp_ajax_stats_rafco',         array( $this, 'stats_rafco'        )           );
		add_action( 'wp_ajax_inline_rafco',        array( $this, 'inline_rafco'       )           );
		add_action( 'wp_ajax_status_rafco',        array( $this, 'status_rafco'       )           );
		add_action( 'wp_ajax_refresh_rafco',       array( $this, 'refresh_rafco'      )           );
		add_action( 'wp_ajax_convert_rafco',       array( $this, 'convert_rafco'      )           );
		add_action( 'wp_ajax_import_rafco',        array( $this, 'import_rafco'       )           );
	}

	/**
	 * Create shortlink function
	 */
	public function create_rafco() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'rafco_editor_create', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = RAFCOCreator_Helper::get_rafco_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// bail if we aren't working with a published or scheduled post
		if ( ! in_array( get_post_status( $post_id ), RAFCOCreator_Helper::get_rafco_status() ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'INVALID_STATUS';
			$ret['message'] = __( 'This is not a valid post status.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a URL
		if ( false !== $link = RAFCOCreator_Helper::get_rafco_meta( $post_id, '_rafco_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'URL_EXISTS';
			$ret['message'] = __( 'A URL already exists.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a permalink
		if ( false === $url = RAFCOCreator_Helper::prepare_api_link( $post_id ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_PERMALINK';
			$ret['message'] = __( 'No permalink could be retrieved.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// check for keyword and get the title
		$keyword = ! empty( $_POST['keyword'] ) ? RAFCOCreator_Helper::prepare_api_keyword( $_POST['keyword'] ) : '';
		$title   = get_the_title( $post_id );

		// set my args for the API call
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keyword );

		// make the API call
		$build  = RAFCOCreator_Helper::run_rafco_api_call( 'shorturl', $args );

		// bail if empty data
		if ( empty( $build ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( false === $build['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// we have done our error checking and we are ready to go
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// get my short URL
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// update the post meta
			update_post_meta( $post_id, '_rafco_url', $shorturl );
			update_post_meta( $post_id, '_rafco_clicks', '0' );

			// and do the API return
			$ret['success'] = true;
			$ret['message'] = __( 'You have created a new RAFCO link.', 'rafcolinks' );
			$ret['linkurl'] = $shorturl;
			$ret['linkbox'] = RAFCOCreator_Helper::get_rafco_linkbox( $shorturl, $post_id );
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'rafcolinks' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * Delete shortlink function
	 */
	public function delete_rafco() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'rafco_editor_delete', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = RAFCOCreator_Helper::get_rafco_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// do a quick check for a URL
		if ( false === $link = RAFCOCreator_Helper::get_rafco_meta( $post_id, '_rafco_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_URL_EXISTS';
			$ret['message'] = __( 'There is no URL to delete.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// passed it all. go forward
		delete_post_meta( $post_id, '_rafco_url' );
		delete_post_meta( $post_id, '_rafco_clicks' );

		// and do the API return
		$ret['success'] = true;
		$ret['message'] = __( 'You have removed your RAFCO link.', 'rafcolinks' );
		$ret['linkbox'] = RAFCOCreator_Helper::get_rafco_subbox( $post_id );
		echo json_encode( $ret );
		die();
	}

	/**
	 * retrieve stats
	 */
	public function stats_rafco() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// bail if the API key or URL have not been entered
		if(	false === $api = RAFCOCreator_Helper::get_rafco_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// verify our nonce
		$check	= check_ajax_referer( 'rafco_inline_update_' . absint( $post_id ), 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// get my click number
		$clicks = RAFCOCreator_Helper::get_single_click_count( $post_id );

		// bad API call
		if ( empty( $clicks['success'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = $clicks['errcode'];
			$ret['message'] = $clicks['message'];
			echo json_encode( $ret );
			die();
		}

		// got it. update the meta
		update_post_meta( $post_id, '_rafco_clicks', $clicks['clicknm'] );

		// and do the API return
		$ret['success'] = true;
		$ret['message'] = __( 'Your RAFCO click count has been updated', 'rafcolinks' );
		$ret['clicknm'] = $clicks['clicknm'];
		echo json_encode( $ret );
		die();
	}

	/**
	 * Create shortlink function inline. Called on ajax
	 */
	public function inline_rafco() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// bail if the API key or URL have not been entered
		if(	false === $api = RAFCOCreator_Helper::get_rafco_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail without a post ID
		if( empty( $_POST['post_id'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_ID';
			$ret['message'] = __( 'No post ID was present.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// now cast the post ID
		$post_id    = absint( $_POST['post_id'] );

		// bail if we aren't working with a published or scheduled post
		if ( ! in_array( get_post_status( $post_id ), RAFCOCreator_Helper::get_rafco_status() ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'INVALID_STATUS';
			$ret['message'] = __( 'This is not a valid post status.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// verify our nonce
		$check	= check_ajax_referer( 'rafco_inline_create_' . absint( $post_id ), 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a URL
		if ( false !== $link = RAFCOCreator_Helper::get_rafco_meta( $post_id, '_rafco_url' ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'URL_EXISTS';
			$ret['message'] = __( 'A URL already exists.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// do a quick check for a permalink
		if ( false === $url = RAFCOCreator_Helper::prepare_api_link( $post_id ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_PERMALINK';
			$ret['message'] = __( 'No permalink could be retrieved.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// get my post URL and title
		$title  = get_the_title( $post_id );

		// check for a keyword
		$keywd  = RAFCOCreator_Helper::get_rafco_keyword( $post_id );

		// set my args for the API call
		$args   = array( 'url' => esc_url( $url ), 'title' => sanitize_text_field( $title ), 'keyword' => $keywd );

		// make the API call
		$build  = RAFCOCreator_Helper::run_rafco_api_call( 'shorturl', $args );

		// bail if empty data
		if ( empty( $build ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( false === $build['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// we have done our error checking and we are ready to go
		if( false !== $build['success'] && ! empty( $build['data']['shorturl'] ) ) {

			// get my short URL
			$shorturl   = esc_url( $build['data']['shorturl'] );

			// update the post meta
			update_post_meta( $post_id, '_rafco_url', $shorturl );
			update_post_meta( $post_id, '_rafco_clicks', '0' );

			// and do the API return
			$ret['success'] = true;
			$ret['message'] = __( 'You have created a new RAFCO link.', 'rafcolinks' );
			$ret['rowactn'] = '<span class="update-rafco">' . RAFCOCreator_Helper::update_row_action( $post_id ) . '</span>';
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'rafcolinks' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * run the status check on call
	 */
	public function status_rafco() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'rafco_status_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = RAFCOCreator_Helper::get_rafco_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// make the API call
		$build  = RAFCOCreator_Helper::run_rafco_api_call( 'db-stats' );

		// handle the check and set it
		$check  = ! empty( $build ) && false !== $build['success'] ? 'connect' : 'noconnect';

		// set the option return
		if ( false !== get_option( 'rafco_api_test' ) ) {
			update_option( 'rafco_api_test', $check );
		} else {
			add_option( 'rafco_api_test', $check, null, 'no' );
		}

		// now get the API data
		$data	= RAFCOCreator_Helper::get_api_status_data();

		// check to see if no data happened
		if( empty( $data ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_STATUS_DATA';
			$ret['message'] = __( 'The status of the RAFCO API could not be determined.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// if we have data, send back things
		if(	! empty( $data ) ) {
			$ret['success'] = true;
			$ret['errcode'] = null;
			$ret['baricon'] = $data['icon'];
			$ret['message'] = $data['text'];
			$ret['stcheck'] = '<span class="dashicons dashicons-yes api-status-checkmark"></span>';
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'rafcolinks' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * run update job to get click counts via manual ajax
	 */
	public function refresh_rafco() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// start our return
		$ret = array();

		// verify our nonce
		$check	= check_ajax_referer( 'rafco_refresh_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = RAFCOCreator_Helper::get_rafco_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// fetch the IDs that contain a RAFCO url meta key
		if ( false === $items = RAFCOCreator_Helper::get_rafco_post_ids() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_POST_IDS';
			$ret['message'] = __( 'There are no items with stored URLs.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// loop the IDs
		foreach ( $items as $item_id ) {

			// get my click number
			$clicks = RAFCOCreator_Helper::get_single_click_count( $item_id );

			// bad API call
			if ( empty( $clicks['success'] ) ) {
				$ret['success'] = false;
				$ret['errcode'] = $clicks['errcode'];
				$ret['message'] = $clicks['message'];
				echo json_encode( $ret );
				die();
			}

			// got it. update the meta
			update_post_meta( $item_id, '_rafco_clicks', $clicks['clicknm'] );
		}

		// and do the API return
		$ret['success'] = true;
		$ret['message'] = __( 'The click counts have been updated', 'rafcolinks' );
		echo json_encode( $ret );
		die();
	}

	/**
	 * convert from Ozh (and Otto's) plugin
	 */
	public function convert_rafco() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// verify our nonce
		$check	= check_ajax_referer( 'rafco_convert_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// filter our key to replace
		$key = apply_filters( 'rafco_key_to_convert', 'rafco_shorturl' );

		// fetch the IDs that contain a RAFCO url meta key
		if ( false === $items = RAFCOCreator_Helper::get_rafco_post_ids( $key ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_KEYS';
			$ret['message'] = __( 'There are no meta keys to convert.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// set up SQL query
		global $wpdb;

		// prepare my query
		$setup  = $wpdb->prepare("
			UPDATE $wpdb->postmeta
			SET    meta_key = '%s'
			WHERE  meta_key = '%s'
			",
			esc_sql( '_rafco_url' ), esc_sql( $key )
		);

		// run SQL query
		$query = $wpdb->query( $setup );

		// start our return
		$ret = array();

		// no matches, return message
		if( $query == 0 ) {
			$ret['success'] = false;
			$ret['errcode'] = 'KEY_MISSING';
			$ret['message'] = __( 'There are no keys matching this criteria. Please try again.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// we had matches. return the success message with a count
		if( $query > 0 ) {
			$ret['success'] = true;
			$ret['errcode'] = null;
			$ret['updated'] = $query;
			$ret['message'] = sprintf( _n( '%d key has been updated.', '%d keys have been updated.', $query, 'rafcolinks' ), $query );
			echo json_encode( $ret );
			die();
		}
	}

	/**
	 * check the RAFCO install for existing links
	 * and pull the data if it exists
	 */
	public function import_rafco() {

		// only run on admin
		if ( ! is_admin() ) {
			die();
		}

		// verify our nonce
		$check	= check_ajax_referer( 'rafco_import_nonce', 'nonce', false );

		// check to see if our nonce failed
		if( ! $check ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NONCE_FAILED';
			$ret['message'] = __( 'The nonce did not validate.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail if the API key or URL have not been entered
		if(	false === $api = RAFCOCreator_Helper::get_rafco_api_data() ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_API_DATA';
			$ret['message'] = __( 'No API data has been entered.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// set my args for the API call
		$args   = array( 'filter' => 'top', 'limit' => apply_filters( 'rafco_import_limit', 999 ) );

		// make the API call
		$fetch  = RAFCOCreator_Helper::run_rafco_api_call( 'stats', $args );

		// bail if empty data
		if ( empty( $fetch ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'EMPTY_API';
			$ret['message'] = __( 'There was an unknown API error.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( false === $fetch['success'] ) {
			$ret['success'] = false;
			$ret['errcode'] = $build['errcode'];
			$ret['message'] = $build['message'];
			echo json_encode( $ret );
			die();
		}

		// bail error received
		if ( empty( $fetch['data']['links'] ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_LINKS';
			$ret['message'] = __( 'There was no available link data to import.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// filter the incoming for matching links
		$filter = RAFCOCreator_Helper::filter_rafco_import( $fetch['data']['links'] );

		// bail error received
		if ( empty( $filter ) ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_MATCHING_LINKS';
			$ret['message'] = __( 'There were no matching links to import.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// set a false flag
		$error  = false;

		// now filter them
		foreach ( $filter as $item ) {

			// do the import
			$import = RAFCOCreator_Helper::maybe_import_link( $item );

			// bail error received
			if ( empty( $import ) ) {
				$error  = true;
				break;
			}
		}

		// bail if we had true on the import
		if ( true === $error ) {
			$ret['success'] = false;
			$ret['errcode'] = 'NO_IMPORT_ACTION';
			$ret['message'] = __( 'The data could not be imported.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// hooray. it worked. do the ajax return
		if ( false === $error ) {
			$ret['success'] = true;
			$ret['message'] = __( 'All available RAFCO data has been imported.', 'rafcolinks' );
			echo json_encode( $ret );
			die();
		}

		// we've reached the end, and nothing worked....
		$ret['success'] = false;
		$ret['errcode'] = 'UNKNOWN';
		$ret['message'] = __( 'There was an unknown error.', 'rafcolinks' );
		echo json_encode( $ret );
		die();
	}

// end class
}

// end exists check
}

// Instantiate our class
new RAFCOCreator_Ajax();

