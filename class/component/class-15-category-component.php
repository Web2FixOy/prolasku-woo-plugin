<?php
namespace EasyCMS_WP\Component;

use \EasyCMS_WP\Log;
use \EasyCMS_WP\Util;

class Category extends \EasyCMS_WP\Template\Component {
	public $taxonomy = 'product_cat';

	public static function can_run() {
		if (
			! class_exists( 'WooCommerce' ) ||
			! class_exists( 'woocommerce_wpml' ) ||
			! $GLOBALS['woocommerce_wpml']->dependencies_are_ok
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
	*/
}
?>