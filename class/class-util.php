<?php
namespace EasyCMS_WP;
defined( 'ABSPATH' ) || exit;

class Util {
	public static function get_date_hour_diff( int $past, int $present = 0 ) {
		if ( ! $present ) {
			$present = time();
		}

		$ret = ( $present - $past ) / 60 * 60;

		return $ret;
	}

	public static function nonce_field() {
		wp_nonce_field( 'easycms_wp_check_req', 'easycms_wp_nonce' );
	}

	public static function create_nonce() {
		return wp_create_nonce( 'easycms_wp_check_req' );
	}

	public static function get_product_pid( int $product_id ) : int {
		$product = wc_get_product( $product_id );
		if ( $product && ( $pid = $product->get_meta( 'easycms_pid', true ) ) ) {
			return $pid;
		}

		return 0;
	}

	public static function strip_locale( array $data ) {
		$tmp = array();

		foreach( $data as $locale => $value ) {
			$locale = strpos( $locale, '_' ) !== false ? strstr( $locale, '_', true ) : $locale;
			if($locale=="zh"){
				// $locale = "zh-hant"; //WooCommerce Chinese traditional
				$locale = "zh-hans"; //WooCommerce Chinese simplified
			}

			$tmp[ $locale ] = $value;
		}

		return $tmp;
	}

	public static function is_language_active( string $language ) {
		global $sitepress;

		$active_lang = $sitepress->get_active_languages();
		return isset( $active_lang[$language] );
	}

	public static function get_default_language() {
		global $sitepress;

		return $sitepress->get_default_language();
	}

	public static function get_current_language() {
		global $sitepress;

		return $sitepress->get_current_language();
	}

	public static function switch_language( string $lang ) {
		global $sitepress;
		
		// Make sure that the $sitepress object is properly loaded and set up
		if ( ! isset( $sitepress ) ) {
			require_once( ABSPATH . '/wp-content/plugins/sitepress-multilingual-cms/sitepress.php' );
		}

		$sitepress->switch_lang( $lang );
		wp_cache_flush();
	}


	public static function currency_exists( string $code ) {
		global $woocommerce_wpml;

		return isset( $woocommerce_wpml ) && ! empty( $woocommerce_wpml->multi_currency->currencies[ $code ] );
	}

	public static function url_to_attachment( string $url, string $filename, int $time = 0 ) {
		global $wpdb;

		$filename = preg_replace( '/[^\w.]/', '_', $filename );

		// Get file title
		$title = preg_replace( '/\.[^.]+$/', '', basename( $filename ) );
		// Does the attachment already exist ?
		// if(self::check_if_local_image_exists($filename, $title, false)){
	 	//        return 'attachment_already_exists';
		// }		


		// return $wpdb;
		// Download file
		$tmp_file = download_url( $url );

		if ( is_wp_error( $tmp_file ) ) {
			return $tmp_file;
		}

		// Verify file type
		$file_type = wp_check_filetype( $filename );
		$file = array(
			'name' => $filename,
			'tmp_name' => $tmp_file,
			'error'    => 0,
			'type'     => $file_type['type'],
		);

		// Prepare file array
		$allowed_types = array(
			'jpg|jpeg|jpe'  => 'image/jpeg',
			'png'           => 'image/png',
		);

		// Handle upload
		$upload = wp_handle_sideload( $file, array( 'test_form' => false, 'mimes' => $allowed_types ), date( 'Y/m', $time ) );
		if (!empty($upload['error'])) {
			@unlink($tmp_file); // Clean up temp file
			return new \WP_Error('upload_error', $upload['error']);
		}

		// Create attachment
		$tmp_attachment_data = array(
			'post_status' => 'inherit',
			'post_title'  => $filename,
			'post_date'   => date( 'Y-m-d H:i:s', ( $time ? $time : null ) ),
			'post_mime_type' => $file_type['type']
		);


		$attachment_id = wp_insert_attachment(
			$tmp_attachment_data,
			$upload['file']
		);

		if ( ! is_wp_error( $attachment_id ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
			
			wp_update_attachment_metadata( $attachment_id, $attachment_data );

			// After wp_update_attachment_metadata()
			if (!file_exists($upload['file'])) {
				error_log("CRITICAL: File missing after upload: " . $upload['file']);
			} else {
				error_log("File successfully saved at: " . $upload['file']);
			}

		}

		// Clean up ONLY the temporary file (keep the uploaded file!)
    	@unlink($tmp_file);

		return $attachment_id;
	}
}
?>