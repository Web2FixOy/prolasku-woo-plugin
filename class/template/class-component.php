<?php
namespace EasyCMS_WP\Template;

use \EasyCMS_WP\Log;

abstract class Component {
	CONST SERVER_ERR  = 1;
	CONST CLIENT_ERR  = 2;
	CONST CONNECT_ERR = 3;
	CONST API_BASE = 'easycms/v1';

	protected $parent;
	private $config;
	private $config_name;
	protected $priority;

	public function __construct( \EasyCMS_WP\EasyCMS_WP $parent, int $priority = 10 ) {
		$this->parent = $parent;
		$this->priority = $priority;

		$this->load_config();
		if ( ! wp_doing_ajax() && ! wp_doing_cron() )
			$this->log( sprintf( __( 'Loaded module %s', 'easycms-wp' ), $this->get_module_name() ), 'debug' );

		$this->hooks();
	}

	public function rest_validate_id( $param ) {
		return ( is_numeric( $param ) && $param > 0 );
	}

	public function rest_validate_number( $param ) {
		return is_numeric( $param );
	}

	public function rest_validate_email( $param ) {
		return filter_var( $param, FILTER_VALIDATE_EMAIL );
	}

	public function rest_validate_array( $param ) {
		return is_array( $param );
	}

	public function rest_validate_image( $param ) {
		return true;
	}

	public function rest_sanitize_price( $param ) {
		return floatval( $param );
	}

	public function rest_sanitize_int( $param ) {
		return intval( $param );
	}

	private function get_sync_option_name() {
		$option_name = sprintf( 'easycms_wp_%s_sync_status', $this->get_module_name() );
		return $option_name;
	}

	// protected function set_sync_status( bool $status ) {
	// 	update_option( $this->get_sync_option_name(), $status );
	// }

	### NEW 2023 time based status
	protected function set_sync_status( bool $status ) {
	    update_option( $this->get_sync_option_name(), $status );
	    if($status){
	        if(!get_option( 'easycms_wp_sync_start_time' )){
	            add_option( 'easycms_wp_sync_start_time', time() );
	        }else{
	            update_option( 'easycms_wp_sync_start_time', time() );
	        }
	    }else{
	        if(get_option( 'easycms_wp_sync_start_time' )){
	            update_option( 'easycms_wp_sync_start_time', false );
	        }
	    }
	}


	public function is_syncing() {
		return get_option( $this->get_sync_option_name(), false );
	}

	protected function rest_response( $data, string $type = 'success', int $code = 200 ) {
		$response = array(
			'code'  => 'success' == $type ? 'OK' : 'FAIL',
			'data'  => $data,
		);

		return new \WP_REST_Response( $response, $code );
	}

	public function rest_check_auth( \WP_REST_Request $req ) {
		$api_config = $this->parent->get_config( 'api' );
		$params = $req->get_params();
		$headers = $req->get_headers();

		// this method seems to be called twice from register_rest_route()
		static $logged = false;

		// Lets avoid flooding the logs with duplicate logs
		if ( ! $logged ) {
			$this->log(
				sprintf(
					__( 'Authorizing REST API request %s %s', 'easycms-wp' ),
					$req->get_method(),
					$req->get_route()
				),
				'info'
			);
		}
		
		if (
			! $api_config                  ||
			! isset( $params['username'] ) ||
			! isset( $params['account'] )  ||
			! isset( $params['password'] ) ||
			! isset( $headers['authorization1'] )
		) {
			if ( ! $logged ) {
				$this->log(
					sprintf(
						__( 'Unable to authorize REST API request %s %s: Missing authentication parameter', 'easycms-wp' ),
						$req->get_method(),
						$req->get_route()
					),
					'error'
				);
			}

			$logged = true;
			return false;
		}

		
		$params['username']             = sanitize_text_field( $params['username'] );
		$params['account']              = absint( $params['account'] );
		$headers['authorization1']      = sanitize_text_field( $headers['authorization1'][0] );

		if (
			$params['username']             === $api_config['username'] &&
			$params['password']             === $api_config['password'] &&
			$params['account']              === $api_config['account']  &&
			$headers['authorization1'] 	=== base64_encode( $api_config['key'] )
		) {
			$logged = true;

			return true;
		} else {
			if ( ! $logged ) {
				$this->log(
					sprintf(
						__( 'Unable to authorize REST API request %s %s: Invalid credentials', 'easycms-wp' ),
						$req->get_method(),
						$req->get_route()
					),
					'error'
				);
			}

			$logged = true;

			return false;
		}
	}

	abstract public function hooks();
	abstract public function fail_safe();


	public static function can_run() {
		// Perform dependency checks before loading a component here. Return false if you want to avoid loading
		return true;
	}

	final public function get_module_name() {
		return strtolower(str_ireplace('easycms_wp\\component\\', '', get_class($this)));
		// DEPRECATED :: return strtolower( str_ireplace( 'easycms_wp\\component\\', null, get_class( $this ) ) );

	}

	private function load_config() {
		$this->config_name = sprintf( 'easycms_wp_component_%s_config', $this->get_module_name() );

		$this->config = get_option( $this->config_name, array() );
	}
	
	public function activation() {
		// run on plugin activation
	}

	public function deactivation() {
		// run on plugin deactivation
	}

	protected function get_config( string $key ) {
		if ( isset( $this->config[ $key ] ) ) {
			return $this->config[ $key ];
		}

		return null;
	}

	protected function set_config( string $key, $value ) {
		$this->config[ $key ] = $value;

		update_option( $this->config_name, $this->config );
	}

	protected function remove_config( string $key ) {
		if ( isset( $this->config[ $key ] ) ) {
			unset( $this->config[ $key ] );
			update_option( $this->config_name, $this->config );
		}
	}

	protected function log( string $message, string $type = 'info' ) {
		if ( 'debug' == $type && ! EASYCMS_WP_DEBUG )
			return;
 
		Log::log( $this->get_module_name(), $message, $type );
	}

	protected function make_request( string $path, string $method = 'POST', array $data = array() ) {
		$ret = '';
	
		$this->log(
			sprintf(
				__( 'Preparing to request to %s', 'easycms-wp' ),
				$path
			),
			'debug'
		);

		$this->log(
			__( 'Getting API config', 'easycms-wp' ),
			'info'
		);

		$api_config = $this->parent->get_config( 'api' );
		$url_params = array(
			'username'  => $api_config['username'],
			'password'  => $api_config['password'],
			'account'   => $api_config['account'],
		);
		$headers = array(
			'Authorization1'  => base64_encode( $api_config['key'] ),
		);

		$url = sprintf( '%s%s?%s', EASYCMS_WP_API_URI, $path, http_build_query( $url_params ) );

		$this->log(
			sprintf(
				__( 'Making request to %s %s with headers: %s', 'easycms-wp' ),
				strtoupper( $method ),
				$url,
				http_build_query( $headers )
			),
			'info'
		);

		$request = wp_remote_request(
			$url,
			array(
				'method'    => $method,
				'body'      => $data,
				'headers'   => $headers,
			)
		);

		$this->log(
			sprintf(
				__( '=====> Here is the body: %s', 'easycms-wp' ),
				json_encode($data) 
			),
			'debug'
		);

		if ( is_wp_error( $request ) ) {
			$this->log(
				sprintf(
					__( 'Unable to reach endpoint %s: %s', 'easycms-wp' ),
					$path,
					$request->get_error_message() 
				),
				'error'
			);

			$ret = new \WP_Error( self::CONNECT_ERR );
		} else if ( $request['response']['code'] >= 500 ) {
			// Server error. We should try again later
			$this->log(
				sprintf(
					__( 'Server error while trying to reach %s (%d %s)', 'easycms-wp' ),
					$path,
					$request['response']['code'],
					$request['response']['message']
				),
				'error'
			);

			$ret = new \WP_Error( self::SERVER_ERR );
		} else if ( $request['response']['code'] < 500 && $request['response']['code'] >= 400 ) {
			// Client Error.

			$this->log(
				sprintf(
					__( 'Error while trying to reach resource %s - (%d %s) -  (%s)', 'easycms-wp' ),
					$path,
					$request['response']['code'],
					$request['response']['message'],
					wp_remote_retrieve_body( $request )
				),
				'error'
			);

			$ret = new \WP_Error( self::CLIENT_ERR );
		} else {
			$ret = apply_filters( 'easycms_wp_component_request_response', wp_remote_retrieve_body( $request ), $this );
		}

		return $ret;
	}
}
?>