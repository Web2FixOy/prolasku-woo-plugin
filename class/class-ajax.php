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

	/*
    public function reset_sync_status() {
        if ( $this->nonce_verify() && ! empty( $_POST['easycms_wp_component'] ) ) {
            $component = strtolower( sanitize_text_field( $_POST['easycms_wp_component'] ) );

            // Sanitize: allow only a-z, 0-9, and underscores
            if ( ! preg_match( '/^[a-z0-9_]+$/', $component ) ) {
                wp_send_json_error( __( 'Invalid component name.', 'easycms-wp' ) );
                return;
            }

            $option_name = 'easycms_wp_' . $component . '_sync_status';

            // Perform the SQL update
            global $wpdb;
            $sql = "UPDATE {$wpdb->options} SET option_value = 0 WHERE option_name = '$option_name'";
            $result = $wpdb->query( $sql );

            if ( $result ) {
                wp_send_json_success( array(
                    'message' => __( 'Sync status reset successfully.', 'easycms-wp' ),
                    'sql_result' => $wpdb->last_query
                ) );
            } else {
                wp_send_json_success( array(
                    'message' => __( 'No change — sync status already reset.', 'easycms-wp' ),
                    'sql_result' => ''
                ) );
            }
        } else {
            wp_send_json_error( __( 'Unauthorized request.', 'easycms-wp' ) );
        }
    }*/

	public function reset_sync_status() {
		if ( $this->nonce_verify() && ! empty( $_POST['easycms_wp_component'] ) ) {
			$component = strtolower( sanitize_text_field( $_POST['easycms_wp_component'] ) );

			// Sanitize: allow only a-z, 0-9, and underscores
			if ( ! preg_match( '/^[a-z0-9_]+$/', $component ) ) {
				wp_send_json_error( __( 'Invalid component name.', 'easycms-wp' ) );
				return;
			}

			$option_name = 'easycms_wp_' . $component . '_sync_status';

			global $wpdb;

			// Prepare DELETE query safely using $wpdb->prepare to avoid SQL injection risks
			$sql = $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", $option_name );
			$result = $wpdb->query( $sql );

			if ( $result ) {
				wp_send_json_success( array(
					'message' => __( 'Sync status entry removed successfully.', 'easycms-wp' ),
					'sql_result' => $wpdb->last_query
				) );
			} else {
				wp_send_json_success( array(
					'message' => __( 'No matching sync status entry found to remove.', 'easycms-wp' ),
					'sql_result' => ''
				) );
			}
		} else {
			wp_send_json_error( __( 'Unauthorized request.', 'easycms-wp' ) );
		}
	}

	public function delete_all() {
		if ( $this->nonce_verify() ) {
			global $wpdb;

			// Define all delete queries in an array
			$queries = [
				"DELETE tr FROM {$wpdb->prefix}term_relationships tr JOIN {$wpdb->prefix}posts p ON p.ID = tr.object_id WHERE p.post_type = 'product';",
				"DELETE tt, t FROM {$wpdb->prefix}term_taxonomy tt JOIN {$wpdb->prefix}terms t ON t.term_id = tt.term_id WHERE tt.taxonomy IN ('product_cat','product_tag','product_brand');",
				"DELETE FROM {$wpdb->prefix}posts WHERE post_type = 'product';",
				"DELETE FROM {$wpdb->prefix}posts WHERE post_type = 'product_variation';",
				"DELETE pm FROM {$wpdb->prefix}postmeta pm LEFT JOIN {$wpdb->prefix}posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL;",
				"DELETE tr FROM {$wpdb->prefix}term_relationships tr LEFT JOIN {$wpdb->prefix}posts wp ON wp.ID = tr.object_id WHERE wp.ID IS NULL;",
				"DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key LIKE '_slw_%';",
				"DELETE a FROM {$wpdb->prefix}posts a JOIN {$wpdb->prefix}posts p ON a.post_parent = p.ID WHERE a.post_type = 'attachment' AND a.post_mime_type LIKE 'image/%' AND p.post_type = 'product';",
				"DELETE wp FROM {$wpdb->prefix}posts wp LEFT JOIN {$wpdb->prefix}posts parent ON wp.post_parent = parent.ID WHERE wp.post_type = 'attachment' AND parent.ID IS NULL;",
				"DELETE tm FROM {$wpdb->prefix}termmeta tm LEFT JOIN {$wpdb->prefix}terms t ON t.term_id = tm.term_id WHERE t.term_id IS NULL;"
			];

			$errors = [];
			foreach ($queries as $query) {
				$result = $wpdb->query( $query );
				if ( $result === false ) {
					$errors[] = $wpdb->last_error;
				}
			}

			if (empty($errors)) {
				wp_send_json_success([
					'message' => __('All products and related data deleted successfully.', 'easycms-wp'),
				]);
			} else {
				wp_send_json_error([
					'message' => __('Some queries failed:', 'easycms-wp') . ' ' . implode('; ', $errors),
				]);
			}
		} else {
			wp_send_json_error(__('Unauthorized request.', 'easycms-wp'));
		}
	}


}
?>