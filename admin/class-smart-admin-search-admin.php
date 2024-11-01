<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/admin
 * @author     Andrea Porotti
 */
class Smart_Admin_Search_Admin {

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
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The list of registered search functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $registered_functions    The list of registered search functions.
	 */
	private $registered_functions = array();

	/**
	 * The results from the executed search functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $search_results    The results from the executed search functions.
	 */
	private $search_results = array();

	/**
	 * The name of the class containing the search functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $search_functions_class    The name of the class containing the search functions.
	 */
	private $search_functions_class = 'Smart_Admin_Search_Functions';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since      1.0.0
	 * @param      string $plugin_name The name of this plugin.
	 * @param      string $plugin_slug The slug of this plugin.
	 * @param      string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $plugin_slug, $version ) {

		$this->plugin_name = $plugin_name;
		$this->plugin_slug = $plugin_slug;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		// Use uncompressed files if debug is enabled (remove ".min" from filename).
		$min = ( WP_DEBUG ) ? '' : '.min';

		wp_enqueue_style( $this->plugin_slug . '-select2', plugin_dir_url( __DIR__ ) . 'assets/select2/select2.min.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_slug, plugin_dir_url( __FILE__ ) . 'css/smart-admin-search-admin' . $min . '.css', array( $this->plugin_slug . '-select2' ), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		// Use uncompressed files if debug is enabled (remove ".min" from filename).
		$min = ( WP_DEBUG ) ? '' : '.min';

		global $locale;
		$short_locale = substr( $locale, 0, 2 );

		wp_enqueue_script( $this->plugin_slug . '-select2-fix', plugin_dir_url( __FILE__ ) . 'js/smart-admin-search-select2-fix' . $min . '.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( $this->plugin_slug . '-select2', plugin_dir_url( __DIR__ ) . 'assets/select2/select2.min.js', array( 'jquery', $this->plugin_slug . '-select2-fix' ), $this->version, true );
		wp_enqueue_script( $this->plugin_slug . '-select2-lang', plugin_dir_url( __DIR__ ) . 'assets/select2/i18n/' . $short_locale . '.js', array( 'jquery', $this->plugin_slug . '-select2' ), $this->version, true );
		wp_enqueue_script( $this->plugin_slug . '-admin', plugin_dir_url( __FILE__ ) . 'js/smart-admin-search-admin' . $min . '.js', array( 'jquery', $this->plugin_slug . '-select2' ), $this->version, true );

		$screen = get_current_screen();
		if ( 'settings_page_smart-admin-search_options' === $screen->id ) {
			wp_enqueue_script( $this->plugin_slug . '-options', plugin_dir_url( __FILE__ ) . 'js/smart-admin-search-options' . $min . '.js', array( 'jquery', $this->plugin_slug . '-admin' ), $this->version, true );
		}

		wp_localize_script(
			$this->plugin_slug . '-admin',
			'sas_values',
			array(
				'ajax'    => array(
					'search_url' => esc_url_raw( rest_url() ) . $this->plugin_slug . '/v1/search',
					'nonce'      => wp_create_nonce( 'wp_rest' ),
				),
				'strings' => array(
					'search_select_placeholder' => esc_html__( 'Hello, how may I help you?', 'smart-admin-search' ),
					'no_permissions'            => esc_html__( 'Sorry, it seems like you are not allowed to edit or view this item.', 'smart-admin-search' ),
				),
				'options' => array(
					'search_keys_shortcut' => $this->get_current_search_keys_shortcut( 'array' ),
					'show_results_url'     => get_option( 'sas_show_results_url', 0 ),
				),
			)
		);

	}

	/**
	 * Adds the admin bar item to open the search modal.
	 *
	 * @since    1.0.0
	 * @param    object $wp_admin_bar    The admin bar object.
	 */
	public function admin_bar_menu( $wp_admin_bar ) {

		// Set node meta.
		$meta = array(
			'title' => sprintf(
				/* translators: %s is the keyboard shortcut to open the search window */
				__( 'Search in the WordPress dashboard (%s)', 'smart-admin-search' ),
				$this->get_current_search_keys_shortcut( 'string' )
			),
		);

		// Check the layout setting to determine the item title.
		$layout = get_option( 'sas_admin_bar_layout', 0 );

		switch ( $layout ) {
			case 0:
				// Text and icon.
				$title = SMART_ADMIN_SEARCH_PLUGIN_NAME;
				break;
			case 1:
				// Icon.
				$title         = '';
				$meta['class'] = 'sas-layout-icon';
				break;
			default:
				// Text and icon.
				$title = SMART_ADMIN_SEARCH_PLUGIN_NAME;
		}

		if ( is_admin() ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'sas_icon',
					'title'  => $title,
					'href'   => '#',
					'parent' => 'top-secondary',
					'meta'   => $meta,
				)
			);
		}

	}

	/**
	 * Adds content to admin footer.
	 *
	 * @since    1.0.0
	 */
	public function admin_footer() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/smart-admin-search-admin-search-modal.php';

	}

	/**
	 * Registers a REST-API custom endpoint for the main search function.
	 *
	 * @since    1.0.0
	 */
	public function rest_api_register_search() {

		register_rest_route(
			$this->plugin_slug . '/v1',
			'search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'smart_admin_search' ),
				'permission_callback' => array( $this, 'smart_admin_search_permission_callback' ),
			)
		);

	}

	/**
	 * The permission callback for the main search function.
	 *
	 * @since    1.0.0
	 */
	public function smart_admin_search_permission_callback() {
		return is_user_logged_in();
	}

	/**
	 * The main search function called from the REST-API.
	 *
	 * @since    1.0.0
	 * @param    array $data Request data.
	 */
	public function smart_admin_search( $data ) {

		// Get the search query.
		$query = ( isset( $data['query'] ) ) ? sanitize_text_field( $data['query'] ) : '';

		if ( ! empty( $query ) ) {

			// Get disabled functions.
			$disabled_functions = get_option( 'sas_disabled_search_functions', array() );

			// Register search functions.
			$this->register_functions();

			// Get the search functions class.
			$search_functions_class = new $this->search_functions_class();

			// Run search functions.
			foreach ( $this->registered_functions as $function ) {
				// Skip disabled functions.
				if ( ! in_array( $function['name'], $disabled_functions, true ) ) {
					$this->search_results = $search_functions_class->{ $function['name'] }( $this->search_results, $query );
				}
			}

			// Parse the results.
			if ( ! empty( $this->search_results ) ) {
				$id = 1;

				foreach ( $this->search_results as $key => $result ) {

					// Add an id to the item.
					$this->search_results[ $key ]['id'] = $id;
					$id++;

					// Add the fallback icon class if icon class and style are empty.
					if ( empty( $result['icon_class'] ) && empty( $result['style'] ) ) {
						$this->search_results[ $key ]['icon_class'] = 'sas-search-result__icon--default';
					}
				}
			}
		}

		return $this->search_results;

	}

	/**
	 * Registers the search functions.
	 *
	 * @since    1.0.0
	 */
	public function register_functions() {

		// Get all the search functions class methods.
		$search_functions_class_methods = get_class_methods( $this->search_functions_class );

		// Get the search functions class.
		$search_functions_class = new $this->search_functions_class();

		// Run search functions class methods that register the search functions.
		$register_method_prefix = 'register_';

		foreach ( $search_functions_class_methods as $method ) {

			// If the method name starts with the correct prefix, run it.
			if ( substr( $method, 0, strlen( $register_method_prefix ) ) === $register_method_prefix ) {
				$this->registered_functions = $search_functions_class->{ $method }( $this->registered_functions );
			}
		}

	}

	/**
	 * Retrieves the registered search functions.
	 *
	 * @since     1.0.0
	 * @param     bool $run_registration    If true, runs the functions registration.
	 *
	 * @return    array    The registered functions.
	 */
	public function get_registered_functions( $run_registration = false ) {
		if ( $run_registration ) {
			$this->register_functions();
		}

		return $this->registered_functions;
	}

	/**
	 * Returns the current search keys shortcut in a specific format.
	 *
	 * @since     1.0.0
	 * @param     string $format    The format of shortcut to be returned.
	 */
	public static function get_current_search_keys_shortcut( $format ) {

		if ( ! empty( $format ) ) {

			// Available formats.
			$shortcut_array  = array();
			$shortcut_string = '';

			// Get the option.
			$option_search_keys_shortcut = get_option( 'sas_search_keys_shortcut', '' );

			if ( ! empty( $option_search_keys_shortcut ) && 'none' !== $option_search_keys_shortcut ) {

				// Get an array from saved string.
				$option_search_keys_shortcut_array = explode( ',', $option_search_keys_shortcut );

				foreach ( $option_search_keys_shortcut_array as $key ) {

					// Get key number and key name.
					$key_data = explode( '|', $key );

					if ( 'array' === $format ) {

						$shortcut_array[] = $key_data[0];

					} elseif ( 'string' === $format ) {

						if ( empty( $shortcut_string ) ) {
							$shortcut_string = $key_data[1];
						} else {
							$shortcut_string .= '+' . $key_data[1];
						}
					}
				}
			} else {
				// Display a default message for the string format.
				$shortcut_string = __( 'no keyboard shortcut set', 'smart-admin-search' );
			}

			return ${ "shortcut_$format" };

		}

	}

	/**
	 * Adds links to the plugin actions in the Plugins page.
	 *
	 * @since     1.0.0
	 * @param     array  $plugin_actions    The plugin action links.
	 * @param     string $plugin_file       The plugin main file name.
	 */
	public function plugin_action_links( $plugin_actions, $plugin_file ) {

		$new_actions = array();

		$new_actions['sas_settings'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( add_query_arg( 'page', 'smart-admin-search_options', admin_url( 'options-general.php' ) ) ),
			__( 'Settings', 'smart-admin-search' ),
		);

		return array_merge( $plugin_actions, $new_actions );

	}

	/**
	 * Shows admin notice suggesting to configure a keyboard shortcut.
	 *
	 * @since     1.5.0
	 */
	public function show_admin_notice_keys_shortcut() {

		if ( get_option( 'sas_show_admin_notice_keys_shortcut' ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<?php
						printf(
							/* translators: %1$s is the plugin name, %2$s is the settings page url. */
							wp_kses( __( 'Thank you for using %1$s, I hope you will like it! Please take a look at the <a href="%2$s">settings</a> page to choose a keyboard shortcut or configure the other plugin options.', 'smart-admin-search' ), array( 'a' => array( 'href' => array() ) ) ),
							esc_html( $this->plugin_name ),
							esc_url( add_query_arg( 'page', 'smart-admin-search_options', admin_url( 'options-general.php' ) ) )
						);
					?>
				</p>
			</div>
			<?php

			// Delete the option to prevent showing the notice again.
			delete_option( 'sas_show_admin_notice_keys_shortcut' );
		}

	}

}
