<?php
namespace EasyCMS_WP\Component;

use \EasyCMS_WP\Log;

class Language extends \EasyCMS_WP\Template\Component {
	private $languages = array();
	private $default = '';

        public function hooks() {
		// add_action( 'easywp_cms_save_api_settings', array( $this, 'sync' ), $this->priority );
        }

	public static function can_run() {
		if ( ! defined( 'ICL_SITEPRESS_VERSION' ) ) {
			Log::log( 'language', __( 'This component depends on WPML plugin to load', 'easycms-wp' ), 'error' );

			return false;
		}

		return true;
	}

        public function fail_safe() {
                if ( $this->has_pending() ) {
			$this->log( __( 'Performing pending failed operations', 'easycms-wp' ), 'info' );
			$this->sync();
		}
        }

	public function sync() {
		global $sitepress;

		set_time_limit(0);

		$this->get_languages();

		if ( $this->languages ) {
			$this->log(
				sprintf(
					__( 'Setting active languages: %s', 'easycms-wp' ),
					implode( ',', $this->languages ) 
				),
				'info'
			);

			$setup_instance = wpml_get_setup_instance();
			$setup_instance->set_active_languages( $this->languages );
			do_action( sprintf( 'easycms_wp_component_%s_set_languages', $this->get_module_name() ), $this->languages );

			if ( $this->default ) {
				$this->log(
					sprintf(
						__( 'Setting site-wide default language to %s', 'easycms-wp' ),
						$this->default
					),
					'info'
				);

				$sitepress->set_default_language( $this->default );

				$this->log(
					sprintf(
						__( 'Site wide default language changed to %s', 'easycms-wp' ),
						$this->default
					),
					'info'
				);

				do_action( sprintf( 'easycms_wp_component_%s_set_default_language', $this->get_module_name() ), $this->default );
			}
		}
	}

	private function set_pending() {
		$this->set_config( 'pending', 1 );
		$retries = $this->get_config( 'pending_retries' );

		if ( $retries === null ) {
			$retries = 0;
		} else {
			$retries++;
		}

		$this->set_config( 'pending_retries', $retries );

		return $retries;
	}

	private function clear_pending() {
		$this->remove_config( 'pending' );
		$this->remove_config( 'pending_retries' );
	}

	public function has_pending() {
		return (bool) $this->get_config( 'pending' );
	}

	public function get_languages() {
		global $sitepress;

		$req = $this->make_request( '/get_languages' );

		if ( is_wp_error( $req ) ) {
			// Failed getting languages. Set pending state to be re-used in fail_safe()
			$this->log(
				sprintf(
					__( 'Failed request, will try again in next cron. Number of retries: %d', 'easycms-wp' ),
					$this->set_pending() 
				),
				'error'
			);

			return ;
		}

		$this->log( __( 'Decoding JSON response', 'easycms-wp' ), 'info' );

		$data = json_decode( $req );

		if ( isset( $data->OUTPUT ) ) {
			$this->log( __( 'Processing decoded data...', 'easycms-wp' ), 'info' );
			$active_lang = array();

			foreach ( $data->OUTPUT as $lang ) {
				$this->log( __( 'Lang data:'.json_encode($lang), 'easycms-wp' ), 'info' );
				// WPML method does not support locale format of foo_fo, so I have to strip out the last _fo
				$code = strpos( $lang->code, '_' ) !== false ? strstr( $lang->code, '_', true ) : $lang->code;
				
				if($code=="zh"){
					// $code = "zh-hant"; //WooCommerce Chinese 
					$code = "zh-hans"; //WooCommerce Chinese 
				}

				$code = filter_var( $code, FILTER_SANITIZE_STRING );

				$active_lang[] = $code;

				if ( $lang->default ) {
					$this->default = $code;
				}
			}

			$this->languages = apply_filters( sprintf( 'easycms_wp_component_%s_active_languages', $this->get_module_name() ), $active_lang );

			// If it was successful, no pending left then
			$this->clear_pending();
			return;
		}

		$this->log( __( 'Received an invalid response format from server', 'easycms-wp' ), 'error' );
	}
}
?>