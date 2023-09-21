<?php
namespace EasyCMS_WP\Component;

use \EasyCMS_WP\Log;
use \EasyCMS_WP\Util;

class Order extends \EasyCMS_WP\Template\Component {
	private $order_status_map = array(
		'pending'    => 1,
		'processing' => [ 10, 110],
		'on-hold'    => 1,
		'completed'  => 5,
		'cancelled'  => -1,
		'refunded'   => -1,
		'failed'     => -1
	);

	private $updated_time = 0;

	public function hooks() {
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'create_order' ) );
		add_action( 'woocommerce_update_order', array( $this, 'create_order' ), 10, 1 );
		add_action( 'rest_api_init', array( $this, 'register_api' ) );
		add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', array( $this, 'handle_custom_order_filter' ), 10, 2 );
	}

	public function fail_safe() {

	}

	public function get_wc_status( $cms_status ) {
		foreach ( $this->order_status_map as $status => $cms ) {
			if ( ( ! is_array( $cms ) && $cms == $cms_status ) || ( is_array( $cms ) && in_array( $cms_status, $cms ) ) ) {
				return $status;
			}
		}

		return 'pending';
	}

	public function register_api() {
		register_rest_route( self::API_BASE, $this->get_module_name(), array(
			'methods' => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_create_order' ),
			'args'                => array(
				'id'               => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
				'order_date'                     => array(
					// 'validate_callback' => array( $this, 'rest_validate_number' ),
					'sanitize_callback' => 'sanitize_text_field',
					'required'          => true,
				),
				'phone_full'             => array(
					'validate_callback' => array( $this, 'rest_validate_number' ),
					'required'          => true,
				),
				'email'                 => array(
					'validate_callback' => array( $this, 'rest_validate_email' ),
					'sanitize_callback' => 'sanitize_email',
					'required'          => true,
				),
				'note'                 => array(
					'required'          => false,
				),
				'status'                 => array(
					'validate_callback' => array( $this, 'rest_validate_number' ),
					'sanitize_callback' => array( $this, 'rest_sanitize_int' ),
					'required'          => true,
				),
				'customer_id'                 => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
				'order_lines'                 => array(
					'validate_callback' => array( $this, 'rest_validate_array' ),
					'required'          => true,
				),
			)
		));

		register_rest_route( self::API_BASE, $this->get_module_name() . '/delete', array(
			'methods' => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_delete_order' ),
			'args'                => array(
				'id'               => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
			)
		));
	}

	public function rest_delete_order( \WP_Rest_Request $request ) {
		$wc_order = $this->get_order_by_id( $request['id'] );

		if ( ! $wc_order ) {
			return $this->rest_response( __( 'Order not found', 'easycms-wp' ), 'FAIL', 404 );
		}

		$wc_order->delete( true );
		$this->log(
			sprintf(
				__( 'Order CMS ID %d deleted successfully', 'easycms-wp' ),
				$request['id']
			),
			'info'
		);

		return $this->rest_response( __( 'Order deleted successfully', 'easycms-wp' ) );
	}

	public function rest_create_order( \WP_REST_Request $request ) {
		$params = $request->get_params();

		remove_action( 'woocommerce_update_order', array( $this, 'create_order' ), 10, 1 );

		try {
			$wc_order = $this->prepare_order_params( $params );

			if ( $wc_order ) {
				$wc_order->calculate_totals();
				$wc_order->add_order_note( __( 'Order created from CMS', 'easycms-wp' ) );
				$this->log(
					sprintf(
						__( 'Order #%d created/updated successfully from CMS', 'easycms-wp' ),
						$wc_order->get_id()
					),
					'info'
				);

				$data = $wc_order->get_meta( 'easycms_wp_data', true );
				if ( is_array( $data ) ) {
					$data = array_replace_recursive( $data, $params );
				}
				$wc_order->update_meta_data( 'easycms_wp_id', $params['id'] );
				$wc_order->update_meta_data( 'easycms_wp_data', $data );
				$wc_order->save_meta_data();

				return $this->rest_response(
					array(
						'id' => $wc_order->get_id(),
						'msg' => __( 'Order created/updated successfully', 'easycms-wp' )
					)
				);
			}
		} catch ( \Exception $e ) {
			$this->log(
				sprintf(
					__( 'Error creating order: %s', 'easycms-wp' ),
					$e->getMessage()
				),
				'error'
			);

			return $this->rest_response( __( 'Error creating order', 'easycms-wp' ), 'FAIL' );
		} finally {
			add_action( 'woocommerce_update_order', array( $this, 'create_order' ), 10, 1 );
		}
	}

	public function prepare_order_params( array $params ) {
		$wc_order = $this->get_order_by_id( $params['id'] );
		$is_updating = false;

		if ( ! $wc_order ) {
			$this->log(
				sprintf(
					__( 'Creating new order from CMS: %d', 'easycms-wp' ),
					$params['id']
				),
				'info'
			);

			$wc_order = wc_create_order();
		} else {
			$is_updating = true;
			$this->log(
				sprintf(
					__( 'Updating WC Order %d from CMS %d', 'easycms-wp' ),
					$wc_order->get_id(),
					$params['id']
				),
				'info'
			);
		}

		if ( ! empty( $params['order_date'] ) ) {
			$wc_order->set_date_created( strtotime( $params['order_date'] ) );
		}

		$wc_order->set_status( $this->get_wc_status( $params['status'] ) );

		if ( ! empty( $params['note'] ) ) {
			$wc_order->add_order_note( $params['note'] );
		}
		$wc_order = apply_filters( 'easycms_wp_prepare_order_params', $wc_order, $params, $is_updating );

		if ( null === $wc_order ) {
			$this->log(
				__( 'Error while setting order parameters', 'easycms-wp' ),
				'error'
			);
		} else if ( $is_updating ) {
			$wc_order->save();
		}

		return $wc_order;
	}

	public function handle_custom_order_filter( $query, $query_vars ) {
		if ( ! empty( $query_vars['meta_query'] ) ) {
			$query['meta_query'] = $query_vars['meta_query'];
		}

		return $query;
	}

	public function get_order_by_id( int $cms_id ) {
		$orders = wc_get_orders(array(
			'limit'       => 1,
			'meta_query'  => array(
				array(
					'key' => 'easycms_wp_id',
					'value' => $cms_id,
				),
			),
		));

		return $orders ? $orders[0] : false;
	}

	public function get_matching_cms_status( string $wc_status ) {
		if ( isset( $this->order_status_map[ $wc_status ] ) ) {
			if ( is_array( $this->order_status_map[ $wc_status ] ) ) {
				return current( $this->order_status_map[ $wc_status ] );
			}

			return $this->order_status_map[ $wc_status ];
		}

		return 0;
	}

	public function get_payment_status( string $wc_status ) {
		$payment_status = -1;

		switch ( $wc_status ) {
			case 'processing':
				$payment_status = 2;
				break;
			case 'on-hold':
				$payment_status = 3;
				break;
			case 'completed':
				$payment_status = 2;
				break;
		}

		return $payment_status;
	}

	private function prepare_order_request( \WC_Order $wc_order ) {

		$order_date = $wc_order->get_date_completed();
		$paid_date = $wc_order->get_date_paid();
		$api_config = $this->parent->get_config( 'api' );
		$easycms_id = $wc_order->get_meta( 'easycms_wp_id', true );
		$payload    = $wc_order->get_meta( 'easycms_wp_data', true );

		$this->log(
			sprintf( __( 'Preparing order data for WC_Order ID: %d to CMS', 'easycms-wp' ), $wc_order->get_id() ),
			'info'
		);


		// echo "<pre>";
		// print_r(" EASYCMS ID: =======================");
		// print_r($easycms_id);

		// print_r("get_shipping_method=======================");
		// print_r($wc_order->get_shipping_method());
		// print_r("\nget_shipping_tax=======================");
		// print_r($wc_order-> get_shipping_tax());
		// print_r("\nget_shipping_to_display=======================");
		// print_r($wc_order-> get_shipping_to_display());
		// print_r("\nget_shipping_total=======================");
		// print_r($wc_order-> get_shipping_total());
		// print_r("\nPayload > order_lines =======================");
		// print_r($post_data['order_lines']);
		// print_r("\nPayload > get_items_tax_classes =======================");
		// print_r($wc_order->get_items_tax_classes());
		// print_r("\nPayload > get_items_tax_classes =======================");
		// print_r($wc_order->get_taxes());
		
		// print_r("\nPayload > VAT PERCENTAGE =======================");
		// print_r($this->vat_percent_from_gross_and_net(($wc_order->get_shipping_tax() + $wc_order->get_shipping_total()), $wc_order->get_shipping_total()));

		
		$shipping_vat_percentage = $this->vat_percent_from_gross_and_net(($wc_order->get_shipping_tax() + $wc_order->get_shipping_total()), $wc_order->get_shipping_total());

		switch($shipping_vat_percentage){
			case 0:
				$shipping_vat_id = 1;// 0% 3000
			break;
			case 14:
				$shipping_vat_id = 2;// 14% 3001
			break;
			default:
				$shipping_vat_id = 3;// 24% 3002
		}

		// echo "<pre>";
		// print_r("\nPayload > shipping_vat_percentage =======================");
		// print_r($shipping_vat_percentage);
		// print_r("\nPayload > shipping_vat_id =======================");
		// print_r($shipping_vat_id);
		// exit();

		$post_data = array(
			'no_vat'     => 0,
			'sale_price_by_net_margin' => 0,
			'sale_price_by_net_margin_percentage' => 0,
			'ignore_customer_pricing'             => 0,
			'order_date'                          => $order_date ? $order_date->date( 'Y-m-d H:i:s' ) : date( 'Y-m-d H:i:s' ),
			'shipping_method'                     => 1,
			'payment_method'                      => $wc_order->get_payment_method(),
			'delivery_date'                       => date( 'Y-m-d H:i:s' ),
			'shipping_address'                    => $wc_order->get_formatted_shipping_address(),
			'shipping_postal'                     => $wc_order->get_shipping_postcode(),
			'shipping_city_id'                    => 0,
			'shipping_country_id'                 => 0,
			'discount'                            => 0,
			'paid_date'                           => $paid_date ? $paid_date->date( 'Y-m-d H:i:s' ) : '',
			'admin_id'                            => $api_config['admin_id'],
			'loading_employee_id'                 => 0,
			'loading_employee_name'               => 0,
			'delivery_employee_id'                => 0,
			'delivery_employee_name'              => '',
			'payer_details'                       => '',
			'payee_details'                       => '',
			'note'                                => $wc_order->get_customer_note(),
			'delivery_instructions'               => '',
			'order_by'                            => 'b2c_webshop_order',
			'order_lines'                         => array(),
			'currency_rate'                       => 1,
			'currency_country_id'                 => 40,
			'calculate_line_pricing_internally'   => 1,
			'status'                              => $this->get_matching_cms_status( $wc_order->get_status() ),
			'payment_status'                      => $this->get_payment_status( $wc_order->get_status() ),
		);


		// exit();

		$post_data = apply_filters( 'easycms_wp_prepare_order_request', $post_data, $wc_order, $this );
		if ( $easycms_id ) {

			// echo "<pre>";
			// echo "\n==========  easycms_id case =====================";
			// print_r($easycms_id);
			// echo "\n====================================================";

			$post_data['order_id'] = $easycms_id;

			foreach ( $payload['order_lines'] as $index => $order_line ) {
				$found = false;

				foreach ( $post_data['order_lines'] as $new_order_line ) {
					if ( $new_order_line['order_line_id'] == $order_line['order_line_id'] ) {
						$found = true;
						break;
					}
				}

				if ( ! $found ) {
					$order_line['deleted'] = 1;
					$post_data['order_lines'][] = $order_line;
				}
			}



		}else{
			// echo "<pre>";
			// echo "\n==========  NO easycms_id case =====================";
			// echo "\n====================================================";
			$post_data['order_lines'][] = array(
				#### here we need to loop through each language from CMS and create product name for those languages
				'product_name' => array(
					'en_gb' => $wc_order->get_shipping_method(),
					'fi' => $wc_order->get_shipping_method(),
					'zh' => $wc_order->get_shipping_method(),
				),
				'pid' => 0,
				'prdNumber' => 0,
				'order_line_id' => 0,
				'line_type' => 0,
				'stock_type_id' => 40,
				'stock_type_name' => array(
					'en_gb' => 'pcs',
					'fi' => 'kpl',
					'zh' => 'pcs',
				),
				'stock_available' => 0,
				'vat_id' => $shipping_vat_id,
				'vat_percent' => $shipping_vat_percentage,
				'unit_price' => $wc_order->get_shipping_total(),
				'unit_price_buy' => 0,
				'ignore_customer_pricing' => 0,
				'customer_pricing_ignored' => 0,
				'vat_id_buy' => 0,
				'quantity' => 1,
				'vat_id_buy' => 0,
				'vat_percent_buy' => 0,
				'symbol' => '€',
				'stock_type_measure' => 0,
				'number' => 1,
				'weight' => 0,
				'weight_type' => 'kg',
				'pallet_qty' => 0,
				'box_qty' => 0,
				'relation_qty_type' => 'unit',
				'stock_alert_qty' => 3,
				'stock_alert_bbd' => 100,
				'pallet_qty_type' => 'box',
				'stock_available_now' => 0,
				'customer_pricing_exist' => 0,
				'category_name' => array(
					'en_gb' => 'Shipping',
					'fi' => 'Toimitus',
					'zh' => 'Shipping',
				),
				'barcode' => 1,
				'barcode_id' => 6,
				'barcode_type' => false,
				'discount' => false,
				'best_before_date' => '2052-02-15',
				'location_id' => '9-62',
				'location' => 'v 1',
				'line_note' => $wc_order->get_shipping_to_display(),
				'supplier_id' => 0,
				'customer_id' => 0,### WHERE TO GET THIS???
				'deleted' => 0,
			);
		}


		return $post_data;
	}

	public function create_order( $order ) {
		$order = new \WC_Order( $order );

		$request = $this->prepare_order_request( $order );

		// echo "<pre>";
		// echo "\n REQUEST IS NOT EMPTY!!! ============================================";
		// print_r($request);
		// echo "\n====================================================";
		// exit();

		if ( $request ) {
			if ( ! empty( $request['order_id'] ) ) {
				$this->log(
					sprintf( __( 'Updating WC_Order ID %d to CMS', 'easycms-wp' ), $order->get_id() ),
					'info'
				);
				// echo "\n=========== REQUEST IS NOT EMPTY > ORDER ID NOT EMPTY: ".$request['order_id']."===============================";

				$req = $this->make_request( '/set_order_update', 'POST', $request );
			} else {
				$this->log(
					sprintf( __( 'Creating WC_Order ID %d to CMS', 'easycms-wp' ), $order->get_id() ),
					'info'
				);
				// echo "\n=========== REQUEST IS NOT EMPTY > ORDER ID IS EMPTY: ===============================";
				$req = $this->make_request( '/set_order', 'POST', $request );
			}

			if ( ! is_wp_error( $req ) ) {
				$response_data = json_decode( $req, true );
				if ( ! $response_data ) {
					$this->log(
						sprintf( __( 'Unable to decode order JSON payload, here is the CMS server response (%s)', 'easycms-wp' ), 
							$req 
						),
						'error'
					);
				} else {
					if ( ! empty( $response_data['OUTPUT']['id'] ) ) {
						remove_action( 'woocommerce_update_order', array( $this, 'create_order' ), 10, 1 );

						$order->update_meta_data( 'easycms_wp_id', $response_data['OUTPUT']['id'] );
						$order->update_meta_data( 'easycms_wp_data', $response_data['OUTPUT'] );
						$order->save_meta_data();
						$order->add_order_note( __( 'Order successfully updated to CMS', 'easycms-wp' ) );
						$this->save_orderline_meta( $order->get_items(), $response_data['OUTPUT'] );

						add_action( 'woocommerce_update_order', array( $this, 'create_order' ), 10, 1 );
						do_action( 'easycms_wp_order_pushed',  $order );

						$this->log(
							sprintf(
								__( 'Order ID %d successfully pushed to CMS.', 'easycms-wp' ),
								$order->get_id()
							),
							'info'
						);
					} else {
						$this->log(
							sprintf(
								__( 'Error pushing order to CMS: %s', 'easycms-wp' ),
								$req
							),
							'error'
						);
					}
				}
			} else {
				$this->log(
					sprintf(
						__( 'Error pushing order to CMS: %s', 'easycms-wp' ),
						$req->get_error_message()
					),
					'error'
				);
			}
		} else {
			$this->log(
				sprintf(
					__( 'No request data was provided to push order id (%d) to CMS', 'easycms-wp' ),
					$order->get_id()
				),
				'error'
			);
		}
	}

	private function save_orderline_meta( array $items, array $payload ) {
		if ( ! $payload ) {
			return;
		}

		$index = 0;
		foreach ( $items as $order_item ) {
			$order_item->update_meta_data( 'easycms_wp_orderline_data', $payload['order_lines'][ $index ] );
			$order_item->save_meta_data();
			$index++;
		}
	}

	private function vat_percent_from_gross_and_net($gross, $net, $decimals=2){ 
		### returns vat percentage from net price and vat amount
		return $vat_percentage = round(((100 * $gross)/$net) - 100, $decimals);
	}

}
?>