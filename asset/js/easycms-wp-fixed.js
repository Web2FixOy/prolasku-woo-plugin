// jQuery Safe Wrapper
(function($) {
    'use strict';

    const easycms_wp_show_logs = ( logs ) => {
        $( '#easycms-wp-logs' ).empty();
        for ( let i = 0; i < logs.length; i++ ) {
            let log = logs[i];
            $( '#easycms-wp-logs' ).append( `${log.logged_at}\t${log.module}:${log.type}\t\t${log.message}\n` );
        }
    };

    $(document).ready( function() {
        
        $( 'form#easycms-wp-log-filter' ).submit( function(e) {
            e.preventDefault();
            let data = $( 'form#easycms-wp-log-filter' ).serialize();
            let btn = $( '#easycms_log_submit_btn' );

            $.ajax({
                url:      EASYCMS_WP.ajax_url,
                dataType: 'json',
                type:     'POST',
                data:     data,
                beforeSend: function() {
                    btn.prop( 'disabled', true );
                },
                success: function(response) {
                    btn.prop( 'disabled', false );
                    easycms_wp_show_logs(response.data);
                }
            });
        });

        $( '#easycms-wp-clear-log' ).click( function(e) {
            e.preventDefault();
            let $this = $(this);

            if ( confirm( 'You are about to clear all logs entry. Continue?' ) ) {
                $.ajax({
                    url:   EASYCMS_WP.ajax_url,
                    dataType: 'json',
                    type:     'post',
                    data:     { action: 'easycms_wp_clear_logs', easycms_wp_nonce: EASYCMS_WP.nonce },
                    beforeSend: function() {
                        $( '#easycms-wp-clear-log' ).prop( 'disabled', true );
                    },
                    success: function() {
                        $( '#easycms-wp-clear-log' ).prop( 'disabled', false );
                    }
                });
            }
        });

        $( '.easycms_wp_sync_btn' ).on( 'click', function(e) {
            e.preventDefault();
            let $this = $( e.target );
            $.ajax({
                url:     EASYCMS_WP.ajax_url,
                dataType: 'json',
                type:     'post',
                data:     { action: 'easycms_wp_run_component', easycms_wp_nonce: EASYCMS_WP.nonce, easycms_wp_component: $this.data('component') },
                beforeSend: function() {
                    $this.prop( 'disabled', true );
                },
                success: function() {
                    $this.prop( 'disabled', false );
                }
            });
        });
        
        $('.easycms_wp_reset_btn').on('click', function(e) {
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
                    beforeSend: function() {
                        $this.prop('disabled', true).text('Resetting...');
                    },
                    success: function(response) {
                        if (response.success) {
                            let message = response.data?.message || 'Reset successful';
                            let sqlResult = response.data?.sql_result || '';

                            console.log('Success:', message);
                            if (sqlResult) {
                                console.log('SQL Query:', sqlResult);
                            }
                            alert(message);
                            $(`.easycms_wp_sync_btn[data-component='${component}']`).prop('disabled', false);
                        } else {
                            let errorMessage = response.data || 'Error resetting sync status.';
                            alert(errorMessage);
                            console.warn('Reset failed:', errorMessage);
                        }
                        $this.prop('disabled', false).text('Reset');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error xhr.responseText:', xhr.responseText);
                        console.error('Error status:', status);
                        console.error('Error error:', error);
                        alert('AJAX request failed.');
                        $this.prop('disabled', false).text('Reset');
                    }
                });
            }
        });
            
        $('.easycms_wp_delete_all_btn').on('click', function(e) {
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
                    beforeSend: function() {
                        $this.prop('disabled', true).text('Deleting data...');
                    },
                    success: function(response) {
                        if (response.success) {
                            let message = response.data?.message || 'delete successful';
                            let sqlResult = response.data?.sql_result || '';

                            console.log('Success:', message);
                            if (sqlResult) {
                                console.log('SQL Query:', sqlResult);
                            }
                            alert(message);
                            $(`.easycms_wp_delete_all_btn`).prop('disabled', false);
                        } else {
                            let errorMessage = response.data || 'Error deleteting sync status.';
                            alert(errorMessage);
                            console.warn('delete failed:', errorMessage);
                        }
                        $this.prop('disabled', false).text('delete');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error xhr.responseText:', xhr.responseText);
                        console.error('Error status:', status);
                        console.error('Error error:', error);
                        alert('AJAX request failed.');
                        $this.prop('disabled', false).text('delete');
                    }
                });
            }
        });

        // Translation Equalizer Functions
        $( '#easycms_wp_translation_stats_btn' ).on( 'click', function(e) {
            e.preventDefault();
            let $this = $(e.target);
            
            $.ajax({
                url: EASYCMS_WP.ajax_url,
                dataType: 'json',
                type: 'post',
                data: {
                    action: 'get_translation_statistics',
                    easycms_wp_nonce: EASYCMS_WP.nonce
                },
                beforeSend: function() {
                    $this.prop('disabled', true).text('Loading...');
                },
                success: function(response) {
                    $this.prop('disabled', false).text('Show Statistics');
                    
                    if (response.success) {
                        let stats = response.data;
                        let html = '<div style="margin-top: 10px;">';
                        
                        // Overall statistics
                        html += `<div style="margin-bottom: 15px; padding: 10px; background: #fff; border: 1px solid #ddd;">
                            <h4>Translation Overview</h4>
                            <p><strong>Total Products:</strong> ${stats.total_products}</p>
                            <p><strong>Products with Missing Translations:</strong> ${stats.missing_translations.length}</p>
                        </div>`;
                        
                        // Language distribution
                        html += `<div style="margin-bottom: 15px; padding: 10px; background: #fff; border: 1px solid #ddd;">
                            <h4>Products per Language</h4>`;
                        
                        for (let [lang, count] of Object.entries(stats.language_counts)) {
                            html += `<p><strong>${lang}:</strong> ${count} products</p>`;
                        }
                        html += '</div>';
                        
                        // Missing translations detail (limit to first 10 for readability)
                        if (stats.missing_translations.length > 0) {
                            html += `<div style="margin-bottom: 15px; padding: 10px; background: #fff; border: 1px solid #ddd;">
                                <h4>Missing Translations (showing first ${Math.min(10, stats.missing_translations.length)} of ${stats.missing_translations.length})</h4>`;
                            
                            let displayCount = Math.min(10, stats.missing_translations.length);
                            for (let i = 0; i < displayCount; i++) {
                                let missing = stats.missing_translations[i];
                                html += `<p><strong>Product PID ${missing.pid}:</strong> Missing ${missing.missing_languages.join(', ')}</p>`;
                            }
                            
                            if (stats.missing_translations.length > 10) {
                                html += `<p><em>... and ${stats.missing_translations.length - 10} more products</em></p>`;
                            }
                            html += '</div>';
                        }
                        
                        html += '</div>';
                        
                        $( '#translation_stats_display' ).html(html);
                    } else {
                        let errorMessage = response.data || 'Error loading translation statistics.';
                        alert(errorMessage);
                        console.error('Failed to load statistics:', errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    $this.prop('disabled', false).text('Show Statistics');
                    console.error('AJAX error:', xhr.responseText, status, error);
                    alert('Failed to load translation statistics.');
                }
            });
        });
        
        $( '#easycms_wp_equalize_translations_btn' ).on( 'click', function(e) {
            e.preventDefault();
            let $this = $(e.target);
            
            if (confirm('This will create missing product translations. This process may take several minutes. Continue?')) {
                // Show progress section
                $( '#equalization_progress' ).show();
                $( '#equalization_log' ).html('Starting translation equalization...\n');
                
                $.ajax({
                    url: EASYCMS_WP.ajax_url,
                    dataType: 'json',
                    type: 'post',
                    data: {
                        action: 'equalize_product_translations',
                        easycms_wp_nonce: EASYCMS_WP.nonce
                    },
                    beforeSend: function() {
                        $this.prop('disabled', true).text('Equalizing...');
                        // Disable other buttons too
                        $( '#easycms_wp_translation_stats_btn' ).prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            let message = response.data?.message || 'Equalization started successfully.';
                            $( '#equalization_log' ).append(`✅ ${message}\n`);
                            
                            if (response.data?.note) {
                                $( '#equalization_log' ).append(`ℹ️  ${response.data.note}\n`);
                            }
                            
                            $( '#equalization_log' ).append(`\nYou can monitor progress in the logs section.\n`);
                        } else {
                            let errorMessage = response.data || 'Error starting translation equalization.';
                            $( '#equalization_log' ).append(`❌ ${errorMessage}\n`);
                            alert(errorMessage);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', xhr.responseText, status, error);
                        $( '#equalization_log' ).append(`❌ AJAX request failed: ${error}\n`);
                        alert('Failed to start translation equalization.');
                    },
                    complete: function() {
                        $this.prop('disabled', false).text('Run Equalizer');
                        $( '#easycms_wp_translation_stats_btn' ).prop('disabled', false);
                        
                        // Auto-hide progress after 30 seconds if completed
                        setTimeout(function() {
                            $( '#equalization_progress' ).fadeOut();
                        }, 30000);
                    }
                });
            }
        });

    });

})(jQuery);