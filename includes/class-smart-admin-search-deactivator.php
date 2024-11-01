<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/includes
 * @author     Andrea Porotti
 */
class Smart_Admin_Search_Deactivator {

	/**
	 * Performs tasks on plugin deactivation.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {

		// Delete all users transients.
		$users = get_users();
		foreach ( $users as $user ) {
			$transient_name = 'sas_admin_menu_user_' . $user->ID;
			if ( get_transient( $transient_name ) ) {
				delete_transient( $transient_name );
			}

			$transient_name = 'sas_admin_submenu_user_' . $user->ID;
			if ( get_transient( $transient_name ) ) {
				delete_transient( $transient_name );
			}
		}

	}

}
