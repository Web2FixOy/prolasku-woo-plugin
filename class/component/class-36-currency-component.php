<?php
namespace EasyCMS_WP\Component;

use \EasyCMS_WP\Log;
use \EasyCMS_WP\Util;

class Currency extends \EasyCMS_WP\Template\Component {
	public static function can_run() {
		if (
			! class_exists( 'WooCommerce' ) ||
			! class_exists( 'woocommerce_wpml' ) ||
			! $GLOBALS['woocommerce_wpml']->dependencies_are_ok
		) {
			Log::log(
				'product',
				__(
					'This component depends on WooCommerce, WooCommerce WPML & dependencies to run',
					'easycms-wp'
				),
				'error'
			);
			return false;
		}

		return true;
	}

	public function sync() {
		if ( $this->is_syncing() ) {
			$this->log( __( 'Sync already running. Cannot start another', 'easycms-wp' ), 'error' );
			return;
		}

		set_time_limit( 0 );
		ignore_user_abort( true );

		$currencies = $this->get_currencies();

		$this->set_sync_status( true );
		$this->log( __( '===SYNC STARTED===', 'easycms-wp'), 'info' );
		while ( ! empty( $currencies ) ) {
			foreach ( $currencies as $currency_data ) {
				$this->insert( $currency_data['currency_alphabetic_code'], $currency_data['rate'] );
			}
			$currencies = $this->get_currencies();
		}
		
		$this->set_sync_status( false );
		$this->log( __( '===SYNC ENDED===', 'easycms-wp'), 'info' );
	}

	public function hooks() {
		// add_action( 'easywp_cms_save_api_settings', array( $this, 'sync' ), $this->priority );
		add_action( 'rest_api_init', array( $this, 'register_api' ) );
	}

	public function fail_safe() {
		if ( $this->has_pending() ) {
			$this->log( __( 'Performing pending failed operations', 'easycms-wp' ), 'info' );
			$this->sync();
		}
	}

	public function register_api() {
		register_rest_route( self::API_BASE, $this->get_module_name(), array(
			'methods' => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_add_currency' ),
			'args'                => array(
				'currency_alphabetic_code'               => array(
					'validate_callback' => array( $this, 'validate_currency_code' ),
					'sanitize_callback' => 'sanitize_text_field',
					'required'          => true,
				),
				'rate'                     => array(
					'validate_callback' => array( $this, 'validate_currency_rate' ),
					'sanitize_callback' => array( $this, 'sanitize_float' ),
					'required'          => true,
				),
			)
		));

		register_rest_route( self::API_BASE, $this->get_module_name() . '/delete', array(
			'methods' => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_delete_currency' ),
			'args'                => array(
				'currency_alphabetic_code'               => array(
					'validate_callback' => array( $this, 'validate_currency_code' ),
					'sanitize_callback' => 'sanitize_text_field',
					'required'          => true,
				),
			)
		));
	}

	public function validate_currency_code( $code ) {
		return is_string( $code ) && strlen( $code ) == 3;
	}

	public function validate_currency_rate( $rate ) {
		return is_numeric( $rate );
	}

	public function sanitize_float( $num ) {
		return floatval( $num );
	}

	public function rest_add_currency( \WP_REST_Request $request ) {
		$code = $request['currency_alphabetic_code'];
		$rate = $request['rate'];

		$this->insert( $code, $rate );
		return $this->rest_response( __( 'Currency added/updated successfully', 'easycms-wp' ) );
	}

	public function rest_delete_currency( \WP_REST_Request $request ) {
		global $sitepress, $woocommerce_wpml;

		$code = $request['currency_alphabetic_code'];

		$this->log(
			sprintf(
				__( 'Deleting currency %s', 'easycms-wp' ),
				$code
			),
			'info'
		);

		if ( Util::currency_exists( $code ) ) {
			$woocommerce_wpml->multi_currency->delete_currency_by_code( $code );

			return $this->rest_response( 'success' );
		}

		return $this->rest_response( __( 'Currency does not exist', 'easycms-wp' ), 'FAIL', 404 );
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

	private function get_currencies( int $limit = 50 ) {
		static $page = 1;
		$offset = ($page - 1) * $limit ;

		$req = $this->make_request(
			'/get_currencies',
			'POST',
			array(
				'start' => $offset,
				'limit' => $limit,
			)
		);

		if ( is_wp_error( $req ) ) {
			$this->log(
				sprintf(
					__( 'Fetching currencies failed: %s. Number of retries: (%d)', 'easycms-wp' ),
					$req->get_error_message(),
					$this->set_pending()
				),
				'error'
			);

			return false;
		}

		$this->log( __( 'Parsing JSON response', 'easycms-wp' ), 'info' );

		$data = json_decode( $req, true );

		if ( isset( $data['OUTPUT'] ) ) {
			$this->clear_pending();

			$page++;
			return array_filter( $data['OUTPUT'], 'is_numeric', ARRAY_FILTER_USE_KEY );
		} else {
			$this->log( __( 'Received an invalid response format from server', 'easycms-wp' ), 'error' );
			return false;
		}
	}

	public function get_term_meta_name() {
		return 'easycms_wp_stock_type_id';
	}

	public function get_term_data_meta_name() {
		return 'easycms_wp_data';
	}

	/**
	 * Copied from class-wcml-multi-currency-configuration.php WCML_Multi_Currency_Configuration::add_currency()
	 */
	public static function add_currency( $currency_code, float $rate ) {
		global $sitepress, $woocommerce_wpml;

		$settings = $woocommerce_wpml->get_settings();

		$active_languages    = $sitepress->get_active_languages();
		$return['languages'] = '';
		foreach ( $active_languages as $language ) {
			if ( ! isset( $settings['currency_options'][ $currency_code ]['languages'][ $language['code'] ] ) ) {
				$settings['currency_options'][ $currency_code ]['languages'][ $language['code'] ] = 1;
			}
		}
		$settings['currency_options'][ $currency_code ]['rate']    = $rate;
		$settings['currency_options'][ $currency_code ]['updated'] = date( 'Y-m-d H:i:s' );

		$wc_currency = wcml_get_woocommerce_currency_option();
		if ( ! isset( $settings['currencies_order'] ) ) {
			$settings['currencies_order'][] = $wc_currency;
		}

		$settings['currencies_order'][] = $currency_code;

		$woocommerce_wpml->update_settings( $settings );
		$woocommerce_wpml->multi_currency->init_currencies();

		foreach ( $woocommerce_wpml->multi_currency->currencies[ $currency_code ] as $key => $value ) {
			if ( $key === 'rate' ) {
				$previous_rate = $woocommerce_wpml->multi_currency->currencies[ $currency_code ][ $key ];
				$woocommerce_wpml->multi_currency->currencies[ $currency_code ]['previous_rate'] = $previous_rate;
				$woocommerce_wpml->multi_currency->currencies[ $currency_code ]['updated']       = date( 'Y-m-d H:i:s' );
			}
			
		}

		$woocommerce_wpml->update_setting( 'currency_options', $woocommerce_wpml->multi_currency->currencies );

	}

	private function insert( $code, $rate ) {
		global $woocommerce_wpml;
		
		$currencies = $woocommerce_wpml->multi_currency->currencies;

		$this->log(
			sprintf(
				__( 'Adding/Updating currency %s with rate %f', 'easycms-wp' ),
				$code,
				$rate
			),
			'info'
		);

		$this->add_currency( $code, $rate );

		$this->log(
			sprintf(
				__( 'Currency %s added successfully', 'easycms-wp' ),
				$code
			),
			'info'
		);
	}
}
?>