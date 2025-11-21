<?php
defined( 'ABSPATH' ) || exit;
?>
<div class="configurations-panel">
	<form action='' method='post' class="api-settings-form">
		<div class="form-header">
			<h3><?php _e( 'API Configuration', 'easycms-wp' ); ?></h3>
			<p class="description"><?php _e( 'Configure your ProLasku API connection settings below.', 'easycms-wp' ); ?></p>
		</div>
		
		<table class='form-table'>
			<tr valign='top'>
				<th scope='row'>
					<label for='api_base_uri'><?php _e( 'API Base URI:', 'easycms-wp' ); ?></label>
				</th>
				<td>
					<input type='text' id="api_base_uri" class="regular-text" disabled value='<?php echo EASYCMS_WP_API_URI; ?>' />
					<p class="description"><?php _e( 'The base URI for the ProLasku API endpoint.', 'easycms-wp' ); ?></p>
				</td>
			</tr>
			<tr valign='top'>
				<th scope='row'>
					<label for='api_username'><?php _e( 'Username:', 'easycms-wp' ); ?></label>
				</th>
				<td>
					<input type='text' id="api_username" class="regular-text" name='api_username' value='<?php echo ( !empty( $options['username'] ) ? esc_attr( $options['username'] ) : '' ); ?>' />
					<p class="description"><?php _e( 'Your ProLasku API username.', 'easycms-wp' ); ?></p>
				</td>
			</tr>
			<tr valign='top'>
				<th scope='row'>
					<label for='api_password'><?php _e( 'Password:', 'easycms-wp' ); ?></label>
				</th>
				<td>
					<input type='password' id="api_password" class="regular-text" name='api_password' value='<?php echo ( !empty( $options['password'] ) ? esc_attr( $options['password'] ) : '' ); ?>' />
					<p class="description"><?php _e( 'Your ProLasku API password.', 'easycms-wp' ); ?></p>
				</td>
			</tr>
			<tr valign='top'>
				<th scope='row'>
					<label for='api_key'><?php _e( 'Authorization Key:', 'easycms-wp' ); ?></label>
				</th>
				<td>
					<input type='text' id="api_key" class="regular-text" name='api_key' value='<?php echo ( !empty( $options['key'] ) ? esc_attr( $options['key'] ) : '' ); ?>' />
					<p class="description"><?php _e( 'Your ProLasku API authorization key.', 'easycms-wp' ); ?></p>
				</td>
			</tr>
			<tr valign='top'>
				<th scope='row'>
					<label for='api_account'><?php _e( 'Account ID:', 'easycms-wp' ); ?></label>
				</th>
				<td>
					<input type='text' id="api_account" class="regular-text" name='api_account' value='<?php echo ( !empty( $options['account'] ) ? esc_attr( $options['account'] ) : '' ); ?>' />
					<p class="description"><?php _e( 'Your ProLasku account ID.', 'easycms-wp' ); ?></p>
				</td>
			</tr>
			<tr valign='top'>
				<th scope='row'>
					<label for='admin_id'><?php _e( 'Admin ID:', 'easycms-wp' ); ?></label>
				</th>
				<td>
					<input type='text' id="admin_id" class="regular-text" name='admin_id' value='<?php echo ( !empty( $options['admin_id'] ) ? esc_attr( $options['admin_id'] ) : '' ); ?>' />
					<p class="description"><?php _e( 'The admin ID for your ProLasku account.', 'easycms-wp' ); ?></p>
				</td>
			</tr>
		</table>
		
		<div class="form-footer">
			<?php wp_nonce_field( self::NONCE_, '__nonce' ); ?>
			<?php submit_button( __( 'Save API Settings', 'easycms-wp' ), 'primary', 'submit', true, array( 'class' => 'button button-primary' ) ); ?>
		</div>
	</form>
</div>

<style>
.configurations-panel {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	padding: 20px;
	margin: 20px 0;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.form-header {
	margin-bottom: 20px;
	padding-bottom: 15px;
	border-bottom: 1px solid #eee;
}

.form-header h3 {
	margin: 0 0 10px 0;
	font-size: 1.3em;
	color: #23282d;
}

.form-header .description {
	margin: 0;
	color: #666;
	font-style: italic;
}

.form-footer {
	margin-top: 20px;
	padding-top: 15px;
	border-top: 1px solid #eee;
}

.api-settings-form .regular-text {
	width: 25em;
}

.api-settings-form .description {
	margin-top: 5px;
	font-size: 13px;
	color: #666;
}
</style>