<?php
namespace EasyCMS_WP;

class Log {
	const TABLE_NAME = 'easycms_wp_logs';

	public static function create_table() {
		global $wpdb;
		
		$table = sprintf( '%s%s', $wpdb->prefix, self::TABLE_NAME );
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS $table
			(
				`ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`module` VARCHAR(128) NOT NULL,
				`message` LONGTEXT,
				`type` VARCHAR(20),
				`logged_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				INDEX( module, logged_at, `type` )
			)"
		);
	}

	public static function add_config_nav( array $nav_items ) {
		$nav_items[] = array(
			'name' => __( 'Logs', 'easycms-wp' ),
			'slug'   => 'logs'
		);
		
		return $nav_items;
	}

	public static function logs_page( $slug ) {
		if ( 'logs' == $slug ) {
			require_once EASYCMS_WP_ADMIN_TEMPLATE_PATH . 'partial/logs.php';
		}
	}

	public static function get_modules() {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;
		$query = $wpdb->get_results( "SELECT DISTINCT `module` FROM $table" );

		$modules = array_map( function( $row ) {
			return $row->module;
		}, $query );

		return $modules;
	}

	public static function get_log_types() {
		return apply_filters( 'easycms_wp_log_get_types', array(
			'info',
			'error',
			'warning',
			'debug',
		) );
	}

	public static function get_logs( string $module = '', string $type = '', int $hours_offset = 0 ) {
		global $wpdb;

		$table = $wpdb->prefix . self::TABLE_NAME;
	
		$sql = "
			SELECT
				`module`,
				`message`,
				`type`,
				`logged_at`
			FROM $table
			WHERE 1=1
		";

		$where = '';
		$values = array();

		if ( $hours_offset ) {
			$where .= 'AND (`logged_at` BETWEEN DATE_SUB(NOW(), INTERVAL %d HOUR ) AND NOW())';
			$values[] = $hours_offset;
		}

		if ( $module ) {
			$where .= ' AND `module` = %s';
			$values[] = $module;
		}

		if ( $type ) {
			$where .= ' AND `type` = %s';
			$values[] = $type;
		}

		if ( $where ) {
			$sql .= $where;
		}

		$sql .= ' ORDER BY `ID` DESC ';

		if ( $values ) {
			$results = $wpdb->get_results(
				$wpdb->prepare( $sql, $values )
			);
		} else {
			$results = $wpdb->get_results( $sql );
		}

		return $results;
	}

	public static function log( string $module, string $message, string $type = 'info' ) {
		global $wpdb;

		$wpdb->show_errors();
		$message = sanitize_text_field( $message );
		$module = sanitize_text_field( $module );
		$type = ! in_array( $type, self::get_log_types() ) ? 'info' : $type;

		$i = $wpdb->insert( sprintf( '%s%s', $wpdb->prefix, self::TABLE_NAME ), array(
			'module'  => $module,
			'message' => $message,
			'type'    => $type,
		));

		do_action( 'easycms_wp_message_logged', $message, $module );
	}

	public static function truncate_table( string $module = '', bool $drop = false ) {
		global $wpdb;
	
		$table = sprintf( '%s%s', $wpdb->prefix, self::TABLE_NAME );
		$where = array();

		if ( $drop ) {
			$wpdb->query( "DROP TABLE $table" );
			do_action( 'easycms_wp_log_table_dropped' );
			return;
		}

		if ( $module ) {
			$where = array( 'module' => $module );
		}

		if ( $where )
			$wpdb->delete( $table, $where );
		else
			$wpdb->query( "TRUNCATE TABLE $table" );

		do_action( 'easycms_wp_log_table_truncated', $module );
	}
}
?>