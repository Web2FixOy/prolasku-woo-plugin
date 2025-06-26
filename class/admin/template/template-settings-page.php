<?php
defined( 'ABSPATH' ) || exit;

$active = isset( $_GET['easycms_wp_tab'] ) ? $_GET['easycms_wp_tab'] : '';
?>

<div class='wrap'>
	<h1><?php echo get_admin_page_title()?></h1>

	<nav class='nav-tab-wrapper'>
		<?php
		$nav_items = apply_filters( 'easycms_wp_admin_nav_items', array() );
		
		foreach ( $nav_items as $item ):
			$url = http_build_query( array_merge( $_GET, [ 'easycms_wp_tab' => $item['slug'] ]) );
		?>
			<a href='?<?php echo $url?>' class='nav-tab <?php echo $item['slug'] == $active ? 'nav-tab-active' : ''?>'><?php echo $item['name']?></a>
		<?php endforeach?>

		<?php do_action( 'easycms_wp_admin_added_nav_items' )?>
	</nav>

	<div class='tab_content'>
		<?php
		$tabs = apply_filters( 'easycms_wp_admin_nav_items', array() );
		foreach ( $tabs as $tab ) {
			if ( $tab['slug'] == $active ) {
				do_action( 'easycms_wp_admin_nav_content', $tab['slug'] );
			}
		}
		?>
	</div>
</div>