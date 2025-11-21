<?php
defined( 'ABSPATH' ) || exit;

$active = isset( $_GET['easycms_wp_tab'] ) ? $_GET['easycms_wp_tab'] : 'configurations';
$active_subtab = isset( $_GET['easycms_wp_subtab'] ) ? $_GET['easycms_wp_subtab'] : 'api-settings';
?>

<div class='wrap'>
	<h1><?php echo get_admin_page_title()?></h1>

	<nav class='nav-tab-wrapper'>
		<?php
		$nav_items = apply_filters( 'easycms_wp_admin_nav_items', array() );
		
		foreach ( $nav_items as $item):
			$url = http_build_query( array_merge( $_GET, [ 'easycms_wp_tab' => $item['slug'], 'easycms_wp_subtab' => '' ]) );
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
				// Check if this tab has subtabs
				$subtabs = apply_filters( 'easycms_wp_admin_subtab_items_' . $tab['slug'], array() );
				
				if ( ! empty( $subtabs ) ) {
					// Display subtabs
					echo '<nav class="subtab-nav-wrapper">';
					foreach ( $subtabs as $subtab ) {
						$url = http_build_query( array_merge( $_GET, [ 'easycms_wp_tab' => $tab['slug'], 'easycms_wp_subtab' => $subtab['slug'] ]) );
						$active_class = ( $subtab['slug'] == $active_subtab || ( empty( $active_subtab ) && $subtab['slug'] == 'api-settings' ) ) ? 'subtab-active' : '';
						echo '<a href="?' . $url . '" class="subtab ' . $active_class . '">' . $subtab['name'] . '</a>';
					}
					echo '</nav>';
					
					// Display active subtab content
					$active_subtab_slug = empty( $active_subtab ) ? 'api-settings' : $active_subtab;
					do_action( 'easycms_wp_admin_subtab_content_' . $tab['slug'], $active_subtab_slug );
				} else {
					// Display regular tab content
					do_action( 'easycms_wp_admin_nav_content', $tab['slug'] );
				}
			}
		}
		?>
	</div>
</div>

<style>
.subtab-nav-wrapper {
	margin: 20px 0;
	border-bottom: 1px solid #ccc;
	padding-bottom: 0;
}

.subtab {
	display: inline-block;
	padding: 8px 16px 8px 16px;
	margin-right: 4px;
	border: 1px solid #ccc;
	border-bottom: none;
	background: #f1f1f1;
	text-decoration: none;
	color: #0073aa;
	border-radius: 3px 3px 0 0;
}

.subtab:hover {
	background: #fff;
	color: #0073aa;
}

.subtab-active {
	background: #fff;
	border-bottom: 1px solid #fff;
	margin-bottom: -1px;
	color: #333;
	font-weight: bold;
}
</style>