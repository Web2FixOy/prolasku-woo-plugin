<?php
namespace EasyCMS_WP;
defined( 'ABSPATH' ) || exit;

class Ajax {
	private $parent;

	public function __construct( EasyCMS_WP $parent ) {
		$this->parent = $parent;
	}

	private function nonce_verify() {
		return (
			isset( $_REQUEST['easycms_wp_nonce' ] ) && 
			wp_verify_nonce( $_REQUEST['easycms_wp_nonce'], 'easycms_wp_check_req' ) &&
			current_user_can( 'manage_options' )
		);
	}

	public function clear_logs() {
		if ( $this->nonce_verify() ) {
			Log::truncate_table();
		}

		wp_send_json_success( __( 'Logs cleared', 'easycms-wp' ) );
	}

	public function run_component() {
		if ( $this->nonce_verify() && ! empty( $_POST['easycms_wp_component'] ) ) {
			$component_name = strtolower( sanitize_text_field( $_POST['easycms_wp_component'] ) );
			$component_inst = $this->parent->get_component_instance( $component_name );

			if ( $component_inst ) {
				$this->parent->run_component( $component_inst );
			}
		}
	}

	public function get_logs() {
		if ( $this->nonce_verify() ) {
			$module = '';
			$type = '';
			$hours = '';
		
			if ( isset( $_POST['easycms_wp_log_module'] ) ) {
				$module = sanitize_text_field( $_POST['easycms_wp_log_module'] );
			}

			if ( isset( $_POST['easycms_wp_log_type'] ) ) {
				$type = sanitize_text_field( $_POST['easycms_wp_log_type'] );
			}

			if ( isset( $_POST['easycms_wp_log_hours'] ) ) {
				$hours = absint( $_POST['easycms_wp_log_hours'] );
			}

			$logs = Log::get_logs( $module, $type, $hours );

			wp_send_json_success( $logs );
		}
	}
}
?>