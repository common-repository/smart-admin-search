<?php
/**
 * The options-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/admin
 */

/**
 * The options-specific functionality of the plugin.
 *
 * Configures the options page and registers the settings.
 *
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/admin
 * @author     Andrea Porotti
 */
class Smart_Admin_Search_Options {

	/**
	 * The name of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The name of this plugin.
	 */
	private $plugin_name;

	/**
	 * The slug of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_slug    The slug of this plugin.
	 */
	private $plugin_slug;

	/**
	 * The slug of options menu.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_slug    The slug of options menu.
	 */
	private $options_slug;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $plugin_slug The slug of this plugin.
	 */
	public function __construct( $plugin_name, $plugin_slug ) {

		$this->plugin_name  = $plugin_name;
		$this->plugin_slug  = $plugin_slug;
		$this->options_slug = $this->plugin_slug . '_options';

	}

	/**
	 * Adds the plugin options page as sub-item in the Settings menu.
	 *
	 * @since    1.0.0
	 */
	public function options_menu() {

		add_options_page(
			sprintf(
				/* translators: %s is the plugin name */
				__( '%s Settings', 'smart-admin-search' ),
				$this->plugin_name
			),
			$this->plugin_name,
			'manage_options',
			$this->options_slug,
			array(
				$this,
				'options_page',
			)
		);

	}

	/**
	 * Shows the options page content.
	 *
	 * @since    1.0.0
	 */
	public function options_page() {

		if ( current_user_can( 'manage_options' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/smart-admin-search-admin-options-page.php';
		}

	}

	/**
	 * Adds the plugin options to the options page.
	 *
	 * @since    1.0.0
	 */
	public function options_init() {

		// -----------------------
		// Display actions notice.
		// -----------------------

		if ( get_transient( 'sas_options_notice' ) ) {
			add_settings_error(
				'sas_maintenance_delete_temp_data',
				'sas_maintenance_delete_temp_data',
				get_transient( 'sas_options_notice' ),
				'updated'
			);

			delete_transient( 'sas_options_notice' );
		}

		// ----------------------
		// Run requested actions.
		// ----------------------

		$action = ( isset( $_GET['sas_action'] ) ) ? sanitize_text_field( wp_unslash( $_GET['sas_action'] ) ) : '';
		$nonce  = ( isset( $_GET['sas_nonce'] ) ) ? sanitize_text_field( wp_unslash( $_GET['sas_nonce'] ) ) : '';

		if ( 'delete_temp_data_user' === $action && wp_verify_nonce( $nonce, 'sas_delete_temp_data_user' ) ) {

			// Delete current user admin menu transients.
			global $current_user;

			delete_transient( 'sas_admin_menu_user_' . $current_user->ID );
			delete_transient( 'sas_admin_submenu_user_' . $current_user->ID );

			// Save notice text.
			set_transient(
				'sas_options_notice',
				esc_html__( 'Successfully deleted temporary data of the current user.', 'smart-admin-search' ),
				60
			);

			// Go back to the options page.
			wp_safe_redirect( wp_get_referer() );

		} elseif ( 'delete_temp_data_all' === $action && wp_verify_nonce( $nonce, 'sas_delete_temp_data_all' ) ) {

			// Delete all users admin menu transients.
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

			// Save notice text.
			set_transient(
				'sas_options_notice',
				esc_html__( 'Successfully deleted temporary data of all users.', 'smart-admin-search' ),
				60
			);

			// Go back to the options page.
			wp_safe_redirect( wp_get_referer() );

		}

		// -------------------------------------------
		// Set keys shortcut to open the search modal.
		// -------------------------------------------

		// Add a section.
		add_settings_section(
			'sas_options_section_keys_shortcuts',
			esc_html__( 'Keyboard shortcuts', 'smart-admin-search' ),
			array(
				$this,
				'options_section_keys_shortcut',
			),
			$this->options_slug
		);

		// Register a setting.
		register_setting(
			$this->options_slug,
			'sas_search_keys_shortcut',
			array(
				'type'              => 'string',
				'show_in_rest'      => false,
				'default'           => '',
				'sanitize_callback' => array(
					$this,
					'option_search_keys_shortcut_sanitize',
				),
			)
		);

		// Add setting field to the section.
		add_settings_field(
			'sas_search_keys_shortcut',
			esc_html__( 'Open the search box', 'smart-admin-search' ),
			array(
				$this,
				'option_search_keys_shortcut',
			),
			$this->options_slug,
			'sas_options_section_keys_shortcuts',
			array(
				'name' => 'sas_search_keys_shortcut',
			)
		);

		// -----------------------------------
		// Enable or disable search functions.
		// -----------------------------------

		// Add a section.
		add_settings_section(
			'sas_options_section_search_functions',
			esc_html__( 'Search functions', 'smart-admin-search' ),
			array(
				$this,
				'options_section_search_functions',
			),
			$this->options_slug
		);

		// Register a setting.
		register_setting(
			$this->options_slug,
			'sas_disabled_search_functions',
			array(
				'type'              => 'array',
				'show_in_rest'      => false,
				'default'           => array(),
				'sanitize_callback' => array(
					$this,
					'option_disabled_search_functions_sanitize',
				),
			)
		);

		// Add setting field to the section.
		add_settings_field(
			'sas_disabled_search_functions',
			esc_html__( 'Select the functions to run on each search', 'smart-admin-search' ),
			array(
				$this,
				'option_disabled_search_functions',
			),
			$this->options_slug,
			'sas_options_section_search_functions',
			array(
				'name' => 'sas_disabled_search_functions',
			)
		);

		// ----------------------
		// Set plugin appearance.
		// ----------------------

		// Add a section.
		add_settings_section(
			'sas_options_section_appearance',
			esc_html__( 'Appearance', 'smart-admin-search' ),
			array(
				$this,
				'options_section_appearance',
			),
			$this->options_slug
		);

		// Register a setting.
		register_setting(
			$this->options_slug,
			'sas_admin_bar_layout',
			array(
				'type'              => 'integer',
				'show_in_rest'      => false,
				'default'           => 0,
				'sanitize_callback' => array(
					$this,
					'option_admin_bar_layout_sanitize',
				),
			)
		);

		// Add setting field to the section.
		add_settings_field(
			'sas_admin_bar_layout',
			esc_html__( 'Choose the layout of the search link on the admin bar', 'smart-admin-search' ),
			array(
				$this,
				'option_admin_bar_layout',
			),
			$this->options_slug,
			'sas_options_section_appearance',
			array(
				'name' => 'sas_admin_bar_layout',
			)
		);

		// Register a setting.
		register_setting(
			$this->options_slug,
			'sas_show_results_url',
			array(
				'type'              => 'boolean',
				'show_in_rest'      => false,
				'default'           => 0,
				'sanitize_callback' => array(
					$this,
					'option_show_results_url_sanitize',
				),
			)
		);

		// Add setting field to the section.
		add_settings_field(
			'sas_show_results_url',
			esc_html__( 'Show destination address below each result', 'smart-admin-search' ),
			array(
				$this,
				'option_show_results_url',
			),
			$this->options_slug,
			'sas_options_section_appearance',
			array(
				'label_for' => 'sas_show_results_url',
			)
		);

		// ----------------------------------------------------
		// Delete settings and data when the plugin is removed.
		// ----------------------------------------------------

		// Add a section.
		add_settings_section(
			'sas_options_section_uninstall',
			esc_html__( 'Plugin uninstall', 'smart-admin-search' ),
			array(
				$this,
				'options_section_uninstall',
			),
			$this->options_slug
		);

		// Register a setting.
		register_setting(
			$this->options_slug,
			'sas_delete_data_on_uninstall',
			array(
				'type'              => 'boolean',
				'show_in_rest'      => false,
				'default'           => 0,
				'sanitize_callback' => array(
					$this,
					'option_delete_data_on_uninstall_sanitize',
				),
			)
		);

		// Add setting field to the section.
		add_settings_field(
			'sas_delete_data_on_uninstall',
			esc_html__( 'Delete all plugin data', 'smart-admin-search' ),
			array(
				$this,
				'option_delete_data_on_uninstall',
			),
			$this->options_slug,
			'sas_options_section_uninstall',
			array(
				'label_for' => 'sas_delete_data_on_uninstall',
			)
		);

		// ----------------------------------------------------
		// Plugin maintenance tools.
		// ----------------------------------------------------

		// Add a section.
		add_settings_section(
			'sas_options_section_maintenance',
			esc_html__( 'Plugin maintenance', 'smart-admin-search' ),
			array(
				$this,
				'options_section_maintenance',
			),
			$this->options_slug
		);

		// Add setting field to the section.
		add_settings_field(
			'sas_maintenance_delete_temp_data_user',
			esc_html__( 'Delete temporary data of the current user', 'smart-admin-search' ),
			array(
				$this,
				'option_maintenance_delete_temp_data_user',
			),
			$this->options_slug,
			'sas_options_section_maintenance'
		);

		// Add setting field to the section.
		add_settings_field(
			'sas_maintenance_delete_temp_data_all',
			esc_html__( 'Delete temporary data of all users', 'smart-admin-search' ),
			array(
				$this,
				'option_maintenance_delete_temp_data_all',
			),
			$this->options_slug,
			'sas_options_section_maintenance'
		);

	}

	/**
	 * Callback for the keys shortcut options section output.
	 *
	 * @since    1.0.0
	 * @param    array $args Array of section attributes.
	 */
	public function options_section_keys_shortcut( $args ) {

		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>">
			<?php echo esc_html__( 'Configure keyboard shortcuts to access plugin features.', 'smart-admin-search' ); ?>
		</p>
		<?php

	}

	/**
	 * Callback for the search_keys_shortcut option value sanitization.
	 *
	 * @since    1.0.0
	 * @param    array $value Option value.
	 */
	public function option_search_keys_shortcut_sanitize( $value ) {

		return $value;

	}

	/**
	 * Callback for the search_keys_shortcut option field output.
	 *
	 * @since    1.0.0
	 * @param    array $args Array of field attributes.
	 */
	public function option_search_keys_shortcut( $args ) {

		// Get the option value.
		$option_search_keys_shortcut = get_option( $args['name'], '' );

		// Get a readable version of the current shortcut.
		$current_search_keys_shortcut = Smart_Admin_Search_Admin::get_current_search_keys_shortcut( 'string' );

		?>
		<fieldset>
			<input type="text" id="sas-capture-search-keys" class="regular-text sas-skip-global-keypress" value="">
			<button type="button" id="sas-capture-search-keys-reset" class="button"><?php echo esc_html__( 'Clear', 'smart-admin-search' ); ?></button>
			<input type="hidden" id="<?php echo esc_attr( $args['name'] ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="<?php echo esc_attr( $option_search_keys_shortcut ); ?>">
			<p class="description">
				<?php echo esc_html__( 'Click on the textbox and then press the keys that you will use to open the search box. Click the Clear button to empty the textbox.', 'smart-admin-search' ); ?>
				<br>
				<?php echo esc_html__( 'The current shortcut is:', 'smart-admin-search' ); ?> <strong><?php echo esc_html( $current_search_keys_shortcut ); ?></strong>.
			</p>
		</fieldset>
		<?php

	}

	// ------------------------------------------------------------------------

	/**
	 * Callback for the search functions options section output.
	 *
	 * @since    1.0.0
	 * @param    array $args Array of section attributes.
	 */
	public function options_section_search_functions( $args ) {

		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>">
			<?php echo esc_html__( 'Configure the available search functions.', 'smart-admin-search' ); ?>
		</p>
		<?php

	}

	/**
	 * Callback for the disabled_search_functions option value sanitization.
	 *
	 * @since    1.0.0
	 * @param    array $value Option value.
	 */
	public function option_disabled_search_functions_sanitize( $value ) {

		// Get the registered functions.
		$admin                      = new Smart_Admin_Search_Admin( '', '', '' );
		$registered_functions       = $admin->get_registered_functions( true );
		$registered_functions_names = array_column( $registered_functions, 'name' );

		// Get registered functions disabled by the user.
		if ( empty( $value ) ) {
			// All functions are disabled.
			$disabled_functions = $registered_functions_names;
		} else {
			$disabled_functions = array_diff( $registered_functions_names, $value );
		}

		return ( ! empty( $disabled_functions ) ) ? $disabled_functions : array( 'none' );

	}

	/**
	 * Callback for the disabled_search_functions option field output.
	 *
	 * @since    1.0.0
	 * @param    array $args Array of field attributes.
	 */
	public function option_disabled_search_functions( $args ) {

		// Get the option value.
		$option_disabled_search_functions = get_option( $args['name'], array() );

		// Get the registered functions.
		$admin                = new Smart_Admin_Search_Admin( '', '', '' );
		$registered_functions = $admin->get_registered_functions( true );

		// Sort functions by display name.
		usort(
			$registered_functions,
			function ( $item1, $item2 ) {
				if ( $item1['display_name'] === $item2['display_name'] ) {
					return 0;
				}
				return $item1['display_name'] < $item2['display_name'] ? -1 : 1;
			}
		);

		?>
		<fieldset>
			<?php foreach ( $registered_functions as $function ) : ?>
				<?php $id_attr = $args['name'] . '_' . $function['name']; ?>
				<?php $checked_attr = ( ! in_array( $function['name'], $option_disabled_search_functions, true ) ) ? 'checked' : ''; ?>

				<input type="checkbox" id="<?php echo esc_attr( $id_attr ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>[]" value="<?php echo esc_attr( $function['name'] ); ?>" <?php echo esc_attr( $checked_attr ); ?>>
				<label for="<?php echo esc_attr( $id_attr ); ?>"><?php echo esc_html( $function['display_name'] ); ?></label>
				<p class="description">
					<?php echo esc_html( $function['description'] ); ?>
				</p>
				<br>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	// ------------------------------------------------------------------------

	/**
	 * Callback for the appearance options section output.
	 *
	 * @since    1.2.0
	 * @param    array $args Array of section attributes.
	 */
	public function options_section_appearance( $args ) {

		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>">
			<?php echo esc_html__( 'Set the plugin appearance.', 'smart-admin-search' ); ?>
		</p>
		<?php

	}

	/**
	 * Callback for the admin_bar_layout option value sanitization.
	 *
	 * @since    1.2.0
	 * @param    integer $value Option value.
	 */
	public function option_admin_bar_layout_sanitize( $value ) {

		return intval( $value );

	}

	/**
	 * Callback for the admin_bar_layout option field output.
	 *
	 * @since    1.2.0
	 * @param    array $args Array of field attributes.
	 */
	public function option_admin_bar_layout( $args ) {

		// Get the option value.
		$option_admin_bar_layout = intval( get_option( $args['name'], 0 ) );

		?>
		<fieldset>
			<?php $id_attr = $args['name'] . '_0'; ?>
			<?php $checked_attr = ( 0 === $option_admin_bar_layout ) ? 'checked' : ''; ?>

			<input type="radio" id="<?php echo esc_attr( $id_attr ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="0" <?php echo esc_attr( $checked_attr ); ?>>
			<label for="<?php echo esc_attr( $id_attr ); ?>"><?php echo esc_html__( 'Text and icon', 'smart-admin-search' ); ?></label>

			<br>

			<?php $id_attr = $args['name'] . '_1'; ?>
			<?php $checked_attr = ( 1 === $option_admin_bar_layout ) ? 'checked' : ''; ?>

			<input type="radio" id="<?php echo esc_attr( $id_attr ); ?>" name="<?php echo esc_attr( $args['name'] ); ?>" value="1" <?php echo esc_attr( $checked_attr ); ?>>
			<label for="<?php echo esc_attr( $id_attr ); ?>"><?php echo esc_html__( 'Icon', 'smart-admin-search' ); ?></label>

			<p class="description">
				<?php echo esc_html__( 'Changes size of the link used to open the search box. Choose "icon" to have a smaller link.', 'smart-admin-search' ); ?>
			</p>
		</fieldset>
		<?php
	}

	/**
	 * Callback for the show_results_url option value sanitization.
	 *
	 * @since    1.3.0
	 * @param    string $value Option value.
	 */
	public function option_show_results_url_sanitize( $value ) {

		if ( '1' !== $value ) {
			return 0;
		}

		return $value;

	}

	/**
	 * Callback for the show_results_url option field output.
	 *
	 * @since    1.3.0
	 * @param    array $args Array of field attributes.
	 */
	public function option_show_results_url( $args ) {

		// Get the option value.
		$option_show_results_url = get_option( $args['label_for'], 0 );

		?>
		<fieldset>
			<input type="checkbox" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $args['label_for'] ); ?>" value="1" <?php checked( $option_show_results_url, 1 ); ?>>
			<label for="<?php echo esc_attr( $args['label_for'] ); ?>"><?php echo esc_html__( 'enabled', 'smart-admin-search' ); ?></label>
			<p class="description">
				<?php echo esc_html__( 'Enable this option to display the address a result will redirect you to after selection.', 'smart-admin-search' ); ?>
			</p>
		</fieldset>
		<?php

	}

	// ------------------------------------------------------------------------

	/**
	 * Callback for the uninstall options section output.
	 *
	 * @since    1.0.0
	 * @param    array $args Array of section attributes.
	 */
	public function options_section_uninstall( $args ) {

		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>">
			<?php echo esc_html__( 'These settings are applied when you uninstall the plugin.', 'smart-admin-search' ); ?>
		</p>
		<?php

	}

	/**
	 * Callback for the delete_data_on_uninstall option value sanitization.
	 *
	 * @since    1.0.0
	 * @param    string $value Option value.
	 */
	public function option_delete_data_on_uninstall_sanitize( $value ) {

		if ( '1' !== $value ) {
			return 0;
		}

		return $value;

	}

	/**
	 * Callback for the delete_data_on_uninstall option field output.
	 *
	 * @since    1.0.0
	 * @param    array $args Array of field attributes.
	 */
	public function option_delete_data_on_uninstall( $args ) {

		// Get the option value.
		$option_delete_data_on_uninstall = get_option( $args['label_for'], 0 );

		?>
		<fieldset>
			<input type="checkbox" id="<?php echo esc_attr( $args['label_for'] ); ?>" name="<?php echo esc_attr( $args['label_for'] ); ?>" value="1" <?php checked( $option_delete_data_on_uninstall, 1 ); ?>>
			<label for="<?php echo esc_attr( $args['label_for'] ); ?>"><?php echo esc_html__( 'enabled', 'smart-admin-search' ); ?></label>
			<p class="description">
				<?php echo esc_html__( 'Please note: enabling this option all data and settings will be PERMANENTLY DELETED when you uninstall the plugin.', 'smart-admin-search' ); ?>
			</p>
		</fieldset>
		<?php

	}

	// ------------------------------------------------------------------------

	/**
	 * Callback for the maintenance section output.
	 *
	 * @since    1.0.0
	 * @param    array $args Array of section attributes.
	 */
	public function options_section_maintenance( $args ) {

		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>">
			<?php echo esc_html__( 'Tools for the plugin maintenance.', 'smart-admin-search' ); ?>
		</p>
		<?php

	}

	/**
	 * Callback for the "delete current user data" button.
	 *
	 * @since    1.0.0
	 */
	public function option_maintenance_delete_temp_data_user() {

		// Get the current url and add the action parameter.
		$url = add_query_arg( 'sas_action', 'delete_temp_data_user' );

		// Add nonce to the url.
		$url = wp_nonce_url( $url, 'sas_delete_temp_data_user', 'sas_nonce' );

		?>
		<a href="<?php echo esc_url( $url ); ?>" class="button">
			<?php echo esc_html__( 'Delete data', 'smart-admin-search' ); ?>
		</a>
		<?php

	}

	/**
	 * Callback for the "delete all users data" button.
	 *
	 * @since    1.0.0
	 */
	public function option_maintenance_delete_temp_data_all() {

		// Get the current url and add the action parameter.
		$url = add_query_arg( 'sas_action', 'delete_temp_data_all' );

		// Add nonce to the url.
		$url = wp_nonce_url( $url, 'sas_delete_temp_data_all', 'sas_nonce' );

		?>
		<a href="<?php echo esc_url( $url ); ?>" class="button">
			<?php echo esc_html__( 'Delete data', 'smart-admin-search' ); ?>
		</a>
		<?php

	}

}
