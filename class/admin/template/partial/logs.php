<?php 
/* PROLASKU */
?>
<div>
	<h3><?php _e( 'Config', 'easycms-wp' )?></h3>
	<form action='' method='post' style='background: #e7e7e7; width: 50%;'>
		<table class='form-table' style='width: 100%;'>
			<tr>
				<td><?php _e( 'Delete logs older than:', 'easycms-wp' )?></td>
				<td><input type='number' value='' /> <?php _e( 'Days', 'easycms-wp' )?>
			</tr>
			<tr>
				<td colspan='2'>
					<?php submit_button() ?>
				</td>
			</tr>
		</table>
	</form>
	<h3><?php _e( 'Logs', 'easycms-wp' )?></h3>

	<form id='easycms-wp-log-filter'>
		<table class='form-table'>
			<tr>
				<td>
					<?php _e( 'Filter by:', 'easycms-wp' )?>
					<select name='easycms_wp_log_module'>
						<?php
						$modules = \EasyCMS_WP\Log::get_modules();
						?>
						<option value=''><?php _e( '-Module-', 'easycms-wp' )?></option>
						<?php foreach ( $modules as $module ):?>
							<option value='<?php echo $module?>'><?php echo ucwords( $module )?></option>
						<?php endforeach; ?>
					</select>
					<select name='easycms_wp_log_type'>
						<?php
						$log_types = \EasyCMS_WP\Log::get_log_types();
						?>
						<option value=''><?php _e( '-Log Type-', 'easycms-wp' )?></option>
						<?php foreach( $log_types as $type ):?>
							<option value='<?php echo $type?>'><?php echo $type?></option>
						<?php endforeach; ?>
					</select>

					<select name='easycms_wp_log_hours'>
						<option value='0'><?php _e( '-All time-', 'easycms-wp' )?></option>
						<option value='1'><?php _e( 'Past 1hr', 'easycms-wp' )?></option>
						<option value='24'><?php _e( 'Past 24hrs', 'easycms-wp' )?></option>
						<option value='168'><?php _e( '7 Days ago', 'easycms-wp' )?></option>
						<option value='720'><?php _e( '30 Days ago', 'easycms-wp' )?></option>
					</select>
					<?php \EasyCMS_WP\Util::nonce_field() ?>
					<input type='hidden' name='action' value='easycms_wp_get_logs' />
					<button class='button-secondary' type='submit' id='easycms_log_submit_btn'><?php _e( 'Fetch logs', 'easycms-wp' )?></button>
					<button class='button-secondary' type='button'><?php _e( 'Export CSV', 'easycms-wp' )?></button>
				</td>
			</tr>
			<tr>
				<td>
					<div class='easycms-wp-logs' id='easycms-wp-logs'></div>
				</td>
			</tr>
		</table>
	</form>

	<button class='button-secondary' id='easycms-wp-clear-log' type='button'><?php _e( 'Clear all logs', 'easycms-wp' )?></button>
</div>
<?php 
/* PROLASKU */
?>