<?php
namespace EasyCMS_WP\Component;

use \EasyCMS_WP\Log;
use \EasyCMS_WP\Util;

class Product extends \EasyCMS_WP\Template\Component {
	private $productSaveActions;

	public function __construct( \EasyCMS_WP\EasyCMS_WP $parent, int $priority = 10 ) {
		global $woocommerce_wpml, $wpdb, $sitepress;

		parent::__construct( $parent, $priority );

		$this->productSaveActions = new \WCML\Rest\ProductSaveActions(
			$sitepress->get_settings(),
			$wpdb,
			$sitepress,
			$woocommerce_wpml
		);
	}

	public function hooks() {
		add_action( 'rest_api_init', array( $this, 'register_api' ) );

		// See - https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query#adding-custom-parameter-support
		add_filter(
			'woocommerce_product_data_store_cpt_get_products_query',
			array( $this, 'add_wc_custom_field_query' ),
			10,
			2
		);

		add_filter( 'easycms_wp_prepare_order_request', array( $this, 'update_wc_order' ), 10, 2 );
		add_filter( 'easycms_wp_prepare_order_params', array( $this, 'add_new_order_products' ), 10, 3 );
	}

	public function add_new_order_products( $wc_order, $params, $is_updating ) {
		if ( null !== $wc_order ) {
			$this->log(
				__( 'Adding order line items', 'easycms-wp' ),
				'info'
			);
			if ( ! empty( $params['order_lines'] ) ) {
				foreach ( $params['order_lines'] as $item ) {
					if ( $is_updating ) {
						$existing_item = $this->get_order_item_by_order_line_id( $item['order_line_id'], $wc_order );
						if ( $item['deleted'] && $existing_item ) {
							$this->log(
								sprintf(
									__( 'Deleting Order line item: %s', 'easycms-wp' ),
									$existing_item->get_name()
								),
								'info'
							);

							$wc_order->remove_item( $existing_item->get_id() );
						}

						if ( $existing_item ) {
							continue;
						}
					}

					if ( ! empty( $item['deleted'] ) ) {
						continue;
					}

					$product_id = $this->get_products_by_pid( $item['pid'] );
					if ( ! $product_id ) {
						$this->log(
							sprintf(
								__( 'Product pid %d not found on WP. Aborting...', 'easycms-wp' ),
								$item['pid']
							),
							'error'
						);

						if ( ! $is_updating ) {
							$wc_order->delete();
						}
						$wc_order = null;
						break;
					}

					$product = wc_get_product( $product_id[0] );

					$this->log(
						sprintf(
							__( 'Adding product %s to order #%d', 'easycms-wp' ),
							$product->get_name(),
							$wc_order->get_id()
						),
						'info'
					);

					$order_item_id = $wc_order->add_product( $product, $item['quantity'] );

					$wc_order_item = new \WC_Order_Item_Product( $order_item_id ); // wc_get_product( $order_item_id );
					$wc_order_item->update_meta_data( 'easycms_wp_orderline_data', $item );
					$wc_order_item->save_meta_data();
					do_action( 'easycms_wp_order_create_item', $wc_order_item, $item, $params, $wc_order );
				}
			} else {
				if ( ! $is_updating )
					$wc_order->delete();
				$wc_order = null;
				$this->log(
					__( 'order_lines is empty or not provided. Aborting...', 'easycms-wp' ),
					'info'
				);
			}
		}

		return $wc_order;
	}

	public function get_order_item_by_order_line_id( $order_line_id, $wc_order ) {
		$items = $wc_order->get_items();
		$ret = false;

		if ( $items ) {
			foreach ( $items as $wc_order_item ) {
				$data = $wc_order_item->get_meta( 'easycms_wp_orderline_data', true );
				if ( $data && $data['order_line_id'] == $order_line_id ) {
					$ret = $wc_order_item;
					break;
				}
			}
		}

		return $ret;
	}

	public function update_wc_order( $post_data, \WC_Order $wc_order ) {
		if ( null === $post_data ) {
			// Has error
			return $post_data;
		}

		$products = $wc_order->get_items();
		if ( $products ) {
			foreach ( $products as $wc_order_item ) {
				$pid = Util::get_product_pid( $wc_order_item->get_product_id() );
				$payload_data = $wc_order_item->get_meta( 'easycms_wp_orderline_data', true );

				if ( ! $pid ) {
					$this->log(
						sprintf(
							__( 'Error: The product (%d:%s) is not synced with CMS', 'easycms-wp' ),
							$wc_order_item->get_product_id(),
							$wc_order_item->get_name()
						),
						'error'
					);

					$post_data = null;
					break;
				}

				$product_data = $this->get_product_data( $wc_order_item->get_product_id() );

				if ( ! $product_data ) {
					$this->log(
						sprintf(
							__(
								'Error setting order product item: No product data available for product %s',
								'easycms-wp'
							),
							$wc_order_item->get_product_id()
						),
						'error'
					);

					$post_data = null;
					break;
				}

				foreach ( $product_data['product_name'] as $lang => $name ) {
					if ( 'en' == $lang ) {
						$product_data['product_name']['en_us'] = $name;
						$product_data['product_name']['en_gb'] = $name;
						unset( $product_data['product_name']['en'] );
					}

					if ( 'fa' == $lang ) {
						$product_data['product_name']['fa_ir'] = $name;
						$product_data['product_name']['fa_af'] = $name;
						unset( $product_data['product_name']['fa'] );
					}

					if ( 'zh-hant' == $lang ) {
						$product_data['product_name']['zh'] = $name;
						$product_data['product_name']['zh'] = $name;
						unset( $product_data['product_name']['zh-hant'] );
					}

					if ( 'zh-hans' == $lang ) {
						$product_data['product_name']['zh'] = $name;
						$product_data['product_name']['zh'] = $name;
						unset( $product_data['product_name']['zh-hans'] );
					}
				}

				$product_data['best_before_date'] = date( 'Y-m-d' );
				$product_data['line_type'] = 0;
				$product_data['number'] = 1;
				$product_data['vat_id'] = $product_data['vat'];
				$product_data['vat_id_buy'] = $product_data['vat_buy'];
				$product_data['unit_price'] = $product_data['price'];
				$product_data['unit_price_buy'] = $product_data['price_buy'];
				$product_data['customer_pricing_exist'] = '';
				$product_data['customer_pricing_ignored'] = 0;
				$product_data['stock_type_measure'] = 1;
				$product_data['quantity'] = $wc_order_item->get_quantity();
				$product_data['barcode_type'] = $product_data['barcode_id'];
				$product_data['line_note'] = $wc_order->get_customer_note();
				$product_data['symbol'] = '€';
				if ( ! empty( $payload_data ) ) {
					$product_data['order_line_id'] = $payload_data['order_line_id'];
				}

				$product_data = apply_filters(
					'easycms_wp_set_order_item_data',
					$product_data,
					$wc_order_item->get_product(),
					$wc_order_item,
					$wc_order
				);

				if ( null === $product_data ) {
					$post_data = null;
					break;
				}

				$post_data['order_lines'][] = $product_data;
			}
		}

		return $post_data;
	}

	public function get_product_data( int $product_id ) {
		$product = wc_get_product( $product_id );
		if ( $product ) {
			return apply_filters( 'easycms_wp_get_product_data', $product->get_meta( 'easycms_data', true ), $product );
		}

		return false;
	}

	public function fail_safe() {

	}

	public function sync() {
		ignore_user_abort(true);
		set_time_limit(0); // Infinite execution time

		if ($this->is_syncing()) {
			$this->log(__('sync()::Sync already running. Cannot start another', 'easycms-wp'), 'error');
			return;
		}

		if (EASYCMS_WP_DEBUG) {
			$this->log(__('sync()::This is in DEBUG mode. Deleting all synced product to re-add', 'easycms-wp'), 'info');

			$args = [
				'meta_query' => [
					[
						'key'     => 'easycms_pid',
						'value'   => '',
						'compare' => 'EXISTS',
					]
				],
				'offset' => 0,
			];

			while ($products = $this->get_products(50, $args)) {
				foreach ($products as $pid) {
					wp_delete_post($pid, true);
				}
			}
		}

		$pids = $this->get_synced_pids();

		$this->log(__('sync()::Start running sync.', 'easycms-wp'), 'info');

		$this->set_sync_status(true);
		$this->log(__('sync()::===SYNC STARTED===', 'easycms-wp'), 'info');

		$this->fetch_products($pids, 1, 10);

		$this->set_sync_status(false);
		$this->log(__('sync()::===SYNC ENDED===', 'easycms-wp'), 'info');
	}

	protected function fetch_products($pids, $page, $limit) {
		$response = $this->make_request('/get_products', 'POST', [
			'NOT_IN' => $pids,
			'start'  => ($page - 1) * $limit,
			'limit'  => $limit,
		]);

		if($page > 1) {
			// return; // this is for development purposes to only allow 1 page to do debugging!
		}

		$this->log(__('fetch_products:: is_syncing is set to: '.($this->is_syncing()===false ? false : ('"'.$this->is_syncing().'"') ), 'easycms-wp'), 'info');

		if (!$this->is_syncing()) {
			$this->log(__('fetch_products::Sync already stopped. Terminated.', 'easycms-wp'), 'error');
			return;
		}

		// $this->log(sprintf(__('fetch_products:: fetched data is %s', 'easycms-wp'), json_encode($response)), 'info');

		if (is_wp_error($response)) {
			$this->log(__('fetch_products:: Fetch error from API. Terminated.', 'easycms-wp'), 'error');
			return;
		}

		$this->log(__('fetch_products:: JSON payload gotten. Proceeding to process', 'easycms-wp'), 'info');

		$data = json_decode($response, true);
		if (!$data || !is_array($data)) {
			$this->log(sprintf(__('fetch_products:: Sync failed and terminated: Unable to parse JSON payload on page %d and offset %d', 'easycms-wp'), $page, ($page - 1) * $limit), 'error');
			return;
		}

		if (empty($data['OUTPUT'])) {
			$this->log(__('fetch_products:: No data in OUTPUT param. Terminating.', 'easycms-wp'), 'info');
			return;
		}

		$this->log(__('fetch_products:: JSON payload parsed successfully. Proceeding to iterate and add', 'easycms-wp'), 'info');

		if (!empty($data['INFO'])) {
			$info = $data['INFO'];
			$this->log(sprintf(__('fetch_products:: Sync process INFO JSON payload response for page %d and offset %d , COUNT: %d , START: %d , LIMIT: %d , TOTAL COUNT: %d ', 'easycms-wp'),
				$page,
				($page - 1) * $limit,
				$info['count'],
				$info['start'],
				$info['limit'],
				$info['total_count']
			), 'info');
		}

		foreach ($data['OUTPUT'] as $product_data) {
			$product_data = $this->prepare_product_data($product_data);
			$this->log(sprintf(__('fetch_products:: fetch_products::product_data (%s)', 'easycms-wp'), json_encode($product_data)), 'debug');
			$this->create_product($product_data);

			// Clear references to avoid memory leaks
			unset($product_data);			
		}

		$total_count = isset($data['INFO']['total_count']) ? (int) $data['INFO']['total_count'] : 0;
		$next_offset = $page * $limit;

		unset($response, $data);

		if ($total_count > $next_offset) {
			$this->log(sprintf(__('fetch_products:: Making request for page %d start %d limit %d', 'easycms-wp'), $page + 1, $next_offset, $limit), 'info');
			$this->fetch_products($pids, $page + 1, $limit); // Recursive call
		}
	}

	public static function can_run() {
		if (
			! class_exists( 'WooCommerce' ) ||
			! class_exists( 'woocommerce_wpml' ) ||
			! $GLOBALS['woocommerce_wpml']->dependencies_are_ok
		) {
			// Log::log(
			// 	'product',
			// 	__(
			// 		'This component depends on WooCommerce, WooCommerce WPML & dependencies to run',
			// 		'easycms-wp'
			// 	),
			// 	'error'
			// );

			add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
			return false;
		}

		return true;
	}

	public static function dependency_notice() {
		$class = 'notice notice-error';

		if ( ! class_exists( 'WooCommerce' ) ) {
			$message = __( 'EasyCMS Product component depends on WooCommerce which is not installed/setup', 'easycms-wp' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}

		if ( ! class_exists( 'woocommerce_wpml' ) ) {
			$message = __( 'EasyCMS Product component depends on WooCommerce WPML which is not installed', 'easycms-wp' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}

		if ( ! $GLOBALS['woocommerce_wpml']->dependencies_are_ok ) {
			$message = __( 'EasyCMS Product component depends on WooCommerce WPML dependencies and proper setup', 'easycms-wp' );
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
	}

	public function register_api() {
		register_rest_route( self::API_BASE, 'product/', array(
				'methods' => 'POST',
				'permission_callback' => array( $this, 'rest_check_auth' ),
				'callback'            => array( $this, 'rest_add_product' ),
				'args'                => array(
					'product_name'        => array(
						'validate_callback' => array( $this, 'rest_validate_array' ),
						'required'          => true,
					),
					'pid'         => array(
						'validate_callback' => array( $this, 'rest_validate_id' ),
						'sanitize_callback' => 'absint',
						'required'          => true,
					),
					'status'         => array(
						'validate_callback' => array( $this, 'rest_validate_id' ),
						'sanitize_callback' => 'absint',
						'required'          => false,
					),
					'prd_start_display'     => array(
						'validate_callback' => array( $this, 'rest_validate_id' ),
						'sanitize_callback' => 'absint',
						'required'          => false,
					),
					'prd_end_display'     => array(
						'validate_callback' => array( $this, 'rest_validate_id' ),
						'sanitize_callback' => 'absint',
						'required'          => false,
					),
					'product_desc'        => array(
						'validate_callback' => array( $this, 'rest_validate_array' ),
						'required'          => false,
					),
					'price'       => array(
						'validate_callback' => array( $this, 'rest_validate_number' ),
						'required'          => true,
						'sanitize_callback' => array( $this, 'rest_sanitize_price' ),
					),
					'price_buy'       => array(
						'validate_callback' => array( $this, 'rest_validate_number' ),
						'required'          => false,
						'sanitize_callback' => array( $this, 'rest_sanitize_price' ),
					),
					'height'          => array(
						'validate_callback' => array( $this, 'rest_validate_number' ),
						'required'          => false,
						'sanitize_callback' => array( $this, 'rest_sanitize_price' ),
					),
					'width'            => array(
						'validate_callback' => array( $this, 'rest_validate_number' ),
						'required'          => false,
						'sanitize_callback' => array( $this, 'rest_sanitize_price' ),
					),
					'length'           => array(
						'validate_callback' => array( $this, 'rest_validate_number' ),
						'required'          => false,
						'sanitize_callback' => array( $this, 'rest_sanitize_price' ),
					),
					'barcode'         => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'images'           => array(
						'validate_callback' => array( $this, 'rest_validate_image' ),
						'required'          => false,
					),
				),
			)
		);

		register_rest_route( self::API_BASE, 'product/(?P<pid>\d+)/update', array(
			'methods'  => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_update_product' ),
			'args'                => array(
				'status'         		=> array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => false,
				),
				'prd_start_display'     => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => false,
				),
				'prd_end_display'     => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => false,
				),
				'product_name'        	=> array(
					'validate_callback' => array( $this, 'rest_validate_array' ),
					'required'          => false,
				),
				'product_desc'        	=> array(
					'validate_callback' => array( $this, 'rest_validate_array' ),
					'required'          => false,
				),
				'price'       			=> array(
					'validate_callback' => array( $this, 'rest_validate_number' ),
					'required'          => false,
					'sanitize_callback' => array( $this, 'rest_sanitize_price' ),
				),
				'price_buy'       		=> array(
					'validate_callback' => array( $this, 'rest_validate_number' ),
					'required'          => false,
					'sanitize_callback' => array( $this, 'rest_sanitize_price' ),
				),
				'height'          		=> array(
					'validate_callback' => array( $this, 'rest_validate_number' ),
					'required'          => false,
					'sanitize_callback' => array( $this, 'rest_sanitize_price' ),
				),
				'width'            		=> array(
					'validate_callback' => array( $this, 'rest_validate_number' ),
					'required'          => false,
					'sanitize_callback' => array( $this, 'rest_sanitize_price' ),
				),
				'length'           		=> array(
					'validate_callback' => array( $this, 'rest_validate_number' ),
					'required'          => false,
					'sanitize_callback' => array( $this, 'rest_sanitize_price' ),
				),
				'barcode'         		=> array(
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'images'           		=> array(
					'validate_callback' => array( $this, 'rest_validate_image' ),
					'required'          => false,
				),
			),
		) );

		register_rest_route( self::API_BASE, 'product/(?P<pid>\d+)/delete', array(
			'methods'             => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_delete_product' ),
		) );

		register_rest_route( self::API_BASE, 'product/(?P<pid>\d+)/test', array(
			'methods'             => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_test_product' ),
		) );
	}

	public function rest_update_product( \WP_REST_Request $request ) {
		global $woocommerce_wpml;

		$pid = absint( $request->get_param( 'pid' ) );

		if ( $pid ) {
			$this->log(
				sprintf(
					__( 'Received request to update products with pid %s', 'easycms-wp' ),
					$pid
				),
				'info'
			);

			$matched_products = $this->get_products_by_pid( $pid );
			$products = array();
			$product_data = $this->prepare_product_data( $request );

			while ( $matched_products ) {
				$products_lang = array_map( array( $this, 'get_product_lang_by_id' ), $matched_products );

				$products_with_lang = array_combine( $products_lang, $matched_products );
				$products = array_merge( $products_with_lang, $products );

				$matched_products = $this->get_products_by_pid( $pid );
			}

			if ( $products ) {
				$main_product = $woocommerce_wpml->products->get_original_product_id( current( $products ) );

				if ( $main_product ) {
					$this->log(
						sprintf(
							__(
								'Updating matched WooCommerce products: %s with original product: %d',
								'easycms-wp'
							),
							implode( ',', $products ),
							$main_product
						),
						'info'
					);

					$errors = false;
					foreach ( $products as $lang => $product_id ) {
						try {
							$prod = $this->set_product_params(
								$product_data,
								$lang,
								$product_id,
								$product_id == $main_product
							);
							$prod->save();
						} catch ( \Exception $e ) {
							$this->log(
								sprintf(
									__( 'Unable to update product: %s', 'easycms-wp' ),
									$e->getMessage()
								),
								'error'
							);
							$errors = true;
							break;
						}
					}

					if ( $errors ) {
						return $this->rest_response( __( 'Product update failed', 'easycms-wp' ), 'fail', 400 );
					}

					$this->sync_translation_products( $main_product, $products );

					return $this->rest_response(
						__( 'Products updated successfully', 'easycms-wp' )
					);
				} else {
					$this->log(
						sprintf(
							__( '%d update failed: Unable to find original product' ),
							$pid
						),
						'error'
					);

					return $this->rest_response( __( 'Unable to update product', 'easycms-wp' ), 'fail', 404 );
				}
			} else {
				$_issue = sprintf(
						__( '%d update failed: No matching product with pid %d' ),
						$pid,
						$pid
					);
				$this->log(
					$_issue,
					'error'
				);

				// Create product? yes, upon receiving this message "product_not_found" CMS will call again with add product command!
				return $this->rest_response( __( 'product_not_found', 'easycms-wp' ), 'fail', 404 );
			}
		}

		return $this->rest_response( __( 'Unable to update product', 'easycms-wp' ), 'fail', 404 );
	}

	private function get_product_lang_by_id( int $product_id ) {
		return $this->productSaveActions->get_element_lang_code( $product_id );
	}


	public function rest_delete_product( \WP_REST_Request $request ) {
		$pid = absint( $request->get_param( 'pid' ) );

		if ( $pid ) {
			$this->log(
				sprintf(
					__( 'Received request to trash products with pid: %d', 'easycms-wp' ),
					$pid
				),
				'info'
			);

			while ( ( $products = $this->get_products_by_pid( $pid ) ) ) {
				$this->log(
					sprintf(
						__( 'Trashing matched WooCommerce Product with IDs: %s', 'easycms-wp' ),
						implode( ',', $products )
					),
					'info'
				);

				foreach ( $products as $product_id ) {
					$this->delete_product( $product_id );
				}
			}

			return $this->rest_response( __( 'Products trashed successfully', 'easycms-wp' ) );
		}

		return $this->rest_response( __( 'Product not found', 'easycms-wp' ), 'FAIL', 404 );
	}

	public function delete_product( int $product_id ) {
		$product = new \WC_Product();
		$product->set_id( $product_id );
		$product->delete();
		// Clear references to avoid memory leaks
		unset($product);		
	}

	public function set_product_params( array $product_data, string $lang, int $product_id = 0, bool $is_parent = true ) {
		global $wpdb;
		
		$this->log(sprintf('Starting set_product_params for %s product ID %d, language %s', 
			$is_parent ? 'parent' : 'translation', 
			$product_id, 
			$lang
		), 'debug');

		if (!$product_id) {
			$product = new \WC_Product();
			$this->log('Creating new product object', 'debug');
		} else {
			$product = wc_get_product($product_id);
			$this->log(sprintf('Retrieved existing product ID %d', $product_id), 'debug');
		}
		
		// Set product details
		if ( isset( $product_data['product_name'][ $lang ] ) ) {
			$product->set_name( sanitize_text_field( $product_data['product_name'][ $lang ] ) );
		}
		if ( isset( $product_data['product_desc'][ $lang ] ) ) {
			$string = sanitize_text_field( $product_data['product_desc'][ $lang ] );
			$string = !empty($string) ? (urldecode(trim($string))) : $string;
			$string = preg_replace('#<br\s*/?>#i', "\n", $string);
			$string = htmlspecialchars_decode(trim($string));
			$string = trim($string);
			$string = ltrim($string);
			$product->set_description( $string );
		}
		if ( isset( $product_data['price'] ) ) {
			$product->set_regular_price( round(floatval( $product_data['price'] ), 3) );
		}
		if ( isset( $product_data['discount'] ) ) {
			$product->set_sale_price( round( round((float)$product_data['price'], 3) * (1 - ( ((float)$product_data['discount']) /100)), 3));
		}
		if ( isset( $product_data['width'] ) ) {
			$product->set_width( floatval( $product_data['width'] ) );
		}
		if ( isset( $product_data['height'] ) ) {
			$product->set_height( floatval( $product_data['height'] ) );
		}
		if ( isset( $product_data['length'] ) ) {
			$product->set_length( floatval( $product_data['length'] ) );
		}
		if ( isset( $product_data['weight'] ) ) {
			$product->set_weight( floatval( $product_data['weight'] ) );
		}
		if ( isset( $product_data['prdStatus'] ) ) {
			$_prd_status = intval($product_data['prdStatus'])==0 ? 'draft' : 'publish';
			$product->set_status( $_prd_status );
		}

		// Handle status based on display dates
		$today_date = strtotime("now");
		if(isset($product_data['prd_start_display']) && isset($product_data['prd_end_display'])){
			$_prd_status = isset($product_data['prdStatus']) && intval($product_data['prdStatus'])==0 ? 'draft' : 'publish';
			if($today_date >= $product_data['prd_start_display'] && $today_date <= $product_data['prd_end_display']){
				// No need to do any changes
			}
			if($today_date < $product_data['prd_start_display']){
				$product->set_status( 'draft' );
			}
			if($today_date > $product_data['prd_end_display']){
				$product->set_status( 'draft' );
			}
		}

		// Set SKU only for parent product
		if ( $is_parent && isset( $product_data['barcode'] ) ) {
			$product->set_sku( $product_data['barcode'] );
		}

		// Handle images for both parent and translations
		if (!empty($product_data['images']) && is_array($product_data['images'])) {
			$this->log(sprintf('Processing %d images for product', count($product_data['images'])), 'debug');
			
			$gallery_images = array();
			foreach ($product_data['images'] as $index => $image_data) {
				$this->log(sprintf('Processing image #%d: %s', $index + 1, $image_data['imgName']), 'debug');
				
				if ((bool) $image_data['visible']) {
					$this->log('Image is marked as visible', 'debug');
					
					if (!isset($image_data['URL'])) {
						$this->log(
							sprintf('Skipping image - missing URL: %s', $image_data['imgName']),
							'warning'
						);
						continue;
					}

					$filename = preg_replace('/[^\w.]/', '_', $image_data['imgName']);
					$title = preg_replace('/\.[^.]+$/', '', basename($filename));
					
					$this->log(sprintf('Checking if image exists locally: %s', $filename), 'debug');
					$attachment_id = $this->check_if_local_image_exists($filename, $title, false);
					
					if (empty($attachment_id)) {
						$this->log('Image not found locally, importing from remote', 'debug');
						
						$attachment_id = Util::url_to_attachment(
							$image_data['URL'],
							$image_data['imgName'],
							$image_data['imgdate']
						);
						
						if (is_wp_error($attachment_id)) {
							$this->log(
								sprintf('Failed to import image: %s', $attachment_id->get_error_message()),
								'error'
							);
							continue;
						}
						
						$this->log(sprintf('Successfully imported image, attachment ID: %d', $attachment_id), 'debug');
						
						// Verify image file exists
						$file_path = get_attached_file($attachment_id);
						if (!$file_path || !file_exists($file_path)) {
							$this->log(
								sprintf('Critical error: Imported image file missing for attachment ID %d', $attachment_id),
								'error'
							);
							continue;
						}
						
						$this->log('Generating image metadata and thumbnails', 'debug');
						$metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
						if (empty($metadata)) {
							$this->log(
								sprintf('Failed to generate metadata for attachment ID %d', $attachment_id),
								'error'
							);
						} else {
							wp_update_attachment_metadata($attachment_id, $metadata);
							$this->log('Successfully generated image metadata', 'debug');
						}
					} else {
						$this->log(sprintf('Using existing attachment ID: %d', $attachment_id), 'debug');
						
						// Verify existing image
						$file_path = get_attached_file($attachment_id);
						if (!$file_path || !file_exists($file_path)) {
							$this->log(
								sprintf('Existing attachment file missing for ID %d - attempting repair', $attachment_id),
								'warning'
							);
							
							// Attempt to re-download if file is missing
							$attachment_id = $this->repair_missing_attachment($attachment_id, $image_data);
							if (is_wp_error($attachment_id)) {
								$this->log(
									sprintf('Failed to repair attachment: %s', $attachment_id->get_error_message()),
									'error'
								);
								continue;
							}
						}
					}
					
					$gallery_images[] = $attachment_id;
					$this->log(sprintf('Added attachment ID %d to gallery', $attachment_id), 'debug');
				} else {
					$this->log('Image is not visible - skipping', 'debug');
				}
			}

			if ($gallery_images) {
				$this->log(sprintf('Setting product images - %d in gallery', count($gallery_images)), 'debug');
				
				// Set main image
				$main_image_id = $gallery_images[0];
				$product->set_image_id($main_image_id);
				$this->log(sprintf('Set main image ID: %d', $main_image_id), 'debug');
				
				// Set gallery images
				array_shift($gallery_images);
				$product->set_gallery_image_ids($gallery_images);
				$this->log(sprintf('Set gallery image IDs: %s', implode(', ', $gallery_images)), 'debug');
				
				// Handle translations
				if (!$is_parent && function_exists('icl_object_id')) {
					$this->log('Processing product translation - linking to same images', 'debug');
					$product->set_image_id($main_image_id);
					$product->set_gallery_image_ids($gallery_images);
					$this->log('Translation images set successfully', 'debug');
				}
			} else {
				$this->log('No valid images available for this product', 'warning');
			}
		} else {
			$this->log('No images provided for this product', 'debug');
		}

		// Add custom metadata
		$product->add_meta_data( 'easycms_data', $product_data );

		$_apply_filters = apply_filters( 'easycms_wp_product_component_before_save_product', $product, $product_data, $lang, $is_parent );
        // Clear references to avoid memory leaks
        unset($product_data, $gallery_images, $main_image_id);		
		return $product;
	}

	private function safe_import_image($image_data) {
		// First download the image
		$attachment_id = Util::url_to_attachment(
			$image_data['URL'],
			$image_data['imgName'],
			$image_data['imgdate']
		);
		
		if (is_wp_error($attachment_id)) {
			return $attachment_id;
		}
		
		// Ensure all image sizes are generated
		$metadata = false;
		$full_size_path = get_attached_file($attachment_id);
		if ($full_size_path && file_exists($full_size_path)) {
			// Regenerate all image sizes
			$metadata = wp_generate_attachment_metadata($attachment_id, $full_size_path);
			wp_update_attachment_metadata($attachment_id, $metadata);
			
			// Prevent WPML from duplicating this media
			if (function_exists('wpml_add_translatable_content')) {
				global $sitepress;
				$sitepress->set_setting('media_files_duplicate', 0);
			}
		}

		// Clear references to avoid memory leaks
		unset($full_size_path, $metadata);
		
		return $attachment_id;
	}

	public function sync_translation_products( int $parent_product_id, array $product_ids ) {
		foreach ( $product_ids as $lang => $product_id ) {
			$product_id = absint( $product_id );
			if ( ! $product_id || $product_id == $parent_product_id ) {
				continue;
			}

			// $lang = $this->productSaveActions->get_element_lang_code( $product_id );

			$this->productSaveActions->run(
				wc_get_product( $product_id ),
				$this->productSaveActions->get_element_trid( $parent_product_id ),
				$lang,
				$parent_product_id
			);

			$this->log(
				sprintf(
					__(
						'Synced translation product %d data with main product %d',
						'easycms-wp'
					),
					$product_id,
					$parent_product_id
				),
				'info'
			);
		}
	}

	public function get_products( int $limit_per_loop = 50, array $args = array() ) {
		static $page = 1;

		$defaults = array(
			'limit'            => $limit_per_loop,
			'return'           => 'ids',
			'status'           => array( 'draft', 'pending', 'private', 'publish' ),
			'type'             => 'simple',
			'suppress_filters' => true,
			'offset'           => $limit_per_loop * ($page - 1),
		);

		$args = array_replace_recursive( $defaults, $args );

		$query = new \WC_Product_Query( $args );
		$products = $query->get_products();

		$page++;

		return $products;
	}

	public function get_synced_pids() {
		$args = array(
			'meta_query' => array(
				'key'     => 'easycms_pid',
				'value'   => '',
				'compare' => 'EXISTS',
			),
		);

		$matched_products = $this->get_products( 50, $args );
		$pids = [];

		while ( $matched_products ) {
			foreach ( $matched_products as $product_id ) {
				$pid = get_post_meta( $product_id, 'easycms_pid', true );
				if ( $pid ) {
					$pids[ $pid ] = 1;
				}
			}

			$matched_products = $this->get_products( 50, $args );
		}

		return array_keys( $pids );
	}

	public function get_products_by_pid( int $pid, int $limit_per_loop = 50 ) {
		$args = array(
			'meta_query'   => array(
				array(
					'key'   => 'easycms_pid',
					'value' => $pid,
				),
			),
		);

		return $this->get_products( $limit_per_loop, $args );
	}

	public function add_wc_custom_field_query( $query, $query_vars ) {
		if ( isset( $query_vars['meta_query'] ) ) {
			$query['meta_query'] = $query_vars['meta_query'];
		}

		return $query;
	}

	private function prepare_product_data( $request ) {
		$product_data = is_object( $request ) ? $request->get_params() : $request;

		if ( !empty( $product_data['product_name'] ) ) {
			$product_data['product_name'] = $this->strip_locale( $product_data['product_name'] );
			$product_data['product_name'] = array_filter(
				$product_data['product_name'],
				array( $this, 'is_language_active' ),
				ARRAY_FILTER_USE_KEY
			);
		}

		if ( !empty( $product_data['product_desc'] ) ) {
			$product_data['product_desc'] = $this->strip_locale( $product_data['product_desc'] );
			$product_data['product_desc'] = array_filter(
				$product_data['product_desc'],
				array( $this, 'is_language_active' ),
				ARRAY_FILTER_USE_KEY
			);
		}

		return $product_data;
	}

	public function is_language_active( string $language ) {
		return Util::is_language_active( $language );
	}

	private function strip_locale( array $data ) {
		return Util::strip_locale( $data );
	}

	public function rest_add_product( \WP_REST_Request $request ) {
		$product_data = $this->prepare_product_data( $request );
		$this->log(
			sprintf(
				__( 'rest_add_product (%s) ', 'easycms-wp' ),
				json_encode($product_data)
			),
			'debug'
		);

		$this->log(
			sprintf(
				__( 'Received request to add product with pid %d', 'easycms-wp' ),
				$product_data['pid']
			),
			'info'
		);

		return $this->create_product( $product_data );
	}

	private function create_product( array $product_data ) {
		global $sitepress, $wpml_post_translations, $woocommerce_wpml, $wpdb;

		$default_lang = $sitepress->get_default_language();

		$this->log(
			sprintf(
				__( 'Create_product::Adding product with pid %d', 'easycms-wp' ),
				$product_data['pid']
			),
			'info'
		);
		$this->log(
			sprintf(
				__( 'create_product::Product stock_data %s', 'easycms-wp' ),
				json_encode($product_data['stock_data'])
			),
			'debug'
		);


		if ( isset( $product_data['product_name'][ $default_lang ] ) ) {
			/*$this->log(
				__( 'Adding product case 1', 'easycms-wp' ),
				'info'
			);
			$this->log(
				sprintf(
					__( 'Adding product case 1-1 product %s', 'easycms-wp' ),
					json_encode($product_data),
				),
				'info'
			);*/
			try {
				/*$this->log(
						__( 'in TRY case 1-2 product', 'easycms-wp' ),
					'info'
				);*/

				$product = $this->set_product_params( $product_data, $default_lang );
				$this->log(
					sprintf(
						__( 'Adding product case 1-3 product %s', 'easycms-wp' ),
						json_encode($product),
					),
					'debug'
				);
				// $product->set_name( $product_data['product_name'][ $default_lang ] );
				$product->add_meta_data( 'easycms_pid', $product_data['pid'], true );
				/*$this->log(
					__( 'Adding product case 1-4', 'easycms-wp' ),
					'info'
				);*/

				$parent_id = $product->save();
				$this->log(
					sprintf(
						__( 'product->save() result as parent_id is: %s', 'easycms-wp' ),
						$parent_id
					),
					'debug'
				);
			} catch ( \Exception $e ) {
				$this->log(
					sprintf(
						__( 'Unable to create product: %s', 'easycms-wp' ),
						$e->getMessage()
					),
					'error'
				);
			}

			if ( ! empty( $parent_id ) ) {
				$this->log(
					sprintf(
						__( 'Product with pid %d added successfully, moving to create translation products', 'easycms-wp' ),
						$product_data['pid']
					),
					'info'
				);

				### this must be placed after product is saved, because we are setting default stock location and for that we need to make sure wp product id is set! 
				$_apply_filters = apply_filters( 'easycms_wp_product_component_before_save_product', $product, $product_data, $default_lang, true );

				foreach ( $product_data['product_name'] as $lang => $name ) {
					if ( $lang == $default_lang ) {
						continue;
					}

					$this->log(
						sprintf(
							__( 'Adding product lang for language: %s', 'easycms-wp' ),
							json_encode($lang),
						),
						'info'
					);


					$translation_prod = $this->set_product_params( $product_data, $lang, 0, false );
					// $translation_prod->set_name( $name );
					$translation_prod->add_meta_data( 'easycms_pid', $product_data['pid'], true );
					$productId = $translation_prod->save();

					if ( $productId ) {
						$this->log(
							sprintf(
								__(
									'Created translation product with lang %s for product with pid %d',
									'easycms-wp'
								),
								$lang,
								$product_data['pid']
							),
							'debug'
						);

						$this->sync_translation_products( $parent_id, array( $lang => $productId ) );
					} else {
						$this->log(
							sprintf(
								__(
									'Unable to create translation product with lang %s for product with pid %d',
									'easycms-wp'
								),
								$lang,
								$product_data['pid']
							),
							'error'
						);
					}
				}

				return new \WP_REST_Response( [ 'code' => 'OK', 'data' => [ 'ID' => $parent_id ] ] );
			} else {
				$this->log(
					sprintf(
						__( 'WooCommerce unable to create product with pid %d', 'easycms-wp' ),
						$product_data['pid']
					),
					'error'
				);

				return new \WP_REST_Response( [ 'code' => 'FAIL', 'data' => __( 'WooCommerce unable to create product', 'easycms-wp' ) ], 500 );
			}
		} else {
			$this->log(
				sprintf(
					__(
						'Unable to add product with pid %s. No product name with the site\'s default language',
						'easycms-wp'
					),
					$product_data['pid']
				),
				'error'
			);

			return new \WP_REST_Response( [ 'code' => 'FAIL', 'data' => __( 'No product name with the site\'s default language', 'easycms-wp' ) ], 401 );
		}
	}

	private function check_if_local_image_exists($filename, $title=false, $check_all_metas=false){
		global $wpdb;
		if($title==false){
			$title = preg_replace( '/\.[^.]+$/', '', basename( $filename ) );
		}
		// $query = "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%/$title'";
		$query = "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE '%/$title%' ORDER BY meta_id DESC";
		if(!$check_all_metas) $query .= " LIMIT 1 ";
		$this->log(sprintf(
			__( '##### ------ ###### new file name is: %s WP UPLOAD PATH IS: %s  The query response is: %s ', 'easycms-wp' ),
			$title,
			json_encode(wp_get_upload_dir()['basedir']),
			json_encode($wpdb->get_results($query))
			// json_encode($wpdb->get_var($query)),
		), 'debug');

		$result = $wpdb->get_results($query);

		if(!empty($result)){
			### making sure we have an array to work with
			$result = json_decode(json_encode($result), true);

			$this->log(sprintf(
				__( '##### ------ ###### response type is: %s  ##### ------ ######', 'easycms-wp' ),
				gettype($result)
			), 'debug');

			foreach ($result as $rk => $rv) {
				$this->log(sprintf(
					__( '##### ------ ###### rk > %s -- rv type is: %s && rv is [%s]', 'easycms-wp' ),
					$rk,
					gettype($rv),
					print_r($rv, true)
				), 'debug');
				$this->log(sprintf(
					__( '##### ------ ###### before if ', 'easycms-wp' )
				), 'debug');

				if(isset($rv['meta_value']) && !empty($rv['meta_value'])){
					$this->log(sprintf(
						__( '##### ------ ###### file path is: %s  ##### ------ ######', 'easycms-wp' ),
						$rv['meta_value']
					), 'debug');
					### checking if attachment file still exists
					$local_url = wp_get_upload_dir()['basedir'].'/'.$rv['meta_value'];	
					if(file_exists($local_url)){
						$this->log(sprintf(
							__( '##### ------ ###### file_exists(%s)  ##### ------ ######', 'easycms-wp' ),
							$local_url
						), 'debug');
					    // $id = attachment_url_to_postid($local_url);

					    ###################################################
						### update existing image metadata for this product
						$attachment_id = $rv['meta_id'];

						$file_type = wp_check_filetype( $filename );
						$tmp_attachment_data = array(
							'post_status' => 'inherit',
							'post_title'  => $filename,
							'post_date'   => date( 'Y-m-d H:i:s', ( $time ? $time : null ) ),
							'post_mime_type' => $file_type['type']
						);
						require_once ABSPATH . 'wp-admin/includes/image.php';
				
						$attachment_id = wp_insert_attachment(
							$tmp_attachment_data,
							$local_url,
							0 // parent id
						);

						$attachment_data = wp_generate_attachment_metadata( $attachment_id, $local_url );
						wp_update_attachment_metadata( $attachment_id, $attachment_data );						
						return $attachment_id;
					}else{
						$this->log(sprintf(
							__( '##### ------ ###### file does not exists using file_exists(%s)  ##### ------ ######', 'easycms-wp' ),
							$local_url
						), 'debug');
					}
				}else{
					$this->log(sprintf(
						__( '##### ------ ###### DID not find an attachment from DB Query  ##### ------ ######', 'easycms-wp' ),
						$local_url
					), 'debug');
				}
				$this->log(sprintf(
					__( '##### ------ ###### after if ', 'easycms-wp' )
				), 'debug');
			}
		}
		return false;
	}


	public function rest_test_product( \WP_REST_Request $request ) {
		$pid = absint( $request->get_param( 'pid' ) );

		if ( $pid ) {

			return $this->rest_response( __( json_encode($this->get_products_by_pid( $pid )), 'easycms-wp' ) );
		}

		return $this->rest_response( __( 'Product not found', 'easycms-wp' ), 'FAIL', 404 );
	}


}
?>