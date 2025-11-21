<?php 
defined( 'ABSPATH' ) || exit;
?>

<div class='delete-products-settings'>
    <h2><?php _e( 'Delete Products', 'easycms-wp' ) ?></h2>
    
    <div class='warning-notice'>
        <div class='notice notice-warning inline'>
            <p><strong><?php _e( 'WARNING:', 'easycms-wp' ) ?></strong> <?php _e( 'This action cannot be undone. Please be careful when deleting products and their associated data.', 'easycms-wp' ) ?></p>
        </div>
    </div>
    
    <div class='delete-options-section'>
        <h3><?php _e( 'Deletion Options', 'easycms-wp' ) ?></h3>
        
        <table class='form-table'>
            <tr valign='top'>
                <td scope='row'>
                    <label for='delete_mode'>
                        <strong><?php _e( 'Delete Mode', 'easycms-wp' ) ?></strong>
                    </label>
                    <p class='description'><?php _e( 'Choose whether to delete all products or products from specific categories.', 'easycms-wp' ) ?></p>
                </td>
                <td>
                    <select id='delete_mode' class='regular-text'>
                        <option value='all'><?php _e( 'Delete All Products', 'easycms-wp' ) ?></option>
                        <option value='category'><?php _e( 'Delete by Category', 'easycms-wp' ) ?></option>
                    </select>
                </td>
            </tr>
            
            <tr valign='top' id='category_selection_row' style='display: none;'>
                <td scope='row'>
                    <label for='category_selection'>
                        <strong><?php _e( 'Select Categories', 'easycms-wp' ) ?></strong>
                    </label>
                    <p class='description'><?php _e( 'Select one or more categories whose products will be deleted.', 'easycms-wp' ) ?></p>
                </td>
                <td>
                    <select id='category_selection' class='regular-text' multiple='multiple' style='height: 150px;'>
                        <!-- Categories will be loaded here -->
                    </select>
                    <p class='description'><?php _e( 'Hold Ctrl/Cmd to select multiple categories', 'easycms-wp' ) ?></p>
                </td>
            </tr>
            
            <tr valign='top' id='delete_category_row' style='display: none;'>
                <td scope='row'>
                    <label for='delete_category'>
                        <strong><?php _e( 'Delete Categories', 'easycms-wp' ) ?></strong>
                    </label>
                    <p class='description'><?php _e( 'Check this to also delete the selected categories after deleting their products.', 'easycms-wp' ) ?></p>
                </td>
                <td>
                    <label class='checkbox'>
                        <input type='checkbox' id='delete_category' name='delete_category'>
                        <?php _e( 'Also delete the selected categories', 'easycms-wp' ) ?>
                    </label>
                </td>
            </tr>
            
            <tr valign='top'>
                <td scope='row'>
                    <label for='delete_translations'>
                        <strong><?php _e( 'Delete Translations', 'easycms-wp' ) ?></strong>
                    </label>
                    <p class='description'><?php _e( 'Delete all language translations of the products.', 'easycms-wp' ) ?></p>
                </td>
                <td>
                    <label class='checkbox'>
                        <input type='checkbox' id='delete_translations' name='delete_translations' checked>
                        <?php _e( 'Delete product translations', 'easycms-wp' ) ?>
                    </label>
                </td>
            </tr>
            
            <tr valign='top'>
                <td scope='row'>
                    <label for='delete_images'>
                        <strong><?php _e( 'Delete Images', 'easycms-wp' ) ?></strong>
                    </label>
                    <p class='description'><?php _e( 'Delete product images from the server. Missing image files will be skipped.', 'easycms-wp' ) ?></p>
                </td>
                <td>
                    <label class='checkbox'>
                        <input type='checkbox' id='delete_images' name='delete_images' checked>
                        <?php _e( 'Delete product images', 'easycms-wp' ) ?>
                    </label>
                </td>
            </tr>
        </table>
    </div>
    
    <div class='delete-actions'>
        <button type='button' id='preview_deletion' class='button button-secondary'>
            <?php _e( 'Preview Deletion', 'easycms-wp' ) ?>
        </button>
        
        <button type='button' id='start_deletion' class='button button-primary' disabled>
            <?php _e( 'Start Deletion', 'easycms-wp' ) ?>
        </button>
    </div>
    
    <div class='preview-section' id='preview_section' style='display: none;'>
        <h3><?php _e( 'Deletion Preview', 'easycms-wp' ) ?></h3>
        <div class='preview-content' id='preview_content'>
            <!-- Preview will be loaded here -->
        </div>
    </div>
    
    <div class='deletion-progress' id='deletion_progress' style='display: none;'>
        <h3><?php _e( 'Deletion Progress', 'easycms-wp' ) ?></h3>
        
        <div class='progress-bar-container'>
            <div class='progress-bar'>
                <div class='progress-fill' id='progress_fill'></div>
            </div>
            <div class='progress-text' id='progress_text'>0%</div>
        </div>
        
        <div class='progress-details' id='progress_details'>
            <p><?php _e( 'Starting deletion process...', 'easycms-wp' ) ?></p>
        </div>
        
        <div class='progress-stats' id='progress_stats'>
            <div class='stat-item'>
                <span class='stat-label'><?php _e( 'Products Deleted:', 'easycms-wp' ) ?></span>
                <span class='stat-value' id='products_deleted'>0</span>
            </div>
            <div class='stat-item'>
                <span class='stat-label'><?php _e( 'Translations Deleted:', 'easycms-wp' ) ?></span>
                <span class='stat-value' id='translations_deleted'>0</span>
            </div>
            <div class='stat-item'>
                <span class='stat-label'><?php _e( 'Images Deleted:', 'easycms-wp' ) ?></span>
                <span class='stat-value' id='images_deleted'>0</span>
            </div>
            <div class='stat-item'>
                <span class='stat-label'><?php _e( 'Errors:', 'easycms-wp' ) ?></span>
                <span class='stat-value' id='deletion_errors'>0</span>
            </div>
        </div>
        
        <div class='progress-log' id='progress_log'>
            <h4><?php _e( 'Deletion Log', 'easycms-wp' ) ?></h4>
            <div class='log-content' id='log_content'>
                <!-- Log messages will appear here -->
            </div>
        </div>
    </div>
    
    <div class='deletion-results' id='deletion_results' style='display: none;'>
        <h3><?php _e( 'Deletion Complete', 'easycms-wp' ) ?></h3>
        <div class='results-content' id='results_content'>
            <!-- Final results will be displayed here -->
        </div>
    </div>
    
    <style>
        .delete-products-settings {
            max-width: 900px;
        }
        
        .warning-notice {
            margin: 20px 0;
        }
        
        .delete-options-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .delete-actions {
            margin: 20px 0;
            padding: 15px;
            background: #f0f6fc;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }
        
        .delete-actions button {
            margin-right: 10px;
        }
        
        .preview-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .preview-content {
            margin-top: 10px;
        }
        
        .deletion-progress {
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .progress-bar-container {
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .progress-bar {
            flex: 1;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: #2196F3;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-weight: bold;
            min-width: 50px;
            text-align: right;
        }
        
        .progress-details {
            margin: 15px 0;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .progress-stats {
            display: flex;
            gap: 20px;
            margin: 15px 0;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        
        .progress-log {
            margin-top: 20px;
        }
        
        .log-content {
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
        
        .log-entry {
            margin-bottom: 5px;
            padding: 2px 0;
        }
        
        .log-entry.success {
            color: #46b450;
        }
        
        .log-entry.error {
            color: #d63638;
        }
        
        .log-entry.warning {
            color: #dba617;
        }
        
        .deletion-results {
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .results-content {
            margin-top: 10px;
        }
        
        .button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .form-table td:first-child {
            width: 300px;
        }
        
        .form-table td:last-child {
            vertical-align: middle;
        }
    </style>
    
    <script>
    // Simple test to ensure script is loading
    console.log('Delete Products script loaded');
    
    // Wait for DOM to be ready
    jQuery(function($) {
        console.log('Delete Products jQuery ready function called');

        // Debug: Check if EASYCMS_WP object is available
        if (typeof EASYCMS_WP === 'undefined') {
            console.error('EASYCMS_WP object not available');
            alert('Error: EASYCMS_WP object not available. Please refresh the page and try again.');
            return;
        }
        
        // Debug: Check if nonce is available
        if (typeof EASYCMS_WP.nonce === 'undefined') {
            console.error('EASYCMS_WP.nonce not available');
            alert('Error: Security nonce not available. Please refresh the page and try again.');
            return;
        }

        console.log('EASYCMS_WP object available:', EASYCMS_WP);
        console.log('Nonce available:', EASYCMS_WP.nonce);
        console.log('AJAX URL available:', EASYCMS_WP.ajax_url);

        var deletionInProgress = false;
        var currentOffset = 0;
        var totalProducts = 0;
        var batchSize = 100; // Increased batch size for better performance
        var cumulativeProducts = 0;
        var cumulativeTranslations = 0;
        var cumulativeImages = 0;
        var cumulativeErrors = 0;

        // Handle delete mode change
        $('#delete_mode').on('change', function() {
            console.log('Delete mode changed to:', $(this).val());
            var mode = $(this).val();
            
            if (mode === 'category') {
                $('#category_selection_row').show();
                $('#delete_category_row').show();
                loadCategories();
            } else {
                $('#category_selection_row').hide();
                $('#delete_category_row').hide();
            }
            
            // Reset preview and start button
            $('#preview_section').hide();
            $('#start_deletion').prop('disabled', true);
        });

        // Load categories
        function loadCategories() {
            console.log('Loading categories...');
            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_product_categories',
                    nonce: EASYCMS_WP.nonce
                },
                success: function(response) {
                    console.log('Categories loaded:', response);
                    if (response.success) {
                        var select = $('#category_selection');
                        select.empty();
                         
                        $.each(response.data.categories, function(index, category) {
                            select.append(
                                $('<option></option>')
                                    .val(category.id)
                                    .text(category.name + ' (' + category.count + ' products)')
                            );
                        });
                    } else {
                        alert('<?php _e( "Error loading categories. Please try again.", "easycms-wp" ) ?>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading categories:', xhr, status, error);
                    alert('<?php _e( "AJAX error occurred. Please try again.", "easycms-wp" ) ?>');
                }
            });
        }

        // Handle preview button
        $('#preview_deletion').on('click', function() {
            console.log('Preview deletion button clicked - main handler');
            
            var mode = $('#delete_mode').val();
            var deleteTranslations = $('#delete_translations').is(':checked');
            var deleteImages = $('#delete_images').is(':checked');
            
            console.log('Mode:', mode);
            console.log('Delete translations:', deleteTranslations);
            console.log('Delete images:', deleteImages);
            
            var requestData = {
                action: 'preview_deletion',
                mode: mode,
                delete_translations: deleteTranslations,
                delete_images: deleteImages,
                nonce: EASYCMS_WP.nonce
            };
            
            console.log('Request data:', requestData);
            console.log('AJAX URL:', EASYCMS_WP.ajax_url);
            
            if (mode === 'category') {
                var selectedCategories = $('#category_selection').val() || [];
                if (selectedCategories.length === 0) {
                    alert('<?php _e( "Please select at least one category.", "easycms-wp" ) ?>');
                    return;
                }
                requestData.category_ids = selectedCategories;
                requestData.delete_category = $('#delete_category').is(':checked');
                console.log('Category mode - selected categories:', selectedCategories);
            }
            
            // Show loading state
            $('#preview_deletion').prop('disabled', true).text('<?php _e( "Loading...", "easycms-wp" ) ?>');
            
            console.log('Starting AJAX request...');
            
            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: requestData,
                dataType: 'json',
                beforeSend: function(xhr) {
                    console.log('AJAX request sending...', xhr);
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                    $('#preview_deletion').prop('disabled', false).text('<?php _e( "Preview Deletion", "easycms-wp" ) ?>');
                    
                    if (response.success) {
                        console.log('Preview data received:', response.data);
                        displayPreview(response.data);
                        $('#start_deletion').prop('disabled', false);
                    } else {
                        alert('<?php _e( "Error generating preview. Please try again.", "easycms-wp" ) ?>');
                        console.error('Server error:', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr, status, error);
                    console.error('Response text:', xhr.responseText);
                    console.error('Status code:', xhr.status);
                    console.error('Response headers:', xhr.getAllResponseHeaders());
                    $('#preview_deletion').prop('disabled', false).text('<?php _e( "Preview Deletion", "easycms-wp" ) ?>');
                    
                    var errorMessage = '<?php _e( "AJAX error occurred. Please try again.", "easycms-wp" ) ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = '<?php _e( "Error: ", "easycms-wp" ) ?>' + xhr.responseJSON.data;
                    } else if (xhr.responseText) {
                        errorMessage += ' - ' + xhr.responseText;
                    }
                    alert(errorMessage);
                },
                complete: function(xhr, status) {
                    console.log('AJAX request completed with status:', status);
                    console.log('Final response:', xhr);
                }
            });
        });

        // Display preview
        function displayPreview(data) {
            var html = '<div class="preview-stats">';
            html += '<h4><?php _e( "Summary", "easycms-wp" ) ?></h4>';
            html += '<p><strong><?php _e( "Products to delete:", "easycms-wp" ) ?></strong> ' + data.product_count + '</p>';
            
            if (data.translation_count > 0) {
                html += '<p><strong><?php _e( "Translations to delete:", "easycms-wp" ) ?></strong> ' + data.translation_count + '</p>';
            }
            
            if (data.image_count > 0) {
                html += '<p><strong><?php _e( "Images to delete:", "easycms-wp" ) ?></strong> ' + data.image_count + '</p>';
            }
            
            html += '</div>';
            
            if (data.categories && data.categories.length > 0) {
                html += '<div class="preview-categories">';
                html += '<h4><?php _e( "Categories", "easycms-wp" ) ?></h4>';
                html += '<ul>';
                $.each(data.categories, function(index, category) {
                    html += '<li>' + category.name + ' (' + category.product_count + ' products)</li>';
                });
                html += '</ul>';
                html += '</div>';
            }
            
            $('#preview_content').html(html);
            $('#preview_section').show();
            
            // Store total products for progress tracking
            totalProducts = data.product_count;
        }

        // Handle start deletion button
        $('#start_deletion').on('click', function() {
            if (!confirm('<?php _e( "Are you sure you want to delete these products? This action cannot be undone.", "easycms-wp" ) ?>')) {
                return;
            }
            
            startDeletion();
        });

        // Start deletion process
        function startDeletion() {
            deletionInProgress = true;
            currentOffset = 0;
            
            // Reset cumulative counters
            cumulativeProducts = 0;
            cumulativeTranslations = 0;
            cumulativeImages = 0;
            cumulativeErrors = 0;
            
            $('#deletion_progress').show();
            $('#preview_section').hide();
            $('#start_deletion').prop('disabled', true);
            $('#preview_deletion').prop('disabled', true);
            
            // Reset display counters
            $('#products_deleted').text('0');
            $('#translations_deleted').text('0');
            $('#images_deleted').text('0');
            $('#deletion_errors').text('0');
            $('#log_content').empty();
            
            var mode = $('#delete_mode').val();
            var deleteTranslations = $('#delete_translations').is(':checked');
            var deleteImages = $('#delete_images').is(':checked');
            
            logMessage('<?php _e( "Starting deletion process...", "easycms-wp" ) ?>', 'info');
            logMessage('<?php _e( "Total products to process: ", "easycms-wp" ) ?>' + totalProducts, 'info');
            logMessage('<?php _e( "Batch size: ", "easycms-wp" ) ?>' + batchSize, 'info');
            
            if (mode === 'all') {
                deleteProductsBatch(true, deleteTranslations, deleteImages);
            } else {
                var selectedCategories = $('#category_selection').val() || [];
                var deleteCategory = $('#delete_category').is(':checked');
                deleteProductsByCategory(selectedCategories, deleteCategory, deleteTranslations, deleteImages);
            }
        }

        // Delete products batch
        function deleteProductsBatch(deleteAll, deleteTranslations, deleteImages) {
            logMessage('<?php _e( "Processing batch starting at offset: ", "easycms-wp" ) ?>' + currentOffset, 'info');
            
            var requestData = {
                action: 'delete_products_batch',
                delete_all: deleteAll,
                delete_translations: deleteTranslations,
                delete_images: deleteImages,
                offset: currentOffset,
                batch_size: batchSize,
                nonce: EASYCMS_WP.nonce
            };
            
            var startTime = new Date().getTime();
            
            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: requestData,
                timeout: 300000, // 5 minutes
                success: function(response) {
                    var endTime = new Date().getTime();
                    var duration = ((endTime - startTime) / 1000).toFixed(2);
                    logMessage('<?php _e( "Batch completed in ", "easycms-wp" ) ?>' + duration + '<?php _e( " seconds", "easycms-wp" ) ?>', 'info');
                    
                    // Debug logging
                    console.log('AJAX Response:', response);
                    logMessage('Debug: more_data = ' + (response.success ? response.data.more_data : 'N/A'), 'info');
                    logMessage('Debug: products_deleted = ' + (response.success ? response.data.products_deleted : 'N/A'), 'info');
                    logMessage('Debug: currentOffset = ' + currentOffset + ', batchSize = ' + batchSize, 'info');
                    
                    if (response.success) {
                        // Accumulate counts
                        cumulativeProducts += (response.data.products_deleted || 0);
                        cumulativeTranslations += (response.data.translations_deleted || 0);
                        cumulativeImages += (response.data.images_deleted || 0);
                        cumulativeErrors += (response.data.errors || 0);
                        
                        updateProgress(response.data);
                        
                        if (response.data.more_data) {
                            currentOffset += batchSize;
                            logMessage('<?php _e( "Continuing to next batch at offset: ", "easycms-wp" ) ?>' + currentOffset, 'info');
                            // Reduced delay for faster processing
                            setTimeout(function() {
                                deleteProductsBatch(deleteAll, deleteTranslations, deleteImages);
                            }, 500);
                        } else {
                            logMessage('<?php _e( "No more data - completing deletion", "easycms-wp" ) ?>', 'info');
                            completeDeletion();
                        }
                    } else {
                        logMessage('Error: ' + (response.data || 'Unknown error'), 'error');
                        completeDeletion();
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = '<?php _e( "AJAX error occurred. Please try again.", "easycms-wp" ) ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = '<?php _e( "Error: ", "easycms-wp" ) ?>' + xhr.responseJSON.data;
                    }
                    logMessage(errorMessage, 'error');
                    completeDeletion();
                }
            });
        }

        // Delete products by category
        function deleteProductsByCategory(categoryIds, deleteCategory, deleteTranslations, deleteImages) {
            logMessage('<?php _e( "Processing category batch starting at offset: ", "easycms-wp" ) ?>' + currentOffset, 'info');
            
            var requestData = {
                action: 'delete_products_by_category',
                category_ids: categoryIds,
                delete_category: deleteCategory,
                delete_translations: deleteTranslations,
                delete_images: deleteImages,
                offset: currentOffset,
                batch_size: batchSize,
                nonce: EASYCMS_WP.nonce
            };
            
            var startTime = new Date().getTime();
            
            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: requestData,
                timeout: 300000, // 5 minutes
                success: function(response) {
                    var endTime = new Date().getTime();
                    var duration = ((endTime - startTime) / 1000).toFixed(2);
                    logMessage('<?php _e( "Category batch completed in ", "easycms-wp" ) ?>' + duration + '<?php _e( " seconds", "easycms-wp" ) ?>', 'info');
                    
                    // Debug logging
                    console.log('Category AJAX Response:', response);
                    logMessage('Debug: more_data = ' + (response.success ? response.data.more_data : 'N/A'), 'info');
                    logMessage('Debug: products_deleted = ' + (response.success ? response.data.products_deleted : 'N/A'), 'info');
                    logMessage('Debug: currentOffset = ' + currentOffset + ', batchSize = ' + batchSize, 'info');
                    
                    if (response.success) {
                        // Accumulate counts
                        cumulativeProducts += (response.data.products_deleted || 0);
                        cumulativeTranslations += (response.data.translations_deleted || 0);
                        cumulativeImages += (response.data.images_deleted || 0);
                        cumulativeErrors += (response.data.errors || 0);
                        
                        updateProgress(response.data);
                        
                        if (response.data.more_data) {
                            currentOffset += batchSize;
                            logMessage('<?php _e( "Continuing to next category batch at offset: ", "easycms-wp" ) ?>' + currentOffset, 'info');
                            // Reduced delay for faster processing
                            setTimeout(function() {
                                deleteProductsByCategory(categoryIds, deleteCategory, deleteTranslations, deleteImages);
                            }, 500);
                        } else {
                            logMessage('<?php _e( "No more category data - completing deletion", "easycms-wp" ) ?>', 'info');
                            completeDeletion();
                        }
                    } else {
                        logMessage('Error: ' + (response.data || 'Unknown error'), 'error');
                        completeDeletion();
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = '<?php _e( "AJAX error occurred. Please try again.", "easycms-wp" ) ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = '<?php _e( "Error: ", "easycms-wp" ) ?>' + xhr.responseJSON.data;
                    }
                    logMessage(errorMessage, 'error');
                    completeDeletion();
                }
            });
        }

        // Update progress
        function updateProgress(data) {
            // Update counters with cumulative values
            $('#products_deleted').text(cumulativeProducts);
            $('#translations_deleted').text(cumulativeTranslations);
            $('#images_deleted').text(cumulativeImages);
            $('#deletion_errors').text(cumulativeErrors);
            
            // Update progress bar based on cumulative progress
            var progress = totalProducts > 0 ? Math.min(100, Math.round((cumulativeProducts / totalProducts) * 100)) : 0;
            $('#progress_fill').css('width', progress + '%');
            $('#progress_text').text(progress + '%');
            
            // Update details with batch info
            var batchInfo = '<?php _e( "Batch completed: ", "easycms-wp" ) ?>' + (data.products_deleted || 0) + '<?php _e( " products in this batch", "easycms-wp" ) ?>';
            var cumulativeInfo = '<?php _e( "Total progress: ", "easycms-wp" ) ?>' + cumulativeProducts + '<?php _e( " / ", "easycms-wp" ) ?>' + totalProducts + '<?php _e( " products", "easycms-wp" ) ?>';
            $('#progress_details').html('<p>' + batchInfo + '<br>' + cumulativeInfo + '</p>');
            
            // Add log messages
            if (data.log_messages && data.log_messages.length > 0) {
                $.each(data.log_messages, function(index, message) {
                    logMessage(message.text, message.type);
                });
            }
        }

        // Add log message
        function logMessage(message, type) {
            type = type || 'info';
            var timestamp = new Date().toLocaleTimeString();
            var logEntry = '<div class="log-entry ' + type + '">[' + timestamp + '] ' + message + '</div>';
            $('#log_content').append(logEntry);
            
            // Auto-scroll to bottom
            var logContent = $('#log_content');
            logContent.scrollTop(logContent[0].scrollHeight);
        }

        // Complete deletion
        function completeDeletion() {
            deletionInProgress = false;
            
            // Use cumulative values for final summary
            var productsDeleted = cumulativeProducts;
            var translationsDeleted = cumulativeTranslations;
            var imagesDeleted = cumulativeImages;
            var errors = cumulativeErrors;
            
            var html = '<div class="final-stats">';
            html += '<h4><?php _e( "Deletion Summary", "easycms-wp" ) ?></h4>';
            html += '<p><strong><?php _e( "Products Deleted:", "easycms-wp" ) ?></strong> ' + productsDeleted + '</p>';
            html += '<p><strong><?php _e( "Translations Deleted:", "easycms-wp" ) ?></strong> ' + translationsDeleted + '</p>';
            html += '<p><strong><?php _e( "Images Deleted:", "easycms-wp" ) ?></strong> ' + imagesDeleted + '</p>';
            html += '<p><strong><?php _e( "Errors:", "easycms-wp" ) ?></strong> ' + errors + '</p>';
            
            // Add performance summary
            var successRate = totalProducts > 0 ? Math.round((productsDeleted / totalProducts) * 100) : 0;
            html += '<p><strong><?php _e( "Success Rate:", "easycms-wp" ) ?></strong> ' + successRate + '%</p>';
            html += '</div>';
            
            $('#results_content').html(html);
            $('#deletion_results').show();
            
            // Re-enable buttons
            $('#start_deletion').prop('disabled', false);
            $('#preview_deletion').prop('disabled', false);
            
            logMessage('<?php _e( "Deletion process completed successfully.", "easycms-wp" ) ?>', 'success');
            logMessage('<?php _e( "Final totals - Products: ", "easycms-wp" ) ?>' + productsDeleted + '<?php _e( ", Translations: ", "easycms-wp" ) ?>' + translationsDeleted + '<?php _e( ", Images: ", "easycms-wp" ) ?>' + imagesDeleted, 'info');
        }
    });
    </script>
</div>