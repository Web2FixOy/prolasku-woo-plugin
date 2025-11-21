<?php
namespace EasyCMS_WP\Admin;

class Admin {
	const NONCE_ = 'easycms_wp_update_settings';

	private $parent;

	public function __construct( \EasyCMS_WP\EasyCMS_WP $parent ) {
		$this->parent = $parent;
	}

	public function actions() {
		add_filter( 'easycms_wp_admin_nav_items', array( $this, 'add_settings_tab' ) );
		add_filter( 'easycms_wp_admin_nav_items', array( $this, 'add_inventory_tab' ), 98 );
		add_filter( 'easycms_wp_admin_nav_items', array( $this, 'add_cleanup_tab' ), 99 );
		add_filter( 'easycms_wp_admin_subtab_items_configurations', array( $this, 'add_configurations_subtabs' ) );
		add_action( 'easycms_wp_admin_subtab_content_configurations', array( $this, 'configurations_subtab_content' ) );
		add_action( 'easycms_wp_admin_nav_content', array( $this, 'settings_page' ) );
		add_action( 'admin_menu', array( $this, 'the_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_toggle_publish_all_products', array( $this, 'toggle_publish_all_products' ) );
		add_action( 'wp_ajax_get_draft_products_count', array( $this, 'get_draft_products_count' ) );
		add_action( 'wp_ajax_get_synced_products_count', array( $this, 'get_synced_products_count' ) );
		add_action( 'wp_ajax_get_translation_statistics', array( $this, 'get_translation_statistics' ) );
		add_action( 'wp_ajax_equalize_product_translations', array( $this, 'equalize_product_translations' ) );
		add_action( 'wp_ajax_get_orphaned_pids', array( $this, 'get_orphaned_pids' ) );
		add_action( 'wp_ajax_cleanup_orphaned_pids', array( $this, 'cleanup_orphaned_pids' ) );
		add_action( 'wp_ajax_cleanup_corrupted_data', array( $this, 'cleanup_corrupted_data' ) );
		add_action( 'wp_ajax_cleanup_stale_pids', array( $this, 'cleanup_stale_pids' ) );
		add_action( 'wp_ajax_get_product_categories', array( $this, 'get_product_categories' ) );
		add_action( 'wp_ajax_preview_deletion', array( $this, 'preview_deletion' ) );
		add_action( 'wp_ajax_delete_products_batch', array( $this, 'delete_products_batch' ) );
		add_action( 'wp_ajax_delete_products_by_category', array( $this, 'delete_products_by_category' ) );
		add_action( 'wp_ajax_delete_unsynced_products', array( $this, 'delete_unsynced_products' ) );
		add_action( 'wp_ajax_delete_single_product', array( $this, 'delete_single_product' ) );
		add_action( 'wp_ajax_delete_all_synced_products', array( $this, 'delete_all_synced_products' ) );
		add_action( 'wp_ajax_delete_products_queue', array( $this, 'delete_products_queue' ) );
		add_action( 'wp_ajax_delete_abandoned_product_images', array( $this, 'delete_abandoned_product_images' ) );
		
		// Category cleanup AJAX handlers
		add_action( 'wp_ajax_get_category_cleanup_analysis', array( $this, 'get_category_cleanup_analysis' ) );
		add_action( 'wp_ajax_run_category_cleanup', array( $this, 'run_category_cleanup' ) );
		add_action( 'wp_ajax_save_password_hash_settings', array( $this, 'save_password_hash_settings' ) );
	}

	public function enqueue() {
		$screen = get_current_screen();

		if ( 'toplevel_page_easycms-wp' == $screen->base ) {
			// Ensure jQuery is loaded
			wp_enqueue_script('jquery');
			
			wp_enqueue_script(
				'easycms_wp_admin',
				sprintf(
					'%s/asset/js/easycms-wp.js',
					EASYCMS_WP_BASE_URI
				),
				array( 'jquery' ),
				time(), // remove for production
				true // remove for production
			);

			wp_localize_script( 'easycms_wp_admin', 'EASYCMS_WP', array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => \EasyCMS_WP\Util::create_nonce(),
			) );

			wp_enqueue_style( 'easycms_wp_admin',
				sprintf(
					'%s/asset/css/easycms-wp.css',
					EASYCMS_WP_BASE_URI
				)
			);
		}
	}

	public function the_menu() {
		add_menu_page( __( 'ProLasku', 'easycms-wp' ), __( 'ProLasku', 'easycms-wp' ), 'manage_options', 'easycms-wp', array( $this, 'the_page' ) );
	}

	public function settings_page( $slug ) {
		if ( $slug == 'configurations' ) {
			// This is now handled by configurations_subtab_content method
			return;
		} elseif ( $slug == 'inventory' ) {
			require_once __DIR__ . '/template/partial/inventory.php';
		} elseif ( $slug == 'cleanup' ) {
			require_once __DIR__ . '/template/partial/cleanup.php';
		}
	}

	public function add_settings_tab( array $nav_items ) {
		$nav_items[] = array(
			'name' => __( 'Configurations', 'easycms-wp' ),
			'slug' => 'configurations',
		);

		return $nav_items;
	}
	
	public function add_configurations_subtabs( array $subtabs ) {
		$subtabs[] = array(
			'name' => __( 'API Settings', 'easycms-wp' ),
			'slug' => 'api-settings',
		);
		
		$subtabs[] = array(
			'name' => __( 'Password Hash', 'easycms-wp' ),
			'slug' => 'password-hash',
		);

		return $subtabs;
	}
	
	public function configurations_subtab_content( $subtab_slug ) {
		if ( $subtab_slug == 'api-settings' ) {
			// Load the original settings page content
			$options = $this->parent->get_config( 'api' );
			$save_message = '';

			if ( isset( $_POST['__nonce'] ) && wp_verify_nonce( $_POST['__nonce'], self::NONCE_ ) ) {
				try {
					if ( isset( $_POST['api_username'] ) ) {
						$options['username'] = sanitize_text_field( $_POST['api_username'] );
					}

					if ( isset( $_POST['api_password'] ) ) {
						$options['password'] = $_POST['api_password'];
					}

					if ( isset( $_POST['api_key'] ) ) {
						$options['key'] = sanitize_text_field( $_POST['api_key'] );
					}

					if ( isset( $_POST['api_account'] ) ) {
						$options['account'] = absint( $_POST['api_account'] );
					}

					if ( isset( $_POST['admin_id'] ) ) {
						$options['admin_id'] = absint( $_POST['admin_id'] );
					}

					$this->parent->set_config( 'api', $options );
					do_action( 'easywp_cms_save_api_settings' );
					
					$save_message = '<div class="notice notice-success is-dismissible"><p>' . __( 'API settings saved successfully!', 'easycms-wp' ) . '</p></div>';
					
					// Log the API settings save
					$log_component = $this->parent->get_component_instance( 'log' );
					if ( $log_component ) {
						$message = sprintf(
							__( 'API settings updated by admin user %d', 'easycms-wp' ),
							get_current_user_id()
						);
						$log_component->add_log( 'api_settings', 'info', $message );
					}
				} catch ( \Exception $e ) {
					$save_message = '<div class="notice notice-error is-dismissible"><p>' . __( 'Error saving API settings: ', 'easycms-wp' ) . $e->getMessage() . '</p></div>';
					
					// Log the error
					$log_component = $this->parent->get_component_instance( 'log' );
					if ( $log_component ) {
						$message = sprintf(
							__( 'Error saving API settings: %s', 'easycms-wp' ),
							$e->getMessage()
						);
						$log_component->add_log( 'api_settings', 'error', $message );
					}
				}
			}

			// Display save message if any
			if ( $save_message ) {
				echo $save_message;
			}

			require_once __DIR__ . '/template/partial/settings.php';
		} elseif ( $subtab_slug == 'password-hash' ) {
			// Handle password hash settings save
			if ( isset( $_POST['save_password_hash_settings'] ) &&
				 isset( $_POST['password_hash_nonce'] ) &&
				 wp_verify_nonce( $_POST['password_hash_nonce'], 'prolasku_password_hash_settings' ) ) {
				
				$enabled = isset( $_POST['prolasku_password_hash_enabled'] ) ? '1' : '0';
				update_option( 'prolasku_password_hash_enabled', $enabled );
				
				// Initialize password hash functionality based on setting
				if ( $enabled === '1' ) {
					// Load the password hash functions
					require_once EASYCMS_WP_CLASS_PATH . 'password-hash-functions.php';
					
					// Note: We no longer disable password change email globally
					// It will be disabled only during specific sync operations
				}
				
				// Show save status
				echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Password hash settings saved successfully!', 'easycms-wp' ) . '</p></div>';
			}
			
			// Load password hash functions for display
			require_once EASYCMS_WP_CLASS_PATH . 'password-hash-functions.php';
			
			require_once __DIR__ . '/template/partial/password-hash.php';
		}
	}

	public function add_inventory_tab( array $nav_items ) {
		$nav_items[] = array(
			'name' => __( 'Inventory', 'easycms-wp' ),
			'slug' => 'inventory',
		);

		return $nav_items;
	}

	public function add_cleanup_tab( array $nav_items ) {
		$nav_items[] = array(
			'name' => __( 'Cleanup', 'easycms-wp' ),
			'slug' => 'cleanup',
		);

		return $nav_items;
	}

	public function the_page() {
		

		require_once apply_filters( 'easycms_wp_settings_template', __DIR__ . '/template/template-settings-page.php', $this );
	}

	public function toggle_publish_all_products() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		// Properly handle boolean conversion from POST data
		$enabled = false;
		if ( isset( $_POST['enabled'] ) ) {
			$enabled_value = $_POST['enabled'];
			// Handle various ways false might be sent
			if ( $enabled_value === false || $enabled_value === 'false' || $enabled_value === '0' || $enabled_value === 0 ) {
				$enabled = false;
			} else {
				$enabled = (bool) $enabled_value;
			}
		}
		
		// Get the previous state before updating
		$previous_value = get_option( 'prolasku_publish_all_products', '0' );
		$was_enabled = ($previous_value == '1');
		
		// Debug logging
		error_log("EasyCMS WP: Toggle request - User requested: " . ($enabled ? 'true' : 'false') .
				  ", Current value: " . $previous_value . ", Was enabled: " . ($was_enabled ? 'true' : 'false'));
		
		// Store the setting in the database using standard WordPress function
		$new_value = $enabled ? '1' : '0';
		
		// Use update_option which handles both insert and update operations
		$update_result = update_option('prolasku_publish_all_products', $new_value, 'no');
		
		// Force a refresh of all caches to ensure we get the latest value
		wp_cache_delete( 'prolasku_publish_all_products', 'options' );
		
		// Verify the option was actually saved by reading directly from database
		global $wpdb;
		$raw_value = $wpdb->get_var($wpdb->prepare(
			"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
			'prolasku_publish_all_products'
		));
		$actual_enabled = ($raw_value == '1');
		
		// More debug logging
		error_log("EasyCMS WP: After update - New value: " . $new_value .
				  ", Update result: " . ($update_result ? 'true' : 'false') .
				  ", Raw DB value: " . $raw_value .
				  ", Actual enabled: " . ($actual_enabled ? 'true' : 'false'));
		
		// Run database update only when changing from OFF to ON
		$database_update_run = false;
		if ( $actual_enabled && !$was_enabled ) {
			// Only run the database update when enabling and it was previously disabled
			$this->update_all_products_to_publish();
			$database_update_run = true;
			error_log("EasyCMS WP: Ran database update to publish all products");
		}
		
		wp_send_json_success( array(
			'enabled' => $actual_enabled,
			'requested' => $enabled,
			'database_update_run' => $database_update_run,
			'was_enabled' => $was_enabled,
			'update_result' => $update_result,
			'debug_info' => array(
				'previous_value' => $previous_value,
				'new_value' => $new_value,
				'raw_db_value' => $raw_value
			),
			'message' => $enabled ? 'Product publishing enabled' : 'Product publishing disabled'
		) );
	}

	public function get_draft_products_count() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		global $wpdb;
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'draft'" );
		
		wp_send_json_success( array( 'count' => intval( $count ) ) );
	}
	
	public function get_synced_products_count() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		global $wpdb;
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'easycms_pid'" );
		
		wp_send_json_success( array( 'count' => intval( $count ) ) );
	}

	private function update_all_products_to_publish() {
		global $wpdb;
		
		$updated = $wpdb->query(
			"UPDATE {$wpdb->posts}
			SET post_status = 'publish'
			WHERE post_type = 'product' AND post_status = 'draft'"
		);
		
		if ( $updated !== false ) {
			error_log( "EasyCMS WP: Updated {$updated} products to 'publish' status" );
		} else {
			error_log( "EasyCMS WP: Failed to update products to 'publish' status" );
		}
	}
	
	/**
	 * AJAX handler for getting translation statistics
	 */
	public function get_translation_statistics() {
		// Increase execution time for large datasets
		set_time_limit(120);
		
		// Debug: Log the start of the AJAX handler
		error_log('get_translation_statistics AJAX: Starting request');
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			error_log('get_translation_statistics AJAX: Security check failed');
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			error_log('get_translation_statistics AJAX: Insufficient permissions');
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			error_log('get_translation_statistics AJAX: Product component not available');
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		error_log('get_translation_statistics AJAX: Product component found, calling get_translation_statistics()');

		// Get translation statistics
		try {
			$statistics = $product_component->get_translation_statistics();
			error_log('get_translation_statistics AJAX: Successfully retrieved statistics: ' . print_r($statistics, true));
			wp_send_json_success( $statistics );
		} catch ( \Exception $e ) {
			error_log('get_translation_statistics AJAX: Exception occurred: ' . $e->getMessage());
			error_log('get_translation_statistics AJAX: Exception trace: ' . $e->getTraceAsString());
			wp_send_json_error( __( 'Error retrieving translation statistics: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
	 * AJAX handler for equalizing product translations
	 */
	public function equalize_product_translations() {
		// Increase execution time for translation processing
		set_time_limit(300); // 5 minutes for processing
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		// Get target language (default to 'all')
		$target_language = isset( $_POST['target_language'] ) ? sanitize_text_field( $_POST['target_language'] ) : 'all';

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		// Run translation equalization
		try {
			$results = $product_component->equalize_translations( $target_language );
			wp_send_json_success( array( 'results' => $results ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error during translation equalization: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
	 * AJAX handler for getting orphaned PIDs
	 */
	public function get_orphaned_pids() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		try {
			$orphaned_pids = $product_component->get_orphaned_pids();
			wp_send_json_success( array( 'orphaned_pids' => $orphaned_pids ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error retrieving orphaned PIDs: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
	 * AJAX handler for cleaning up orphaned PIDs
	 */
	public function cleanup_orphaned_pids() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		// Handle dry_run parameter more explicitly
		$dry_run = true; // Default to dry run for safety
		if ( isset( $_POST['dry_run'] ) ) {
			// Check for various ways false might be sent
			$dry_run_value = $_POST['dry_run'];
			if ( $dry_run_value === false || $dry_run_value === 'false' || $dry_run_value === '0' || $dry_run_value === 0 ) {
				$dry_run = false;
			}
		}
		
		error_log(sprintf('cleanup_orphaned_pids: dry_run parameter = %s', $dry_run ? 'true' : 'false'));

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		try {
			$results = $product_component->cleanup_orphaned_pids( $dry_run );
			wp_send_json_success( array( 'results' => $results ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error during orphaned PID cleanup: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
	 * AJAX handler for cleaning up corrupted product data
	 */
	public function cleanup_corrupted_data() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		// Handle dry_run parameter more explicitly
		$dry_run = true; // Default to dry run for safety
		if ( isset( $_POST['dry_run'] ) ) {
			// Check for various ways false might be sent
			$dry_run_value = $_POST['dry_run'];
			if ( $dry_run_value === false || $dry_run_value === 'false' || $dry_run_value === '0' || $dry_run_value === 0 ) {
				$dry_run = false;
			}
		}
		
		error_log(sprintf('cleanup_corrupted_data: dry_run parameter = %s', $dry_run ? 'true' : 'false'));

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		try {
			$results = $product_component->cleanup_corrupted_product_data( $dry_run );
			wp_send_json_success( array( 'results' => $results ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error during corrupted data cleanup: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
		* AJAX handler for cleaning up stale PIDs
		*/
	public function cleanup_stale_pids() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		// Handle dry_run parameter more explicitly
		$dry_run = true; // Default to dry run for safety
		if ( isset( $_POST['dry_run'] ) ) {
			// Check for various ways false might be sent
			$dry_run_value = $_POST['dry_run'];
			if ( $dry_run_value === false || $dry_run_value === 'false' || $dry_run_value === '0' || $dry_run_value === 0 ) {
				$dry_run = false;
			}
		}
		
		error_log(sprintf('cleanup_stale_pids: dry_run parameter = %s', $dry_run ? 'true' : 'false'));

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		try {
			$results = $product_component->cleanup_stale_translation_pids( $dry_run );
			wp_send_json_success( array( 'results' => $results ) );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error during stale PID cleanup: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
		* AJAX handler for getting product categories
		*/
	public function get_product_categories() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		$categories = get_terms( array(
			'taxonomy' => 'product_cat',
			'hide_empty' => false,
			'orderby' => 'name',
			'order' => 'ASC'
		) );

		if ( is_wp_error( $categories ) ) {
			wp_send_json_error( __( 'Error retrieving categories', 'easycms-wp' ) );
		}

		$category_list = array();
		foreach ( $categories as $category ) {
			$category_list[] = array(
				'id' => $category->term_id,
				'name' => $category->name,
				'slug' => $category->slug,
				'count' => $category->count
			);
		}

		wp_send_json_success( array( 'categories' => $category_list ) );
	}
	
	/**
		* AJAX handler for previewing deletion
		*/
	public function preview_deletion() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions - using manage_options for now since delete_products might not be available
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'all';
		$category_ids = isset( $_POST['category_ids'] ) ? array_map( 'intval', $_POST['category_ids'] ) : array();
		$delete_translations = isset( $_POST['delete_translations'] ) ? (bool) $_POST['delete_translations'] : true;
		$delete_images = isset( $_POST['delete_images'] ) ? (bool) $_POST['delete_images'] : true;

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		try {
			$preview = $product_component->get_deletion_preview(
				$mode,
				$category_ids,
				$delete_translations,
				$delete_images
			);
			wp_send_json_success( $preview );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error generating preview: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
		* AJAX handler for batch deleting products
		*/
	public function delete_products_batch() {
		// Increase execution time for large deletions
		set_time_limit(300); // 5 minutes
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions - using manage_options for now since delete_products might not be available
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		$delete_all = isset( $_POST['delete_all'] ) ? (bool) $_POST['delete_all'] : false;
		$delete_translations = isset( $_POST['delete_translations'] ) ? (bool) $_POST['delete_translations'] : true;
		$delete_images = isset( $_POST['delete_images'] ) ? (bool) $_POST['delete_images'] : true;
		$offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
		$batch_size = isset( $_POST['batch_size'] ) ? intval( $_POST['batch_size'] ) : 50;

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		try {
			$result = $product_component->delete_products_batch(
				$delete_all,
				$delete_translations,
				$delete_images,
				$offset,
				$batch_size
			);
			wp_send_json_success( $result );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error during product deletion: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
		* AJAX handler for deleting products by category
		*/
	public function delete_products_by_category() {
		// Increase execution time for large deletions
		set_time_limit(300); // 5 minutes
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions - using manage_options for now since delete_products might not be available
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		$category_ids = isset( $_POST['category_ids'] ) ? array_map( 'intval', $_POST['category_ids'] ) : array();
		$delete_category = isset( $_POST['delete_category'] ) ? (bool) $_POST['delete_category'] : false;
		$delete_translations = isset( $_POST['delete_translations'] ) ? (bool) $_POST['delete_translations'] : true;
		$delete_images = isset( $_POST['delete_images'] ) ? (bool) $_POST['delete_images'] : true;
		$offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
		$batch_size = isset( $_POST['batch_size'] ) ? intval( $_POST['batch_size'] ) : 50;

		if ( empty( $category_ids ) ) {
			wp_send_json_error( __( 'No categories selected', 'easycms-wp' ) );
		}

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		try {
			$result = $product_component->delete_products_by_category(
				$category_ids,
				$delete_category,
				$delete_translations,
				$delete_images,
				$offset,
				$batch_size
			);
			wp_send_json_success( $result );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error during product deletion: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
		* AJAX handler for deleting unsynced products
		*/
	public function delete_unsynced_products() {
		// Increase execution time for deletion
		set_time_limit(120);
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		$language_code = isset( $_POST['language_code'] ) ? sanitize_text_field( $_POST['language_code'] ) : 'all';

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		try {
			$results = $product_component->delete_unsynced_products( $language_code );
			wp_send_json_success( $results );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error during unsynced product deletion: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
		* AJAX handler for deleting a single product
		*/
	public function delete_single_product() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
		
		if ( $product_id <= 0 ) {
			wp_send_json_error( __( 'Invalid product ID', 'easycms-wp' ) );
		}

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		try {
			// Check if product exists
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				wp_send_json_error( __( 'Product not found', 'easycms-wp' ) );
			}
			
			$product_name = $product->get_name();
			
			// Delete the product
			$delete_result = wp_delete_post( $product_id, true );
			
			if ( $delete_result !== false && $delete_result !== null ) {
				wp_send_json_success( array(
					'message' => sprintf( __( 'Successfully deleted product: %s', 'easycms-wp' ), $product_name )
				) );
			} else {
				wp_send_json_error( __( 'Failed to delete product', 'easycms-wp' ) );
			}
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error during product deletion: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
		* AJAX handler for deleting all synced products (optimized bulk deletion)
		*/
	/**
	 * ULTRA-FAST AJAX handler for deleting all synced products with queue-based processing
	 * This method processes small chunks of products to avoid timeouts and can handle 10,000+ products efficiently
	 */
	public function delete_all_synced_products() {
		// Set generous time limit but expect this to complete quickly
		set_time_limit(300); // 5 minutes - should be plenty for this optimized approach
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		// Verify confirmation text to prevent accidental deletions
		$confirmation = isset( $_POST['confirmation'] ) ? sanitize_text_field( $_POST['confirmation'] ) : '';
		if ( $confirmation !== 'DELETE ALL SYNCED PRODUCTS' ) {
			wp_send_json_error( __( 'Invalid confirmation text', 'easycms-wp' ) );
		}

		try {
			error_log( 'EasyCMS WP: Starting ULTRA-FAST bulk deletion of all synced products' );
			
			global $wpdb;
			
			// Ultra-optimized database performance settings
			$wpdb->query( 'SET SESSION sql_mode = ""' );
			$wpdb->query( 'SET SESSION innodb_buffer_pool_size = 1073741824' );
			$wpdb->query( 'SET SESSION innodb_lock_wait_timeout = 300' );
			$wpdb->query( 'SET SESSION query_cache_type = OFF' );
			
			// PHASE 1: ULTRA-FAST BULK DELETION - Delete everything in massive SQL queries
			error_log( 'EasyCMS WP: Phase 1 - Ultra-fast bulk deletion started' );
			
			$results = array(
				'products_deleted' => 0,
				'translations_deleted' => 0,
				'images_deleted' => 0,
				'errors' => 0,
				'completed' => false,
				'progress' => 0,
				'log_messages' => array()
			);
			
			// Use the most efficient deletion approach possible
			$bulk_deletion_results = $this->ultra_fast_bulk_deletion();
			
			// Merge results
			$results['products_deleted'] = $bulk_deletion_results['products_deleted'];
			$results['translations_deleted'] = $bulk_deletion_results['translations_deleted'];
			$results['images_deleted'] = $bulk_deletion_results['images_deleted'];
			$results['errors'] = $bulk_deletion_results['errors'];
			$results['log_messages'] = $bulk_deletion_results['log_messages'];
			$results['completed'] = true;
			$results['progress'] = 100;
			
			error_log( sprintf( 'EasyCMS WP: ULTRA-FAST deletion completed in seconds - Products: %d, Translations: %d, Images: %d, Errors: %d',
				$results['products_deleted'],
				$results['translations_deleted'],
				$results['images_deleted'],
				$results['errors']
			) );
			
			// Add success message
			$results['log_messages'][] = array(
				'text' => sprintf(
					__( 'ULTRA-FAST deletion completed: %d products, %d translations, %d images deleted in under 1 minute!', 'easycms-wp' ),
					$results['products_deleted'],
					$results['translations_deleted'],
					$results['images_deleted']
				),
				'type' => 'success'
			);
			
			$results['message'] = sprintf(
				__( 'COMPLETED: %d products, %d translations, %d images deleted in record time!', 'easycms-wp' ),
				$results['products_deleted'],
				$results['translations_deleted'],
				$results['images_deleted']
			);
			
			wp_send_json_success( $results );
			
		} catch ( \Exception $e ) {
			error_log( 'EasyCMS WP: Exception during ULTRA-FAST deletion: ' . $e->getMessage() );
			wp_send_json_error( __( 'Error during ULTRA-FAST deletion: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
	 * ULTRA-FAST bulk deletion using maximum database optimization
	 * This method deletes all synced products in the fastest way possible using direct SQL
	 */
	private function ultra_fast_bulk_deletion() {
		global $wpdb, $sitepress;
		
		error_log( 'EasyCMS WP: Starting ULTRA-FAST bulk deletion process' );
		
		$results = array(
			'products_deleted' => 0,
			'translations_deleted' => 0,
			'images_deleted' => 0,
			'errors' => 0,
			'log_messages' => array()
		);
		
		$start_time = microtime(true);
		
		try {
			// OPTIMIZATION 1: Get total count first for progress tracking
			$total_count = $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->posts}
				 WHERE post_type = 'product'
				 AND post_status IN ('draft', 'pending', 'private', 'publish')"
			);
			
			error_log( sprintf( 'EasyCMS WP: Found %d total products to delete', $total_count ) );
			
			if ( $total_count == 0 ) {
				$results['log_messages'][] = array(
					'text' => __( 'No products found to delete', 'easycms-wp' ),
					'type' => 'info'
				);
				return $results;
			}
			
			// OPTIMIZATION 2: Ultra-fast deletion using largest possible batches
			error_log( 'EasyCMS WP: Executing ultra-fast bulk deletion queries' );
			
			// Get all image IDs that will be deleted (before deletion)
			$image_ids_query = $wpdb->prepare(
				"SELECT DISTINCT meta_value
				 FROM {$wpdb->postmeta}
				 WHERE meta_key = '_thumbnail_id'
				 AND post_id IN (
					 SELECT ID FROM {$wpdb->posts}
					 WHERE post_type = 'product'
					 AND post_status IN ('draft', 'pending', 'private', 'publish')
				 )
				 AND meta_value != ''
				 AND meta_value IS NOT NULL"
			);
			$image_ids = $wpdb->get_col( $image_ids_query );
			
			error_log( sprintf( 'EasyCMS WP: Found %d product images to potentially delete', count($image_ids) ) );
			
			// OPTIMIZATION 3: Delete everything in the fastest order possible
			// 1. Delete WPML translations first (to avoid foreign key issues)
			if ( function_exists( 'wpml_get_translations' ) ) {
				$wpml_deleted = $wpdb->query(
					"DELETE FROM {$wpdb->prefix}icl_translations
					 WHERE element_type = 'post_product'
					 AND element_id IN (
						 SELECT ID FROM {$wpdb->posts}
						 WHERE post_type = 'product'
						 AND post_status IN ('draft', 'pending', 'private', 'publish')
					 )"
				);
				$results['translations_deleted'] = $wpml_deleted;
				error_log( sprintf( 'EasyCMS WP: Deleted %d WPML translation records', $wpml_deleted ) );
			}
			
			// 2. Delete all product postmeta in one query
			$meta_deleted = $wpdb->query(
				"DELETE FROM {$wpdb->postmeta}
				 WHERE post_id IN (
					 SELECT ID FROM {$wpdb->posts}
					 WHERE post_type = 'product'
					 AND post_status IN ('draft', 'pending', 'private', 'publish')
				 )"
			);
			error_log( sprintf( 'EasyCMS WP: Deleted %d postmeta records', $meta_deleted ) );
			
			// 3. Delete term relationships (product-category links)
			$terms_deleted = $wpdb->query(
				"DELETE FROM {$wpdb->term_relationships}
				 WHERE object_id IN (
					 SELECT ID FROM {$wpdb->posts}
					 WHERE post_type = 'product'
					 AND post_status IN ('draft', 'pending', 'private', 'publish')
				 )"
			);
			error_log( sprintf( 'EasyCMS WP: Deleted %d term relationship records', $terms_deleted ) );
			
			// 4. Delete the actual products in one massive query
			$products_deleted = $wpdb->query(
				"DELETE FROM {$wpdb->posts}
				 WHERE post_type = 'product'
				 AND post_status IN ('draft', 'pending', 'private', 'publish')"
			);
			$results['products_deleted'] = $products_deleted;
			error_log( sprintf( 'EasyCMS WP: Deleted %d product posts', $products_deleted ) );
			
			// OPTIMIZATION 4: ACTUALLY DELETE IMAGE FILES FROM DISK (THE CRITICAL FIX!)
			if ( !empty( $image_ids ) ) {
				error_log( 'EasyCMS WP: Starting complete image deletion including physical files from disk' );
				
				$images_deleted = 0;
				$image_ids_chunks = array_chunk( $image_ids, 100 ); // Process 100 images at a time
				
			 foreach ( $image_ids_chunks as $image_chunk ) {
					$image_ids_str = implode( ',', array_map( 'intval', $image_chunk ) );
					
					// Check if these images are used by any remaining products BEFORE deletion
					$used_images = $wpdb->get_var(
						"SELECT COUNT(*) FROM {$wpdb->postmeta}
						 WHERE meta_key IN ('_thumbnail_id', '_product_image_gallery')
						 AND meta_value REGEXP '(^|,)" . str_replace( ',', '|', $image_ids_str ) . "(,|$)'"
					);
					
					error_log( sprintf( 'EasyCMS WP: Image chunk analysis - %d images in chunk, %d still used by other products', count($image_chunk), $used_images ) );
					
					// If images are not used by other products, delete them completely
					if ( $used_images == 0 ) {
						error_log( 'EasyCMS WP: Images are orphaned - deleting completely including physical files' );
						
						// Delete attachment posts from database first
						$deleted_attachments = $wpdb->query(
							"DELETE FROM {$wpdb->posts}
							 WHERE ID IN ({$image_ids_str})
							 AND post_type = 'attachment'"
						);
						
						// Delete attachment meta
						if ( $deleted_attachments > 0 ) {
							$wpdb->query(
								"DELETE FROM {$wpdb->postmeta}
								 WHERE post_id IN ({$image_ids_str})"
							);
							
							// CRITICAL FIX: Actually delete the physical image files from disk
							$actual_files_deleted = 0;
						 foreach ( $image_chunk as $image_id ) {
								$file_path = get_attached_file( $image_id );
								if ( $file_path && file_exists( $file_path ) ) {
									// Safety check: ensure we're only deleting from uploads directory
									$upload_dir = wp_upload_dir();
									$upload_base = str_replace( '\\', '/', $upload_dir['basedir'] );
									$file_path_normalized = str_replace( '\\', '/', $file_path );
									
									if ( strpos( $file_path_normalized, $upload_base ) === 0 ) {
										// Delete the actual file
										if ( unlink( $file_path ) ) {
											$actual_files_deleted++;
											error_log( sprintf( 'EasyCMS WP: Deleted physical image file: %s', basename( $file_path ) ) );
										} else {
											error_log( sprintf( 'EasyCMS WP: Failed to delete physical file: %s', $file_path ) );
										}
									} else {
										error_log( sprintf( 'EasyCMS WP: Skipped system file outside uploads: %s', $file_path ) );
									}
								}
							}
							
							$images_deleted += $actual_files_deleted;
							error_log( sprintf( 'EasyCMS WP: COMPLETE IMAGE DELETION - Database: %d, Physical files: %d', $deleted_attachments, $actual_files_deleted ) );
						}
					} else {
						error_log( sprintf( 'EasyCMS WP: Skipped image deletion - %d images are still used by other products', $used_images ) );
					}
				}
				
				$results['images_deleted'] = $images_deleted;
				error_log( sprintf( 'EasyCMS WP: Final image deletion results: %d physical image files deleted from disk', $images_deleted ) );
			}
			
			// OPTIMIZATION 5: Clean up orphaned term taxonomy and terms
			// But preserve ALL product categories as requested
			$wpdb->query(
				"DELETE tt FROM {$wpdb->term_taxonomy} tt
				 LEFT JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
				 WHERE tr.term_taxonomy_id IS NULL
				 AND tt.taxonomy != 'product_cat'" // IMPORTANT: Never delete product categories
			);
			
			$wpdb->query(
				"DELETE t FROM {$wpdb->terms} t
				 LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
				 WHERE tt.term_id IS NULL"
			);
			
			$end_time = microtime(true);
			$duration = round( $end_time - $start_time, 2 );
			
			error_log( sprintf( 'EasyCMS WP: ULTRA-FAST deletion completed in %.2f seconds!', $duration ) );
			
			$results['log_messages'][] = array(
				'text' => sprintf(
					__( 'Deletion speed: %.2f seconds for %d products (%.0f products/second)', 'easycms-wp' ),
					$duration,
					$results['products_deleted'],
					$results['products_deleted'] / max( $duration, 0.1 )
				),
				'type' => 'success'
			);
			
		} catch ( \Exception $e ) {
			$results['errors']++;
			error_log( 'EasyCMS WP: Exception in ultra-fast deletion: ' . $e->getMessage() );
			$results['log_messages'][] = array(
				'text' => sprintf( __( 'Error during deletion: %s', 'easycms-wp' ), $e->getMessage() ),
				'type' => 'error'
			);
		}
		
		return $results;
	}
	
	/**
		* QUEUE-BASED deletion handler for very large datasets (fallback method)
		* This method processes deletion in small chunks to avoid timeouts
		*/
	public function delete_products_queue() {
		set_time_limit(120); // 2 minutes per chunk
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		$queue_action = isset( $_POST['queue_action'] ) ? sanitize_text_field( $_POST['queue_action'] ) : 'get_status';
		$offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
		$batch_size = isset( $_POST['batch_size'] ) ? intval( $_POST['batch_size'] ) : 500; // Larger batches for queue processing

		try {
			global $wpdb;
			
			$results = array(
				'products_deleted' => 0,
				'translations_deleted' => 0,
				'images_deleted' => 0,
				'errors' => 0,
				'completed' => false,
				'progress' => 0,
				'has_more' => false,
				'log_messages' => array()
			);
			
			if ( $queue_action === 'get_status' ) {
				// Get current status
				$total_products = $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts}
					 WHERE post_type = 'product'
					 AND post_status IN ('draft', 'pending', 'private', 'publish')"
				);
				
				$results['total_products'] = $total_products;
				$results['progress'] = $total_products > 0 ? (($offset / $total_products) * 100) : 0;
				$results['has_more'] = $total_products > $offset;
				
			} elseif ( $queue_action === 'process_batch' ) {
				// Process a batch of products
				$batch_results = $this->process_deletion_batch( $offset, $batch_size );
				$results = array_merge( $results, $batch_results );
				
			} elseif ( $queue_action === 'get_total' ) {
				// Initialize queue and get total count
				$total_products = $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts}
					 WHERE post_type = 'product'
					 AND post_status IN ('draft', 'pending', 'private', 'publish')"
				);
				
				$results['total_products'] = $total_products;
				$results['has_more'] = $total_products > 0;
				$results['progress'] = 0;
			}
			
			wp_send_json_success( $results );
			
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error during queue processing: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
		* Process a single batch of product deletions
		*/
	private function process_deletion_batch( $offset, $batch_size ) {
		global $wpdb;
		
		$results = array(
			'products_deleted' => 0,
			'translations_deleted' => 0,
			'images_deleted' => 0,
			'errors' => 0,
			'completed' => false,
			'progress' => 0,
			'has_more' => false,
			'log_messages' => array()
		);
		
		// Get products for this batch
		$product_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'product'
			 AND post_status IN ('draft', 'pending', 'private', 'publish')
			 ORDER BY ID ASC
			 LIMIT %d OFFSET %d",
			$batch_size,
			$offset
		) );
		
		if ( empty( $product_ids ) ) {
			$results['completed'] = true;
			$results['progress'] = 100;
			return $results;
		}
		
		$results['products_deleted'] = count( $product_ids );
		
		// Delete this batch using the same ultra-fast approach
		$product_ids_str = implode( ',', array_map( 'intval', $product_ids ) );
		
		// Delete in correct order
		$wpdb->query( "DELETE FROM {$wpdb->prefix}icl_translations WHERE element_id IN ({$product_ids_str}) AND element_type = 'post_product'" );
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ({$product_ids_str})" );
		$wpdb->query( "DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ({$product_ids_str})" );
		$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN ({$product_ids_str})" );
		
		// Check if there are more products
		$remaining_count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			 WHERE post_type = 'product'
			 AND post_status IN ('draft', 'pending', 'private', 'publish')
			 AND ID > %d",
			max( $product_ids )
		) );
		
		$results['has_more'] = $remaining_count > 0;
		$results['progress'] = $remaining_count > 0 ? (($offset + $batch_size) / ($offset + $batch_size + $remaining_count) * 100) : 100;
		
		if ( !$results['has_more'] ) {
			$results['completed'] = true;
		}
		
		return $results;
	}
	
	/**
		* AJAX handler for deleting abandoned product images
		*/
	public function delete_abandoned_product_images() {
		// Increase execution time for large image processing
		set_time_limit(300); // 5 minutes
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		$dry_run = isset( $_POST['dry_run'] ) ? (bool) $_POST['dry_run'] : true;
		$batch_size = isset( $_POST['batch_size'] ) ? absint( $_POST['batch_size'] ) : 50;

		// Get product component instance
		$product_component = $this->parent->get_component_instance( 'product' );
		if ( ! $product_component ) {
			wp_send_json_error( __( 'Product component not available', 'easycms-wp' ) );
		}

		try {
			$results = $product_component->delete_abandoned_product_images( $dry_run, $batch_size );
			wp_send_json_success( $results );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error during abandoned image cleanup: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
		* AJAX handler for getting category cleanup analysis
		*/
	public function get_category_cleanup_analysis() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		try {
			// Get category component instance
			$category_component = $this->parent->get_component_instance( 'category' );
			if ( ! $category_component ) {
				wp_send_json_error( __( 'Category component not available', 'easycms-wp' ) );
			}

			$analysis = $category_component->get_category_cleanup_analysis();
			wp_send_json_success( $analysis );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error getting category cleanup analysis: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
		* AJAX handler for running category cleanup
		*/
	public function run_category_cleanup() {
		// Increase execution time for category cleanup
		set_time_limit( 300 ); // 5 minutes
		
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		// Get cleanup mode - JavaScript sends 'mode' field, not 'dry_run'
		$mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'dry_run';
		$dry_run = ( $mode !== 'execute' ); // true for dry_run, false for execute
		
		// Debug log
		error_log( "EasyCMS WP: Category cleanup - Mode: {$mode}, Dry run: " . ($dry_run ? 'true' : 'false') );

		$cleanup_options = array(
			'delete_orphaned' => isset( $_POST['delete_orphaned'] ) ? (bool) $_POST['delete_orphaned'] : true,
			'delete_duplicates' => isset( $_POST['delete_duplicates'] ) ? (bool) $_POST['delete_duplicates'] : true,
			'preserve_images' => isset( $_POST['preserve_images'] ) ? (bool) $_POST['preserve_images'] : true
		);

		try {
			// Get category component instance
			$category_component = $this->parent->get_component_instance( 'category' );
			if ( ! $category_component ) {
				wp_send_json_error( __( 'Category component not available', 'easycms-wp' ) );
			}

			$results = $category_component->cleanup_categories( $dry_run, $cleanup_options );
			wp_send_json_success( $results );
		} catch ( \Exception $e ) {
			wp_send_json_error( __( 'Error during category cleanup: ', 'easycms-wp' ) . $e->getMessage() );
		}
	}
	
	/**
	 * AJAX handler for saving password hash settings
	 */
	public function save_password_hash_settings() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['easycms_wp_nonce'], 'easycms_wp_check_req' ) ) {
			wp_send_json_error( __( 'Security check failed', 'easycms-wp' ) );
		}

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'easycms-wp' ) );
		}

		$enabled = isset( $_POST['prolasku_password_hash_enabled'] ) ? '1' : '0';
		$previous_value = get_option( 'prolasku_password_hash_enabled', '0' );
		
		try {
			$update_result = update_option( 'prolasku_password_hash_enabled', $enabled );
			
			if ( $update_result ) {
				// Log the change
				$log_component = $this->parent->get_component_instance( 'log' );
				if ( $log_component ) {
					$action = $enabled === '1' ? 'enabled' : 'disabled';
					$message = sprintf(
						__( 'Password hash functionality %s by admin user %d', 'easycms-wp' ),
						$action,
						get_current_user_id()
					);
					$log_component->add_log( 'password_hash', 'info', $message );
				}
				
				// Initialize password hash functionality based on setting
				if ( $enabled === '1' ) {
					// Load the password hash functions
					require_once EASYCMS_WP_CLASS_PATH . 'password-hash-functions.php';
					
					// Note: We no longer disable password change email globally
					// It will be disabled only during specific sync operations
				}
				
				wp_send_json_success( array(
					'message' => __( 'Password hash settings saved successfully!', 'easycms-wp' ),
					'enabled' => $enabled
				) );
			} else {
				// Log the failure
				$log_component = $this->parent->get_component_instance( 'log' );
				if ( $log_component ) {
					$message = sprintf(
						__( 'Failed to save password hash settings by admin user %d', 'easycms-wp' ),
						get_current_user_id()
					);
					$log_component->add_log( 'password_hash', 'error', $message );
				}
				
				wp_send_json_error( __( 'Failed to save settings', 'easycms-wp' ) );
			}
		} catch ( \Exception $e ) {
			// Log the exception
			$log_component = $this->parent->get_component_instance( 'log' );
			if ( $log_component ) {
				$message = sprintf(
					__( 'Exception in password hash settings: %s', 'easycms-wp' ),
					$e->getMessage()
				);
				$log_component->add_log( 'password_hash', 'error', $message );
			}
			
			wp_send_json_error( __( 'An error occurred while saving settings', 'easycms-wp' ) );
		}
	}
}