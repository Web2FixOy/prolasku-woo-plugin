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
})