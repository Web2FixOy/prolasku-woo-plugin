<?php
namespace EasyCMS_WP\Component;

use \EasyCMS_WP\Log;
use \EasyCMS_WP\Util;

class Brand extends \EasyCMS_WP\Template\Component {
	public $taxonomy = 'product_brand';

	public static function can_run() {
		if (
			! class_exists( 'WooCommerce' ) ||
			! class_exists( 'woocommerce_wpml' ) ||
			! $GLOBALS['woocommerce_wpml']->dependencies_are_ok
		) {
			Log::log(
				'brand',
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
		set_time_limit( 0 );
		ignore_user_abort( true );

		$brands = $this->get_brand();
		while ( ! empty( $brands ) ) {
			foreach ( $brands as $index => $data ) {
				$this->insert_brand( $data );
			}
			$brands = $this->get_brand();
		}
	}

	public function hooks() {
		// add_action( 'easywp_cms_save_api_settings', array( $this, 'sync' ), $this->priority );
		add_action( 'rest_api_init', array( $this, 'register_api' ) );

		add_filter( 'easycms_wp_product_component_before_save_product', array( $this, 'set_product_brand' ), 10, 2 );
		add_action( 'woocommerce_after_product_object_save', array( $this, 'set_object_terms' ) );
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
			'callback'            => array( $this, 'rest_add_brand' ),
			'args'                => array(
				'bid'               => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
				'parent_id'                     => array(
					'validate_callback' => array( $this, 'rest_validate_number' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
				'brand_name'             => array(
					'validate_callback' => array( $this, 'rest_validate_array' ),
					'required'          => true,
				),
			)
		));

		register_rest_route( self::API_BASE, $this->get_module_name() . '/delete', array(
			'methods' => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_delete_brand' ),
			'args'                => array(
				'bid'               => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
			)
		));
	}

	public function set_product_brand( \WC_Product $product, array $product_data ) {
		if ( ! empty( $product_data['bid'] ) ) {
			$term = $this->get_term( $product_data['bid'] );
			if ( $term ) {
				$this->log(
					__( 'Setting product brand ID', 'easycms-wp' ),
					'info'
				);

				$this->product_bid = $term->term_id;
			} else {
				$this->log(
					sprintf(
						__( 'Unable to set brand ID to product pid %d. Brand ID %d not found on WP', 'easycms-wp' ),
						$product_data['pid'],
						$product_data['bid']
					),
					'warning'
				);
			}
		}

		return $product;
	}

	public function set_object_terms( \WC_Product $product ) {
		if ( isset( $this->product_bid ) ) {
			wp_set_object_terms( $product->get_id(), [ $this->product_bid ], $this->taxonomy );
		}
	}

	public function rest_add_brand( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$term = $this->insert_brand( $params );

		if ( $term ) {
			return $this->rest_response( $term->term_id );
		}

		return $this->rest_response( '', 'FAIL', 400 );
	}

	public function rest_delete_brand( \WP_REST_Request $request ) {
		$bid = $request['bid'];
		$matching_terms = $this->get_all_terms( $bid );

		if ( $matching_terms ) {
			foreach ( $matching_terms as $term_id ) {
				wp_delete_term( $term_id, $this->taxonomy );
			}

			$this->log(
				sprintf(
					__( 'Brand bid %d deleted successfully', 'easycms-wp' ),
					$request['bid']
				),
				'info'
			);
		}

		return $this->rest_response( 'success' );
	}

	private function get_all_terms( int $bid ) {
		global $wpdb;

		$query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `term_id` FROM {$wpdb->termmeta} WHERE meta_key = %s AND meta_value = %d",
				$this->get_term_meta_name(),
				$bid,
			)
		);

		$ret = array();
		if ( $query ) {
			foreach ( $query as $row ) {
				$ret[] = $row->term_id;
			}
		}

		return $ret;
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

	private function get_brand( int $bid = 0, int $limit = 50 ) {
		static $page = 1;
		$offset = ($page - 1) * $limit ;

		$req = $this->make_request(
			'/get_brands',
			'POST',
			array(
				'bid' => $bid,
				'start' => $offset,
				'limit' => $limit,
			)
		);

		if ( is_wp_error( $req ) ) {
			$this->log(
				sprintf(
					__( 'Fetching brands failed: %s. Number of retries: (%d)', 'easycms-wp' ),
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
		return 'easycms_wp_bid';
	}

	public function get_term( int $bid, string $lang = '' ) {
		$result = false;
		$current_lang = Util::get_default_language();

		if ( $lang ) {
			Util::switch_language( $lang );
		}

		$args = array(
			'taxonomy'   => $this->taxonomy,
			'hide_empty' => false,
			'number'     => 1,
			'meta_query' => array(
				array(
					'key' => $this->get_term_meta_name(),
					'value' => $bid,
				),
			),
		);

		$query = new \WP_Term_Query( $args );
		if ( $query->get_terms() ) {
			$terms = $query->get_terms();
			$result = $terms[0];
		}

		if ( $lang ) {
			Util::switch_language( $current_lang );
		}

		return $result;
	}

	private function prepare_data( array $category_data ) {
		if ( ! empty( $category_data['brand_name'] ) ) {
			$category_data['brand_name'] = Util::strip_locale( $category_data['brand_name'] );
			$category_data['brand_name'] = array_filter(
				$category_data['brand_name'],
				array( '\EasyCMS_WP\Util', 'is_language_active' ),
				ARRAY_FILTER_USE_KEY
			);
		}

		return $category_data;
	}

	public function insert_brand( array $data ) {
		$data = $this->prepare_data( $data );
		$default_lang = Util::get_default_language();

		if ( ! empty( $data['brand_name'][ $default_lang ] ) ) {
			$main_term = $this->insert( $data, 0, $default_lang );

			if ( $main_term  ) {
				$this->log(
					__( 'Brand added successfully', 'easycms-wp' ),
					'info'
				);

				return $main_term;
			}

			return 0;
		}

		$this->log(
			sprintf(
				__( 'Unable to add brand. No translation found for default WP lang', 'easycms-wp' )
			),
			'error'
		);

		return 0;
	}

	private function insert( array $data, int $parent_term_tax_id, string $lang ) {
		if ( ! empty( $data['bid'] ) ) {
			$default_lang = Util::get_default_language();
			$ret = 0;

			Util::switch_language( $lang );

			$existing_term = $this->get_term( $data['bid'] );
			$parent_term = null;

			if ( 0 != $data['parent_id'] ) {
				$this->log(
					__( 'This brand is a child. Trying to get parent', 'easycms-wp' ),
					'info'
				);

				$parent_term = $this->get_term( $data['parent_id'] );
				if ( ! $parent_term ) {
					$this->log(
						sprintf(
							__( 'Unable to get parent brand (%d) in WP', 'easycms-wp' ),
							$data['parent_id']
						),
						'warning'
					);
					$this->log(
						sprintf(
							__( 'Trying to get parent brand (%d) from CMS', 'easycms-wp' ),
							$data['parent_id']
						),
						'info'
					);

					$parent_product_data = $this->get_brand( $data['parent_id'] );
					if ( ! empty( $parent_product_data ) ) {
						$parent_term = $this->insert_brand( $parent_product_data[0] );
					} else {
						$this->log(
							sprintf(
								__( '%d parent brand (%d) data not found from CMS server', 'easycms-wp' ),
								$data['bid'],
								$data['parent_id']
							),
							'error'
						);
					}
				}
			}

			if ( 0 != $data['parent_id'] && ! $parent_term ) {
				$this->log(
					sprintf(
						__(
							'Adding brand (%d) failed. Unable to retrieve parent brand (%d) from both CMS and WP',
							'easycms-wp'
						),
						$data['bid'],
						$data['parent_id']
					),
					'error'
				);

			} else if ( $lang && ! empty( $data['brand_name'][ $lang ] ) ) {
				$slug = sanitize_title_with_dashes( $data['brand_name'][ $lang ], '', 'save' );

				$args = array(
					'slug' => $slug, // Slug changes on update too
				);

				if  ( ! empty( $parent_term ) ) {
					$args['parent'] = $parent_term->term_id;
				}

				if ( ! $existing_term ) {
					$term = wp_insert_term(
						$data['brand_name'][ $lang ],
						$this->taxonomy,
						$args
					);
				} else {
					$args['name'] = $data['brand_name'][ $lang ];
					$term = wp_update_term( $existing_term->term_id, $this->taxonomy, $args );
				}

				if ( is_wp_error( $term ) ) {
					$this->log(
						sprintf(
							__( 'Failed adding brand with bid (%d): %s', 'easycms-wp' ),
							$data['bid'],
							$term->get_error_message()
						),
						'error'
					);
				} else {
					update_term_meta( $term['term_id'], $this->get_term_meta_name(), $data['bid'] );
					
					$ret = (object) $term;
				}
			} else {
				$this->log(
					sprintf(
						__( 'Unable to add translation for %s. No translation text found', 'easycms-wp' ),
						$lang
					),
					'error'
				);
			}
		} else {
			$this->log(
				__( 'Cannot add brand. No bid specified in parameters', 'easycms-wp' ),
				'error'
			);
		}

		Util::switch_language( $default_lang );
		return $ret;
	}
}
?>