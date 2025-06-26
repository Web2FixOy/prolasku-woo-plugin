<?php
namespace EasyCMS_WP\Component;

use \EasyCMS_WP\Log;
use \EasyCMS_WP\Util;

class Stock_Type extends \EasyCMS_WP\Template\Component {
	public $taxonomy = 'stock_type';

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
		set_time_limit( 0 );
		ignore_user_abort( true );

		$stock_types = $this->get_stock_type();
		while ( ! empty( $stock_types ) ) {
			foreach ( $stock_types as $index => $stock_type_data ) {
				$this->insert_stock_type( $stock_type_data );
			}
			$stock_types = $this->get_stock_type();
		}
	}

	public function hooks() {
		// add_action( 'easywp_cms_save_api_settings', array( $this, 'sync' ), $this->priority );
		add_action( 'rest_api_init', array( $this, 'register_api' ) );

		add_filter( 'easycms_wp_product_component_before_save_product', array( $this, 'set_product_stock_type' ), 10, 3 );
		add_action( 'woocommerce_after_product_object_save', array( $this, 'set_object_terms' ) );
		add_filter( 'easycms_wp_set_order_item_data', array( $this, 'set_order_product_fields' ), 10, 2 );


		add_action( 'init', array( $this, 'register_taxonomy_with_wpml' ) );

	}
	public function register_taxonomy_with_wpml() {
	    if ( function_exists( 'icl_object_id' ) ) {
	        $GLOBALS['sitepress']->register_taxonomy( 'stock_type', 'product' );
	    }
	}
	public function fail_safe() {
		if ( $this->has_pending() ) {
			$this->log( __( 'Performing pending failed operations', 'easycms-wp' ), 'info' );
			$this->sync();
		}
	}

	public function set_order_product_fields( $product_data, \WC_Product $product ) {
		if ( null === $product_data ) {
			return $product_data;
		}

		if ( ! empty( $product_data['stock_type_id'] ) ) {
			$term = $this->get_term( $product_data['stock_type_id'] );
			if (
				$term &&
				( $data = get_term_meta( $term->term_id, $this->get_term_data_meta_name(), true ) )
			) {

				$product_data['stock_type_name'] = $data['stock_type_name'];
			} else {
				$this->log(
					sprintf(
						__( 'stock_type for this product (%s) is not synced yet. Aborting...', 'easycms-wp' ),
						$product->get_name()
					),
					'error'
				);

				$product_data = null;
			}
		}

		return $product_data;
	}

	public function register_api() {
		register_rest_route( self::API_BASE, $this->get_module_name(), array(
			'methods' => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_add_stock_type' ),
			'args'                => array(
				'stock_type_id'               => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
				'parent_id'                     => array(
					'validate_callback' => array( $this, 'rest_validate_number' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
				'stock_type_name'             => array(
					'validate_callback' => array( $this, 'rest_validate_array' ),
					'required'          => true,
				),
			)
		));

		register_rest_route( self::API_BASE, $this->get_module_name() . '/delete', array(
			'methods' => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_delete_stock_type' ),
			'args'                => array(
				'stock_type_id'               => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
			)
		));
	}

	public function set_product_stock_type( \WC_Product $product, array $product_data, string $lang ) {
		$this->stock_type_term_id = null;
		if ( ! empty( $product_data['stock_type_id'] ) ) {
			$stock_type = $this->get_term( $product_data['stock_type_id'], $lang );
			if ( $stock_type ) {
				$this->stock_type_term_id = $stock_type->term_id;
			} else {
				$this->log(
					sprintf(
						__( 'Unable to set product (%d) stock_type. stock_type or translation not found on WP', 'easycms-wp' ),
						$product_data['pid']
					),
					'warning'
				);
			}
		}

		return $product;
	}

	public function set_object_terms( \WC_Product $product ) {
		if ( isset( $this->stock_type_term_id ) ) {
			wp_set_object_terms( $product->get_id(), [ $this->stock_type_term_id ], $this->taxonomy );
			$this->log(
				sprintf(
					__( 'Stock type set successfully' )
				),
				'info'
			);
		}
	}

	public function rest_add_stock_type( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$term = $this->insert_stock_type( $params );

		if ( $term ) {
			return $this->rest_response( $term->term_id );
		}

		return $this->rest_response( '', 'FAIL', 400 );
	}

	public function rest_delete_stock_type( \WP_REST_Request $request ) {
		$stock_type_id = $request['stock_type_id'];
		$matching_terms = $this->get_all_terms( $stock_type_id );

		if ( $matching_terms ) {
			foreach ( $matching_terms as $term_id ) {
				wp_delete_term( $term_id, $this->taxonomy );
			}
		}

		return $this->rest_response( 'success' );
	}

	private function get_all_terms( int $stock_type_id ) {
		global $wpdb;

		$query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `term_id` FROM {$wpdb->termmeta} WHERE meta_key = %s AND meta_value = %d",
				$this->get_term_meta_name(),
				$stock_type_id,
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

	private function get_stock_type( int $stock_type_id = 0, int $limit = 50 ) {
		static $page = 1;
		$offset = ($page - 1) * $limit ;

		$req = $this->make_request(
			'/get_stock_types',
			'POST',
			array(
				'stock_type_id' => $stock_type_id,
				'start' => $offset,
				'limit' => $limit,
			)
		);

		if ( is_wp_error( $req ) ) {
			$this->log(
				sprintf(
					__( 'Fetching stock_types failed: %s. Number of retries: (%d)', 'easycms-wp' ),
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

	public function get_term( int $stock_type_id, string $lang = '' ) {
		$result = false;
		$current_lang = Util::get_default_language();

		if ( $lang ) {
			Util::switch_language( $lang );
		}

		$args = array(
			'cache_results' => false,
			'taxonomy'   => $this->taxonomy,
			'hide_empty' => false,
			'number'     => 1,
			'meta_query' => array(
				array(
					'key' => $this->get_term_meta_name(),
					'value' => $stock_type_id,
				),
			),
		);

		// $query = new \WP_Term_Query( $args );
		// if ( $query->get_terms() ) {
		// 	$terms = $query->get_terms();
		// 	$result = $terms[0];
		// }

		$query = new \WP_Term_Query( $args );
		if ( $query->get_terms() ) {
			$terms = $query->get_terms();
			

			foreach ($terms as $term) {
			  $translated_term_id = apply_filters( 'wpml_object_id', $term->term_id, $term->taxonomy, false, $lang );
			  $this->log(
			    sprintf(
			      __( 'RESULT for translated_term_id %s', 'easycms-wp' ),
			      json_encode($translated_term_id)
			    ),
			    'debug'
			  );
			  if ( $translated_term_id ) {
			    $translated_term = get_term( $translated_term_id, $term->taxonomy );
			    $this->log(
			      sprintf(
			        __( 'RESULT for translated_term %s', 'easycms-wp' ),
			        json_encode($translated_term)
			      ),
			      'debug'
			    );
			    if ( $translated_term ) {
			      $result = $translated_term;
			      break;
			    }
			  }
			}

		}
		
		if ( $lang ) {
			Util::switch_language( $current_lang );
		}

		return $result;
	}

	private function prepare_stock_type_data( array $stock_type_data ) {
		if ( ! empty( $stock_type_data['stock_type_name'] ) ) {
			$stock_type_data['stock_type_name'] = Util::strip_locale( $stock_type_data['stock_type_name'] );
			$stock_type_data['stock_type_name'] = array_filter(
				$stock_type_data['stock_type_name'],
				array( '\EasyCMS_WP\Util', 'is_language_active' ),
				ARRAY_FILTER_USE_KEY
			);
		}

		return $stock_type_data;
	}

	private function perform_translation( array $insert_term_result, int $parent_term_tax_id, string $lang ) {
		global $sitepress, $wpml_term_translations, $woocommerce_wpml;

		$trid = null;
		if ( $parent_term_tax_id ) {
			$this->log(
				sprintf(
					__( 'Performing translation for term_id %d', 'easycms-wp' ),
					$insert_term_result['term_id']
				),
				'info'
			);

			$trid = $wpml_term_translations->get_element_trid( $parent_term_tax_id );
			if ( ! $trid ) {
				$this->log(
					sprintf(
						__( 'Translation fail. Failed to find parent translation stock_type', 'easycms-wp' )
					),
					'error'
				);

				return;
			}
		}

		$sitepress->set_element_language_details( $insert_term_result['term_taxonomy_id'], 'tax_' . $this->taxonomy, $trid, $lang );
		$woocommerce_wpml->terms->update_terms_translated_status( $this->taxonomy );
		$this->log(
			sprintf(
				__('Translation done for term_id %s', 'easycms-wp' ),
				$insert_term_result['term_id']
			),
			'info'
		);
	}

	public function insert_stock_type( array $stock_type_data ) {
		$stock_type_data = $this->prepare_stock_type_data( $stock_type_data );
		$default_lang = Util::get_default_language();

		if ( ! empty( $stock_type_data['stock_type_name'][ $default_lang ] ) ) {
			$main_term = $this->insert( $stock_type_data, 0, $default_lang );

			if ( $main_term ) {
				foreach ( $stock_type_data['stock_type_name'] as $lang => $name ) {
					if ( $lang == $default_lang ) {
						continue;
					}

					$this->log(
						sprintf(
							__( 'Adding translation for %s and stock_type_id %d', 'easycms-wp' ),
							$lang,
							$stock_type_data['stock_type_id']
						),
						'info'
					);

					$trans_term = $this->insert( $stock_type_data, $main_term->term_taxonomy_id, $lang );
				}

				$this->log(
					__( 'stock_type added successfully', 'easycms-wp' ),
					'info'
				);

				return $main_term;
			}

			return 0;
		}

		$this->log(
			sprintf(
				__( 'Unable to add stock_type. No translation found for default WP lang', 'easycms-wp' )
			),
			'error'
		);

		return 0;
	}

	private function insert( array $stock_type_data, int $parent_term_tax_id, string $lang ) {
		if ( ! empty( $stock_type_data['stock_type_id'] ) ) {
			$default_lang = Util::get_default_language();
			$ret = 0;

			Util::switch_language( $lang );

			$existing_term = $this->get_term( $stock_type_data['stock_type_id'], $lang );
			$parent_term = null;

			if ( 0 != $stock_type_data['parent_id'] ) {
				$this->log(
					__( 'This stock_type is a child. Trying to get parent', 'easycms-wp' ),
					'info'
				);

				$parent_term = $this->get_term( $stock_type_data['parent_id'] );
				if ( ! $parent_term ) {
					$this->log(
						sprintf(
							__( 'Unable to get parent stock_type (%d) in WP', 'easycms-wp' ),
							$stock_type_data['parent_id']
						),
						'warning'
					);
					$this->log(
						sprintf(
							__( 'Trying to get parent stock_type (%d) from CMS', 'easycms-wp' ),
							$stock_type_data['parent_id']
						),
						'info'
					);

					$parent_product_data = $this->get_stock_type( $stock_type_data['parent_id'] );
					if ( ! empty( $parent_product_data ) ) {
						$parent_term = $this->insert_stock_type( $parent_product_data[0] );
					} else {
						$this->log(
							sprintf(
								__( '%d parent stock_type (%d) data not found from CMS server', 'easycms-wp' ),
								$stock_type_data['stock_type_id'],
								$stock_type_data['parent_id']
							),
							'error'
						);
					}
				}
			}

			if ( 0 != $stock_type_data['parent_id'] && ! $parent_term ) {
				$this->log(
					sprintf(
						__(
							'Adding stock_type (%d) failed. Unable to retrieve parent stock_type (%d) from both CMS and WP',
							'easycms-wp'
						),
						$stock_type_data['stock_type_id'],
						$stock_type_data['parent_id']
					),
					'error'
				);

			} else if ( $lang && ! empty( $stock_type_data['stock_type_name'][ $lang ] ) ) {
				$slug = sprintf(
					'%s-%s',
					sanitize_title_with_dashes( $stock_type_data['stock_type_name'][ $lang ], '', 'save' ),
					$lang
				);

				$args = array(
					'slug' => $slug, // Slug changes on update too
				);

				if  ( ! empty( $parent_term ) ) {
					$args['parent'] = $parent_term->term_id;
				}

				if ( ! $existing_term ) {
					$term = wp_insert_term(
						$stock_type_data['stock_type_name'][ $lang ],
						$this->taxonomy,
						$args
					);
				} else {
					$args['name'] = $stock_type_data['stock_type_name'][ $lang ];
					$term = wp_update_term( $existing_term->term_id, $this->taxonomy, $args );
				}

				if ( is_wp_error( $term ) ) {
					$this->log(
						sprintf(
							__( 'Failed adding stock_type with stock_type_id (%d): %s', 'easycms-wp' ),
							$stock_type_data['stock_type_id'],
							$term->get_error_message()
						),
						'error'
					);
				} else {
					update_term_meta( $term['term_id'], $this->get_term_meta_name(), $stock_type_data['stock_type_id'] );
					update_term_meta( $term['term_id'], $this->get_term_data_meta_name(), $stock_type_data );
					if ( ! empty( $stock_type_data['image'] ) ) {
						$att_id = Util::url_to_attachment( $stock_type_data['image'], basename( $stock_type_data['image'] ) );
						if ( is_wp_error( $att_id ) ) {
							$this->log(
								sprintf(
									__( 'Error while setting image for stock_type stock_type_id %d: %s', 'easycms-wp' ),
									$stock_type_data['stock_type_id'],
									$att_id->get_error_message()
								),
								'error'
							);
						} else {
							update_term_meta( $term['term_id'], 'thumbnail_id', $att_id );
						}
					}
					$this->perform_translation( $term, $parent_term_tax_id, $lang );

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
				__( 'Cannot add stock_type. No stock_type_id specified in parameters', 'easycms-wp' ),
				'error'
			);
		}

		Util::switch_language( $default_lang );
		return $ret;
	}
}
?>