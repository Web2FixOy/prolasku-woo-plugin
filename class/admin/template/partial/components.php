<?php
/* ProLasku*/
?>

<table class='form-table' style='width: 50%;'>
		<?php
		$components = $this->get_runnable_components();
		$i = 1;
		foreach ( $components as $name => $obj ):
		?>
			<tr class='<?php echo $i%2 ? 'alternate' : ''?>'>
				<td><?php echo ucwords( str_replace( '_', ' ', $name ) )?></td>
				<td>
					<button type='button' <?php echo ( $obj->is_syncing() ? 'disabled' : '' )?> data-component='<?php echo $name?>' class='button-secondary easycms_wp_sync_btn'>
						<?php ( $obj->is_syncing() ? _e( 'Sync in progress', 'easycms-wp' ) : _e( 'Sync now', 'easycms-wp' ) )?>
					</button>
				</td>
			</tr>
		<?php
		$i++;
		endforeach;
		?>
</table>

<?php 
/* PROLASKU */
?>