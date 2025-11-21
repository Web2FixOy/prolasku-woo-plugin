<?php
namespace EasyCMS_WP\Component;

use \EasyCMS_WP\Log;
use \EasyCMS_WP\Util;

class Category extends \EasyCMS_WP\Template\Component {
	public $taxonomy = 'product_cat';

	public static function can_run() {
		// Include compatibility functions if not already loaded
		if ( ! function_exists( 'prolasku_wpml_dependencies_ok' ) ) {
			$compat_file = EASYCMS_WP_CLASS_PATH . 'wpml-compatibility.php';
			if ( file_exists( $compat_file ) ) {
				require_once( $compat_file );
			}
		}
		
		// Safe fallback if compatibility function doesn't exist
		$wpml_ok = function_exists( 'prolasku_wpml_dependencies_ok' )
			? prolasku_wpml_dependencies_ok()
			: ( isset( $GLOBALS['woocommerce_wpml'] ) && property_exists( $GLOBALS['woocommerce_wpml'], 'dependencies_are_ok' )
				? $GLOBALS['woocommerce_wpml']->dependencies_are_ok
				: true );
		
		if (
			! class_exists( 'WooCommerce' ) ||
			! class_exists( 'woocommerce_wpml' ) ||
			! $wpml_ok
		) {
			Log::log(
				'category',
				__(
					'This component depends on WooCommerce, WooCommerce WPML & dependencies to run',
					'easycms-wp'
				),
				'error'
			);

			// add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
			return false;
		}

		return true;
	}

	public static function dependency_notice() {
		$class = 'notice notice-error';
		$message = __( 'EasyCMS Category component depends on WooCommerce, WooCommerce WPML & dependencies to run', 'easycms-wp' );
		
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public function sync() {
		set_time_limit( 0 );
		ignore_user_abort( true );

		$categories = $this->get_category();
		while ( ! empty( $categories ) ) {
			foreach ( $categories as $index => $category_data ) {
				$this->insert_category( $category_data );
			}
			$categories = $this->get_category();
		}
	}

	public function hooks() {
		// add_action( 'easywp_cms_save_api_settings', array( $this, 'sync' ), $this->priority );
		add_action( 'rest_api_init', array( $this, 'register_api' ) );

		add_filter( 'easycms_wp_product_component_before_save_product', array( $this, 'set_product_category' ), 10, 3 );
		add_filter( 'easycms_wp_set_order_item_data', array( $this, 'set_order_product_fields' ), 10, 2 );
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

		if ( ! empty( $product_data['cid'] ) ) {
			$term = $this->get_term( $product_data['cid'] );
			if (
				$term &&
				( $data = get_term_meta( $term->term_id, $this->get_term_data_meta_name(), true ) )
			) {

				$product_data['category_name'] = $data['category_name'];
			} else {
				$this->log(
					sprintf(
						__( 'Category for this product (%s) is not synced yet. Aborting...', 'easycms-wp' ),
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
			'callback'            => array( $this, 'rest_add_category' ),
			'args'                => array(
				'cid'               => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
				'parent_id'                     => array(
					'validate_callback' => array( $this, 'rest_validate_number' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
				'category_name'             => array(
					'validate_callback' => array( $this, 'rest_validate_array' ),
					'required'          => true,
				),
			)
		));

		register_rest_route( self::API_BASE, $this->get_module_name() . '/delete', array(
			'methods' => 'POST',
			'permission_callback' => array( $this, 'rest_check_auth' ),
			'callback'            => array( $this, 'rest_delete_category' ),
			'args'                => array(
				'cid'               => array(
					'validate_callback' => array( $this, 'rest_validate_id' ),
					'sanitize_callback' => 'absint',
					'required'          => true,
				),
			)
		));
	}

	public function set_product_category( \WC_Product $product, array $product_data, string $lang ) {
		if ( ! empty( $product_data['cid'] ) ) {
			$category = $this->get_term( $product_data['cid'], $lang );
			if ( $category ) {
				$product->set_category_ids( array( $category->term_id ) );
			} else {
				$this->log(
					sprintf(
						__( 'Unable to set product (%d) category. Category or translation not found on WP', 'easycms-wp' ),
						$product_data['pid']
					),
					'warning'
				);
			}
		}

		return $product;
	}

	public function rest_add_category( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$term = $this->insert_category( $params );

		if ( $term ) {
			return $this->rest_response( $term->term_id );
		}

		return $this->rest_response( '', 'FAIL', 400 );
	}

	public function rest_delete_category( \WP_REST_Request $request ) {
		$cid = $request['cid'];
		$matching_terms = $this->get_all_terms( $cid );

		if ( $matching_terms ) {
			foreach ( $matching_terms as $term_id ) {
				wp_delete_term( $term_id, $this->taxonomy );
			}
		}

		return $this->rest_response( 'success' );
	}

	private function get_all_terms( int $cid ) {
		global $wpdb;

		$query = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `term_id` FROM {$wpdb->termmeta} WHERE meta_key = %s AND meta_value = %d",
				$this->get_term_meta_name(),
				$cid,
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

	private function get_category( int $cid = 0, int $limit = 50 ) {
		static $page = 1;
		$offset = ($page - 1) * $limit ;

		$req = $this->make_request(
			'/get_categories',
			'POST',
			array(
				'cid' => $cid,
				'start' => $offset,
				'limit' => $limit,
			)
		);

		if ( is_wp_error( $req ) ) {
			$this->log(
				sprintf(
					__( 'Fetching categories failed: %s. Number of retries: (%d)', 'easycms-wp' ),
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
		return 'easycms_wp_cid';
	}

	public function get_term_data_meta_name() {
		return 'easycms_wp_data';
	}

	public function get_term( int $cid, string $lang = '' ) {
		global $wpdb;
		$result = false;
		$previous_lang = Util::get_default_language();

		if ( !empty($lang) ) {
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
					'value' => $cid,
				),
			),
		);		

		/* WORKING SIMPLE QUERY! */
		/*
		$query = "SELECT t.*, tt.*, tr.* " .
			"FROM {$wpdb->terms} AS t " .
			"INNER JOIN {$wpdb->term_taxonomy} AS tt " .
			"ON t.term_id = tt.term_id " .
			"INNER JOIN {$wpdb->termmeta} AS tm " .
			"ON t.term_id = tm.term_id " .
			"INNER JOIN {$wpdb->prefix}icl_translations AS tr " .
			"ON tt.term_taxonomy_id = tr.element_id " .
			"WHERE tt.taxonomy = '{$this->taxonomy}' " .
			"AND tm.meta_key = '{$this->get_term_meta_name()}' " .
			"AND tm.meta_value = '{$cid}' " .
			"AND tr.element_type = 'tax_{$this->taxonomy}' " .
			"AND tr.language_code = '{$lang}' " .
			"LIMIT 1";


		$terms = $wpdb->get_results( $query );
		if(!empty($terms)){
			$result = $terms[0];			
		}

		$this->log(
			sprintf(
				__( 'RESULT for language %s is %s', 'easycms-wp' ),
				$lang,
				json_encode($terms)
			),
			'info'
		);

		return $result;
		*/
		
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

		if ( !empty($lang) ) {
			Util::switch_language( $previous_lang );
		}

		return $result;
	}

	private function prepare_category_data( array $category_data ) {
		if ( ! empty( $category_data['category_name'] ) ) {
			$category_data['category_name'] = Util::strip_locale( $category_data['category_name'] );
			$category_data['category_name'] = array_filter(
				$category_data['category_name'],
				array( '\EasyCMS_WP\Util', 'is_language_active' ),
				ARRAY_FILTER_USE_KEY
			);
		}

		return $category_data;
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
						__( 'Translation fail. Failed to find parent translation category', 'easycms-wp' )
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
				__('Translation done for term_id %s AND term_taxonomy_id %s AND this->taxonomy %s', 'easycms-wp' ),
				$insert_term_result['term_id'],
				$insert_term_result['term_taxonomy_id'],
				$this->taxonomy
			),
			'info'
		);
	}

	public function insert_category( array $category_data ) {
		$category_data = $this->prepare_category_data( $category_data );
		$default_lang = Util::get_default_language();

		$this->log(
			sprintf(
				__( 'INSERT_CATEGORY FUNCTION, default_lang is: (%s) AND category_name for default language is: (%s)', 'easycms-wp' ),
				$default_lang,
				$category_data['category_name'][$default_lang]
			),
			'debug'
		);

		if ( ! empty( $category_data['category_name'][ $default_lang ] ) ) {
			$main_term = $this->insert( $category_data, 0, $default_lang );// 0 means this is the main language for this category 

			$this->log(
				sprintf(
					__( 'Main term for the inserted category is: %s', 'easycms-wp' ),
					json_encode($main_term)
				),
				'debug'
			);

			if ( $main_term ) {
				foreach ( $category_data['category_name'] as $lang => $name ) {
					if ( $lang == $default_lang ) {
						continue;
					}
					$this->log(
						sprintf(
							__( 'Adding translation for %s and cid %d default lang: %s', 'easycms-wp' ),
							$lang,
							$category_data['cid'],
							$default_lang
						),
						'info'
					);


					$trans_term = $this->insert( $category_data, $main_term->term_taxonomy_id, $lang );
					$this->log(
						sprintf(
							__( 'Sub term for the inserted category is: %s', 'easycms-wp' ),
							json_encode($trans_term)
						),
						'debug'
					);

				}

				$this->log(
					__( 'Category added successfully', 'easycms-wp' ),
					'info'
				);

				return $main_term;
			}

			return 0;
		}

		$this->log(
			sprintf(
				__( 'Unable to add category. No translation found for default WP lang', 'easycms-wp' )
			),
			'error'
		);

		return 0;
	}


	/*
	Here is what the insert code does:

	If the $category_data['cid'] is not empty, it continues with the insertion process.

	It checks if the term already exists for the language in question by calling $this->get_term($category_data['cid'], $lang).

	If the term does not exist for the given language, it checks if the category is a child by checking if $category_data['parent_id'] is equal to 0.

	If the category is a child, it attempts to retrieve the parent category from the WordPress database by calling $this->get_term($category_data['parent_id']).

	If the parent category does not exist in the WordPress database, it attempts to retrieve the parent category data from the CMS server by calling $this->get_category($category_data['parent_id']).

	If the parent category data is retrieved, it inserts the parent category into the WordPress database by calling $this->insert_category($parent_product_data[0]).

	If the parent category data cannot be retrieved, the code logs an error message.

	If the parent category exists or if the category is not a child, it sets the $args array which will be used to insert the term.

	The code then inserts the term into the WordPress database by calling wp_insert_term( $category_data['cid'], 'product_cat', $args ).

	The code then updates the term's meta data by calling update_term_meta( $ret, 'cid', $category_data['cid'] ).

	Finally, the code returns the term ID.
	*/
	private function insert( array $category_data, int $parent_term_tax_id, string $lang ) {
		if ( ! empty( $category_data['cid'] ) ) {
			$default_lang = Util::get_default_language();
			$ret = 0;

			Util::switch_language( $lang );

			$existing_term = null;
			$existing_term = $this->get_term( $category_data['cid'], $lang);
			// Check if the term already exists for the given language
    		// $term_exists = term_exists( $category_data['cid'], 'product_cat', $parent_term_tax_id );

			$this->log(
				sprintf(
					__( '### INSERT FUNCTION, parent_id is: (%s) - existing_term for LANG: %s is: %s', 'easycms-wp' ),
					$category_data['parent_id'],
					$lang,
					json_encode($existing_term),
				),
				'debug'
			);

			#### Figuring out if there is parent_term
			$parent_term = null;
			if ( 0 != $category_data['parent_id'] ) {
				$this->log(
					__( 'This category is a child. Trying to get parent', 'easycms-wp' ),
					'info'
				);

				$parent_term = $this->get_term( $category_data['parent_id'] );
				if ( ! $parent_term ) {
					$this->log(
						sprintf(
							__( 'Unable to get parent category (%d) in WP', 'easycms-wp' ),
							$category_data['parent_id']
						),
						'warning'
					);
					$this->log(
						sprintf(
							__( 'Trying to get parent category (%d) from CMS', 'easycms-wp' ),
							$category_data['parent_id']
						),
						'info'
					);

					$parent_product_data = $this->get_category( $category_data['parent_id'] );
					if ( ! empty( $parent_product_data ) ) {
						$parent_term = $this->insert_category( $parent_product_data[0] );
					} else {
						$this->log(
							sprintf(
								__( '%d parent category (%d) data not found from CMS server', 'easycms-wp' ),
								$category_data['cid'],
								$category_data['parent_id']
							),
							'error'
						);
					}
				}
			}

			if ( 0 != $category_data['parent_id'] && ! $parent_term ) {
				$this->log(
					sprintf(
						__(
							'Adding category (%d) failed. Unable to retrieve parent category (%d) from both CMS and WP',
							'easycms-wp'
						),
						$category_data['cid'],
						$category_data['parent_id']
					),
					'error'
				);

			} else if ( $lang && ! empty( $category_data['category_name'][ $lang ] ) ) {
				$slug = sprintf(
					'%s-%s',
					sanitize_title_with_dashes( $category_data['category_name'][ $lang ], '', 'save' ),
					$lang
				);

				$args = array(
					'slug' => $slug, // Slug changes on update too
					'meta_lang' => $lang,
				);

				if  ( ! empty( $parent_term ) ) {
					$args['parent'] = $parent_term->term_id;
				}else{
					$args['parent'] = null;
				}

				if ( ! $existing_term ) {
					$term = wp_insert_term(
						$category_data['category_name'][ $lang ],
						$this->taxonomy,
						$args
					);
					$this->log(
						sprintf(
							__( 'existing_term NOT EXIST , new inserted term is: %s', 'easycms-wp' ),
							json_encode($term)
						),
						'debug'
					);

				} else {
					$args['name'] = $category_data['category_name'][ $lang ];
					$term = wp_update_term( $existing_term->term_id, $this->taxonomy, $args );
					$this->log(
						sprintf(
							__( 'existing_term EXISTs, updated term is: %s', 'easycms-wp' ),
							json_encode($term)
						),
						'debug'
					);
				}

				if ( is_wp_error( $term ) ) {
					$this->log(
						sprintf(
							__( 'Failed adding category with cid (%d): %s', 'easycms-wp' ),
							$category_data['cid'],
							$term->get_error_message()
						),
						'error'
					);
				} else {
					$this->log(
						sprintf(
							__( 'INSERT::ELSE STATEMENT - Adding category with cid (%d), SLUG: %s, Category name: %s ', 'easycms-wp' ),
							$category_data['cid'],
							$slug,
							$category_data['category_name'][ $lang ]
						),
						'info'
					);
					update_term_meta( $term['term_id'], 'meta_lang', $lang );
					update_term_meta( $term['term_id'], $this->get_term_meta_name(), $category_data['cid'] );
					update_term_meta( $term['term_id'], $this->get_term_data_meta_name(), $category_data );
					if ( ! empty( $category_data['image'] ) ) {
						$att_id = Util::url_to_attachment( $category_data['image'], basename( $category_data['image'] ) );
						if ( is_wp_error( $att_id ) ) {
							$this->log(
								sprintf(
									__( 'Error while setting image for category cid %d: %s', 'easycms-wp' ),
									$category_data['cid'],
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
				__( 'Cannot add category. No cid specified in parameters', 'easycms-wp' ),
				'error'
			);
		}

		Util::switch_language( $default_lang );
		return $ret;
	}


	/*
	private function insert_ORIGINAL( array $category_data, int $parent_term_tax_id, string $lang ) {
		if ( ! empty( $category_data['cid'] ) ) {
			$default_lang = Util::get_default_language();
			$ret = 0;

			Util::switch_language( $lang );

			$existing_term = $this->get_term( $category_data['cid'] );
			

			// $this->log(
			// 	sprintf(
			// 		__( 'INSERT FUNCTION, parent_id is: (%s)', 'easycms-wp' ),
			// 		$category_data['parent_id']
			// 	),
			// 	'info'
			// );


			#### Figuring out if there is parent_term
			$parent_term = null;
			if ( 0 != $category_data['parent_id'] ) {
				$this->log(
					__( 'This category is a child. Trying to get parent', 'easycms-wp' ),
					'info'
				);

				$parent_term = $this->get_term( $category_data['parent_id'] );
				if ( ! $parent_term ) {
					$this->log(
						sprintf(
							__( 'Unable to get parent category (%d) in WP', 'easycms-wp' ),
							$category_data['parent_id']
						),
						'warning'
					);
					$this->log(
						sprintf(
							__( 'Trying to get parent category (%d) from CMS', 'easycms-wp' ),
							$category_data['parent_id']
						),
						'info'
					);

					$parent_product_data = $this->get_category( $category_data['parent_id'] );
					if ( ! empty( $parent_product_data ) ) {
						$parent_term = $this->insert_category( $parent_product_data[0] );
					} else {
						$this->log(
							sprintf(
								__( '%d parent category (%d) data not found from CMS server', 'easycms-wp' ),
								$category_data['cid'],
								$category_data['parent_id']
							),
							'error'
						);
					}
				}
			}

			if ( 0 != $category_data['parent_id'] && ! $parent_term ) {
				$this->log(
					sprintf(
						__(
							'Adding category (%d) failed. Unable to retrieve parent category (%d) from both CMS and WP',
							'easycms-wp'
						),
						$category_data['cid'],
						$category_data['parent_id']
					),
					'error'
				);

			} else if ( $lang && ! empty( $category_data['category_name'][ $lang ] ) ) {
				$slug = sprintf(
					'%s-%s',
					sanitize_title_with_dashes( $category_data['category_name'][ $lang ], '', 'save' ),
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
						$category_data['category_name'][ $lang ],
						$this->taxonomy,
						$args
					);
				} else {
					$args['name'] = $category_data['category_name'][ $lang ];
					$term = wp_update_term( $existing_term->term_id, $this->taxonomy, $args );
				}

				if ( is_wp_error( $term ) ) {
					$this->log(
						sprintf(
							__( 'Failed adding category with cid (%d): %s', 'easycms-wp' ),
							$category_data['cid'],
							$term->get_error_message()
						),
						'error'
					);
				} else {
					$this->log(
						sprintf(
							__( 'INSERT::ELSE STATEMENT - Adding category with cid (%d), SLUG: %s, Category name: %s ', 'easycms-wp' ),
							$category_data['cid'],
							$slug,
							$category_data['category_name'][ $lang ]
						),
						'info'
					);
					update_term_meta( $term['term_id'], $this->get_term_meta_name(), $category_data['cid'] );
					update_term_meta( $term['term_id'], $this->get_term_data_meta_name(), $category_data );
					if ( ! empty( $category_data['image'] ) ) {
						$att_id = Util::url_to_attachment( $category_data['image'], basename( $category_data['image'] ) );
						if ( is_wp_error( $att_id ) ) {
							$this->log(
								sprintf(
									__( 'Error while setting image for category cid %d: %s', 'easycms-wp' ),
									$category_data['cid'],
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
				__( 'Cannot add category. No cid specified in parameters', 'easycms-wp' ),
				'error'
			);
		}

		Util::switch_language( $default_lang );
		return $ret;
	}
	
	/**
	 * Get category statistics for cleanup analysis - IMPROVED VERSION
	 */
	public function get_category_statistics() {
		set_time_limit( 120 );
		
		global $wpdb;
		$stats = array();
		
		try {
			// Get categories by language
			$default_lang = Util::get_default_language();
			$all_languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=asc' );
			
			$this->log(
				sprintf(
					__( 'DEBUG: Default language: %s, Active languages: %s', 'easycms-wp' ),
					$default_lang,
					json_encode( array_keys( $all_languages ) )
				),
				'info'
			);
			
			if ( ! $all_languages ) {
				$all_languages = array( $default_lang => array( 'native_name' => $default_lang ) );
			}
			
			// Count categories per language
			$category_counts = array();
			$main_category_ids = array();
			
			// First, get all main categories (default language)
			Util::switch_language( $default_lang );
			$main_categories = get_terms( array(
				'taxonomy' => $this->taxonomy,
				'hide_empty' => false,
				'cache_results' => false,
				'number' => 1000
			) );
			
			$this->log(
				sprintf(
					__( 'DEBUG: Main language %s has %d total terms', 'easycms-wp' ),
					$default_lang,
					is_wp_error( $main_categories ) ? 0 : count( $main_categories )
				),
				'info'
			);
			
			if ( ! is_wp_error( $main_categories ) ) {
				// FIX: Count only categories with CID meta fields
				$main_categories_with_cid = 0;
				foreach ( $main_categories as $category ) {
					$cid = get_term_meta( $category->term_id, $this->get_term_meta_name(), true );
					
					$this->log(
						sprintf(
							__( 'DEBUG: Main category "%s" (term_id: %d) has CID: %s', 'easycms-wp' ),
							$category->name,
							$category->term_id,
							$cid ?: 'NULL/NOT SET'
						),
						'debug'
					);
					
					if ( $cid ) {
						$main_categories_with_cid++;
						$main_category_ids[ $cid ] = array(
							'term_id' => $category->term_id,
							'name' => $category->name,
							'slug' => $category->slug
						);
					}
				}
				$category_counts[ $default_lang ] = $main_categories_with_cid; // FIX: Use count with CID
			}
			
			$this->log(
				sprintf(
					__( 'DEBUG: Found %d main categories with CIDs', 'easycms-wp' ),
					count( $main_category_ids )
				),
				'info'
			);
			
			// Count translations for each language and add to category_counts
			$translation_counts = array();
			foreach ( $all_languages as $lang_code => $lang_data ) {
				if ( $lang_code === $default_lang ) {
					continue;
				}
				
				$this->log(
					sprintf( __( 'DEBUG: Processing language for count: %s', 'easycms-wp' ), $lang_code ),
					'info'
				);
				
				Util::switch_language( $lang_code );
				$translated_categories = get_terms( array(
					'taxonomy' => $this->taxonomy,
					'hide_empty' => false,
					'cache_results' => false,
					'number' => 1000
				) );
				
				// FIX: Count only categories with CID meta fields for translations too
				$categories_with_cid = 0;
				$categories_without_cid = 0;
				$cid_list = array(); // Track all CIDs found
				
				if ( ! is_wp_error( $translated_categories ) ) {
					foreach ( $translated_categories as $category ) {
						$cid = get_term_meta( $category->term_id, $this->get_term_meta_name(), true );
						if ( $cid ) {
							$categories_with_cid++;
							$cid_list[] = $cid;
							$this->log(
								sprintf(
									__( 'DEBUG: Translation category "%s" (term_id: %d) has CID: %d', 'easycms-wp' ),
									$category->name,
									$category->term_id,
									$cid
								),
								'debug'
							);
						} else {
							$categories_without_cid++;
							$this->log(
								sprintf(
									__( 'DEBUG: Translation category "%s" (term_id: %d) has NO CID', 'easycms-wp' ),
									$category->name,
									$category->term_id
								),
								'debug'
							);
						}
					}
				}
				
				$this->log(
					sprintf( __( 'DEBUG: Language %s has %d categories with CID, %d without CID (total: %d)', 'easycms-wp' ), $lang_code, $categories_with_cid, $categories_without_cid, is_wp_error( $translated_categories ) ? 0 : count( $translated_categories ) ),
					'info'
				);
				
				// CRITICAL DEBUG: Show all CIDs found
				if ( ! empty( $cid_list ) ) {
					$this->log(
						sprintf(
							__( 'DEBUG: Language %s CIDs found: %s', 'easycms-wp' ),
							$lang_code,
							implode( ', ', $cid_list )
						),
						'info'
					);
				}
				
				// CRITICAL DEBUG: Show if this matches expected count
				$expected_count = 69; // User said translations should be 69
				if ( $categories_with_cid !== $expected_count ) {
					$this->log(
						sprintf(
							__( 'DEBUG: COUNT MISMATCH - Language %s has %d categories with CID but expected %d!', 'easycms-wp' ),
							$lang_code,
							$categories_with_cid,
							$expected_count
						),
						'warning'
					);
					
					// Show which category might be extra
					if ( $categories_with_cid > $expected_count ) {
						$this->log(
							sprintf(
								__( 'DEBUG: Language %s has %d EXTRA categories with CID!', 'easycms-wp' ),
								$lang_code,
								$categories_with_cid - $expected_count
							),
							'warning'
						);
					}
				} else {
					$this->log(
						sprintf(
							__( 'DEBUG: COUNT OK - Language %s has %d categories with CID as expected', 'easycms-wp' ),
							$lang_code,
							$categories_with_cid
						),
						'info'
					);
				}
				
				$translation_counts[ $lang_code ] = $categories_with_cid;
				$category_counts[ $lang_code ] = $categories_with_cid; // CRITICAL FIX: Add to category_counts
			}
			
			Util::switch_language( $default_lang );
			
			// Find orphaned translations
			$this->log(
				__( 'DEBUG: Starting orphaned translation detection...', 'easycms-wp' ),
				'info'
			);
			$orphaned_translations = $this->find_orphaned_translations( $main_category_ids, $all_languages );
			
			$stats = array(
				'main_language' => $default_lang,
				'category_counts' => $category_counts,
				'translation_counts' => $translation_counts,
				'total_main_categories' => count( $main_category_ids ), // FIX: Use categories with CIDs
				'total_translations' => array_sum( $translation_counts ),
				'orphaned_translations' => $orphaned_translations,
				'languages' => array_keys( $all_languages )
			);
			
			$this->log(
				sprintf(
					__( 'DEBUG: Statistics complete - Main: %d, Translations: %d, Orphaned: %d', 'easycms-wp' ),
					$stats['total_main_categories'],
					$stats['total_translations'],
					count( $orphaned_translations )
				),
				'info'
			);
			
		} catch ( \Exception $e ) {
			$this->log(
				sprintf( __( 'Error getting category statistics: %s', 'easycms-wp' ), $e->getMessage() ),
				'error'
			);
		}
		
		return $stats;
	}
	
	/**
	 * Find orphaned translations that don't belong to main categories - WORKING VERSION
	 */
	private function find_orphaned_translations( $main_category_ids, $all_languages ) {
		$orphaned = array();
		$default_lang = Util::get_default_language();
		
		$this->log(
			sprintf(
				__( 'DEBUG: Looking for orphaned categories. Main language: %s, Main category IDs count: %d', 'easycms-wp' ),
				$default_lang,
				count( $main_category_ids )
			),
			'info'
		);
		
		// Get all valid CIDs from main language
		$valid_main_cids = array_keys( $main_category_ids );
		$this->log(
			sprintf(
				__( 'DEBUG: Valid main CIDs (%d): %s', 'easycms-wp' ),
				count( $valid_main_cids ),
				implode( ', ', $valid_main_cids )
			),
			'info'
		);
		
		// Process each translation language
		foreach ( $all_languages as $lang_code => $lang_data ) {
			if ( $lang_code === $default_lang ) {
				continue; // Skip main language
			}
			
			$this->log(
				sprintf( __( 'DEBUG: Processing language: %s', 'easycms-wp' ), $lang_code ),
				'info'
			);
			
			Util::switch_language( $lang_code );
			
			$translated_categories = get_terms( array(
				'taxonomy' => $this->taxonomy,
				'hide_empty' => false,
				'cache_results' => false,
				'number' => 1000
			) );
			
			if ( is_wp_error( $translated_categories ) ) {
				$this->log(
					sprintf( __( 'DEBUG: Error getting categories for language %s', 'easycms-wp' ), $lang_code ),
					'error'
				);
				continue;
			}
			
			$this->log(
				sprintf(
					__( 'DEBUG: Language %s has %d total categories', 'easycms-wp' ),
					$lang_code,
					count( $translated_categories )
				),
				'info'
			);
			
			$processed_count = 0;
			$skipped_count = 0;
			$orphaned_count = 0;
			$cid_occurrences = array(); // Track CID occurrences to find duplicates
			
			foreach ( $translated_categories as $category ) {
				$cid = get_term_meta( $category->term_id, $this->get_term_meta_name(), true );
				
				// Skip categories without CID
				if ( ! $cid ) {
					$skipped_count++;
					$this->log(
						sprintf(
							__( 'DEBUG: Skipping category "%s" (term_id: %d) in %s - no CID meta field', 'easycms-wp' ),
							$category->name,
							$category->term_id,
							$lang_code
						),
						'debug'
					);
					continue;
				}
				
				$processed_count++;
				
				// Track CID occurrences for duplicate detection
				if ( ! isset( $cid_occurrences[ $cid ] ) ) {
					$cid_occurrences[ $cid ] = array();
				}
				$cid_occurrences[ $cid ][] = $category;
				
				$this->log(
					sprintf(
						__( 'DEBUG: Checking category "%s" (term_id: %d) with CID: %d', 'easycms-wp' ),
						$category->name,
						$category->term_id,
						$cid
					),
					'debug'
				);
			}
			
			// Now process each CID and find duplicates and orphaned
			foreach ( $cid_occurrences as $cid => $categories_with_same_cid ) {
				$category_count = count( $categories_with_same_cid );
				
				if ( $category_count > 1 ) {
					// DUPLICATE CID FOUND - Mark all but the first as orphaned
					$this->log(
						sprintf(
							__( 'DEBUG: DUPLICATE CID %d found %d times in %s', 'easycms-wp' ),
							$cid,
							$category_count,
							$lang_code
						),
						'warning'
					);
					
					// Keep the first one, mark the rest as orphaned
					for ( $i = 1; $i < $category_count; $i++ ) {
						$category = $categories_with_same_cid[ $i ];
						$orphaned_count++;
						$this->log(
							sprintf(
								__( 'DEBUG: ORPHANED DUPLICATE - Category "%s" (term_id: %d, CID: %d) in %s is a duplicate', 'easycms-wp' ),
								$category->name,
								$category->term_id,
								$cid,
								$lang_code
							),
							'info'
						);
						
						$orphaned[] = array(
							'term_id' => $category->term_id,
							'cid' => $cid,
							'name' => $category->name,
							'slug' => $category->slug,
							'language' => $lang_code,
							'count' => $category->count,
							'reason' => 'Duplicate CID in same language',
							'no_cid_meta' => false
						);
					}
				} else {
					// SINGLE CID - Check if it's orphaned (not in main language)
					$category = $categories_with_same_cid[ 0 ];
					
					// SIMPLE ORPHANED CHECK: If CID is not in main language CIDs, it's orphaned
					if ( ! in_array( $cid, $valid_main_cids ) ) {
						$orphaned_count++;
						$this->log(
							sprintf(
								__( 'DEBUG: ORPHANED FOUND - Category "%s" (CID: %d) in %s does not exist in main language', 'easycms-wp' ),
								$category->name,
								$cid,
								$lang_code
							),
							'info'
						);
						
						$orphaned[] = array(
							'term_id' => $category->term_id,
							'cid' => $cid,
							'name' => $category->name,
							'slug' => $category->slug,
							'language' => $lang_code,
							'count' => $category->count,
							'reason' => 'CID exists but not in main language',
							'no_cid_meta' => false
						);
					} else {
						$this->log(
							sprintf(
								__( 'DEBUG: OK - Category "%s" (CID: %d) in %s exists in main language', 'easycms-wp' ),
								$category->name,
								$cid,
								$lang_code
							),
							'debug'
						);
					}
				}
			}
			
			$this->log(
				sprintf(
					__( 'DEBUG: Language %s summary: %d processed, %d skipped (no CID), %d orphaned found', 'easycms-wp' ),
					$lang_code,
					$processed_count,
					$skipped_count,
					$orphaned_count
				),
				'info'
			);
		}
		
		Util::switch_language( $default_lang );
		
		$this->log(
			sprintf(
				__( 'DEBUG: FINAL - Found %d orphaned categories total', 'easycms-wp' ),
				count( $orphaned )
			),
			'info'
		);
		
		return $orphaned;
	}
	
	/**
	 * Get detailed analysis of duplicate and abandoned categories
	 */
	public function get_category_cleanup_analysis() {
		set_time_limit( 300 );
		
		$stats = $this->get_category_statistics();
		
		$this->log(
			sprintf(
				__( 'ANALYSIS: Statistics retrieved - Main: %d, Translations: %d, Orphaned: %d', 'easycms-wp' ),
				$stats['total_main_categories'],
				$stats['total_translations'],
				count( $stats['orphaned_translations'] )
			),
			'info'
		);
		
		// Check for mismatches after cleanup
		$this->check_for_mismatches( $stats );
		
		$analysis = array(
			'statistics' => $stats,
			'duplicate_categories' => $this->find_duplicate_categories(),
			'abandoned_categories' => $stats['orphaned_translations'],
			'total_candidates_for_deletion' => 0
		);
		
		// Count total categories that can be safely deleted
		$analysis['total_candidates_for_deletion'] = count( $analysis['abandoned_categories'] );
		
		$this->log(
			sprintf(
				__( 'ANALYSIS: Final result - %d candidates for deletion', 'easycms-wp' ),
				$analysis['total_candidates_for_deletion']
			),
			'info'
		);
		
		return $analysis;
	}
	
	/**
	 * Check for category count mismatches between languages
	 */
	private function check_for_mismatches( $stats ) {
		$default_lang = Util::get_default_language();
		$main_count = $stats['total_main_categories'];
		
		foreach ( $stats['category_counts'] as $lang => $count ) {
			if ( $lang === $default_lang ) {
				continue;
			}
			
			if ( $count !== $main_count ) {
				$this->log(
					sprintf(
						__( '❌ MISMATCH DETECTED! %s: %d vs Main: %d', 'easycms-wp' ),
						strtoupper( $lang ),
						$count,
						$main_count
					),
					'warning'
				);
			} else {
				$this->log(
					sprintf(
						__( '✅ COUNT OK - %s: %d matches Main: %d', 'easycms-wp' ),
						strtoupper( $lang ),
						$count,
						$main_count
					),
					'info'
				);
			}
		}
	}
	
	/**
	 * Find duplicate categories (same CID in same language)
	 */
	private function find_duplicate_categories() {
		global $wpdb;
		$duplicates = array();
		$default_lang = Util::get_default_language();
		
		$all_languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=asc' );
		if ( ! $all_languages ) {
			$all_languages = array( $default_lang => array( 'native_name' => $default_lang ) );
		}
		
	 foreach ( $all_languages as $lang_code => $lang_data ) {
			Util::switch_language( $lang_code );
			
			$query = $wpdb->prepare(
				"SELECT tm1.meta_value as cid, t1.term_id, t1.name, t1.slug
				 FROM {$wpdb->terms} t1
				 INNER JOIN {$wpdb->term_taxonomy} tt1 ON t1.term_id = tt1.term_id
				 INNER JOIN {$wpdb->termmeta} tm1 ON t1.term_id = tm1.term_id
				 WHERE tt1.taxonomy = %s
				 AND tm1.meta_key = %s
				 ORDER BY tm1.meta_value, t1.term_id",
				$this->taxonomy,
				$this->get_term_meta_name()
			);
			
			$results = $wpdb->get_results( $query );
			
			$cid_groups = array();
		 foreach ( $results as $result ) {
				if ( ! isset( $cid_groups[ $result->cid ] ) ) {
					$cid_groups[ $result->cid ] = array();
				}
				$cid_groups[ $result->cid ][] = $result;
			}
			
			// Find groups with more than one entry (duplicates)
		 foreach ( $cid_groups as $cid => $entries ) {
				if ( count( $entries ) > 1 ) {
					$duplicates[ $lang_code ] = $entries;
				}
			}
		}
		
		Util::switch_language( $default_lang );
		return $duplicates;
	}
	
	/**
	 * Clean up orphaned and duplicate categories
	 * 
	 * @param bool $dry_run Whether to actually delete or just show what would be deleted
	 * @param array $options Cleanup options
	 * @return array Results of the cleanup operation
	 */
	/**
	 * Clean up orphaned and duplicate categories - CONSISTENT IMPROVED VERSION
	 *
	 * @param bool $dry_run Whether to actually delete or just show what would be deleted
	 * @param array $options Cleanup options
	 * @return array Results of the cleanup operation
	 */
	public function cleanup_categories( $dry_run = true, $options = array() ) {
		set_time_limit( 300 );
		
		$results = array(
			'dry_run' => $dry_run,
			'deleted_categories' => array(),
			'errors' => array(),
			'warnings' => array(),
			'summary' => array()
		);
		
		try {
			// Get statistics using the improved method
			$analysis = $this->get_category_cleanup_analysis();
			$orphaned_categories = $analysis['abandoned_categories'];
			
			$this->log(
				sprintf(
					__( 'CLEANUP: Found %d orphaned categories to process', 'easycms-wp' ),
					count( $orphaned_categories )
				),
				'info'
			);
			
			// DEBUG: Log all orphaned categories found
			if ( ! empty( $orphaned_categories ) ) {
				$this->log( __( 'CLEANUP: Orphaned categories details:', 'easycms-wp' ), 'info' );
				foreach ( $orphaned_categories as $category ) {
					$this->log(
						sprintf(
							__( 'CLEANUP: Orphaned - "%s" (term_id: %d, CID: %d, lang: %s, count: %d, reason: %s)', 'easycms-wp' ),
							$category['name'],
							$category['term_id'],
							$category['cid'],
							$category['language'],
							$category['count'],
							$category['reason']
						),
						'info'
					);
				}
			} else {
				$this->log( __( 'CLEANUP: No orphaned categories found!', 'easycms-wp' ), 'warning' );
			}
			
			// Process each orphaned category
			foreach ( $orphaned_categories as $category ) {
				$this->log(
					sprintf(
						__( 'CLEANUP: Processing category "%s" (term_id: %d) - has %d products', 'easycms-wp' ),
						$category['name'],
						$category['term_id'],
						$category['count']
					),
					'info'
				);
				
				// For duplicate categories, we need to reassign products before deletion
				if ( $category['count'] > 0 && strpos( $category['reason'], 'Duplicate' ) !== false ) {
					$this->log(
						sprintf(
							__( 'CLEANUP: DUPLICATE with products - Category "%s" has %d products, will reassign before deletion', 'easycms-wp' ),
							$category['name'],
							$category['count']
						),
						'info'
					);
					
					// Find the correct category to reassign products to
					$correct_category = $this->find_correct_category_for_duplicate( $category['cid'], $category['language'] );
					
					if ( $correct_category ) {
						if ( ! $dry_run ) {
							$this->reassign_products_to_category( $category['term_id'], $correct_category->term_id );
						}
					} else {
						$results['warnings'][] = sprintf(
							__( 'Cannot delete category "%s" (%s) - no correct category found to reassign %d products', 'easycms-wp' ),
							$category['name'],
							$category['language'],
							$category['count']
						);
						$this->log(
							sprintf(
								__( 'CLEANUP: SKIPPING category "%s" - no correct category found for reassignment', 'easycms-wp' ),
								$category['name']
							),
							'warning'
						);
						continue;
					}
				} else if ( $category['count'] > 0 ) {
					// For non-duplicate orphaned categories with products, skip deletion
					$results['warnings'][] = sprintf(
						__( 'Category "%s" (%s) has %d products and will not be deleted', 'easycms-wp' ),
						$category['name'],
						$category['language'],
						$category['count']
					);
					$this->log(
						sprintf(
							__( 'CLEANUP: SKIPPING category "%s" - has %d products', 'easycms-wp' ),
							$category['name'],
							$category['count']
						),
						'info'
					);
					continue;
				}
				
				// Check if category has images that might be used elsewhere
				$thumbnail_id = get_term_meta( $category['term_id'], 'thumbnail_id', true );
				if ( $thumbnail_id ) {
					$results['warnings'][] = sprintf(
						__( 'Category "%s" (%s) has an image attachment (ID: %d) that will be preserved', 'easycms-wp' ),
						$category['name'],
						$category['language'],
						$thumbnail_id
					);
				}
				
				$this->log(
					sprintf(
						__( 'CLEANUP: %s category "%s" (term_id: %d)', 'easycms-wp' ),
						$dry_run ? 'DRY RUN - Would delete' : 'EXECUTING - Deleting',
						$category['name'],
						$category['term_id']
					),
					'info'
				);
				
				if ( $dry_run ) {
					$results['deleted_categories'][] = array(
						'action' => 'would_delete_orphaned',
						'term_id' => $category['term_id'],
						'cid' => $category['cid'],
						'name' => $category['name'],
						'language' => $category['language'],
						'count' => $category['count'],
						'reason' => isset( $category['no_cid_meta'] ) ? 'No CID meta field' : 'Not in main language'
					);
				} else {
					// Actually delete the category
					$this->log(
						sprintf(
							__( 'CLEANUP: EXECUTING wp_delete_term for term_id %d', 'easycms-wp' ),
							$category['term_id']
						),
						'info'
					);
					
					$delete_result = wp_delete_term( $category['term_id'], $this->taxonomy );
					
					if ( is_wp_error( $delete_result ) ) {
						$results['errors'][] = sprintf(
							__( 'Failed to delete category "%s" (%s): %s', 'easycms-wp' ),
							$category['name'],
							$category['language'],
							$delete_result->get_error_message()
						);
						$this->log(
							sprintf(
								__( 'CLEANUP: ERROR deleting category "%s": %s', 'easycms-wp' ),
								$category['name'],
								$delete_result->get_error_message()
							),
							'error'
						);
					} else {
						$results['deleted_categories'][] = array(
							'action' => 'deleted_orphaned',
							'term_id' => $category['term_id'],
							'cid' => $category['cid'],
							'name' => $category['name'],
							'language' => $category['language'],
							'count' => $category['count'],
							'reason' => isset( $category['no_cid_meta'] ) ? 'No CID meta field' : 'Not in main language'
						);
						$this->log(
							sprintf(
								__( 'CLEANUP: SUCCESS deleted category "%s" (term_id: %d)', 'easycms-wp' ),
								$category['name'],
								$category['term_id']
							),
							'info'
						);
					}
				}
			}
			
			// Summary
			$results['summary'] = array(
				'total_main_categories' => $analysis['statistics']['total_main_categories'],
				'to_delete' => count( $results['deleted_categories'] ),
				'errors' => count( $results['errors'] ),
				'warnings' => count( $results['warnings'] ),
				'orphaned_found' => count( $orphaned_categories )
			);
			
		} catch ( \Exception $e ) {
			$results['errors'][] = sprintf( __( 'Cleanup failed: %s', 'easycms-wp' ), $e->getMessage() );
		}
		
		$this->log(
			sprintf(
				__( 'Category cleanup completed: %d categories %s. Found %d main categories to match against.', 'easycms-wp' ),
				$results['summary']['to_delete'],
				$dry_run ? 'would be deleted' : 'deleted',
				$results['summary']['total_main_categories']
			),
			'info'
		);
		
		return $results;
	}
	
	/**
		* Find the correct category for a duplicate (the first one with the same CID)
		*/
	private function find_correct_category_for_duplicate( $cid, $language ) {
		global $wpdb;
		
		$this->log(
			sprintf(
				__( 'CLEANUP: Looking for correct category for CID %d in language %s', 'easycms-wp' ),
				$cid,
				$language
			),
			'info'
		);
		
		// Switch to the target language
		$default_lang = Util::get_default_language();
		Util::switch_language( $language );
		
		// Find the first category with this CID (the one we should keep)
		$query = $wpdb->prepare(
			"SELECT t.term_id, t.name, t.slug
			 FROM {$wpdb->terms} t
			 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			 INNER JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
			 WHERE tt.taxonomy = %s
			 AND tm.meta_key = %s
			 AND tm.meta_value = %d
			 ORDER BY t.term_id ASC
			 LIMIT 1",
			$this->taxonomy,
			$this->get_term_meta_name(),
			$cid
		);
		
		$result = $wpdb->get_row( $query );
		
		Util::switch_language( $default_lang );
		
		if ( $result ) {
			$this->log(
				sprintf(
					__( 'CLEANUP: Found correct category "%s" (term_id: %d) for CID %d', 'easycms-wp' ),
					$result->name,
					$result->term_id,
					$cid
				),
				'info'
			);
			return $result;
		}
		
		$this->log(
			sprintf(
				__( 'CLEANUP: ERROR - No correct category found for CID %d in language %s', 'easycms-wp' ),
				$cid,
				$language
			),
			'error'
		);
		return false;
	}
	
	/**
		* Reassign products from one category to another
		*/
	private function reassign_products_to_category( $from_term_id, $to_term_id ) {
		global $wpdb;
		
		$this->log(
			sprintf(
				__( 'CLEANUP: Reassigning products from term_id %d to term_id %d', 'easycms-wp' ),
				$from_term_id,
				$to_term_id
			),
			'info'
		);
		
		// Get all products in the source category
		$products = get_posts( array(
			'post_type' => 'product',
			'numberposts' => -1,
			'tax_query' => array(
				array(
					'taxonomy' => $this->taxonomy,
					'field' => 'term_id',
					'terms' => $from_term_id,
				)
			)
		) );
		
		$reassigned_count = 0;
		
		foreach ( $products as $product ) {
			// Get current category IDs
			$current_category_ids = wp_get_post_terms( $product->ID, $this->taxonomy, array( 'fields' => 'ids' ) );
			
			// Remove the source category and add the target category
			$new_category_ids = array_diff( $current_category_ids, array( $from_term_id ) );
			$new_category_ids[] = $to_term_id;
			$new_category_ids = array_unique( $new_category_ids );
			
			// Update the product categories
			$result = wp_set_post_terms( $product->ID, $new_category_ids, $this->taxonomy );
			
			if ( ! is_wp_error( $result ) ) {
				$reassigned_count++;
				$this->log(
					sprintf(
						__( 'CLEANUP: Reassigned product "%s" (ID: %d) from category %d to %d', 'easycms-wp' ),
						$product->post_title,
						$product->ID,
						$from_term_id,
						$to_term_id
					),
					'info'
				);
			} else {
				$this->log(
					sprintf(
						__( 'CLEANUP: ERROR reassigning product "%s" (ID: %d): %s', 'easycms-wp' ),
						$product->post_title,
						$product->ID,
						$result->get_error_message()
					),
					'error'
				);
			}
		}
		
		$this->log(
			sprintf(
				__( 'CLEANUP: Successfully reassigned %d products from term_id %d to term_id %d', 'easycms-wp' ),
				$reassigned_count,
				$from_term_id,
				$to_term_id
			),
			'info'
		);
		
		return $reassigned_count;
	}
}
?>