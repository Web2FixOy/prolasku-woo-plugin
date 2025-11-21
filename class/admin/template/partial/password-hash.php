<?php
defined( 'ABSPATH' ) || exit;

// Get current password hash setting
$password_hash_enabled = get_option('prolasku_password_hash_enabled', '0');
?>
<div class="configurations-panel">
	<form id="password-hash-settings-form" action='' method='post' class="password-hash-form">
		<div class="form-header">
			<h3><?php _e( 'Password Hash Configuration', 'easycms-wp' ); ?></h3>
			<p class="description"><?php _e( 'Configure Bcrypt password hashing for enhanced mobile app security.', 'easycms-wp' ); ?></p>
		</div>
		
		<table class='form-table'>
			<tr valign='top'>
				<th scope='row'>
					<label for='prolasku_password_hash_enabled'><?php _e( 'Enable Password Hash:', 'easycms-wp' ); ?></label>
				</th>
				<td>
					<fieldset>
						<label class="checkbox-container">
							<input type='checkbox'
								   id='prolasku_password_hash_enabled'
								   name='prolasku_password_hash_enabled'
								   value='1'
								   <?php checked($password_hash_enabled, '1'); ?> />
							<span class="checkbox-text"><?php _e( 'Enable Bcrypt password hashing for mobile app integration', 'easycms-wp' ); ?></span>
						</label>
						<p class="description">
							<?php _e( 'When enabled, passwords will be hashed using Bcrypt for enhanced security. This is required for mobile app integration.', 'easycms-wp' ); ?>
						</p>
					</fieldset>
				</td>
			</tr>
			<tr valign='top'>
				<th scope='row'>
					<label><?php _e( 'Current Status:', 'easycms-wp' ); ?></label>
				</th>
				<td>
					<span id="password-hash-status" class="status-indicator <?php echo $password_hash_enabled === '1' ? 'status-enabled' : 'status-disabled'; ?>">
						<?php echo $password_hash_enabled === '1' ? __( 'Enabled', 'easycms-wp' ) : __( 'Disabled', 'easycms-wp' ); ?>
					</span>
					<p class="description">
						<?php
						if ($password_hash_enabled === '1') {
							_e( 'Password hashing is currently active. New passwords will use Bcrypt.', 'easycms-wp' );
						} else {
							_e( 'Password hashing is currently disabled. Standard WordPress hashing is being used.', 'easycms-wp' );
						}
						?>
					</p>
				</td>
			</tr>
			<tr valign='top'>
				<th scope='row'>
					<label><?php _e( 'Feature Information:', 'easycms-wp' ); ?></label>
				</th>
				<td>
					<div class="password-hash-info">
						<div class="info-section">
							<h4><?php _e( 'What this does:', 'easycms-wp' ); ?></h4>
							<ul>
								<li><?php _e( 'Replaces WordPress default password hashing with Bcrypt', 'easycms-wp' ); ?></li>
								<li><?php _e( 'Provides enhanced security for mobile app authentication', 'easycms-wp' ); ?></li>
								<li><?php _e( 'Disables password change email notifications', 'easycms-wp' ); ?></li>
							</ul>
						</div>
						
						<div class="info-section">
							<h4><?php _e( 'Important Notes:', 'easycms-wp' ); ?></h4>
							<ul>
								<li><?php _e( 'When enabled, all new passwords will use Bcrypt hashing', 'easycms-wp' ); ?></li>
								<li><?php _e( 'Existing passwords will continue to work during login', 'easycms-wp' ); ?></li>
								<li><?php _e( 'This setting is required for ProLasku mobile app integration', 'easycms-wp' ); ?></li>
							</ul>
						</div>
						
						<div class="info-section warning">
							<h4><?php _e( 'Security Notice:', 'easycms-wp' ); ?></h4>
							<p><?php _e( 'This feature is specifically designed for ProLasku mobile app integration. Only enable if you are using the ProLasku mobile application.', 'easycms-wp' ); ?></p>
						</div>
					</div>
				</td>
			</tr>
		</table>
		
		<div class="form-footer">
			<?php wp_nonce_field( 'prolasku_password_hash_settings', 'password_hash_nonce' ); ?>
			<input type="submit" name="save_password_hash_settings" class="button button-primary" value="<?php _e( 'Save Password Hash Settings', 'easycms-wp' ); ?>" />
			<span id="password-hash-save-status" class="save-status"></span>
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

.checkbox-container {
	display: flex;
	align-items: flex-start;
	gap: 8px;
}

.checkbox-text {
	font-weight: 500;
}

.status-indicator {
	padding: 6px 12px;
	border-radius: 4px;
	font-weight: bold;
	display: inline-block;
}

.status-enabled {
	background-color: #d4edda;
	color: #155724;
	border: 1px solid #c3e6cb;
}

.status-disabled {
	background-color: #f8d7da;
	color: #721c24;
	border: 1px solid #f5c6cb;
}

.password-hash-info {
	background-color: #f9f9f9;
	border: 1px solid #ddd;
	padding: 20px;
	border-radius: 4px;
	max-width: 600px;
}

.info-section {
	margin-bottom: 20px;
}

.info-section:last-child {
	margin-bottom: 0;
}

.info-section h4 {
	margin: 0 0 10px 0;
	color: #23282d;
	font-size: 1.1em;
}

.info-section ul {
	margin: 0 0 0 20px;
	padding: 0;
}

.info-section li {
	margin-bottom: 5px;
}

.info-section.warning {
	background-color: #fff3cd;
	border: 1px solid #ffeaa7;
	padding: 15px;
	border-radius: 4px;
}

.info-section.warning h4 {
	color: #856404;
}

.info-section.warning p {
	margin: 0;
	color: #856404;
}

.save-status {
	margin-left: 15px;
	font-weight: bold;
}

.password-hash-form .description {
	margin-top: 8px;
	font-size: 13px;
	color: #666;
	line-height: 1.4;
}
</style>