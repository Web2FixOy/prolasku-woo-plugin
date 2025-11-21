<?php
/**
 * Plugin Name: ProLasku-WooCommerce Integration
 * Author:	ProLasku
 * Author URI:	Prolasku.fi
 * Description:	Integrates middleware to sync WordPress data with EasyCMS
 * Plugin URI:  ProLasku
 * Version:	2.3
 * Text Domain:	easycms-wp
 */

defined( 'ABSPATH' ) || exit;

defined( 'EASYCMS_WP_VERSION' )              || define( 'EASYCMS_WP_VERSION', 2.0 );
defined( 'EASYCMS_WP_PATH' )                 || define( 'EASYCMS_WP_PATH', sprintf( '%s/', __DIR__ ) );
defined( 'EASYCMS_WP_CLASS_PATH' )           || define( 'EASYCMS_WP_CLASS_PATH', sprintf( '%sclass/', EASYCMS_WP_PATH ) );
defined( 'EASYCMS_WP_COMPONENT_PATH' )       || define( 'EASYCMS_WP_COMPONENT_PATH', sprintf( '%scomponent/', EASYCMS_WP_CLASS_PATH ) );
defined( 'EASYCMS_WP_API_URI' )              || define( 'EASYCMS_WP_API_URI', 'https://easycms.fi/public_api' );
defined( 'EASYCMS_WP_CONFIG' )               || define( 'EASYCMS_WP_CONFIG', 'easycms_' );
defined( 'EASYCMS_WP_TEMPLATE_PATH' )        || define( 'EASYCMS_WP_TEMPLATE_PATH', sprintf( '%stemplate/', EASYCMS_WP_CLASS_PATH ) );
defined( 'EASYCMS_WP_DEBUG' )                || define( 'EASYCMS_WP_DEBUG', false );
defined( 'EASYCMS_WP_CRON_HOOK' )            || define( 'EASYCMS_WP_CRON_HOOK', 'easycms_wp_cron_hook' );
defined( 'EASYCMS_WP_ADMIN_TEMPLATE_PATH' )  || define( 'EASYCMS_WP_ADMIN_TEMPLATE_PATH', sprintf( '%sadmin/template/', EASYCMS_WP_CLASS_PATH ) );
defined( 'EASYCMS_WP_BASE_URI' )             || define( 'EASYCMS_WP_BASE_URI', plugins_url( '', __FILE__ ) );

require_once EASYCMS_WP_CLASS_PATH . 'class-easycms-wp.php';
require_once EASYCMS_WP_CLASS_PATH . 'Bcrypt.php';
require_once EASYCMS_WP_CLASS_PATH . 'password-hash-functions.php';

if ( empty( $GLOBALS['easycms_wp'] ) ) {
	$GLOBALS['easycms_wp'] = new \EasyCMS_WP\EasyCMS_WP();
}

register_activation_hook( __FILE__, array( $GLOBALS['easycms_wp'], 'activation' ) );
register_deactivation_hook( __FILE__, array( $GLOBALS['easycms_wp'], 'deactivation' ) );

?>