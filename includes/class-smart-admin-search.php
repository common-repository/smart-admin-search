<?php
/**
 * The file that defines the core plugin class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @since      1.0.0
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/includes
 * @author     Andrea Porotti
 */
class Smart_Admin_Search {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Smart_Admin_Search_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The name of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The name of this plugin.
	 */
	protected $plugin_name;

	/**
	 * The slug of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_slug    The slug of this plugin.
	 */
	protected $plugin_slug;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = SMART_ADMIN_SEARCH_PLUGIN_NAME;
		$this->plugin_slug = SMART_ADMIN_SEARCH_PLUGIN_SLUG;
		$this->version     = SMART_ADMIN_SEARCH_PLUGIN_VERSION;

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smart-admin-search-loader.php';

		/**
		 * The class responsible for defining internationalization functionality of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smart-admin-search-i18n.php';

		/**
		 * The class providing the search functions.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smart-admin-search-functions.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-smart-admin-search-admin.php';

		/**
		 * The class responsible for building the options page.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-smart-admin-search-options.php';

		$this->loader = new Smart_Admin_Search_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Smart_Admin_Search_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		global $wp_version;

		$plugin_admin = new Smart_Admin_Search_Admin( $this->get_plugin_name(), $this->get_plugin_slug(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_bar_menu', $plugin_admin, 'admin_bar_menu', ( version_compare( $wp_version, '6.6-RC1', '>=' ) ) ? 1 : 10 );
		$this->loader->add_action( 'admin_footer', $plugin_admin, 'admin_footer' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'show_admin_notice_keys_shortcut' );
		$this->loader->add_action( 'rest_api_init', $plugin_admin, 'rest_api_register_search' );
		$this->loader->add_filter( 'plugin_action_links_smart-admin-search/smart-admin-search.php', $plugin_admin, 'plugin_action_links', 10, 2 );

		// Plugin options.
		$plugin_options = new Smart_Admin_Search_Options( $this->get_plugin_name(), $this->get_plugin_slug() );
		$this->loader->add_action( 'admin_menu', $plugin_options, 'options_menu' );
		$this->loader->add_action( 'admin_init', $plugin_options, 'options_init' );

		// Search functions requirements.
		$search_functions = new Smart_Admin_Search_Functions();
		$this->loader->add_filter( 'adminmenu', $search_functions, 'get_admin_menu' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The slug of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The slug of the plugin.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Smart_Admin_Search_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
