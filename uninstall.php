<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @package    Smart_Admin_Search
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Perform security checks.
$is_plugin_valid = ( isset( $_REQUEST['plugin'] ) && strpos( sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ), 'smart-admin-search' ) !== false ) ? true : false;
$is_slug_valid   = ( isset( $_REQUEST['slug'] ) && strpos( sanitize_text_field( wp_unslash( $_REQUEST['slug'] ) ), 'smart-admin-search' ) !== false ) ? true : false;
$is_user_allowed = current_user_can( 'delete_plugins' );

if ( ! $is_plugin_valid || ! $is_slug_valid || ! $is_user_allowed ) {
	exit;
}

// Check if plugin settings and data must be removed.
$option_delete_data_on_uninstall = get_option( 'sas_delete_data_on_uninstall' );

if ( '1' === $option_delete_data_on_uninstall ) {

	// Delete options.
	$options = array(
		'sas_search_keys_shortcut',
		'sas_disabled_search_functions',
		'sas_delete_data_on_uninstall',
		'sas_admin_bar_layout',
		'sas_show_results_url',
	);

	foreach ( $options as $option ) {
		if ( get_option( $option ) ) {
			delete_option( $option );
		}
	}
}
