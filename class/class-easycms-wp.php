<?php
namespace EasyCMS_WP;

final class EasyCMS_WP {
	protected $admin_instance;
	protected $ajax;
	private $config;
	private $components = array();

	public function __construct() {
		$this->_load_libraries();
		$this->_load_config();

		$this->_actions();
	}

	private function _actions() {
		$this->admin_instance->actions();

		add_action( EASYCMS_WP_CRON_HOOK, array( $this, 'perform_cron' ) );
		add_action( 'init', array( $this, 'register_stock_type' ) );
		add_action( 'init', array( $this, 'load_components' ), 100 );
		add_action('init', array($this, 'schedule_check_sync_status'));
		

		// Hooks defined in admin/template/template-settings-page.php
		add_filter( 'easycms_wp_admin_nav_items', array( '\EasyCMS_WP\Log', 'add_config_nav' ) );
		add_action( 'easycms_wp_admin_nav_content', array( '\EasyCMS_WP\Log', 'logs_page' ) );

		add_filter( 'easycms_wp_admin_nav_items', array( $this, 'add_component_config_nav' ) );
		add_action( 'easycms_wp_admin_nav_content', array( $this, 'components_page' ) );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_ajax_easycms_wp_get_logs', array( $this->ajax, 'get_logs' ) );
			add_action( 'wp_ajax_easycms_wp_clear_logs', array( $this->ajax, 'clear_logs' ) );
			add_action( 'wp_ajax_easycms_wp_run_component', array( $this->ajax, 'run_component' ) );
			add_action( 'wp_ajax_easycms_wp_reset_sync_status', array( $this->ajax, 'reset_sync_status' ) );
			add_action( 'wp_ajax_easycms_wp_delete_all', array( $this->ajax, 'delete_all' ) );
		}
	}

	private function _load_libraries() {
		require_once 'class-util.php';
		require_once 'class-log.php';
		require_once 'class-ajax.php';
		require_once 'admin/class-admin.php';

		require_once sprintf( '%sclass-component.php', EASYCMS_WP_TEMPLATE_PATH );

		$this->admin_instance = new Admin\Admin( $this );
		$this->ajax = new Ajax( $this );
	}
	### NEW 2023 check function
	public function check_sync_status() {
	    // Get the current sync status
	    $sync_status = get_option( $this->get_sync_option_name(), false );
	    if( $sync_status ) {
	        // Check how long the sync has been in progress
	        $start_time = get_option( 'easycms_wp_sync_start_time', false );
	        if( $start_time ) {
	            $current_time = time();
	            $time_elapsed = $current_time - $start_time;
	            if( $time_elapsed >= 43200 ) { // 43200 seconds = 12 hours
	                // Reset the sync status and re-enable the sync button
	                update_option( $this->get_sync_option_name(), false );
	                update_option( 'easycms_wp_sync_start_time', false );
	                // Check if email is set up correctly
	                if( get_option( 'admin_email' ) ) {
	                    // Send a notification to the user
	                    wp_mail( get_option( 'admin_email' ), 'Sync Reset', 'The sync "'.$this->get_sync_option_name().'" has been reset due to a stuck status. The sync started at: '.date('Y-m-d H:i:s', $start_time).' and reset at: '.date('Y-m-d H:i:s', time()).' Please start the sync again.' );
	                }
	                $this->log(
	                    sprintf(
	                        __( 'The sync %s has been reset due to a stuck status. The sync started at: %s and reset at: %s', 'easycms-wp' ),
	                        $this->get_sync_option_name(),
	                        date('Y-m-d H:i:s', $start_time),
	                        date('Y-m-d H:i:s', time())
	                    ),
	                    'info'
	                );
	            }
	        }
	    }
	}

	### NEW 2023 check function
	public function schedule_check_sync_status() {
	    if (!wp_next_scheduled('check_sync_status')) {
	        // wp_schedule_event(time(), 'three_hours', 'check_sync_status');
	        wp_schedule_event(time(), 'hourly', 'check_sync_status');
	    }
	}
	public function load_components() {
		$component_files = glob( sprintf( '%sclass-*-component.php', EASYCMS_WP_COMPONENT_PATH ) );
		$component_files = apply_filters( 'easycms_wp_component_files', $component_files );

		if ( $component_files ) {
			sort( $component_files );
			foreach ( $component_files as $file ) {
				$basename = basename( $file );

				if ( preg_match( '/\-(\d+)\-/', $basename, $matches ) ) {
					$priority = $matches[1];
				} else {
					$priority = 10;
				}
				
				$basename = preg_replace( '/(\-\d+)/', '', $basename );
				$class_name = strtolower( str_replace( array( 'class-', '-component.php' ), '', $basename ) );
				$class_name = preg_replace( '/\W/', '_', $class_name );

				$class = "\EasyCMS_WP\Component\\$class_name";

				require_once $file;

				if ( class_exists( $class ) && $class::can_run() ) {
					$this->components[ $class_name ] = new $class( $this );
					do_action( sprintf( 'easycms_wp_loaded_%s_component', $class_name ), $this->components[ $class_name ] );
				}
			}
		}
	}

	public function get_component_instance( string $name ) {
		$name = strtolower( $name );

		if ( isset( $this->components[ $name ] ) ) {
			return $this->components[ $name ];
		}

		return null;
	}

	private function _load_config() {
		$this->config = apply_filters( 'easycms_wp_load_config', get_option( EASYCMS_WP_CONFIG, array() ), $this );

		return $this;
	}

	public function add_component_config_nav( $nav_items ) {
		$nav_items[] = array(
			'name' => __( 'Components', 'easycms-wp' ),
			'slug'   => 'components'
		);
		
		return $nav_items;
	}

	public function components_page( $slug ) {
		if ( 'components' == $slug ) {
			require_once EASYCMS_WP_ADMIN_TEMPLATE_PATH . 'partial/components.php';
		}
	}

	public function get_runnable_components() {
		$components = array_filter( $this->components, array( $this, 'is_component_runnable' ) );

		return apply_filters( 'easycms_wp_get_runnable_components', $components, $this->components );
	}

	public function is_component_runnable( \EasyCMS_WP\Template\Component $component ) {
		return apply_filters( 'easycms_wp_is_component_runnable', method_exists( $component, 'sync' ), $component );
	}

	public function run_component( \EasyCMS_WP\Template\Component $component ) {
		if ( $this->is_component_runnable( $component ) ) {
			do_action( 'easycms_wp_before_component_run', $component, $this );
			$component->sync();
			do_action( 'easycms_wp_after_component_run', $component, $this );
		}
	}

	public function get_config( string $name ) {
		if ( isset( $this->config[ $name ] ) ) {
			return apply_filters( 'easycms_wp_get_config', $this->config[ $name ], $name, $this );
		}

		return null;
	}

	public function set_config( string $name, $value ) {
		$this->config[ $name ] = apply_filters( 'easycms_wp_set_config', $value, $name, $this );

		update_option( EASYCMS_WP_CONFIG, $this->config );
		do_action( 'easycms_wp_config_updated', $this->config, $this );
	}

	public function perform_cron() {
		foreach ( $this->components as $component ) {
			$component->fail_safe();
		}
	}

	public function activation() {
		Log::create_table();
		$this->load_components();
		
		// Need to make sure there are no existing cron.
		// See - https://developer.wordpress.org/plugins/cron/scheduling-wp-cron-events/#scheduling-the-task
		$this->unschedule_cron();
		$this->schedule_cron();

		foreach ( $this->components as $component ) {
			if ( method_exists( $component, 'activation' ) ) {
				$component->activation();
			}
		}
	}

	private function schedule_cron() {
		wp_schedule_event( time(), 'hourly', EASYCMS_WP_CRON_HOOK );
	}

	private function unschedule_cron() {
		$next_schedule = wp_next_scheduled( EASYCMS_WP_CRON_HOOK );

		// Remove all scheduled cron
		while ( $next_schedule ) {
			wp_unschedule_event( $next_schedule, EASYCMS_WP_CRON_HOOK );

			$next_schedule = wp_next_scheduled( EASYCMS_WP_CRON_HOOK );
		}
	}

	public function deactivation() {
		Log::truncate_table( '', true );
		delete_option( EASYCMS_WP_CONFIG );

		$this->unschedule_cron();

		foreach ( $this->components as $component ) {
			if ( method_exists( $component, 'deactivation' ) ) {
				$component->deactivation();
			}
		}
	}

	function register_stock_type() {

		$labels = array(
			'name'                       => _x( 'Stock Types', 'Taxonomy General Name', 'easycms-wp' ),
			'singular_name'              => _x( 'Stock Type', 'Taxonomy Singular Name', 'easycms-wp' ),
			'menu_name'                  => __( 'Stock Types', 'easycms-wp' ),
			'all_items'                  => __( 'All Items', 'easycms-wp' ),
			'parent_item'                => __( 'Parent Item', 'easycms-wp' ),
			'parent_item_colon'          => __( 'Parent Item:', 'easycms-wp' ),
			'new_item_name'              => __( 'New Item Name', 'easycms-wp' ),
			'add_new_item'               => __( 'Add New Item', 'easycms-wp' ),
			'edit_item'                  => __( 'Edit Item', 'easycms-wp' ),
			'update_item'                => __( 'Update Item', 'easycms-wp' ),
			'view_item'                  => __( 'View Item', 'easycms-wp' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'easycms-wp' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'easycms-wp' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'easycms-wp' ),
			'popular_items'              => __( 'Popular Items', 'easycms-wp' ),
			'search_items'               => __( 'Search Items', 'easycms-wp' ),
			'not_found'                  => __( 'Not Found', 'easycms-wp' ),
			'no_terms'                   => __( 'No items', 'easycms-wp' ),
			'items_list'                 => __( 'Items list', 'easycms-wp' ),
			'items_list_navigation'      => __( 'Items list navigation', 'easycms-wp' ),
		);
		$capabilities = array(
			'manage_terms'               => 'manage_woocommerce',
			'edit_terms'                 => 'manage_woocommerce',
			'delete_terms'               => 'manage_woocommerce',
			'assign_terms'               => 'manage_woocommerce',
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
			'capabilities'               => $capabilities,
		);
		register_taxonomy( 'stock_type', array( 'product' ), $args );
		register_taxonomy_for_object_type( 'stock_type', 'product' );
	
	}

	function __destruct() {
		unset( $this->components );
		unset( $this->config );
	}
}
?>