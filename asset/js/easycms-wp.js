const easycms_wp_show_logs = ( logs ) => {
	jQuery( '#easycms-wp-logs' ).empty()
	for ( let i = 0; i < logs.length; i++ ) {
		let log = logs[i]
		jQuery( '#easycms-wp-logs' ).append( `${log.logged_at}\t${log.module}:${log.type}\t\t${log.message}\n`)
	}
	// logs.forEach( log => {
	// 	jQuery( '#easycms-wp-logs' ).append( `${log.logged_at}\t${log.module}:${log.type}\t\t${log.message}\n`)
	// })
}

jQuery(document).ready( $ => {
	
	$( 'form#easycms-wp-log-filter' ).submit( e => {
		e.preventDefault()
		let data = $( 'form#easycms-wp-log-filter' ).serialize()
		let btn = $( '#easycms_log_submit_btn' )

		$.ajax({
			url:      EASYCMS_WP.ajax_url,
			dataType: 'json',
			type:     'POST',
			data:     data,
			beforeSend: () => {
				btn.prop( 'disabled', true )
			},
			success: response => {
				btn.prop( 'disabled', false )
				easycms_wp_show_logs(response.data)
			}
		})
	})

	$( '#easycms-wp-clear-log' ).click( e => {
		e.preventDefault()
		let $this = $(this)

		if ( confirm( 'You are about to clear all logs entry. Continue?' ) ) {
			$.ajax({
				url:   EASYCMS_WP.ajax_url,
				dataType: 'json',
				type:     'post',
				data:     { action: 'easycms_wp_clear_logs', easycms_wp_nonce: EASYCMS_WP.nonce },
				beforeSend: () => {
					$( '#easycms-wp-clear-log' ).prop( 'disabled', true )
				},
				success: () => {
					$( '#easycms-wp-clear-log' ).prop( 'disabled', false )
				}
			})
		}
	})

	$( '.easycms_wp_sync_btn' ).on( 'click', e => {
		e.preventDefault()
		let $this = $( e.target )
		$.ajax({
			url:     EASYCMS_WP.ajax_url,
			dataType: 'json',
			type:     'post',
			data:     { action: 'easycms_wp_run_component', easycms_wp_nonce: EASYCMS_WP.nonce, easycms_wp_component: $this.data('component') },
			beforeSend: () => {
				$this.prop( 'disabled', true )
			},
			success: () => {
				$this.prop( 'disabled', false )
			}
		})
	})
	
	$('.easycms_wp_reset_btn').on('click', e => {
		e.preventDefault();
		let $this = $(e.target);
		let component = $this.data('component');
		if (!component) return;

		if (confirm(`Reset sync status for "${component}"?`)) {
			$.ajax({
				url: EASYCMS_WP.ajax_url,
				dataType: 'json',
				type: 'post',
				data: {
					action: 'easycms_wp_reset_sync_status',
					easycms_wp_nonce: EASYCMS_WP.nonce,
					easycms_wp_component: component
				},
				beforeSend: () => {
					$this.prop('disabled', true).text('Resetting...');
				},
				success: (response) => {
					if (response.success) {
						// Extract data correctly from response.data
						let message = response.data?.message || 'Reset successful';
						let sqlResult = response.data?.sql_result || '';

						console.log('Success:', message);
						if (sqlResult) {
							console.log('SQL Query:', sqlResult);
						}
						alert(message);
						$(`.easycms_wp_sync_btn[data-component='${component}']`).prop('disabled', false);
					} else {
						// Handle error responses from wp_send_json_error
						let errorMessage = response.data || 'Error resetting sync status.';
						alert(errorMessage);
						console.warn('Reset failed:', errorMessage);
					}
					$this.prop('disabled', false).text('Reset');
				},
				error: (xhr, status, error) => {
					console.error('Error xhr.responseText:', xhr.responseText);
					console.error('Error status:', status);
					console.error('Error error:', error);
					alert('AJAX request failed.');
					$this.prop('disabled', false).text('Reset');
				}
			});
		}
	});
		
	$('.easycms_wp_delete_all_btn').on('click', e => {
		e.preventDefault();
		let $this = $(e.target);
		if (confirm(`Delete all synced data?`)) {
			$.ajax({
				url: EASYCMS_WP.ajax_url,
				dataType: 'json',
				type: 'post',
				data: {
					action: 'easycms_wp_delete_all',
					easycms_wp_nonce: EASYCMS_WP.nonce
				},
				beforeSend: () => {
					$this.prop('disabled', true).text('Deleting data...');
				},
				success: (response) => {
					if (response.success) {
						// Extract data correctly from response.data
						let message = response.data?.message || 'delete successful';
						let sqlResult = response.data?.sql_result || '';

						console.log('Success:', message);
						if (sqlResult) {
							console.log('SQL Query:', sqlResult);
						}
						alert(message);
						$(`.easycms_wp_delete_all_btn`).prop('disabled', false);
					} else {
						// Handle error responses from wp_send_json_error
						let errorMessage = response.data || 'Error deleteting sync status.';
						alert(errorMessage);
						console.warn('delete failed:', errorMessage);
					}
					$this.prop('disabled', false).text('delete');
				},
				error: (xhr, status, error) => {
					console.error('Error xhr.responseText:', xhr.responseText);
					console.error('Error status:', status);
					console.error('Error error:', error);
					alert('AJAX request failed.');
					$this.prop('disabled', false).text('delete');
				}
			});
		}
	});

})