<?php 
defined( 'ABSPATH' ) || exit;

// Get count of synced products
global $wpdb;
$synced_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'easycms_pid'" );
?>

<div class='delete-components-settings'>
    <h2><?php _e( 'Delete Synced Components', 'easycms-wp' ) ?></h2>
    
    <div class='warning-section'>
        <div class='notice notice-error inline'>
            <h3><?php _e( '⚠️ WARNING: This action is irreversible!', 'easycms-wp' ) ?></h3>
            <p><?php _e( 'This section allows you to permanently delete all products that were synced from EasyCMS. This action:', 'easycms-wp' ) ?></p>
            <ul>
                <li><?php _e( 'Will permanently delete all products with easycms_pid metadata', 'easycms-wp' ) ?></li>
                <li><?php _e( 'Will delete associated product images if they are not used by other products', 'easycms-wp' ) ?></li>
                <li><?php _e( 'Cannot be undone - there is no restore functionality', 'easycms-wp' ) ?></li>
                <li><?php _e( 'Will affect all language translations of the products', 'easycms-wp' ) ?></li>
            </ul>
        </div>
    </div>

    <div class='delete-products-section'>
        <h3><?php _e( 'Delete All Synced Products', 'easycms-wp' ) ?></h3>
        
        <table class='form-table'>
            <tr valign='top'>
                <td scope='row'>
                    <strong><?php _e( 'Current synced products count:', 'easycms-wp' ) ?></strong>
                </td>
                <td>
                    <span id='synced_products_count' class='synced-count'><?php echo intval( $synced_count ); ?></span>
                    <button type='button' id='refresh_synced_count' class='button button-secondary'>
                        <?php _e( 'Refresh Count', 'easycms-wp' ) ?>
                    </button>
                </td>
            </tr>
            
            <tr valign='top'>
                <td scope='row' colspan='2'>
                    <div class='delete-confirmation'>
                        <p><strong><?php _e( 'To proceed with deletion, you must type the confirmation text exactly as shown below:', 'easycms-wp' ) ?></strong></p>
                        <code>DELETE ALL SYNCED PRODUCTS</code>
                        <input type='text' id='delete_confirmation' class='regular-text' placeholder='<?php _e( 'Type the confirmation text above', 'easycms-wp' ) ?>'>
                        <div id='confirmation_status' class='confirmation-status'>
                            <span class='status-indicator'></span>
                            <span class='status-text'><?php _e( 'Waiting for confirmation...', 'easycms-wp' ) ?></span>
                        </div>
                    </div>
                </td>
            </tr>
            
            <tr valign='top'>
                <td scope='row' colspan='2'>
                    <div class='test-section'>
                        <button type='button' id='test_delete_single' class='button button-secondary'>
                            <?php _e( 'Test Delete Single Product', 'easycms-wp' ) ?>
                        </button>
                        <p class='description'>
                            <?php _e( 'Test the deletion process on a single product first to verify it works.', 'easycms-wp' ) ?>
                        </p>
                    </div>
                    
                    <hr style='margin: 20px 0;'>
                    
                    <div class='full-delete-section'>
                        <button type='button' id='delete_all_products' class='button button-primary delete-button' disabled>
                            <?php _e( 'Delete All Synced Products', 'easycms-wp' ) ?>
                        </button>
                        <p class='description'>
                            <?php _e( 'This will permanently delete all synced products and their images. This action cannot be undone.', 'easycms-wp' ) ?>
                        </p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class='delete-abandoned-images-section'>
        <h3><?php _e( 'Delete Abandoned Product Images', 'easycms-wp' ) ?></h3>
        
        <div class='info-section'>
            <div class='notice notice-info inline'>
                <h4><?php _e( 'ℹ️ What are abandoned product images?', 'easycms-wp' ) ?></h4>
                <p><?php _e( 'These are product images that exist on your disk but are no longer referenced by any product in your database. This typically happens when products are deleted but their images remain on the server.', 'easycms-wp' ) ?></p>
                <p><strong><?php _e( 'This tool will:', 'easycms-wp' ) ?></strong></p>
                <ul>
                    <li><?php _e( 'Find product images that are no longer used by any product', 'easycms-wp' ) ?></li>
                    <li><?php _e( 'Only target images that were originally product images', 'easycms-wp' ) ?></li>
                    <li><?php _e( 'Preserve all images used by posts, pages, and other content', 'easycms-wp' ) ?></li>
                    <li><?php _e( 'Free up disk space by removing orphaned image files', 'easycms-wp' ) ?></li>
                </ul>
            </div>
        </div>
        
        <div class='abandoned-images-settings'>
            <table class='form-table'>
                <tr valign='top'>
                    <td scope='row'>
                        <strong><?php _e( 'Mode:', 'easycms-wp' ) ?></strong>
                    </td>
                    <td>
                        <label>
                            <input type='radio' name='cleanup_mode' value='dry_run' checked>
                            <?php _e( 'Dry Run (Recommended) - Show what would be deleted without actually deleting', 'easycms-wp' ) ?>
                        </label>
                        <br>
                        <label>
                            <input type='radio' name='cleanup_mode' value='actual'>
                            <?php _e( 'Actual Deletion - Permanently delete abandoned images', 'easycms-wp' ) ?>
                        </label>
                    </td>
                </tr>
                
                <tr valign='top'>
                    <td scope='row'>
                        <strong><?php _e( 'Batch Size:', 'easycms-wp' ) ?></strong>
                    </td>
                    <td>
                        <select name='batch_size' id='batch_size'>
                            <option value='25'><?php _e( '25 images (Safe)', 'easycms-wp' ) ?></option>
                            <option value='50' selected><?php _e( '50 images (Recommended)', 'easycms-wp' ) ?></option>
                            <option value='100'><?php _e( '100 images (Fast)', 'easycms-wp' ) ?></option>
                            <option value='200'><?php _e( '200 images (Very Fast)', 'easycms-wp' ) ?></option>
                        </select>
                        <p class='description'>
                            <?php _e( 'Number of images to process per batch. Smaller batches are safer but slower.', 'easycms-wp' ) ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class='abandoned-images-actions'>
            <button type='button' id='analyze_abandoned_images' class='button button-secondary'>
                <?php _e( 'Analyze Abandoned Images', 'easycms-wp' ) ?>
            </button>
            <button type='button' id='cleanup_abandoned_images' class='button button-primary' disabled>
                <?php _e( 'Clean Up Abandoned Images', 'easycms-wp' ) ?>
            </button>
            <p class='description'>
                <?php _e( 'First run "Analyze" to see what would be deleted, then run "Clean Up" to actually delete the files.', 'easycms-wp' ) ?>
            </p>
        </div>
    </div>

    <div class='status-section'>
        <h3><?php _e( 'Operation Status', 'easycms-wp' ) ?></h3>
        <div id='delete_status' class='status-container'>
            <p><?php _e( 'No operation performed yet.', 'easycms-wp' ) ?></p>
        </div>
    </div>

    <style>
        .delete-components-settings {
            max-width: 800px;
        }

        .warning-section {
            margin-bottom: 30px;
        }

        .warning-section .notice-error {
            padding: 20px;
            border-left: 4px solid #dc3232;
        }

        .warning-section h3 {
            color: #dc3232;
            margin-top: 0;
        }

        .warning-section ul {
            margin: 10px 0 0 20px;
        }

        .delete-products-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
        }

        .synced-count {
            font-size: 18px;
            font-weight: bold;
            color: #d63638;
            margin-right: 10px;
        }

        .delete-confirmation {
            background: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
        }

        .delete-confirmation code {
            display: block;
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 14px;
            color: #d63638;
            font-weight: bold;
        }

        .delete-button {
            background: #dc3232;
            border-color: #dc3232;
            color: #fff;
        }

        .delete-button:hover:not(:disabled) {
            background: #c71e1e;
            border-color: #c71e1e;
        }

        .delete-button:disabled {
            background: #ccc;
            border-color: #ccc;
            cursor: not-allowed;
        }

        .confirmation-status {
            margin-top: 10px;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
        }

        .confirmation-status.waiting {
            background: #f8f9fa;
            border: 1px solid #ddd;
            color: #666;
        }

        .confirmation-status.valid {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .confirmation-status.invalid {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .confirmation-status.waiting .status-indicator {
            background: #666;
        }

        .confirmation-status.valid .status-indicator {
            background: #28a745;
        }

        .confirmation-status.invalid .status-indicator {
            background: #dc3545;
        }

        .status-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
        }

        .status-container {
            min-height: 50px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .status-container.loading {
            background: #fff3cd;
            border-color: #ffeaa7;
        }

        .status-container.success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .status-container.error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            background: #0073aa;
            width: 0%;
            transition: width 0.3s ease;
        }

        .form-table td:first-child {
            width: 300px;
        }

        .form-table td:last-child {
            vertical-align: middle;
        }

        .time-estimate {
            background: #e7f3ff;
            border: 1px solid #0073aa;
            border-radius: 4px;
            padding: 10px;
            margin-top: 10px;
            font-size: 13px;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .success-message h4 {
            margin-top: 0;
            color: #155724;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .error-message h4 {
            margin-top: 0;
            color: #721c24;
        }

        .test-section {
            background: #f0f6fc;
            padding: 15px;
            border: 1px solid #c3d4e7;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .full-delete-section {
            margin-top: 20px;
        }

        .delete-abandoned-images-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-section .notice-info {
            padding: 15px;
            border-left: 4px solid #0073aa;
        }

        .info-section h4 {
            color: #0073aa;
            margin-top: 0;
        }

        .info-section ul {
            margin: 10px 0 0 20px;
        }

        .abandoned-images-settings {
            background: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 15px 0;
        }

        .abandoned-images-actions {
            margin-top: 20px;
        }

        .abandoned-images-actions .button {
            margin-right: 10px;
        }

        .abandoned-images-actions .button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .abandoned-images-results {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .abandoned-images-summary {
            background: #e7f3ff;
            border: 1px solid #0073aa;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
            font-size: 14px;
        }

        .abandoned-images-summary strong {
            color: #0073aa;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Define translated strings to avoid PHP escaping issues
        var translations = {
            'confirmation_valid': '<?php echo esc_js( __( 'Confirmation valid - Delete button enabled', 'easycms-wp' ) ); ?>',
            'confirmation_invalid': '<?php echo esc_js( __( 'Confirmation text does not match exactly', 'easycms-wp' ) ); ?>',
            'waiting_confirmation': '<?php echo esc_js( __( 'Waiting for confirmation...', 'easycms-wp' ) ); ?>',
            'invalid_confirmation': '<?php echo esc_js( __( 'Invalid confirmation. Please type the exact confirmation text.', 'easycms-wp' ) ); ?>',
            'are_you_sure': '<?php echo esc_js( __( "Are you absolutely sure you want to delete ALL synced products? This action cannot be undone and will permanently remove products and their images.", "easycms-wp" ) ); ?>',
            'final_warning': '<?php echo esc_js( __( "This is your final warning. All synced products will be permanently deleted. Type 'OK' to confirm.", "easycms-wp" ) ); ?>',
            'deleting': '<?php echo esc_js( __( "Deleting...", "easycms-wp" ) ); ?>',
            'deleting_message': '<?php echo esc_js( __( "Deleting products... Please wait, this may take a while.", "easycms-wp" ) ); ?>',
            'deletion_success': '<?php echo esc_js( __( "Deletion Completed Successfully!", "easycms-wp" ) ); ?>',
            'deleted_products': '<?php echo esc_js( __( "Deleted products:", "easycms-wp" ) ); ?>',
            'image_errors': '<?php echo esc_js( __( "Image files that could not be deleted:", "easycms-wp" ) ); ?>',
            'check_logs': '<?php echo esc_js( __( "Check error logs for details about image deletion failures.", "easycms-wp" ) ); ?>',
            'deletion_failed': '<?php echo esc_js( __( "Deletion Failed", "easycms-wp" ) ); ?>',
            'deletion_error': '<?php echo esc_js( __( "Deletion Error", "easycms-wp" ) ); ?>',
            'error_occurred': '<?php echo esc_js( __( "An error occurred during deletion. Please try again.", "easycms-wp" ) ); ?>',
            'deletion_timeout': '<?php echo esc_js( __( "Deletion timed out. The operation may still be running in the background. Please check your products and try again if needed.", "easycms-wp" ) ); ?>'
        };

        // Handle refresh synced count button
        $('#refresh_synced_count').on('click', function() {
            refreshSyncedCount();
        });

        function refreshSyncedCount() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_synced_products_count',
                    nonce: EASYCMS_WP.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#synced_products_count').text(response.data.count);
                    }
                }
            });
        }

        // Handle confirmation text input
        $('#delete_confirmation').on('input keyup paste', function() {
            var confirmation = $(this).val().trim();
            var expectedText = 'DELETE ALL SYNCED PRODUCTS';
            var $deleteButton = $('#delete_all_products');
            var $statusDiv = $('#confirmation_status');
            var $statusText = $statusDiv.find('.status-text');
            
            // Debug logging
            console.log('Confirmation text:', confirmation);
            console.log('Expected text:', expectedText);
            console.log('Match:', confirmation === expectedText);
            
            if (confirmation === expectedText) {
                $deleteButton.prop('disabled', false);
                $statusDiv.removeClass('waiting invalid').addClass('valid');
                $statusText.text(translations.confirmation_valid);
                console.log('Button enabled');
            } else if (confirmation.length > 0) {
                $deleteButton.prop('disabled', true);
                $statusDiv.removeClass('waiting valid').addClass('invalid');
                $statusText.text(translations.confirmation_invalid);
                console.log('Button disabled - text mismatch');
            } else {
                $deleteButton.prop('disabled', true);
                $statusDiv.removeClass('valid invalid').addClass('waiting');
                $statusText.text(translations.waiting_confirmation);
                console.log('Button disabled - empty');
            }
        });

        // Handle test delete single product button
        $('#test_delete_single').on('click', function() {
            var $button = $(this);
            var originalText = $button.text();
            
            if (!confirm('<?php _e( "This will delete ONE product as a test. Are you sure you want to continue?", "easycms-wp" ) ?>')) {
                return;
            }
            
            // Disable button and show loading state
            $button.text('<?php _e( "Testing...", "easycms-wp" ) ?>').prop('disabled', true).addClass('loading');
            $('#delete_status').html('<p><?php _e( "Testing deletion on single product...", "easycms-wp" ) ?></p>').addClass('loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'test_delete_single_product',
                    nonce: EASYCMS_WP.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var statusHtml = '<div class="success-message">';
                        statusHtml += '<h4><?php _e( "Test Deletion Successful!", "easycms-wp" ) ?></h4>';
                        statusHtml += '<p>' + response.data.message + '</p>';
                        statusHtml += '<p><?php _e( "The deletion process is working correctly. You can now proceed with full deletion.", "easycms-wp" ) ?></p>';
                        statusHtml += '</div>';
                        
                        $('#delete_status').html(statusHtml).removeClass('loading').addClass('success');
                        
                        // Refresh count after test deletion
                        setTimeout(function() {
                            refreshSyncedCount();
                        }, 1000);
                    } else {
                        $('#delete_status').html('<div class="error-message"><h4><?php _e( "Test Deletion Failed", "easycms-wp" ) ?></h4><p>' + response.data + '</p></div>').removeClass('loading').addClass('error');
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = '<?php _e( "An error occurred during test deletion. Please check server logs.", "easycms-wp" ) ?>';
                    $('#delete_status').html('<div class="error-message"><h4><?php _e( "Test Error", "easycms-wp" ) ?></h4><p>' + errorMsg + '</p></div>').removeClass('loading').addClass('error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false).removeClass('loading');
                }
            });
        });

        // Handle delete all products button
        $('#delete_all_products').on('click', function() {
            var $button = $(this);
            var originalText = $button.text();
            var confirmation = $('#delete_confirmation').val().trim();
            
            if (confirmation !== 'DELETE ALL SYNCED PRODUCTS') {
                alert(translations.invalid_confirmation);
                return;
            }
            
            if (!confirm(translations.are_you_sure)) {
                return;
            }
            
            if (!confirm(translations.final_warning)) {
                return;
            }
            
            // Disable button and show loading state
            $button.text(translations.deleting).prop('disabled', true).addClass('loading');
            $('#delete_status').html('<p>' + translations.deleting_message + '</p>').addClass('loading');
            
            // Add progress bar
            var progressHtml = '<div class="progress-bar"><div class="progress-fill"></div></div>';
            $('#delete_status').append(progressHtml);
            
            // Animate progress bar
            var progress = 0;
            var progressInterval = setInterval(function() {
                progress += Math.random() * 10;
                if (progress > 90) progress = 90;
                $('.progress-fill').css('width', progress + '%');
            }, 500);
            
            // Show estimated time based on product count
            var currentCount = parseInt($('#synced_products_count').text());
            var estimatedMinutes = Math.ceil(currentCount / 1000); // Rough estimate: ~1000 products per minute
            var timeEstimate = '';
            if (estimatedMinutes > 1) {
                timeEstimate = ' Estimated time: ' + estimatedMinutes + ' minutes.';
            } else {
                timeEstimate = ' Estimated time: less than 1 minute.';
            }
            
            $('#delete_status').append('<p class="time-estimate"><strong>Note:</strong> Deleting ' + currentCount + ' products.' + timeEstimate + '</p>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_all_synced_products',
                    confirmation: confirmation,
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 600000, // 10 minute timeout for large deletions
                success: function(response) {
                    clearInterval(progressInterval);
                    $('.progress-fill').css('width', '100%');
                    
                    if (response.success) {
                        var statusHtml = '<div class="success-message">';
                        statusHtml += '<h4>' + translations.deletion_success + '</h4>';
                        statusHtml += '<p>' + response.data.message + '</p>';
                        statusHtml += '<p>' + translations.deleted_products + ' <strong>' + response.data.deleted_count + '</strong> of <strong>' + (response.data.total_products || currentCount) + '</strong> total synced products.</p>';
                        if (response.data.image_errors > 0) {
                            statusHtml += '<p>' + translations.image_errors + ' <strong>' + response.data.image_errors + '</strong></p>';
                            statusHtml += '<p>' + translations.check_logs + '</p>';
                        }
                        statusHtml += '<p><strong>Operation completed successfully!</strong></p>';
                        statusHtml += '</div>';
                        
                        $('#delete_status').html(statusHtml).removeClass('loading').addClass('success');
                        
                        // Refresh count after successful deletion
                        setTimeout(function() {
                            refreshSyncedCount();
                        }, 1000);
                    } else {
                        $('#delete_status').html('<div class="error-message"><h4>' + translations.deletion_failed + '</h4><p>' + response.data + '</p></div>').removeClass('loading').addClass('error');
                    }
                },
                error: function(xhr, status, error) {
                    clearInterval(progressInterval);
                    var errorMsg = translations.error_occurred;
                    if (status === 'timeout') {
                        errorMsg = 'The deletion operation timed out after 10 minutes. For very large datasets (13,000+ products), this is normal. The operation may still be running in the background. Please check your products count in a few minutes and try again if needed.';
                    }
                    $('#delete_status').html('<div class="error-message"><h4>' + translations.deletion_error + '</h4><p>' + errorMsg + '</p></div>').removeClass('loading').addClass('error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false).removeClass('loading');
                    // Clear confirmation field
                    $('#delete_confirmation').val('');
                    // Disable button again
                    $('#delete_all_products').prop('disabled', true);
                }
            });
        });

        // Handle abandoned image analysis and cleanup
        $('#analyze_abandoned_images').on('click', function() {
            var $button = $(this);
            var originalText = $button.text();
            
            // Disable buttons and show loading state
            $button.text('<?php _e( "Analyzing...", "easycms-wp" ) ?>').prop('disabled', true);
            $('#cleanup_abandoned_images').prop('disabled', true);
            $('#delete_status').html('<p><?php _e( "Analyzing abandoned images... This may take a few moments.", "easycms-wp" ) ?></p>').addClass('loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_abandoned_product_images',
                    dry_run: true,
                    batch_size: parseInt($('#batch_size').val()),
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 300000, // 5 minute timeout for analysis
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var resultHtml = '<div class="abandoned-images-results">';
                        
                        resultHtml += '<h4><?php _e( "Analysis Results", "easycms-wp" ) ?></h4>';
                        resultHtml += '<div class="abandoned-images-summary">';
                        resultHtml += '<strong><?php _e( "Summary:", "easycms-wp" ) ?></strong><br>';
                        resultHtml += '<?php _e( "Total images found:", "easycms-wp" ) ?> ' + data.total_images_found + '<br>';
                        resultHtml += '<?php _e( "Product images found:", "easycms-wp" ) ?> ' + data.product_images_found + '<br>';
                        resultHtml += '<?php _e( "Abandoned images found:", "easycms-wp" ) ?> ' + data.abandoned_images_found;
                        if (data.abandoned_images_found > 0) {
                            var estimatedSpace = (data.abandoned_images_found * 2).toFixed(1); // Rough estimate: 2MB per image
                            resultHtml += '<br><?php _e( "Estimated space to free:", "easycms-wp" ) ?> ~' + estimatedSpace + ' MB';
                        }
                        resultHtml += '</div>';
                        
                        if (data.log_messages && data.log_messages.length > 0) {
                            resultHtml += '<h5><?php _e( "Details:", "easycms-wp" ) ?></h5>';
                            resultHtml += '<ul>';
                            data.log_messages.forEach(function(msg) {
                                resultHtml += '<li class="' + msg.type + '">' + msg.text + '</li>';
                            });
                            resultHtml += '</ul>';
                        }
                        
                        resultHtml += '</div>';
                        
                        $('#delete_status').html(resultHtml).removeClass('loading').addClass('success');
                        
                        // Enable cleanup button if there are abandoned images
                        if (data.abandoned_images_found > 0) {
                            $('#cleanup_abandoned_images').prop('disabled', false);
                        }
                        
                    } else {
                        $('#delete_status').html('<div class="error-message"><h4><?php _e( "Analysis Failed", "easycms-wp" ) ?></h4><p>' + response.data + '</p></div>').removeClass('loading').addClass('error');
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = '<?php _e( "An error occurred during analysis. Please try again.", "easycms-wp" ) ?>';
                    if (status === 'timeout') {
                        errorMsg = '<?php _e( "Analysis timed out. The site may have many images to analyze.", "easycms-wp" ) ?>';
                    }
                    $('#delete_status').html('<div class="error-message"><h4><?php _e( "Analysis Error", "easycms-wp" ) ?></h4><p>' + errorMsg + '</p></div>').removeClass('loading').addClass('error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        });

        // Handle actual cleanup
        $('#cleanup_abandoned_images').on('click', function() {
            var $button = $(this);
            var originalText = $button.text();
            var cleanupMode = $('input[name="cleanup_mode"]:checked').val();
            
            if (cleanupMode === 'dry_run') {
                alert('<?php _e( "Please select 'Actual Deletion' mode to permanently delete images.", "easycms-wp" ) ?>');
                return;
            }
            
            if (!confirm('<?php _e( "Are you sure you want to permanently delete abandoned product images? This action cannot be undone.", "easycms-wp" ) ?>')) {
                return;
            }
            
            // Disable buttons and show loading state
            $button.text('<?php _e( "Cleaning Up...", "easycms-wp" ) ?>').prop('disabled', true);
            $('#analyze_abandoned_images').prop('disabled', true);
            $('#delete_status').html('<p><?php _e( "Cleaning up abandoned images... Please wait, this may take several minutes.", "easycms-wp" ) ?></p>').addClass('loading');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_abandoned_product_images',
                    dry_run: false,
                    batch_size: parseInt($('#batch_size').val()),
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 600000, // 10 minute timeout for cleanup
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        var resultHtml = '<div class="abandoned-images-results">';
                        
                        resultHtml += '<h4><?php _e( "Cleanup Completed", "easycms-wp" ) ?></h4>';
                        resultHtml += '<div class="abandoned-images-summary">';
                        resultHtml += '<strong><?php _e( "Results:", "easycms-wp" ) ?></strong><br>';
                        resultHtml += '<?php _e( "Images deleted:", "easycms-wp" ) ?> ' + data.images_deleted + '<br>';
                        resultHtml += '<?php _e( "Disk space freed:", "easycms-wp" ) ?> ' + (data.disk_space_freed / (1024*1024)).toFixed(2) + ' MB<br>';
                        resultHtml += '<?php _e( "Errors:", "easycms-wp" ) ?> ' + data.errors;
                        resultHtml += '</div>';
                        
                        if (data.log_messages && data.log_messages.length > 0) {
                            resultHtml += '<h5><?php _e( "Details:", "easycms-wp" ) ?></h5>';
                            resultHtml += '<ul>';
                            data.log_messages.forEach(function(msg) {
                                resultHtml += '<li class="' + msg.type + '">' + msg.text + '</li>';
                            });
                            resultHtml += '</ul>';
                        }
                        
                        resultHtml += '</div>';
                        
                        $('#delete_status').html(resultHtml).removeClass('loading').addClass('success');
                        
                    } else {
                        $('#delete_status').html('<div class="error-message"><h4><?php _e( "Cleanup Failed", "easycms-wp" ) ?></h4><p>' + response.data + '</p></div>').removeClass('loading').addClass('error');
                    }
                },
                error: function(xhr, status, error) {
                    var errorMsg = '<?php _e( "An error occurred during cleanup. Please try again.", "easycms-wp" ) ?>';
                    if (status === 'timeout') {
                        errorMsg = '<?php _e( "Cleanup timed out. The operation may still be running in the background.", "easycms-wp" ) ?>';
                    }
                    $('#delete_status').html('<div class="error-message"><h4><?php _e( "Cleanup Error", "easycms-wp" ) ?></h4><p>' + errorMsg + '</p></div>').removeClass('loading').addClass('error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                    $('#analyze_abandoned_images').prop('disabled', false);
                    // Disable cleanup button after completion
                    $('#cleanup_abandoned_images').prop('disabled', true);
                }
            });
        });
    });
    </script>
</div>