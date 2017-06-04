<?php
/**
 * RAFCO Link Creator - Front End Module
 *
 * Contains front end functions
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

if ( ! class_exists( 'RAFCOCreator_Front' ) ) {

// Start up the engine
class RAFCOCreator_Front
{

	/**
	 * This is our constructor
	 *
	 * @return RAFCOCreator_Front
	 */
	public function __construct() {
		add_action( 'wp_head',                      array( $this, 'shortlink_meta'      )           );
		add_action( 'rafco_display',               array( $this, 'rafco_display'      )           );
	}

	/**
	 * add shortlink into head if present
	 *
	 * @return [type] [description]
	 */
	public function shortlink_meta() {

		// no shortlinks exist on non-singular items, so bail
		if ( ! is_singular() ) {
			return;
		}

		// check options to see if it's enabled
		if ( false === RAFCOCreator_Helper::get_rafco_option( 'sht' ) ) {
			return;
		}

		// call the global post object
		global $post;

		// bail without a post object
		if ( empty( $post ) || ! is_object( $post ) || empty( $post->ID ) ) {
			return;
		}

		// check existing postmeta for RAFCO link
		if ( false === $link = RAFCOCreator_Helper::get_rafco_meta( $post->ID ) ) {
			return;
		}

		// got a RAFCO? well then add it
		echo '<link href="' . esc_url( $link ) . '" rel="shortlink">' . "\n";
	}

	/**
	 * our pre-built template tag
	 *
	 * @return [type] [description]
	 */
	public function rafco_display( $post_id = 0, $echo = false ) {

		// no display exist on non-singular items, so bail
		if ( ! is_singular() ) {
			return;
		}

		// fetch the post ID if not provided
		if ( empty( $post_id ) ) {

			// call the object
			global $post;

			// bail if missing
			if ( empty( $post ) || ! is_object( $post ) || empty( $post->ID ) ) {
				return;
			}

			// set my post ID
			$post_id	= absint( $post->ID );
		}

		// check for the link
		if ( false === $link = RAFCOCreator_Helper::get_rafco_meta( $post_id ) ) {
			return;
		}

		// set an empty
		$show   = '';

		// build the markup
		$show  .= '<p class="rafco-display">' . __( 'Shortlink:', 'wprafco' );
			$show  .= '<input id="rafco-link-' . absint( $post_id ) . '" class="rafco-link" size="28" title="' . __( 'click to highlight', 'wprafco' ) . '" type="url" name="rafco-link-' . absint( $post_id ) . '" value="'. esc_url( $link ) .'" readonly="readonly" tabindex="501" onclick="this.focus();this.select()" />';
		$show  .= '</p>';

		// echo the box if requested
		if ( ! empty( $echo ) ) {
			echo apply_filters( 'rafco_template_tag', $show, $post_id );
		}

		// return the box
		return apply_filters( 'rafco_template_tag', $show, $post_id );
	}

// end class
}

// end exists check
}

// Instantiate our class
new RAFCOCreator_Front();

