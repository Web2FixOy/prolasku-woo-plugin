<?php
namespace EasyCMS_WP\Component;

use \EasyCMS_WP\Log;
use \EasyCMS_WP\Util;

class Stock_Location extends \EasyCMS_WP\Template\Component {
	public $taxonomy = 'location';

	public static function can_run() {
		if ( ! class_exists( 'SlwMain' ) ) {
			Log::log(
				'stock_locations',
				__( 'This component depends on SLW plugin to run', 'easycms-wp' ),
				'error'
			);
			add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );

			return false;
		}

		return true;
	}

	public static function dependency_notice() {
		$class = 'notice notice-error';
		$message = __( 'EasyCMS Stock Locations component depends on SLW plugin to run', 'easycms-wp' );
		
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public function sync() {
		set_time_limit( 0 );
		ignore_user_abort( true );

		$locations = $this->get_locations();
		if ( $locations ) {
			foreach ( $locations as $location ) {
				$this->insert_location( $location->location_id, $location->name, $location->email, (array) $location );
			}
		}
	}

	public function hooks() {
		// add_action( 'easywp_cms_save_api_settings', array( $this, 'sync' ) );
		add_action( 'rest_api_init', array( $this, 'register_api' ) );

		add_action( 'woocommerce_after_product_object_save', array( $this, 'set_object_terms' ) );
		add_filter( 'easycms_wp_product_component_before_save_product', array( $this, 'insert_product_stock' ), 10, 2 );
		// add_filter( 'easycms_wp_product_component_after_save_product', array( $this, 'insert_product_stock_after' ), 10, 2 );
		add_filter( 'easycms_wp_set_order_item_data', array( $this, 'insert_order_product_stock' ), 10, 3 );

		add_action( 'easycms_wp_order_create_item', array( $this, 'allocate_location_to_new_order_item' ), 10, 3 );
	}

	public function fail_safe() {
		if ( $this->has_pending() ) {
			$this->log( __( 'Performing pending failed operations', 'easycms-wp' ), 'info' );
			$this->sync();
		}
	}

	public function allocate_location_to_new_order_item( $wc_order_item, $order_line_item, $params ) {
		if ( ! empty( $params['location_id'] ) ) {
			$this->log(
				__( 'Setting location for order line item', 'easycms-wp' ),
				'info'
			);

			list ( $location_id, $shelf_id ) = explode( '-', $params['location_id'] );
			$term = $this->get_term( $location_id );
			if ( $term ) {
				$wc_order_item->update_meta( '_stock_location', $term->term_id );
				$wc_order_item->save_meta_data();

				$this->log(
					sprintf(
						__( 'Stock location set successfully', 'easycms-wp' )
					),
					'info'
				);
			} else {
				$this->log(
					sprintf(
						__( 'Unable to find stock location id %d on WP', 'easycms-wp' ),
						$location_id
					),
					'error'
				);
			}
		}
	}

	public function get_shelf_id( $location_id, $product_data ) {
		$locations = $product_data['stock_data'];
		$shelf_id = 0;

		foreach ( $locations as $location ) {
			if ( $location['location_id'] == $location_id ) {
				$shelf_id = $location['shelf_id'];
				break;
			}
		}

		return $shelf_id;
	}

	public function insert_order_product_stock( $product_data, \WC_Product $product, $wc_order_item ) {
		if ( null === $product_data ) {
			return $product_data;
		}
		// echo "<pre>";
		// print_r($product_data);
		// exit();
		$product_data['stock_available'] = $product_data['stock_available_now'] = 0;
		$product_data['location'] = $product_data['location_id'] = '';


		if ( ! empty( $product_data['stock_data'] ) ) {
			// var_dump($product_data['stock_data']);
			// var_dump($wc_order_item);
			$location = $wc_order_item->get_meta( '_stock_location' );
				$this->log(
					sprintf(
						__( 'here is _stock_location metadata: %s', 'easycms-wp' ),
						json_encode($location)
					),
					'debug'
				);
			// var_dump($location);
			if ( ! empty( $location ) ) {
				$term_id = $location; // key( $locations );
				$stock = \SLW\SRC\Helpers\SlwStockAllocationHelper::
					get_product_stock_location( $product->get_id(), $term_id );
				if ( $stock && isset( $stock[ $term_id ] ) ) {
					$product_data['stock_available'] = $stock[ $term_id ]->quantity;
					$product_data['stock_available_now'] = $stock[ $term_id ]->quantity;

					$data = get_term_meta( $term_id, 'easycms_wp_data', true );
					if ( $data ) {
						$product_data['location'] = $data['name'];
						$product_data['location_id'] = $data['location_id'] . '-' . $this->get_shelf_id( $data['location_id'], $product_data );
						$this->log(
							sprintf(
								__( 'SLW data for product %s is location_id: %s & location: %s', 'easycms-wp' ),
								$product->get_name(),
								$product_data['location_id'],
								$product_data['location']
							),
							'debug'
						);
					} else {
						$this->log(
							sprintf(
								__( 'Error stock location data not found' )
							),
							'error'
						);
						// $product_data = null;
					}
					
				} else {
					// $product_data = null;
					$this->log(
						sprintf(
							__( 'Unable to get stock locations for product %s', 'easycms-wp' ),
							$product->get_name()
						),
						'error'
					);
				}
			} else {
				// $product_data = null;
				$this->log(
					sprintf(
						__( 'Customer has not selected a location at the cart level! Unable to find SLW data for product %s', 'easycms-wp' ),
						$product->get_name()
					),
					'debug'
				);
			}
		} else {
			$this->log(
				sprintf(
					__( 'Product %s does not have stock locations', 'easycms-wp' ),
					$product->get_name()
				),
				'warning'
			);
		}

		return $product_data;
	}

	public function register_api() {
		register_rest_route( self::API_BASE, $this->get_module_name(), array(
			'methods' => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_add_location' ),
			'args'                => array(
				'location_id'               => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
				'email'                     => array(
					'sanitize_callback' => 'sanitize_email',
					'required'          => true,
				),
				'name'                      => array(
					'sanitize_callback' => 'sanitize_text_field',
					'required'          => true,
				),
				'locationNumber'            => array(
					'sanitize_callback' => 'sanitize_text_field',
					'required'          => true,
				),
				'cr_location_id'            => array(
					'sanitize_callback' => 'sanitize_text_field',
					'required'          => true,
				),
			)
		));
	}

	public function insert_product_stock( \WC_Product $product, array $product_data ) {	
		$response = false;
		if ( ! empty( $product_data['stock_data'] ) ) {
			$this->stock_data = array();
			$total_quantity = 0;
			foreach ( $product_data['stock_data'] as $stock ) {

				#### incase we did not pass the stock data correctly as an array, make sure we do that!
				### wrong example:
				### [stock_data] => Array
                ### (
                ###     [log_id] => 22
                ###     [pid] => 11834
                ###     [location_id] => 9
                ###     [shelf_id] => 65
                ###     [stock] => 100
                ### )
                ### Correct example:
				### [stock_data] => Array
                ### (
                ### 	Array(
                ###     	[log_id] => 22
                ###     	[pid] => 11834
                ###     	[location_id] => 9
                ###     	[shelf_id] => 65
                ###     	[stock] => 100
				###		)
                ### )

				if(!is_array($stock)){
					$this->log(
						sprintf(
							__(
								'Error adding stock the product.
								 make sure to pass an array into API data for stock_data, each location must have own array!, here is what you passed in: (%s)',
								'easycms-wp'
							),
							json_encode($stock)
						),
						'warning'
					);
					continue;
				}

				$term = $this->get_term( $stock['location_id'] );
				if ( ! $term ) {
					$this->log(
						sprintf(
							__(
								'Error adding stock the product.
								 Cannot find stock location linked to location_id (%s).
								 Skipping... HERE IS FULL DATA (%s)',
								'easycms-wp'
							),
							$stock['location_id'],
							json_encode($stock)
						),
						'warning'
					);
					continue;
				}

				$this->stock_data[ $term->term_id ] = intval( $stock['stock'] );
				$total_quantity += $this->stock_data[ $term->term_id ];

				$product->update_meta_data( sprintf( '_stock_at_%d', $term->term_id ), $this->stock_data[ $term->term_id ] );

				if(!$response){// do not update if _slw_default_location was already set in the loop!
					$this_wp_prd_id = $product->get_id();
					if(!empty($this_wp_prd_id) && isset($stock['default_location']) && !empty($stock['default_location'])){
		                // save product default location
		                $response  = update_post_meta( $this_wp_prd_id, '_slw_default_location', $term->term_id );
					}
                }

			}

			$product->set_manage_stock( true );
			$product->set_stock_quantity( $total_quantity );
			// $product->set_category_ids( array_keys( $stock_data ) );
		}

		return $product;
	}

	// public function insert_product_stock_after( \WC_Product $product, array $product_data ) {	
	// 	$response = false;
	// 	if ( ! empty( $product_data['stock_data'] ) ) {
	// 		$this->stock_data = array();
	// 		foreach ( $product_data['stock_data'] as $stock ) {
	// 			$term = $this->get_term( $stock['location_id'] );
	// 			if ( ! $term ) {
	// 				$this->log(
	// 					sprintf(
	// 						__(
	// 							'Error adding stock default location to the product.
	// 							 Cannot find stock location linked to location_id (%s).
	// 							 Skipping...',
	// 							'easycms-wp'
	// 						),
	// 						$stock['location_id']
	// 					),
	// 					'warning'
	// 				);
	// 				continue;
	// 			}

	// 			if(!$response){// do not update if _slw_default_location was already set in the loop!
	// 				$this_wp_prd_id = $product->get_id();
	// 				if(!empty($this_wp_prd_id) && isset($stock['default_location']) && !empty($stock['default_location'])){
	// 	                // save product default location
	// 	                $response  = update_post_meta( $this_wp_prd_id, '_slw_default_location', $term->term_id );
	// 				}
	// 			}

	// 		}
	// 	}
	// 	return $product;
	// }

	public function set_object_terms( \WC_Product $product ) {
		if ( isset( $this->stock_data ) ) {
			wp_set_object_terms( $product->get_id(), array_keys( $this->stock_data ), $this->taxonomy );
		}
	}

	public function rest_add_location( \WP_REST_Request $request ) {
		$term_id = $this->insert_location( $request['location_id'], $request['name'], $request['email'], $request->get_params() );

		if ( $term_id ) {
			return $this->rest_response( $term_id );
		}

		return $this->rest_response( '', 'FAIL', 400 );
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

	private function get_locations() {
		$req = $this->make_request( '/get_locations' );
		if ( is_wp_error( $req ) ) {
			$this->log(
				sprintf(
					__( 'Fetching stock locations failed: %s. Number of retries: (%d)', 'easycms-wp' ),
					$req->get_error_message(),
					$this->set_pending()
				),
				'error'
			);

			return false;
		}

		$this->log( __( 'Parsing JSON response', 'easycms-wp' ), 'info' );

		$data = json_decode( $req );

		if ( isset( $data->OUTPUT ) ) {
			$this->clear_pending();

			return $data->OUTPUT;
		} else {
			$this->log( __( 'Received an invalid response format from server', 'easycms-wp' ), 'error' );
		}
	}

	public function get_term_meta_name() {
		return 'easycms_wp_location_id';
	}

	public function get_term( int $location_id ) {
		$args = array(
			'taxonomy'   => $this->taxonomy,
			'hide_empty' => false,
			'number'     => 1,
			'meta_query' => array(
				array(
					'key'   => $this->get_term_meta_name(),
					'value' => $location_id,
				),
			),
		);

		$query = new \WP_Term_Query( $args );
		if ( $query->get_terms() ) {
			$terms = $query->get_terms();
			return $terms[0];
		}

		return false;
	}

	public function insert_location( int $location_id, string $name, string $email, array $data ) {
		if ( $location_id ) {
			$existing_term = $this->get_term( $location_id );

			if ( $existing_term ) {
				$this->log(
					sprintf(
						__( 'Updating stock location (%s) with location_id (%d)', 'easycms-wp' ),
						$name,
						$location_id
					),
					'info'
				);

				$term = wp_update_term( $existing_term->term_id, 'location', array( 'name' => $name ) );
			} else {
				$this->log(
					sprintf(
						__( 'Creating stock location (%s) with location_id (%d)', 'easycms-wp' ),
						$name,
						$location_id
					),
					'info'
				);
				$term = wp_insert_term( $name, $this->taxonomy );
			}
			
			if ( is_wp_error( $term ) ) {
				if ( ! $existing_term ) {
					$this->log(
						sprintf(
							__( 'Failed to create stock location (%s) with location_id (%d): %s', 'easycms-wp' ),
							$name,
							$location_id,
							$term->get_error_message()
						),
						'error'
					);
				} else {
					$this->log(
						sprintf(
							__( 'Failed to update stock location (%s) with location_id (%d): %s', 'easycms-wp' ),
							$name,
							$location_id,
							$term->get_error_message()
						),
						'error'
					);
				}

				return false;
			}

			if ( ! $existing_term ) {
				update_term_meta( $term['term_id'], 'slw_default_location', 0 );
				update_term_meta( $term['term_id'], 'slw_backorder_location', 0 );
				update_term_meta( $term['term_id'], 'slw_auto_allocate', 0 );
				update_term_meta( $term['term_id'], 'slw_location_priority', 0 );

				$this->log(
					__( 'Stock location created successfully', 'easycms-wp' ),
					'info'
				);
			}

			update_term_meta( $term['term_id'], 'slw_location_email', sanitize_email( $email ) );
			update_term_meta( $term['term_id'], $this->get_term_meta_name(), $location_id );
			update_term_meta( $term['term_id'], 'easycms_wp_data', $data );

			if ( $existing_term ) {
				$this->log(
					__( 'Stock location updated successfully', 'easycms-wp' ),
					'info'
				);
			}

			return $term['term_id'];
		}
	}
}
?>