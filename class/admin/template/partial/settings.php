<?php 
defined( 'ABSPATH' ) || exit;
?>
<form action='' method='post'>
	<table class='form-table'>
		<tr valign='top'>
			<td scope='row'>
				<label for=''><?php _e( 'API Base URI:', 'easycms-wp' )?></label>
			</td>
			<td>
				<input type='text' disabled value='<?php echo EASYCMS_WP_API_URI?>' />
			</td>
		</tr>
		<tr valign='top'>
			<td scope='row'>
				<label for=''><?php _e( 'Username:', 'easycms-wp' )?></label>
			</td>
			<td>
				<input type='text' name='api_username' value='<?php echo ( !empty( $options['username'] ) ? $options['username'] : '' )?>' />
			</td>
		</tr>
		<tr valign='top'>
			<td scope='row'>
				<label for=''><?php _e( 'Password:', 'easycms-wp' )?></label>
			</td>
			<td>
				<input type='password' name='api_password' value='<?php echo ( !empty( $options['password'] ) ? $options['password'] : '' )?>' />
			</td>
		</tr>
		<tr valign='top'>
			<td scope='row'>
				<label for=''><?php _e( 'Authorization Key:', 'easycms-wp' )?></label>
			</td>
			<td>
				<input type='text' name='api_key' value='<?php echo ( !empty( $options['key'] ) ? $options['key'] : '' )?>' />
			</td>
		</tr>
		<tr valign='top'>
			<td scope='row'>
				<label for=''><?php _e( 'Account ID:', 'easycms-wp' )?></label>
			</td>
			<td>
				<input type='text' name='api_account' value='<?php echo ( !empty( $options['account'] ) ? $options['account'] : '' )?>' />
			</td>
		</tr>
		<tr valign='top'>
			<td scope='row'>
				<label for=''><?php _e( 'Admin ID:', 'easycms-wp' )?></label>
			</td>
			<td>
				<input type='text' name='admin_id' value='<?php echo ( !empty( $options['admin_id'] ) ? $options['admin_id'] : '' )?>' />
			</td>
		</tr>
		<tr valign='top'>
			<td scope='row' colspan='2'>
				<?php wp_nonce_field( self::NONCE_, '__nonce' )?>
				<?php submit_button()?>
			</td>
		</tr>
	</table>
</form>
<?php 
/* PROLASKU */
?>