<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/includes
 */

/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/includes
 * @author     Andrea Porotti
 */
class Smart_Admin_Search_Activator {

	/**
	 * Performs tasks on plugin activation.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		// --------------------------
		// Initialize plugin options.
		// --------------------------

		// Keys shortcut to open search modal.
		if ( false === get_option( 'sas_search_keys_shortcut' ) ) {
			add_option( 'sas_search_keys_shortcut', 'none', '', 'no' );
		}

		// Disabled search functions.
		if ( false === get_option( 'sas_disabled_search_functions' ) ) {
			add_option( 'sas_disabled_search_functions', array( 'none' ), '', 'no' );
		}

		// Delete settings and data when the plugin is removed.
		if ( false === get_option( 'sas_delete_data_on_uninstall' ) ) {
			add_option( 'sas_delete_data_on_uninstall', 0, '', 'no' );
		}

		// Choose admin bar search link layout.
		if ( false === get_option( 'sas_admin_bar_layout' ) ) {
			add_option( 'sas_admin_bar_layout', 0, '', 'no' );
		}

		// Display the results url.
		if ( false === get_option( 'sas_show_results_url' ) ) {
			add_option( 'sas_show_results_url', 0, '', 'no' );
		}

		// Display admin notice suggesting to configure a keyboard shortcut.
		if ( false === get_option( 'sas_show_admin_notice_keys_shortcut' ) && 'none' === get_option( 'sas_search_keys_shortcut' ) ) {
			add_option( 'sas_show_admin_notice_keys_shortcut', 1, '', 'no' );
		}
	}

}
