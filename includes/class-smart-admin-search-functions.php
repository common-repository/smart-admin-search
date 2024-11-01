<?php
/**
 * The available search functions.
 *
 * @since      1.0.0
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/includes
 */

/**
 * The available search functions.
 *
 * @package    Smart_Admin_Search
 * @subpackage Smart_Admin_Search/includes
 * @author     Andrea Porotti
 */
class Smart_Admin_Search_Functions {

	// ----------------------------
	// Search the admin menu items.
	// ----------------------------

	/**
	 * Saves the admin menu to the database.
	 *
	 * @since    1.0.0
	 */
	public function get_admin_menu() {

		global $menu, $submenu, $current_user;

		// Get menus from transients.
		$transient_menu    = get_transient( 'sas_admin_menu_user_' . $current_user->ID );
		$transient_submenu = get_transient( 'sas_admin_submenu_user_' . $current_user->ID );

		// Set the transient if it is false or different from the corresponding global menu.

		// First level menu.
		$global_menu = $menu;

		// Menu items to be skipped.
		$menu_items_to_skip = array(
			'wp-menu-separator',
			'menu-top menu-icon-links',
		);

		// Parse all menu items.
		array_walk(
			$global_menu,
			function( $item, $key ) use ( &$global_menu, $menu_items_to_skip ) {

				if ( ! in_array( $item[4], $menu_items_to_skip, true ) ) {
					// Remove any HTML code from item name. If the name is empty, remove the item.
					$name = trim( sanitize_text_field( ( strpos( $item[0], '<' ) > 0 ) ? strstr( $item[0], '<', true ) : $item[0] ) );

					if ( ! empty( $name ) ) {
						$global_menu[ $key ][0] = $name;
					} else {
						unset( $global_menu[ $key ] );
					}
				} else {
					// Remove the menu item.
					unset( $global_menu[ $key ] );
				}

			}
		);

		if ( false === $transient_menu || $global_menu !== $transient_menu ) {
			set_transient( 'sas_admin_menu_user_' . $current_user->ID, $global_menu );
		}

		// Submenu.
		$global_submenu = $submenu;

		// Parse all menu items.
		array_walk(
			$global_submenu,
			function( $item, $key ) use ( &$global_submenu ) {

				if ( ! empty( $key ) ) {
					foreach ( $item as $item_key => $menu_item ) {
						// Remove any HTML code from item name.
						$global_submenu[ $key ][ $item_key ][0] = trim( sanitize_text_field( ( strpos( $menu_item[0], '<' ) > 0 ) ? strstr( $menu_item[0], '<', true ) : $menu_item[0] ) );

						// Remove any 'return' parameter from file name.
						$global_submenu[ $key ][ $item_key ][2] = remove_query_arg( 'return', wp_kses_decode_entities( $menu_item[2] ) );
					}
				} else {
					// Remove the item if it has no parent (empty $key).
					unset( $global_submenu[ $key ] );
				}

			}
		);

		if ( false === $transient_submenu || $global_submenu !== $transient_submenu ) {
			set_transient( 'sas_admin_submenu_user_' . $current_user->ID, $global_submenu );
		}

	}

	/**
	 * Registers the function that looks for an admin menu item containing the search query.
	 *
	 * @since    1.0.0
	 * @param    array $registered_functions    The list of registered search functions.
	 */
	public function register_search_admin_menu( $registered_functions ) {

		// Register the function.
		$registered_functions[] = array(
			'name'         => 'search_admin_menu',
			'display_name' => esc_html__( 'Admin Menu', 'smart-admin-search' ),
			'description'  => esc_html__( 'Search items in the admin menu.', 'smart-admin-search' ),
		);

		return $registered_functions;

	}

	/**
	 * Looks for an admin menu item containing the search query.
	 *
	 * @since    1.0.0
	 * @param    array  $search_results    The global search results.
	 * @param    string $query             The search query.
	 */
	public function search_admin_menu( $search_results, $query ) {

		global $current_user;

		// Get menus from transients.
		$admin_menu    = get_transient( 'sas_admin_menu_user_' . $current_user->ID );
		$admin_submenu = get_transient( 'sas_admin_submenu_user_' . $current_user->ID );

		// Search the first level menu items.
		if ( ! empty( $admin_menu ) ) {

			foreach ( $admin_menu as $menu_item ) {

				// Get item name.
				$name = $menu_item[0];

				// Get item icon.
				$icon       = $menu_item[6];
				$icon_class = '';
				$style      = '';
				if ( substr( $icon, 0, strlen( 'dashicons' ) ) === 'dashicons' ) { // -- if it's a dashicons class
					$icon_class = $icon;
				} elseif ( substr( $icon, 0, strlen( 'data:image' ) ) === 'data:image' ) { // -- if it's an image
					$style = 'background-image: url(\'' . $icon . '\');';
				}

				// Check if the item name contains the query.
				if ( ! empty( $name ) && strpos( strtolower( $name ), strtolower( $query ) ) !== false ) {

					// Generate item url.
					if ( strpos( $menu_item[2], '.php' ) !== false ) {
						// The item contains a file name.
						$url = wp_specialchars_decode( admin_url( $menu_item[2] ) );
					} else {
						// Use admin.php if no file name has been found.
						$url = wp_specialchars_decode( add_query_arg( 'page', $menu_item[2], admin_url( '/admin.php' ) ) );
					}

					// Add the item to search results.
					$search_results[] = array(
						'text'        => $name,
						'description' => esc_html__( 'Admin menu item.', 'smart-admin-search' ),
						'link_url'    => $url,
						'icon_class'  => $icon_class,
						'style'       => $style,
					);

				}
			}
		}

		// Search the submenu items.
		if ( ! empty( $admin_menu ) && ! empty( $admin_submenu ) ) {

			array_walk(
				$admin_submenu,
				function( $item, $key ) use ( &$search_results, $query, $admin_menu ) {

					foreach ( $item as $item_key => $menu_item ) {

						// Get parent item.
						$parent_item = $this->get_admin_menu_item_by_key( $admin_menu, $key );

						// Get item name.
						$name = $menu_item[0];

						// Set full item name.
						$full_name = $parent_item['name'] . ' / ' . $name;

						// Get item icon.
						$icon       = $parent_item['icon'];
						$icon_class = '';
						$style      = '';
						if ( substr( $icon, 0, strlen( 'dashicons' ) ) === 'dashicons' ) { // -- if it's a dashicons class
							$icon_class = $icon;
						} elseif ( substr( $icon, 0, strlen( 'data:image' ) ) === 'data:image' ) { // -- if it's an image
							$style = 'background-image: url(\'' . $icon . '\');';
						}

						// Check if the item full name contains the query.
						if ( ! empty( $full_name ) && strpos( strtolower( $full_name ), strtolower( $query ) ) !== false ) {

							// Generate item url.
							if ( strpos( $menu_item[2], '.php' ) !== false ) {
								// The item contains a file name.
								$url = wp_specialchars_decode( admin_url( $menu_item[2] ) );
							} elseif ( strpos( $key, '.php' ) !== false ) {
								// The item parent contains a file name.
								$url = wp_specialchars_decode( add_query_arg( 'page', $menu_item[2], admin_url( $key ) ) );
							} else {
								// Use admin.php if no file name has been found.
								$url = wp_specialchars_decode( add_query_arg( 'page', $menu_item[2], admin_url( '/admin.php' ) ) );
							}

							// Add the item to search results.
							$search_results[] = array(
								'text'        => $full_name,
								'description' => esc_html__( 'Admin menu item.', 'smart-admin-search' ),
								'link_url'    => $url,
								'icon_class'  => $icon_class,
								'style'       => $style,
							);

						}
					}

				}
			);

		}

		return $search_results;

	}

	/**
	 * Gets data of a first level admin menu item by the item key.
	 *
	 * @since    1.0.0
	 * @param    array  $admin_menu    The first level admin menu.
	 * @param    string $key           Menu item key.
	 */
	private function get_admin_menu_item_by_key( $admin_menu, $key ) {

		$item_data = array(
			'name' => '',
			'icon' => '',
		);

		if ( ! empty( $key ) ) {

			foreach ( $admin_menu as $menu_item ) {

				if ( $menu_item[2] === $key ) {

					$item_data['name'] = $menu_item[0];
					$item_data['icon'] = $menu_item[6];

					return $item_data;

				}
			}
		}

		return $item_data;

	}

	/**
	 * Registers the function that looks for posts containing the search query.
	 *
	 * @since    1.1.0
	 * @param    array $registered_functions    The list of registered search functions.
	 */
	public function register_search_posts( $registered_functions ) {

		// Register the function.
		$registered_functions[] = array(
			'name'         => 'search_posts',
			'display_name' => esc_html__( 'Posts', 'smart-admin-search' ),
			'description'  => esc_html__( 'Search posts.', 'smart-admin-search' ),
		);

		return $registered_functions;

	}

	/**
	 * Looks for posts containing the search query.
	 *
	 * @since    1.1.0
	 * @param    array  $search_results    The global search results.
	 * @param    string $query             The search query.
	 */
	public function search_posts( $search_results, $query ) {

		if ( current_user_can( 'edit_posts' ) ) {
			$args = array(
				'post_type'      => 'post',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				's'              => $query,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'perm'           => 'editable',
			);

			$posts_query = new WP_Query( $args );
			$posts       = $posts_query->posts;
			wp_reset_postdata();

			foreach ( $posts as $post ) {
				// Skip this post if it's private and the user can't access private posts.
				if ( ! ( 'private' === $post->post_status && ! current_user_can( 'read_private_posts' ) ) ) {

					$text = ( ! empty( $post->post_title ) ) ? $post->post_title : esc_html__( '(no title)', 'smart-admin-search' );

					if ( 'publish' !== $post->post_status ) {
						$post_status = get_post_status_object( $post->post_status )->label;
						$text       .= ' (' . $post_status . ')';
					}

					$link_url = get_edit_post_link( $post->ID, '' );

					if ( empty( $link_url ) && 'draft' !== $post->post_status ) {
						$link_url = get_the_permalink( $post->ID );
					}

					$icon_class = 'dashicons-admin-post';
					$style      = '';

					// Add the item to search results.
					$search_results[] = array(
						'text'        => $text,
						'description' => esc_html__( 'Post.', 'smart-admin-search' ),
						'link_url'    => ( ! empty( $link_url ) ) ? $link_url : '',
						'icon_class'  => $icon_class,
						'style'       => $style,
					);
				}
			}
		}

		return $search_results;

	}

	/**
	 * Registers the function that looks for pages containing the search query.
	 *
	 * @since    1.1.0
	 * @param    array $registered_functions    The list of registered search functions.
	 */
	public function register_search_pages( $registered_functions ) {

		// Register the function.
		$registered_functions[] = array(
			'name'         => 'search_pages',
			'display_name' => esc_html__( 'Pages', 'smart-admin-search' ),
			'description'  => esc_html__( 'Search pages.', 'smart-admin-search' ),
		);

		return $registered_functions;

	}

	/**
	 * Looks for pages containing the search query.
	 *
	 * @since    1.1.0
	 * @param    array  $search_results    The global search results.
	 * @param    string $query             The search query.
	 */
	public function search_pages( $search_results, $query ) {

		if ( current_user_can( 'edit_pages' ) ) {
			$args = array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				's'              => $query,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'perm'           => 'editable',
			);

			$pages_query = new WP_Query( $args );
			$pages       = $pages_query->posts;
			wp_reset_postdata();

			foreach ( $pages as $page ) {
				// Skip this page if it's private and the user can't access private pages.
				if ( ! ( 'private' === $page->post_status && ! current_user_can( 'read_private_pages' ) ) ) {

					$text = ( ! empty( $page->post_title ) ) ? $page->post_title : esc_html__( '(no title)', 'smart-admin-search' );

					if ( 'publish' !== $page->post_status ) {
						$page_status = get_post_status_object( $page->post_status )->label;
						$text       .= ' (' . $page_status . ')';
					}

					$link_url = get_edit_post_link( $page->ID, '' );

					if ( empty( $link_url ) && 'draft' !== $page->post_status ) {
						$link_url = get_the_permalink( $page->ID );
					}

					$icon_class = 'dashicons-admin-page';
					$style      = '';

					// Add the item to search results.
					$search_results[] = array(
						'text'        => $text,
						'description' => esc_html__( 'Page.', 'smart-admin-search' ),
						'link_url'    => ( ! empty( $link_url ) ) ? $link_url : '',
						'icon_class'  => $icon_class,
						'style'       => $style,
					);
				}
			}
		}

		return $search_results;

	}

	/**
	 * Registers the function that looks for users containing the search query.
	 *
	 * @since    1.2.0
	 * @param    array $registered_functions    The list of registered search functions.
	 */
	public function register_search_users( $registered_functions ) {

		// Register the function.
		$registered_functions[] = array(
			'name'         => 'search_users',
			'display_name' => esc_html__( 'Users', 'smart-admin-search' ),
			'description'  => esc_html__( 'Search users.', 'smart-admin-search' ),
		);

		return $registered_functions;

	}

	/**
	 * Looks for users containing the search query.
	 *
	 * @since    1.2.0
	 * @param    array  $search_results    The global search results.
	 * @param    string $query             The search query.
	 */
	public function search_users( $search_results, $query ) {

		if ( current_user_can( 'edit_users' ) ) {
			$args = array(
				'search' => '*' . $query . '*',
			);

			$users_query = new WP_User_Query( $args );
			$users       = $users_query->get_results();

			foreach ( $users as $user ) {
				$text       = $user->data->display_name . ' (' . esc_html__( 'Username:', 'smart-admin-search' ) . ' ' . $user->data->user_login . ')';
				$link_url   = get_edit_user_link( $user->ID );
				$icon_class = 'dashicons-admin-users';
				$style      = '';

				// Add the item to search results.
				$search_results[] = array(
					'text'        => $text,
					'description' => esc_html__( 'User.', 'smart-admin-search' ),
					'link_url'    => ( ! empty( $link_url ) ) ? $link_url : '',
					'icon_class'  => $icon_class,
					'style'       => $style,
				);
			}
		}

		return $search_results;

	}

	/**
	 * Registers the function that looks for media containing the search query.
	 *
	 * @since    1.3.0
	 * @param    array $registered_functions    The list of registered search functions.
	 */
	public function register_search_media( $registered_functions ) {

		// Register the function.
		$registered_functions[] = array(
			'name'         => 'search_media',
			'display_name' => esc_html__( 'Media', 'smart-admin-search' ),
			'description'  => esc_html__( 'Search media.', 'smart-admin-search' ),
		);

		return $registered_functions;

	}

	/**
	 * Looks for media containing the search query.
	 *
	 * @since    1.3.0
	 * @param    array  $search_results    The global search results.
	 * @param    string $query             The search query.
	 */
	public function search_media( $search_results, $query ) {

		if ( current_user_can( 'upload_files' ) ) {
			$args = array(
				'post_type'      => 'attachment',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				's'              => $query,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'perm'           => 'editable',
			);

			$media_query = new WP_Query( $args );
			$media       = $media_query->posts;
			wp_reset_postdata();

			foreach ( $media as $media_item ) {
				$text       = ( ! empty( $media_item->post_title ) ) ? $media_item->post_title : esc_html__( '(no title)', 'smart-admin-search' );
				$link_url   = get_edit_post_link( $media_item->ID, '' );
				$icon_class = 'dashicons-admin-media';
				$style      = '';
				$image      = '<img src="' . wp_get_attachment_thumb_url( $media_item->ID ) . '" />';

				// Add the item to search results.
				$search_results[] = array(
					'text'        => $text,
					'description' => esc_html__( 'Media.', 'smart-admin-search' ),
					'link_url'    => ( ! empty( $link_url ) ) ? $link_url : '',
					'icon_class'  => $icon_class,
					'style'       => $style,
					'preview'     => $image,
				);
			}
		}

		return $search_results;

	}

	/**
	 * Registers the function that looks for custom post types containing the search query.
	 *
	 * @since    1.4.0
	 * @param    array $registered_functions    The list of registered search functions.
	 */
	public function register_search_cpt( $registered_functions ) {

		// Register the function.
		$registered_functions[] = array(
			'name'         => 'search_cpt',
			'display_name' => esc_html__( 'Custom post types', 'smart-admin-search' ),
			'description'  => esc_html__( 'Search custom post type content.', 'smart-admin-search' ),
		);

		return $registered_functions;

	}

	/**
	 * Looks for custom post types containing the search query.
	 *
	 * @since    1.4.0
	 * @param    array  $search_results    The global search results.
	 * @param    string $query             The search query.
	 */
	public function search_cpt( $search_results, $query ) {

		if ( current_user_can( 'edit_posts' ) ) {
			// Get all custom post types.
			$cpt_obj = get_post_types(
				array(
					'_builtin' => false,
					'public'   => true,
				),
				'objects',
				'and'
			);

			if ( ! empty( $cpt_obj ) ) {
				$cpt_names = array_keys( $cpt_obj );

				// Search custom post types posts.
				$args = array(
					'post_type'      => $cpt_names,
					'post_status'    => 'any',
					'posts_per_page' => -1,
					's'              => $query,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'perm'           => 'editable',
				);

				$posts_query = new WP_Query( $args );
				$posts       = $posts_query->posts;
				wp_reset_postdata();

				foreach ( $posts as $post ) {
					// Skip this post if it's private and the user can't access private posts.
					if ( ! ( 'private' === $post->post_status && ! current_user_can( 'read_private_posts' ) ) ) {

						$text = ( ! empty( $post->post_title ) ) ? $post->post_title : esc_html__( '(no title)', 'smart-admin-search' );

						if ( 'publish' !== $post->post_status ) {
							$post_status = get_post_status_object( $post->post_status )->label;
							$text       .= ' (' . $post_status . ')';
						}

						$link_url = get_edit_post_link( $post->ID, '' );

						if ( empty( $link_url ) && 'draft' !== $post->post_status ) {
							$link_url = get_the_permalink( $post->ID );
						}

						$cpt_icon   = $cpt_obj[ $post->post_type ]->menu_icon;
						$icon_class = ( ! empty( $cpt_icon ) ) ? $cpt_icon : 'dashicons-admin-post';
						$style      = '';

						// Add the item to search results.
						$search_results[] = array(
							'text'        => $text,
							'description' => $cpt_obj[ $post->post_type ]->labels->singular_name,
							'link_url'    => ( ! empty( $link_url ) ) ? $link_url : '',
							'icon_class'  => $icon_class,
							'style'       => $style,
						);
					}
				}
			}
		}

		return $search_results;

	}
}
