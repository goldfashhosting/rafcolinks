<?php
/**
 * RAFCO Link Creator - Settings Module
 *
 * Contains the specific settings page configuration
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

if ( ! class_exists( 'RAFCOCreator_Settings' ) ) {

// Start up the engine
class RAFCOCreator_Settings
{

	/**
	 * This is our constructor
	 *
	 * @return RAFCOCreator_Settings
	 */
	public function __construct() {
		add_action( 'admin_menu',                   array( $this, 'rafco_menu_item'    )           );
		add_action( 'admin_init',                   array( $this, 'reg_settings'        )           );
		add_action( 'admin_init',                   array( $this, 'store_settings'      )           );
		add_action( 'admin_notices',                array( $this, 'settings_messages'   )           );
		add_filter( 'plugin_action_links',          array( $this, 'quick_link'          ),  10, 2   );
	}

	/**
	 * show settings link on plugins page
	 *
	 * @param  [type] $links [description]
	 * @param  [type] $file  [description]
	 * @return [type]        [description]
	 */
	public function quick_link( $links, $file ) {

		static $this_plugin;

		if ( ! $this_plugin ) {
			$this_plugin = RAFCO_BASE;
		}

		// check to make sure we are on the correct plugin
		if ( $file != $this_plugin ) {
			return $links;
		}

		// buil my link
		$single = '<a href="' . menu_page_url( 'rafco-settings', 0 ) . '">' . __( 'Settings', 'rafcolinks' ) . '</a>';

		// get it in the group
		array_push( $links, $single );

		// return it
		return $links;
	}

	/**
	 * call the menu page for the RAFCO settings
	 *
	 * @return void
	 */
	public function rafco_menu_item() {
		add_options_page( __( 'RAFCO Settings', 'rafcolinks' ), __( 'RAFCO Settings', 'rafcolinks' ), apply_filters( 'rafco_settings_cap', 'manage_options' ), 'rafco-settings', array( __class__, 'rafco_settings_page' ) );
	}

	/**
	 * Register settings
	 *
	 * @return
	 */
	public function reg_settings() {
		register_setting( 'rafco_options', 'rafco_options' );
	}

	/**
	 * check for, sanitize, and store our options
	 *
	 * @return [type] [description]
	 */
	public function store_settings() {

		// make sure we have our settings item
		if ( empty( $_POST['rafco-options'] ) ) {
			return;
		}

		// verify our nonce
		if ( ! isset( $_POST['rafco_settings_save'] ) || ! wp_verify_nonce( $_POST['rafco_settings_save'], 'rafco_settings_save_nonce' ) ) {
			return;
		}

		// cast our options as a variable
		$data   = (array) $_POST['rafco-options'];

		// set an empty
		$store  = array();

		// check and sanitize the URL
		if ( ! empty( $data['url'] ) ) {
			$store['url']   = esc_url( RAFCOCreator_Helper::strip_trailing_slash( $data['url'] ) );
		}

		// check and sanitize the API key
		if ( ! empty( $data['api'] ) ) {
			$store['api']   = sanitize_text_field( $data['api'] );
		}

		// check the boolean for autosave
		if ( ! empty( $data['sav'] ) ) {
			$store['sav']   = true;
		}

		// check the boolean for scheduled
		if ( ! empty( $data['sch'] ) ) {
			$store['sch']   = true;
		}

		// check the boolean for shortlink
		if ( ! empty( $data['sht'] ) ) {
			$store['sht']   = true;
		}

		// check the boolean for using CPTs
		if ( ! empty( $data['cpt'] ) ) {
			$store['cpt']   = true;
		}

		// check the each possible CPT
		if ( ! empty( $data['cpt'] ) && ! empty( $data['typ'] ) ) {
			$store['typ']   = RAFCOCreator_Helper::sanitize_array_text( $data['typ'] );
		}

		// filter it
		$store  = array_filter( $store );

		// pass it
		self::save_redirect_settings( $store );
	}

	/**
	 * save our settings and redirect to the proper place
	 *
	 * @param  array  $data [description]
	 * @param  string $key  [description]
	 * @return [type]       [description]
	 */
	public static function save_redirect_settings( $data = array(), $key = 'rafco-settings' ) {

		// first purge the API check
		delete_option( 'rafco_api_test' );

		// delete if empty, else go through some checks
		if ( empty( $data ) ) {
			// delete the key
			delete_option( 'rafco_options' );
			// get the link
			$redirect   = self::get_settings_page_link( $key, 'rafco-deleted=1' );
			// and redirect
			wp_redirect( $redirect, 302 );
			// and exit
			exit();
		}

		// we got something. check and store
		if ( get_option( 'rafco_options' ) !== false ) {
			update_option( 'rafco_options', $data );
		} else {
			add_option( 'rafco_options', $data, null, 'no' );
		}

		// get the link
		$redirect   = self::get_settings_page_link( $key, 'rafco-saved=1' );

		// and redirect
		wp_redirect( $redirect, 302 );

		// and exit
		exit();
	}

	/**
	 * display the admin settings based on the
	 * provided query string
	 *
	 * @return [type] [description]
	 */
	public function settings_messages() {

		// check for string first
		if ( empty( $_GET['rafco-action'] ) ) {
			return;
		}

		// our saved
		if ( ! empty( $_GET['rafco-saved'] ) ) {
			// the message
			echo '<div class="updated settings-error" id="setting-error-settings_updated">';
			echo '<p><strong>' . __( 'Your settings have been saved.', 'rafcolinks' ) . '</strong></p>';
			echo '</div>';
		}

		// our deleted
		if ( ! empty( $_GET['rafco-deleted'] ) ) {
			// the message
			echo '<div class="error settings-error" id="setting-error-settings_updated">';
			echo '<p><strong>' . __( 'Your settings have been deleted.', 'rafcolinks' ) . '</strong></p>';
			echo '</div>';
		}
	}

	/**
	 * get the link of my settings page
	 *
	 * @param  string $page   [description]
	 * @param  string $string [description]
	 * @return [type]         [description]
	 */
	public static function get_settings_page_link( $page = 'rafco-settings', $string = '' ) {

		// get the base
		$base   = menu_page_url( $page, 0 ) . '&rafco-action=1';

		// build the link
		$link   = ! empty( $string ) ? $base . '&' . $string : $base;

		// return it as base or with a string
		return esc_url_raw( html_entity_decode( $link ) );
	}

	/**
	 * Display main options page structure
	 *
	 * @return void
	 */
	public static function rafco_settings_page() {

		// bail if current user cannot manage options
		if(	! current_user_can( apply_filters( 'rafco_settings_cap', 'manage_options' ) ) ) {
			return;
		}
		?>

		<div class="wrap">
		<h2><?php _e( 'RAFCO Link Creator Settings', 'rafcolinks' ); ?></h2>

		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<?php
			self::settings_side();
			self::settings_open();
			?>

		   	<div class="rafco-form-text">
		   	<p><?php _e( 'Below are the basic settings for the RAFCO creator. A reminder, your RAFCO install cannot be public.', 'rafcolinks' ); ?></p>
			</div>

			<div class="rafco-form-options">
				<form method="post">
				<?php
				// fetch our data for the settings
				$data   = RAFCOCreator_Helper::get_rafco_option();

				// filter and check each one
				$url    = ! empty( $data['url'] ) ? $data['url'] : '';
				$api    = ! empty( $data['api'] ) ? $data['api'] : '';
				$save   = ! empty( $data['sav'] ) ? true : false;
				$schd   = ! empty( $data['sch'] ) ? true : false;
				$short  = ! empty( $data['sht'] ) ? true : false;
				$cpts   = ! empty( $data['cpt'] ) ? true : false;
				$types  = ! empty( $data['typ'] ) ? (array) $data['typ'] : array();

				// load the settings fields
				wp_nonce_field( 'rafco_settings_save_nonce', 'rafco_settings_save', false, true );
				?>

				<table class="form-table rafco-table">
				<tbody>
					<tr>
						<th><?php _e( 'Your G ShortLinks', 'rafcolinks' ); ?></th>
						<td>
							<input type="hidden" class="regular-text code" value="http://rafco.in" id="rafco-url" name="rafco-options[url]">
							
						</td>
					</tr>

					<tr>
						<th><?php _e( 'RAFCO API Signature Key', 'rafcolinks' ); ?></th>
						<td class="apikey-field-wrapper">
							<input type="hidden" class="regular-text code" value="e8dbd00175" id="rafco-api" name="rafco-options[api]" autocomplete="off">
							<span class="dashicons dashicons-visibility password-toggle"></span>
							<p class="description"><?php _e('Found in the tools section on your RAFCO admin page.', 'rafcolinks') ?></p>
						</td>
					</tr>

					<tr>
						<th><?php _e( 'Auto generate links', 'rafcolinks' ) ?></th>
						<td class="setting-item">
							<input type="checkbox" name="rafco-options[sav]" id="rafco-sav" value="true" <?php checked( $save, true ); ?> />
							<label for="rafco-sav"><?php _e( 'Create a RAFCO link when a post is saved.', 'rafcolinks' ); ?></label>
						</td>
					</tr>

					<tr>
						<th><?php _e( 'Scheduled Content', 'rafcolinks' ) ?></th>
						<td class="setting-item">
							<input type="checkbox" name="rafco-options[sch]" id="rafco-sch" value="true" <?php checked( $schd, true ); ?> />
							<label for="rafco-sch"><?php _e( 'Create a RAFCO link when a scheduled post publishes.', 'rafcolinks' ); ?></label>
						</td>
					</tr>

					<tr>
						<th><?php _e( 'Use RAFCO for shortlink', 'rafcolinks' ) ?></th>
						<td class="setting-item">
							<input type="checkbox" name="rafco-options[sht]" id="rafco-sht" value="true" <?php checked( $short, true ); ?> />
							<label for="rafco-sht"><?php _e( 'Use the RAFCO link wherever wp_shortlink is fired', 'rafcolinks' ); ?></label>
						</td>
					</tr>

					<tr class="setting-item-types">
						<th><?php _e( 'Include Custom Post Types', 'rafcolinks' ) ?></th>
						<td class="setting-item">
							<input type="checkbox" name="rafco-options[cpt]" id="rafco-cpt" value="true" <?php checked( $cpts, true ); ?> />
							<label for="rafco-cpt"><?php _e( 'Display the RAFCO creator on public custom post types', 'rafcolinks' ); ?></label>
						</td>
					</tr>

					<tr class="secondary rafco-types" style="display:none;">
						<th><?php _e( 'Select the types to include', 'rafcolinks' ); ?></th>
						<td><?php echo self::post_types( $types ); ?></td>
					</tr>

				</tbody>
				</table>

				<p><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /></p>
				</form>

			</div>

		<?php self::settings_close(); ?>

		</div>
		</div>

	<?php }

	/**
	 * fetch our custom post types and display checkboxes
	 * @param  array  $types [description]
	 * @return [type]        [description]
	 */
	private static function post_types( $selected = array() ) {

		// grab CPTs
		$args	= array(
			'public'    => true,
			'_builtin'  => false
		);

		// fetch the types
		$types	= get_post_types( $args, 'objects' );

		// return empty if none exist
		if ( empty( $types ) ) {
			return;
		}

		// output loop of types
		$boxes	= '';

		// loop my types
		foreach ( $types as $type ) {

			// type variables
			$name	= $type->name;
			$label	= $type->labels->name;

			// check for setting in array
			$check  = ! empty( $selected ) && in_array( $name, $selected ) ? 'checked="checked"' : '';

			// output checkboxes
			$boxes	.= '<span>';
				$boxes	.= '<input type="checkbox" name="rafco-options[typ][' . esc_attr( $name ) . ']" id="rafco-options-typ-' . esc_attr( $name ) . '" value="' . esc_attr( $name ) . '" ' . $check . ' />';
				$boxes	.= '<label for="rafco-options-typ-' . esc_attr( $name ) . '">' . esc_attr( $label ) . '</label>';
			$boxes	.= '</span>';
		}

		// return my boxes
		return $boxes;
	}

	/**
	 * Some extra stuff for the settings page
	 *
	 * this is just to keep the area cleaner
	 *
	 */
	public static function settings_side() { ?>

		<div id="side-info-column" class="inner-sidebar">

			<div class="meta-box-sortables">
				<?php self::sidebox_about(); ?>
			</div>

			<div class="meta-box-sortables">
				<?php self::sidebox_status(); ?>
			</div>

			<div class="meta-box-sortables">
				<?php self::sidebox_data(); ?>
			</div>

			<div class="meta-box-sortables">
				<?php self::sidebox_links(); ?>
			</div>

		</div> <!-- // #side-info-column .inner-sidebar -->

	<?php }

	/**
	 * the about sidebox
	 */
	public static function sidebox_about() { ?>

		<div id="rafco-admin-about" class="postbox rafco-sidebox">
			<h3 class="hndle" id="about-sidebar"><?php _e( 'About the Plugin', 'rafcolinks' ); ?></h3>
			<div class="inside">

				<p><strong><?php _e( 'Questions?', 'rafcolinks' ); ?></strong><br />

				<?php echo sprintf( __( 'Talk to <a href="%s" class="external">@norcross</a> on twitter or visit the <a href="%s" class="external">plugin support forum</a> for bugs or feature requests.', 'rafcolinks' ), esc_url( 'https://twitter.com/norcross' ), esc_url( 'https://wordpress.org/support/plugin/rafco-link-creator/' ) ); ?></p>

				<p><strong><?php _e( 'Enjoy the plugin?', 'rafcolinks' ); ?></strong><br />

				<?php echo sprintf( __( '<a href="%s" class="admin-twitter-link">Tweet about it</a> and consider donating.', 'rafcolinks' ), 'http://twitter.com/?status=I\'m using @norcross\'s RAFCO Link Creator plugin - check it out! http://l.norc.co/rafco/' ); ?>

				<p><strong><?php _e( 'Donate:', 'rafcolinks' ) ?></strong><br />

				<?php _e( 'A lot of hard work goes into building plugins - support your open source developers. Include your twitter username and I\'ll send you a shout out for your generosity. Thank you!', 'rafcolinks' ); ?></p>

				<?php self::side_paypal(); ?>
			</div>
		</div>

	<?php }

	/**
	 * the status sidebox
	 */
	public static function sidebox_status() {

		// get my API status data
		if ( false === $data = RAFCOCreator_Helper::get_api_status_data() ) {
			return;
		}
		?>

		<div id="rafco-admin-status" class="postbox rafco-sidebox">
			<h3 class="hndle" id="status-sidebar"><?php echo $data['icon']; ?><?php _e( 'API Status Check', 'rafcolinks' ); ?></h3>
			<div class="inside">
				<form>

				<p class="api-status-text"><?php echo esc_attr( $data['text'] ); ?></p>

				<p class="api-status-actions">
					<input type="button" class="rafco-click-status button-primary" value="<?php _e( 'Check Status', 'rafcolinks' ); ?>" >
					<span class="spinner rafco-spinner rafco-status-spinner"></span>
					<?php wp_nonce_field( 'rafco_status_nonce', 'rafco_status', false, true ); ?>

				</p>

				</form>
			</div>
		</div>

	<?php }

	/**
	 * the data sidebox
	 */
	public static function sidebox_data() { ?>

		<div id="rafco-data-refresh" class="postbox rafco-sidebox">
			<h3 class="hndle" id="data-sidebar"><?php _e( 'Data Options', 'rafcolinks' ); ?></h3>
			<div class="inside">
				<form>
					<p><?php _e( 'Click the button below to refresh the click count data for all posts with a RAFCO link.', 'rafcolinks' ); ?></p>
					<input type="button" class="rafco-click-updates button-primary" value="<?php _e( 'Refresh Click Counts', 'rafcolinks' ); ?>" >
					<span class="spinner rafco-spinner rafco-refresh-spinner"></span>
					<?php wp_nonce_field( 'rafco_refresh_nonce', 'rafco_refresh', false, true ); ?>

					<hr />

					<p><?php _e( 'Click the button below to attempt an import of existing RAFCO links.', 'rafcolinks' ); ?></p>
					<input type="button" class="rafco-click-import button-primary" value="<?php _e( 'Import Existing URLs', 'rafcolinks' ); ?>" >
					<span class="spinner rafco-spinner rafco-import-spinner"></span>
					<?php wp_nonce_field( 'rafco_import_nonce', 'rafco_import', false, true ); ?>

					<hr />

					<p><?php _e( 'Using Ozh\'s plugin? Click here to convert the existing meta keys', 'rafcolinks' ); ?></p>
					<input type="button" class="rafco-convert button-primary" value="<?php _e( 'Convert Meta Keys', 'rafcolinks' ); ?>" >
					<span class="spinner rafco-spinner rafco-convert-spinner"></span>
					<?php wp_nonce_field( 'rafco_convert_nonce', 'rafco_convert', false, true ); ?>

				</form>
			</div>
		</div>

	<?php }

	/**
	 * the links sidebox
	 */
	public static function sidebox_links() { ?>

		<div id="rafco-admin-links" class="postbox rafco-sidebox">
			<h3 class="hndle" id="links-sidebar"><?php _e( 'Additional Links', 'rafcolinks' ); ?></h3>
			<div class="inside">
				<ul>
					<li><a href="http://rafco.org/" target="_blank"><?php _e( 'RAFCO homepage', 'rafcolinks' ); ?></a></li>
					<li><a href="http://wordpress.org/extend/plugins/rafco-link-creator/" target="_blank"><?php _e( 'Plugin on WP.org', 'rafcolinks' ); ?></a></li>
					<li><a href="https://github.com/norcross/rafco-link-creator/" target="_blank"><?php _e( 'Plugin on GitHub', 'rafcolinks' ); ?></a></li>
					<li><a href="http://wordpress.org/support/plugin/rafco-link-creator/" target="_blank"><?php _e( 'Support Forum', 'rafcolinks' ); ?></a><li>
				</ul>
			</div>
		</div>

	<?php }

	/**
	 * paypal form for donations
	 */
	public static function side_paypal() { ?>

		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="11085100">
			<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="<?php _e( 'PayPal - The safer, easier way to pay online!', 'rafcolinks' ); ?>">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>

	<?php }

	/**
	 * open up the settings page markup
	 */
	public static function settings_open() { ?>

		<div id="post-body" class="has-sidebar">
			<div id="post-body-content" class="has-sidebar-content">
				<div id="normal-sortables" class="meta-box-sortables">
					<div id="about" class="postbox">
						<div class="inside">

	<?php }

	/**
	 * close out the settings page markup
	 */
	public static function settings_close() { ?>

						<br class="clear" />
						</div>
					</div>
				</div>
			</div>
		</div>

	<?php }

// end class
}

// end exists check
}

// Instantiate our class
new RAFCOCreator_Settings();

