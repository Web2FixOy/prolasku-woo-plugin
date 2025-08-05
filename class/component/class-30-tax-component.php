<?php
namespace EasyCMS_WP\Component;

use \EasyCMS_WP\Log;

class Tax extends \EasyCMS_WP\Template\Component {
	public $wpdb;
	public $data_table;

	public function __construct( \EasyCMS_WP\EasyCMS_WP $parent, int $priority = 10 ) {
		global $wpdb;
		parent::__construct( $parent, $priority );

		$this->wpdb = $wpdb;
		$this->data_table = $this->wpdb->prefix . 'easycms_tax_data';
	}

	public static function can_run() {
		if ( ! class_exists( 'woocommerce' ) ) {
			Log::log(
				'tax',
				__( 'This component depends on WooCommerce plugin to run', 'easycms-wp' ),
				'error'
			);

			return false;
		}

		return true;
	}

	public function activation() {
		$this->wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$this->data_table}
			(
				ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
				tax_rate_class_id BIGINT UNSIGNED NOT NULL,
				vat_id BIGINT UNSIGNED NOT NULL,
				data LONGTEXT,
				UNIQUE KEY (tax_rate_class_id),
				UNIQUE KEY (vat_id),
				FOREIGN KEY (tax_rate_class_id) REFERENCES `{$this->wpdb->prefix}wc_tax_rate_classes`(tax_rate_class_id)
				ON DELETE CASCADE ON UPDATE CASCADE
			)"
		);
	}
	
	public function deactivation() {
		// $this->wpdb->query(
		// 	"DROP TABLE IF EXISTS {$this->data_table}"
		// );
	}

	public function sync() {
		set_time_limit( 0 );
		ignore_user_abort( true );

		$this->log( __( '===SYNC STARTED===', 'easycms-wp' ), 'info' );

		while ( ( $taxes = $this->get_taxes() ) ) {
			foreach ( $taxes as $tax_data ) {
				$tax_data = $this->set_name( $tax_data );
				$this->add_tax_class( $tax_data );
			}
		}
		$this->log( __( '===SYNC ENDED===', 'easycms-wp' ), 'info' );

		ignore_user_abort( false );
	}

	private function set_name( array $data ) {
		if ( isset( $data['vat_account'], $data['vat_name'] ) ) {
			$data['cr_tax_name'] = sprintf( 'ALV %d%% [%s]', $data['vat_name'], $data['vat_account'] );
		}

		return $data;
	}

	public function hooks() {
		// add_action( 'easywp_cms_save_api_settings', array( $this, 'sync' ), $this->priority );
		add_action( 'rest_api_init', array( $this, 'register_api' ) );

		add_filter( 'easycms_wp_product_component_before_save_product', array( $this, 'insert_product_tax_class' ), 10, 2 );
		add_filter( 'easycms_wp_set_order_item_data', array( $this, 'set_order_item_vat' ), 10, 2 );
	}

	public function set_order_item_vat( ?array $product_data, \WC_Product $product ) {
		if ( null === $product_data ) {
			return $product_data;
		}

		$vat_data = $this->get_tax_class_by_vat_id( $product_data['vat'] );
		$vat_buy_data = $this->get_tax_class_by_vat_id( $product_data['vat_buy'] );
		if ( ! empty( $vat_data->data ) ) {
			$data = unserialize( $vat_data->data );
			$product_data['vat_percent'] = $data['vat_name'];
		} else {
			$this->log(
				sprintf(
					__( 'Unable to set vat_percent on order item: %s', 'easycms-wp' ),
					$product->get_name()
				),
				'error'
			);

			$product_data = null;
		}

		if ( null !== $product_data ) {
			if ( ! empty( $vat_buy_data->data ) ) {
				$data = unserialize( $vat_buy_data->data );
				$product_data['vat_percent_buy'] = $data['vat_name'];
			} else {
				$this->log(
					sprintf(
						__( 'Unable to set vat_percent on order item: %s', 'easycms-wp' ),
						$product->get_name()
					),
					'error'
				);

				$product_data = null;
			}
		}

		return $product_data;
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
			'callback'            => array( $this, 'rest_add_tax' ),
			'args'                => array(
				'vatId'               => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
				'vat_account'                      => array(
					'sanitize_callback' => 'sanitize_text_field',
					'required'          => true,
				),
				'vat_name'               => array(
					'validate_callback' => array( $this, 'rest_validate_number' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
			)
		));

		register_rest_route( self::API_BASE, $this->get_module_name() . '/delete', array(
			'methods' => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_delete_tax' ),
			'args'                => array(
				'vatId'               => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
			)
		));
	}

	public function insert_product_tax_class( \WC_Product $product, array $product_data ) {
		if ( ! empty( $product_data['vat'] ) ) {
			$tax_class = $this->get_tax_class_by_vat_id( absint( $product_data['vat'] ) );
			if ( ! $tax_class ) {
				$this->log(
					sprintf(
						__( 'Error finding product tax class in WP. Skipping product tax class...', 'easycms-wp' ),
					),
					'warning'
				);
			} else {
				$product->set_tax_class( $tax_class->slug );
				$this->log(
					__( 'Set product tax class successfully', 'easycms-wp' ),
					'info'
				);
			}
		}

		return $product;
	}

	public function rest_add_tax( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$params = $this->set_name( $params );
		$term_id = $this->add_tax_class( $params );

		if ( $term_id ) {
			return $this->rest_response( $term_id );
		}

		return $this->rest_response( '', 'FAIL', 400 );
	}

	public function rest_delete_tax( \WP_REST_Request $request ) {
		$tax_class = $this->get_tax_class_by_vat_id( $request['vatId'] );

		$this->log(
			sprintf(
				__( 'Deleting tax with vatId = %d', 'easycms-wp' ),
				$request['vatId']
			),
			'info'
		);

		if ( ! $tax_class ) {
			$this->log(
				sprintf(
					__( 'Delete failed: Unable to find tax class with vatId = %d', 'easycms-wp' ),
					$request['vatId']
				),
				'error'
			);
		} else {
			$this->delete_tax_class( $tax_class->slug );
		}

		return $this->rest_response( 'success' );
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

	public function add_tax_class( array $payload ) {
		$existing = $this->get_tax_class_by_vat_id( $payload['vatId'] );

		if ( $existing ) {
			$this->log(
				sprintf(
					__( 'Tax class %s already exists. Deleting to re-add', 'easycms-wp' ),
					$payload['cr_tax_name']
				),
				'info'
			);

			\WC_Tax::delete_tax_class_by( 'slug', $existing->slug );
		}

		$tax_class = \WC_Tax::create_tax_class( $payload['cr_tax_name'] );
		if ( is_wp_error( $tax_class ) ) {
			$this->log(
				sprintf(
					__( 'Error while adding tax class %s: %s', 'easycms-wp' ),
					$payload['cr_tax_name'],
					$tax_class->get_error_message()
				),
				'error'
			);

			return;
		}

		$this->log(
			sprintf(
				__( '%s tax class added successfully', 'easycms-wp' ),
				$payload['cr_tax_name']
			),
			'info'
		);

		if ( $this->link_tax_class_to_vat( $tax_class['slug'], $payload ) ) {
			\WC_Tax::_insert_tax_rate( array(
				'tax_rate'          => absint( $payload['vat_name'] ),
				'tax_rate_name'     => sprintf( '%d%%', $payload['vat_name'] ),
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => $tax_class['slug'],	
			));
		}

		return $tax_class;
	}

	private function link_tax_class_to_vat( string $slug, array $data ) {
		$tax_class_id = $this->get_tax_class_id( $slug );
		
		if ( $tax_class_id ) {
			$insert = $this->wpdb->insert(
				$this->data_table,
				array(
					'tax_rate_class_id'      => $tax_class_id,
					'vat_id'                 => $data['vatId'],
					'data'                   => serialize( $data ),
				)
			);

			return $insert;
		} else {
			$this->log(
				sprintf(
					__( 'Error finding tax class ID. Unable to save tax data for %s tax class', 'easycms-wp' ),
					$data['cr_tax_name']
				),
				'error'
			);

			return false;
		}
	}

	private function prepare_slug( int $id, string $name ) {
		return sprintf( '%s-%d', sanitize_title( $name ), $id );
	}

	public function get_tax_class_id( string $slug ) {
		$id = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT `tax_rate_class_id` FROM {$this->wpdb->prefix}wc_tax_rate_classes WHERE slug = %s",
				$slug
			)
		);

		return $id;
	}

	public function get_tax_class_by_vat_id( int $vat_id ) {
		$table = $this->wpdb->prefix . 'wc_tax_rate_classes';

		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT
					TAX_CLASSES.slug,
					TAX_CLASSES.name,
					DATA_TABLE.data
				FROM
					{$this->data_table} AS DATA_TABLE
				INNER JOIN
					{$table} AS TAX_CLASSES
					ON ( DATA_TABLE.tax_rate_class_id = TAX_CLASSES.tax_rate_class_id )
				WHERE
					DATA_TABLE.vat_id = %d",
				$vat_id
			)
		);

		return $row;
	}

	public function delete_tax_class( string $slug ) {
		$delete = \WC_Tax::delete_tax_class_by( 'slug', $slug );
		if ( is_wp_error( $delete ) ) {
			$this->log(
				sprintf(
					__( 'Error while deleting tax class with slug %s: %s', 'easycms-wp' ),
					$slug,
					$delete->get_error_message()
				),
				'error'
			);
		}

		$this->log(
			sprintf(
				__( 'Successfully deleted tax class with slug: %s', 'easycms-wp' ),
				$slug
			),
			'info'
		);

		return;
	}

	private function get_taxes( int $limit = 50 ) {
		static $page = 1;

		$offset = $limit * ( $page - 1 );

		$req = $this->make_request( '/get_taxes', 'POST', array( 'start' => $offset, 'limit' => $limit ) );
		if ( is_wp_error( $req ) ) {
			$this->log(
				sprintf(
					__( 'Fetching taxes failed: %s. Number of retries: (%d)', 'easycms-wp' ),
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

			return $data['OUTPUT'];
		} else {
			$this->log( __( 'Received an invalid response format from server', 'easycms-wp' ), 'error' );

			return false;
		}
	}
}
?>