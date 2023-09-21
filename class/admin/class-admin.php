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
		add_action( 'easycms_wp_admin_nav_content', array( $this, 'settings_page' ) );
		add_action( 'admin_menu', array( $this, 'the_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		
	}

	public function enqueue() {
		$screen = get_current_screen();

		if ( 'toplevel_page_easycms-wp' == $screen->base ) {
			wp_enqueue_script(
				'easycms_wp_admin',
				sprintf(
					'%s/asset/js/easycms-wp.js',
					EASYCMS_WP_BASE_URI
				),
				array( 'jquery' )
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
		if ( $slug == '' ) {
			$options = $this->parent->get_config( 'api' );

			if ( isset( $_POST['__nonce'] ) && wp_verify_nonce( $_POST['__nonce'], self::NONCE_ ) ) {
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
			}

			require_once __DIR__ . '/template/partial/settings.php';
		}
	}

	public function add_settings_tab( array $nav_items ) {
		$nav_items[] = array(
			'name' => __( 'API Settings', 'easycms-wp' ),
			'slug' => '',
		);

		return $nav_items;
	}

	public function the_page() {
		

		require_once apply_filters( 'easycms_wp_settings_template', __DIR__ . '/template/template-settings-page.php', $this );
	}
}
?>