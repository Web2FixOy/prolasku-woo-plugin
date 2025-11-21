<?php 
defined( 'ABSPATH' ) || exit;

// Get current settings from simple option (bypasses complex config)
$publish_all_value = get_option( 'prolasku_publish_all_products', 0 );
$publish_all_enabled = ($publish_all_value == 1);

// Get draft products count
global $wpdb;
$draft_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'draft'" );
?>

<div class='inventory-settings'>
    <h2><?php _e( 'Inventory Management', 'easycms-wp' ) ?></h2>
    
    <div class='publish-all-section'>
        <h3><?php _e( 'Product Publishing Settings', 'easycms-wp' ) ?></h3>
        
        <table class='form-table'>
            <tr valign='top'>
                <td scope='row'>
                    <label for='publish_all_products'>
                        <strong><?php _e( 'Publish all products', 'easycms-wp' ) ?></strong>
                    </label>
                    <p class='description'><?php _e( 'When enabled, all products will be set to "publish" status during synchronization, regardless of their status in the external system.', 'easycms-wp' ) ?></p>
                </td>
                <td>
                    <input type='checkbox' id='publish_all_products' name='publish_all_products' value='1' <?php checked( $publish_all_enabled ); ?>>
                    <label for='publish_all_products'><?php _e( 'Enable product publishing', 'easycms-wp' ) ?></label>
                </td>
            </tr>
            
            <tr valign='top'>
                <td scope='row'>
                    <strong><?php _e( 'Total draft products count:', 'easycms-wp' ) ?></strong>
                </td>
                <td>
                    <span id='draft_products_count' class='draft-count'><?php echo intval( $draft_count ); ?></span>
                    <button type='button' id='refresh_count' class='button button-secondary'>
                        <?php _e( 'Refresh Count', 'easycms-wp' ) ?>
                    </button>
                    
                </td>
            </tr>
        </table>
    </div>

    <div class='translation-equalizer-section'>
        <h3><?php _e( 'Product Translation Equalizer', 'easycms-wp' ) ?></h3>
        <p class='description'><?php _e( 'Check and fix missing product translations to ensure consistency across all languages.', 'easycms-wp' ) ?></p>
        
        <div class='translation-stats-container'>
            <div class='stats-loading' style='display: none;'>
                <p><strong><?php _e( 'Loading translation statistics...', 'easycms-wp' ) ?></strong></p>
                <div class='spinner is-active'></div>
            </div>
            
            <div class='stats-content' id='translation_stats_content' style='display: none;'>
                <!-- Statistics will be loaded here -->
            </div>
            
            <div class='unsynced-products-container' id='unsynced_products_container' style='display: none; margin-top: 20px;'>
                <!-- Unsynced products will be displayed here -->
            </div>
        </div>
        
        <div class='translation-actions'>
            <button type='button' id='refresh_translation_stats' class='button button-secondary'>
                <?php _e( 'Refresh Statistics', 'easycms-wp' ) ?>
            </button>
            
            <div class='equalize-actions' id='equalize_actions' style='display: none;'>
                <select id='target_language' class='regular-text'>
                    <option value='all'><?php _e( 'All Languages', 'easycms-wp' ) ?></option>
                </select>
                
                <button type='button' id='equalize_translations' class='button button-primary'>
                    <?php _e( 'Equalize Translations', 'easycms-wp' ) ?>
                </button>
            </div>
        </div>
        
        <div class='equalize-progress' id='equalize_progress' style='display: none;'>
            <p><strong><?php _e( 'Processing translations...', 'easycms-wp' ) ?></strong></p>
            <div class='spinner is-active'></div>
            <div class='progress-message' id='progress_message'></div>
        </div>
        
        <div class='equalize-results' id='equalize_results' style='display: none;'>
            <!-- Results will be displayed here -->
        </div>
    </div>

    <div class='orphaned-pids-section'>
        <h3><?php _e( 'Orphaned Product Cleanup', 'easycms-wp' ) ?></h3>
        <p class='description'><?php _e( 'Find and remove products that exist in non-main languages but not in the main language (Finnish). These orphaned products cause translation equalization to fail.', 'easycms-wp' ) ?></p>
        
        <div class='orphaned-pids-container'>
            <div class='orphaned-loading' style='display: none;'>
                <p><strong><?php _e( 'Scanning for orphaned products...', 'easycms-wp' ) ?></strong></p>
                <div class='spinner is-active'></div>
            </div>
            
            <div class='orphaned-content' id='orphaned_content' style='display: none;'>
                <!-- Orphaned PIDs will be displayed here -->
            </div>
        </div>
        
        <div class='orphaned-actions'>
            <button type='button' id='scan_orphaned_pids' class='button button-secondary'>
                <?php _e( 'Scan for Orphaned Products', 'easycms-wp' ) ?>
            </button>
            
            <div class='cleanup-actions' id='cleanup_actions' style='display: none;'>
                <button type='button' id='dry_run_cleanup' class='button'>
                    <?php _e( 'Preview Cleanup', 'easycms-wp' ) ?>
                </button>
                <button type='button' id='actual_cleanup' class='button button-primary'>
                    <?php _e( 'Clean Up Orphaned Products', 'easycms-wp' ) ?>
                </button>
            </div>
        </div>
        
        <div class='cleanup-progress' id='cleanup_progress' style='display: none;'>
            <p><strong><?php _e( 'Processing cleanup...', 'easycms-wp' ) ?></strong></p>
            <div class='spinner is-active'></div>
            <div class='cleanup-message' id='cleanup_message'></div>
        </div>
        
        <div class='cleanup-results' id='cleanup_results' style='display: none;'>
            <!-- Results will be displayed here -->
        </div>
    </div>

    <div class='corrupted-data-section'>
        <h3><?php _e( 'Corrupted Data Cleanup', 'easycms-wp' ) ?></h3>
        <p class='description'><?php _e( 'Find and clean up corrupted product data such as orphaned postmeta records, incorrect post types, and trashed products. These issues can cause translation equalization to fail.', 'easycms-wp' ) ?></p>
        
        <div class='corrupted-data-container'>
            <div class='corrupted-loading' style='display: none;'>
                <p><strong><?php _e( 'Scanning for corrupted data...', 'easycms-wp' ) ?></strong></p>
                <div class='spinner is-active'></div>
            </div>
            
            <div class='corrupted-content' id='corrupted_content' style='display: none;'>
                <!-- Corrupted data will be displayed here -->
            </div>
        </div>
        
        <div class='corrupted-actions'>
            <button type='button' id='scan_corrupted_data' class='button button-secondary'>
                <?php _e( 'Scan for Corrupted Data', 'easycms-wp' ) ?>
            </button>
            
            <div class='corrupted-cleanup-actions' id='corrupted_cleanup_actions' style='display: none;'>
                <button type='button' id='dry_run_corrupted_cleanup' class='button'>
                    <?php _e( 'Preview Cleanup', 'easycms-wp' ) ?>
                </button>
                <button type='button' id='actual_corrupted_cleanup' class='button button-primary'>
                    <?php _e( 'Clean Up Corrupted Data', 'easycms-wp' ) ?>
                </button>
            </div>
        </div>
        
        <div class='corrupted-progress' id='corrupted_progress' style='display: none;'>
            <p><strong><?php _e( 'Processing cleanup...', 'easycms-wp' ) ?></strong></p>
            <div class='spinner is-active'></div>
            <div class='corrupted-message' id='corrupted_message'></div>
        </div>
        
        <div class='corrupted-results' id='corrupted_results' style='display: none;'>
            <!-- Results will be displayed here -->
        </div>
    </div>

    <div class='stale-pids-section'>
        <h3><?php _e( 'Stale PID Cleanup', 'easycms-wp' ) ?></h3>
        <p class='description'><?php _e( 'Find and clean up PIDs that no longer exist in the database but are still being counted in translation statistics. These stale PIDs cause translation equalization to fail even when statistics show 0 missing translations.', 'easycms-wp' ) ?></p>
        
        <div class='stale-pids-container'>
            <div class='stale-loading' style='display: none;'>
                <p><strong><?php _e( 'Scanning for stale PIDs...', 'easycms-wp' ) ?></strong></p>
                <div class='spinner is-active'></div>
            </div>
            
            <div class='stale-content' id='stale_content' style='display: none;'>
                <!-- Stale PIDs will be displayed here -->
            </div>
        </div>
        
        <div class='stale-actions'>
            <button type='button' id='scan_stale_pids' class='button button-secondary'>
                <?php _e( 'Scan for Stale PIDs', 'easycms-wp' ) ?>
            </button>
            
            <div class='stale-cleanup-actions' id='stale_cleanup_actions' style='display: none;'>
                <button type='button' id='dry_run_stale_cleanup' class='button'>
                    <?php _e( 'Preview Cleanup', 'easycms-wp' ) ?>
                </button>
                <button type='button' id='actual_stale_cleanup' class='button button-primary'>
                    <?php _e( 'Clean Up Stale PIDs', 'easycms-wp' ) ?>
                </button>
            </div>
        </div>
        
        <div class='stale-progress' id='stale_progress' style='display: none;'>
            <p><strong><?php _e( 'Processing cleanup...', 'easycms-wp' ) ?></strong></p>
            <div class='spinner is-active'></div>
            <div class='stale-message' id='stale_message'></div>
        </div>
        
        <div class='stale-results' id='stale_results' style='display: none;'>
            <!-- Results will be displayed here -->
        </div>
    </div>

    <style>
        .inventory-settings {
            max-width: 800px;
        }

        .publish-all-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
        }

        .draft-count {
            font-size: 18px;
            font-weight: bold;
            color: #d63638;
            margin-right: 10px;
        }

        .form-table td:first-child {
            width: 300px;
        }

        .form-table td:last-child {
            vertical-align: middle;
        }

        .translation-equalizer-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
        }

        .translation-stats-container {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .stats-loading {
            text-align: center;
            padding: 20px;
        }

        .spinner {
            float: none;
            margin: 10px auto;
        }

        .translation-stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .translation-stats-table th,
        .translation-stats-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .translation-stats-table th {
            background: #f5f5f5;
            font-weight: bold;
        }

        .translation-stats-table .count {
            font-weight: bold;
            text-align: center;
        }

        .translation-stats-table .missing {
            color: #d63638;
            font-weight: bold;
            text-align: center;
        }

        .translation-stats-table .missing.zero {
            color: #46b450;
        }

        .translation-stats-table .unsynced {
            color: #d63638;
            font-weight: bold;
            text-align: center;
        }

        .translation-stats-table .unsynced.zero {
            color: #46b450;
        }

        .translation-stats-table .actions {
            text-align: center;
        }

        .translation-stats-table .actions .button {
            margin: 0 2px;
        }

        .translation-actions {
            margin: 15px 0;
            padding: 15px;
            background: #f0f6fc;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }

        .equalize-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .equalize-progress {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 15px 0;
        }

        .progress-message {
            margin-top: 10px;
            font-style: italic;
            color: #666;
        }

        .equalize-results {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .result-item {
            margin-bottom: 15px;
            padding: 10px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .result-item h4 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .result-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
        }

        .result-stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .result-stat.success {
            color: #46b450;
        }

        .result-stat.failed {
            color: #d63638;
        }

        .result-errors {
            margin-top: 10px;
            padding: 10px;
            background: #fef7f7;
            border: 1px solid #d63638;
            border-radius: 4px;
            font-size: 12px;
            color: #d63638;
        }

        .button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .orphaned-pids-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
        }

        .orphaned-pids-container {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .orphaned-loading {
            text-align: center;
            padding: 20px;
        }

        .orphaned-content {
            margin-top: 10px;
        }

        .orphaned-summary {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .orphaned-count {
            font-size: 18px;
            font-weight: bold;
            color: #d63638;
        }

        .orphaned-details {
            margin-top: 15px;
            max-height: 300px;
            overflow-y: auto;
        }

        .orphaned-pid-item {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }

        .orphaned-pid-item:last-child {
            border-bottom: none;
        }

        .orphaned-actions {
            margin: 15px 0;
            padding: 15px;
            background: #f0f6fc;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }

        .cleanup-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .cleanup-progress {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 15px 0;
        }

        .cleanup-message {
            margin-top: 10px;
            font-style: italic;
            color: #666;
        }

        .cleanup-results {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .cleanup-summary {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .cleanup-stat {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }

        .cleanup-stat.success {
            color: #46b450;
        }

        .cleanup-stat.error {
            color: #d63638;
        }

        .corrupted-data-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
        }

        .corrupted-data-container {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .corrupted-loading {
            text-align: center;
            padding: 20px;
        }

        .corrupted-content {
            margin-top: 10px;
        }

        .corrupted-summary {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .corrupted-count {
            font-size: 18px;
            font-weight: bold;
            color: #d63638;
        }

        .corrupted-details {
            margin-top: 15px;
            max-height: 300px;
            overflow-y: auto;
        }

        .corrupted-item {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }

        .corrupted-item:last-child {
            border-bottom: none;
        }

        .corrupted-actions {
            margin: 15px 0;
            padding: 15px;
            background: #f0f6fc;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }

        .corrupted-cleanup-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .corrupted-progress {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 15px 0;
        }

        .corrupted-message {
            margin-top: 10px;
            font-style: italic;
            color: #666;
        }

        .corrupted-results {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .corrupted-cleanup-summary {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .corrupted-cleanup-stat {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }

        .corrupted-cleanup-stat.success {
            color: #46b450;
        }

        .corrupted-cleanup-stat.error {
            color: #d63638;
        }

        .stale-pids-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
        }

        .stale-pids-container {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .stale-loading {
            text-align: center;
            padding: 20px;
        }

        .stale-content {
            margin-top: 10px;
        }

        .stale-summary {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .stale-count {
            font-size: 18px;
            font-weight: bold;
            color: #d63638;
        }

        .stale-details {
            margin-top: 15px;
            max-height: 300px;
            overflow-y: auto;
        }

        .stale-pid-item {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }

        .stale-pid-item:last-child {
            border-bottom: none;
        }

        .stale-actions {
            margin: 15px 0;
            padding: 15px;
            background: #f0f6fc;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
        }

        .stale-cleanup-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .stale-progress {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 15px 0;
        }

        .stale-message {
            margin-top: 10px;
            font-style: italic;
            color: #666;
        }

        .stale-results {
            margin: 15px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .stale-cleanup-summary {
            background: #fff;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .stale-cleanup-stat {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }

        .stale-cleanup-stat.success {
            color: #46b450;
        }

        .stale-cleanup-stat.error {
            color: #d63638;
        }
        
        .unsynced-products-section {
            margin-top: 20px;
            padding: 15px;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        
        .unsynced-products-section h4 {
            margin: 0 0 10px 0;
            color: #d63638;
        }
        
        .unsynced-products-list table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .unsynced-products-list th,
        .unsynced-products-list td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .unsynced-products-list th {
            background: #f5f5f5;
            font-weight: bold;
        }
        
        .unsynced-products-list .actions {
            text-align: center;
        }
        
        .unsynced-products-list .actions .button {
            margin: 0 2px;
        }
        
        .unsynced-products-list .button-danger {
            background: #d63638;
            border-color: #d63638;
            color: white;
        }
        
        .unsynced-products-list .button-danger:hover {
            background: #a00;
            border-color: #a00;
        }
        
        .product-search-link {
            color: #0073aa !important;
            text-decoration: none !important;
            font-weight: 500;
        }
        
        .product-search-link:hover {
            color: #005a87 !important;
            text-decoration: underline !important;
        }
        
        .product-search-link:before {
            content: "🔍 ";
            font-size: 12px;
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Ensure jQuery is properly available
        if (typeof $ === 'undefined') {
            $ = jQuery;
        }
        
        // Debug: Verify script is loading
        console.log('EasyCMS WP: Inventory script loaded');
        console.log('EasyCMS WP: jQuery version:', $.fn.jquery);
        console.log('EasyCMS WP: EASYCMS_WP object:', typeof EASYCMS_WP);
        
        // Debug: Check if toggle element exists
        var toggleElement = $('#publish_all_products');
        console.log('EasyCMS WP: Toggle element found:', toggleElement.length > 0);
        console.log('EasyCMS WP: Toggle element:', toggleElement);

        // Handle checkbox change
        $(document).on('change', '#publish_all_products', function() {
            // Get the checkbox state
            var isEnabled = $(this).is(':checked');
            
            // Disable the checkbox during AJAX request to prevent multiple clicks
            $(this).prop('disabled', true);
            
            // Add debug logging
            console.log('Checkbox changed to: ' + (isEnabled ? 'checked' : 'unchecked'));
            console.log('Sending AJAX request with enabled=' + isEnabled);
            
            // Use the correct AJAX action
            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'toggle_publish_all_products',
                    enabled: isEnabled,
                    nonce: EASYCMS_WP.nonce
                },
                success: function(response) {
                    console.log('AJAX response received:', response);
                    console.log('Response data.enabled:', response.data.enabled);
                    console.log('Response data.was_enabled:', response.data.was_enabled);
                    console.log('Response data.message:', response.data.message);
                    
                    // Show debug info if available
                    if (response.data.debug_info) {
                        console.log('Debug info:', response.data.debug_info);
                        console.log('Update result:', response.data.update_result);
                    }
                    
                    if (response.success) {
                        // The server successfully processed our request
                        // Update the toggle to match the server's actual state
                        var serverState = response.data.enabled;
                        var userRequested = response.data.requested;
                        var wasEnabled = response.data.was_enabled;
                        var databaseUpdateRun = response.data.database_update_run;
                        
                        console.log('DEBUG: User requested:', userRequested, 'Server returned:', serverState, 'DB update run:', databaseUpdateRun);
                        
                        // Check if there's a mismatch between requested and actual state
                        if (userRequested !== serverState) {
                            console.error('WARNING: Mismatch detected! User requested:', userRequested, 'but server returned:', serverState);
                            console.error('This indicates the option was not saved correctly.');
                        }
                        
                        // Use the actual server state (what was actually saved to database)
                        $('#publish_all_products').prop('checked', serverState);
                        
                        // Show the message from the server
                        alert(response.data.message);
                        
                        // If database update was run, refresh the draft count after a short delay
                        if (databaseUpdateRun) {
                            setTimeout(function() {
                                refreshDraftCount();
                            }, 1000);
                        }
                        
                        // Log for debugging
                        console.log('Setting updated successfully. Previous state: ' + wasEnabled + ', User requested: ' + userRequested + ', Server returned: ' + serverState + ', DB Update: ' + databaseUpdateRun);
                    } else {
                        // Server returned an error - this means the setting failed to save
                        alert('<?php _e( "Error updating setting: ", "easycms-wp" ) ?>' + (response.data || 'Unknown error') + '<?php _e( " Please try again.", "easycms-wp" ) ?>');
                        // Revert the toggle to original state since the save failed
                        $('#publish_all_products').prop('checked', !isEnabled);
                        console.log('Update failed: ' + (response.data || 'Unknown error'));
                        console.log('Reverted toggle to original state: ' + (!isEnabled ? 'true' : 'false'));
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX error occurred:', status, error);
                    console.log('Response text:', xhr.responseText);
                    alert('<?php _e( "AJAX error occurred. Please try again.", "easycms-wp" ) ?>');
                    // Revert the toggle if AJAX failed
                    $('#publish_all_products').prop('checked', !isEnabled);
                },
                complete: function() {
                    // Re-enable the toggle after request completes
                    $('#publish_all_products').prop('disabled', false);
                }
            });
        });

        // Handle refresh count button
        $('#refresh_count').on('click', function() {
            refreshDraftCount();
        });
        
        function refreshDraftCount() {
            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_draft_products_count',
                    nonce: EASYCMS_WP.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#draft_products_count').text(response.data.count);
                    }
                }
            });
        }

        // Translation Equalizer JavaScript
        function loadTranslationStatistics() {
            $('.stats-loading').show();
            $('.stats-content').hide();
            $('#equalize_actions').hide();
            $('#equalize_results').hide();

            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_translation_statistics',
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 300000, // 5 minutes timeout
                success: function(response) {
                    console.log('AJAX Success - Response:', response);
                    console.log('Response success:', response.success);
                    console.log('Response data:', response.data);
                    
                    if (response.success) {
                        console.log('Displaying statistics:', response.data);
                        displayTranslationStatistics(response.data);
                    } else {
                        console.log('AJAX returned error:', response.data);
                        alert('<?php _e( "Error loading translation statistics: ", "easycms-wp" ) ?>' + (response.data || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error - Status:', status);
                    console.log('AJAX Error - Error:', error);
                    console.log('AJAX Error - Response text:', xhr.responseText);
                    console.log('AJAX Error - Response JSON:', xhr.responseJSON);
                    
                    var errorMessage = '<?php _e( "Error loading translation statistics. Please try again.", "easycms-wp" ) ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = '<?php _e( "Error: ", "easycms-wp" ) ?>' + xhr.responseJSON.data;
                    }
                    alert(errorMessage);
                },
                complete: function() {
                    $('.stats-loading').hide();
                }
            });
        }

        function displayTranslationStatistics(statistics) {
            var html = '<table class="translation-stats-table">';
            html += '<thead><tr>';
            html += '<th><?php _e( "Language", "easycms-wp" ) ?></th>';
            html += '<th><?php _e( "Total Products", "easycms-wp" ) ?></th>';
            html += '<th><?php _e( "Synced Products", "easycms-wp" ) ?></th>';
            html += '<th><?php _e( "Unsynced Products", "easycms-wp" ) ?></th>';
            html += '<th><?php _e( "Missing", "easycms-wp" ) ?></th>';
            html += '<th><?php _e( "Actions", "easycms-wp" ) ?></th>';
            html += '</tr></thead><tbody>';

            var hasMissingTranslations = false;
            var languageOptions = '';
            var hasUnsyncedProducts = false;
            var unsyncedProductsHtml = '';

            for (var langCode in statistics) {
                var stats = statistics[langCode];
                var missingClass = stats.missing_count > 0 ? 'missing' : 'missing zero';
                var unsyncedClass = stats.unsynced_count > 0 ? 'unsynced' : 'unsynced zero';
                
                html += '<tr>';
                html += '<td>' + stats.language_name + '</td>';
                html += '<td class="count">' + stats.total_count + '</td>';
                html += '<td class="count">' + stats.synced_count + '</td>';
                html += '<td class="' + unsyncedClass + '">' + stats.unsynced_count + '</td>';
                html += '<td class="' + missingClass + '">' + stats.missing_count + '</td>';
                html += '<td class="actions">';
                
                if (stats.unsynced_count > 0) {
                    html += '<button type="button" class="button button-small delete-unsynced" data-language="' + langCode + '"><?php _e( "Delete Unsynced", "easycms-wp" ) ?></button>';
                    hasUnsyncedProducts = true;
                }
                
                html += '</td>';
                html += '</tr>';

                if (stats.missing_count > 0) {
                    hasMissingTranslations = true;
                    languageOptions += '<option value="' + langCode + '">' + stats.language_name + ' (' + stats.missing_count + ' missing)</option>';
                }
                
                // Display unsynced products for main language (Finnish) - Show if there are unsynced products OR if there's a count discrepancy
                var countDiscrepancy = (stats.total_count > stats.synced_count);
                if (langCode === 'fi' && (stats.unsynced_count > 0 || countDiscrepancy) && stats.unsynced_details) {
                    unsyncedProductsHtml += '<div class="unsynced-products-section">';
                    unsyncedProductsHtml += '<h4><?php _e( "Unsynced Products (No PID)", "easycms-wp" ) ?></h4>';
                    unsyncedProductsHtml += '<p><?php _e( "These products exist in Finnish but don\'t have a PID from CMS:", "easycms-wp" ) ?></p>';
                    
                    if (stats.unsynced_details.length === 0) {
                        unsyncedProductsHtml += '<p style="color: #d63638;"><strong><?php _e( "Count discrepancy detected:", "easycms-wp" ) ?></strong> ';
                        unsyncedProductsHtml += '<?php _e( "Total products:", "easycms-wp" ) ?> ' + stats.total_count + ', ';
                        unsyncedProductsHtml += '<?php _e( "Synced products:", "easycms-wp" ) ?> ' + stats.synced_count + ', ';
                        unsyncedProductsHtml += '<?php _e( "Expected unsynced:", "easycms-wp" ) ?> ' + (stats.total_count - stats.synced_count);
                        unsyncedProductsHtml += '</p>';
                        unsyncedProductsHtml += '<p><?php _e( "The system is having trouble detecting products without valid PIDs. Please check the product data manually.", "easycms-wp" ) ?></p>';
                    } else {
                        unsyncedProductsHtml += '<div class="unsynced-products-list">';
                        unsyncedProductsHtml += '<table class="widefat">';
                        unsyncedProductsHtml += '<thead><tr>';
                        unsyncedProductsHtml += '<th><?php _e( "ID", "easycms-wp" ) ?></th>';
                        unsyncedProductsHtml += '<th><?php _e( "Product Name", "easycms-wp" ) ?></th>';
                        unsyncedProductsHtml += '<th><?php _e( "SKU", "easycms-wp" ) ?></th>';
                        unsyncedProductsHtml += '<th><?php _e( "Actions", "easycms-wp" ) ?></th>';
                        unsyncedProductsHtml += '</tr></thead><tbody>';
                        
                        for (var i = 0; i < stats.unsynced_details.length; i++) {
                            var product = stats.unsynced_details[i];
                            unsyncedProductsHtml += '<tr>';
                            unsyncedProductsHtml += '<td>' + product.id + '</td>';
                            unsyncedProductsHtml += '<td><a href="' + product.admin_search_link + '" target="_blank" title="<?php _e('View in WooCommerce products with search pre-filled', 'easycms-wp') ?>" class="product-search-link">' + product.title + '</a></td>';
                            unsyncedProductsHtml += '<td>' + product.sku + '</td>';
                            unsyncedProductsHtml += '<td class="actions">';
                            unsyncedProductsHtml += '<a href="' + product.edit_link + '" class="button button-small" target="_blank"><?php _e( "Edit", "easycms-wp" ) ?></a>';
                            unsyncedProductsHtml += '<a href="' + product.view_link + '" class="button button-small" target="_blank"><?php _e( "View", "easycms-wp" ) ?></a>';
                            unsyncedProductsHtml += '<button type="button" class="button button-small button-danger delete-single-unsynced" data-product-id="' + product.id + '" data-product-name="' + product.title + '"><?php _e( "Delete", "easycms-wp" ) ?></button>';
                            unsyncedProductsHtml += '</td>';
                            unsyncedProductsHtml += '</tr>';
                        }
                        
                        unsyncedProductsHtml += '</tbody></table>';
                        unsyncedProductsHtml += '</div>';
                    }
                    
                    unsyncedProductsHtml += '</div>';
                }
            }

            html += '</tbody></table>';

            $('.stats-content').html(html);
            $('.stats-content').show();
            
            // Display unsynced products container if there are any
            if (unsyncedProductsHtml) {
                $('#unsynced_products_container').html(unsyncedProductsHtml);
                $('#unsynced_products_container').show();
            } else {
                $('#unsynced_products_container').hide();
            }

            // Update language select options
            if (hasMissingTranslations) {
                $('#target_language').html('<option value="all"><?php _e( "All Languages", "easycms-wp" ) ?></option>' + languageOptions);
                $('#equalize_actions').show();
            } else {
                $('#equalize_actions').hide();
            }
        }

        function equalizeTranslations() {
            var targetLanguage = $('#target_language').val();
            
            $('#equalize_progress').show();
            $('#equalize_actions').hide();
            $('#equalize_results').hide();
            $('#progress_message').html('<?php _e( "Starting translation equalization...", "easycms-wp" ) ?>');

            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'equalize_product_translations',
                    target_language: targetLanguage,
                    nonce: EASYCMS_WP.nonce
                },
                success: function(response) {
                    if (response.success) {
                        displayEqualizeResults(response.data.results);
                        // Refresh statistics after equalization
                        setTimeout(function() {
                            loadTranslationStatistics();
                        }, 2000);
                    } else {
                        alert('<?php _e( "Error equalizing translations. Please try again.", "easycms-wp" ) ?>');
                        $('#equalize_progress').hide();
                        $('#equalize_actions').show();
                    }
                },
                error: function() {
                    alert('<?php _e( "AJAX error occurred. Please try again.", "easycms-wp" ) ?>');
                    $('#equalize_progress').hide();
                    $('#equalize_actions').show();
                }
            });
        }

        function displayEqualizeResults(results) {
            var html = '<h4><?php _e( "Translation Equalization Results", "easycms-wp" ) ?></h4>';
            
            for (var langCode in results) {
                var result = results[langCode];
                html += '<div class="result-item">';
                html += '<h4>' + result.language_name + '</h4>';
                
                html += '<div class="result-stats">';
                html += '<div class="result-stat"><span><?php _e( "Processed:", "easycms-wp" ) ?></span> <strong>' + result.processed + '</strong></div>';
                html += '<div class="result-stat success"><span><?php _e( "Success:", "easycms-wp" ) ?></span> <strong>' + result.success + '</strong></div>';
                html += '<div class="result-stat failed"><span><?php _e( "Failed:", "easycms-wp" ) ?></span> <strong>' + result.failed + '</strong></div>';
                html += '</div>';
                
                if (result.errors && result.errors.length > 0) {
                    html += '<div class="result-errors">';
                    html += '<strong><?php _e( "Sample Errors:", "easycms-wp" ) ?></strong><br>';
                    html += result.errors.join('<br>');
                    html += '</div>';
                }
                
                html += '</div>';
            }

            $('#equalize_results').html(html);
            $('#equalize_progress').hide();
            $('#equalize_results').show();
        }

        // Event handlers
        $('#refresh_translation_stats').on('click', function() {
            loadTranslationStatistics();
        });

        $('#equalize_translations').on('click', function() {
            if (confirm('<?php _e( "This will create missing product translations. Continue?", "easycms-wp" ) ?>')) {
                equalizeTranslations();
            }
        });

        // Delete unsynced products functionality
        $(document).on('click', '.delete-unsynced', function() {
            var language = $(this).data('language');
            var languageName = $(this).closest('tr').find('td:first').text();
            
            if (confirm('<?php _e( "WARNING: This will permanently delete all unsynced products in", "easycms-wp" ) ?> ' + languageName + '. <?php _e( "This action cannot be undone. Continue?", "easycms-wp" ) ?>')) {
                deleteUnsyncedProducts(language, languageName);
            }
        });

        function deleteUnsyncedProducts(language, languageName) {
            var $button = $('.delete-unsynced[data-language="' + language + '"]');
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('<?php _e( "Deleting...", "easycms-wp" ) ?>');

            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_unsynced_products',
                    language_code: language,
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 300000, // 5 minutes timeout
                success: function(response) {
                    console.log('Delete unsynced products response:', response);
                    console.log('Response data:', response.data);
                    
                    if (response.success) {
                        // The backend sends results directly in response.data
                        var results = response.data;
                        console.log('Using results:', results);
                        
                        if (!results) {
                            console.error('No results found in response:', response);
                            alert('<?php _e( "Error: Invalid response structure from server", "easycms-wp" ) ?>');
                            return;
                        }
                        
                        var message = '<?php _e( "Successfully deleted", "easycms-wp" ) ?> ' + (results.products_deleted || 0) + ' <?php _e( "unsynced products in", "easycms-wp" ) ?> ' + languageName + '.';
                        
                        if (results.errors > 0) {
                            message += '\n<?php _e( "Errors encountered:", "easycms-wp" ) ?> ' + results.errors;
                        }
                        
                        alert(message);
                        
                        // Refresh statistics after deletion
                        setTimeout(function() {
                            loadTranslationStatistics();
                        }, 1000);
                    } else {
                        alert('<?php _e( "Error deleting unsynced products:", "easycms-wp" ) ?> ' + (response.data || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Delete unsynced products error:', status, error);
                    var errorMessage = '<?php _e( "Error deleting unsynced products. Please try again.", "easycms-wp" ) ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = '<?php _e( "Error:", "easycms-wp" ) ?> ' + xhr.responseJSON.data;
                    }
                    alert(errorMessage);
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
    
        // Delete single unsynced product functionality
        $(document).on('click', '.delete-single-unsynced', function() {
            var productId = $(this).data('product-id');
            var productName = $(this).data('product-name');
            
            if (confirm('<?php _e( "WARNING: This will permanently delete product", "easycms-wp" ) ?> "' + productName + '". <?php _e( "This action cannot be undone. Continue?", "easycms-wp" ) ?>')) {
                deleteSingleProduct(productId, productName);
            }
        });
    
        function deleteSingleProduct(productId, productName) {
            var $button = $('.delete-single-unsynced[data-product-id="' + productId + '"]');
            var originalText = $button.text();
            
            $button.prop('disabled', true).text('<?php _e( "Deleting...", "easycms-wp" ) ?>');

            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'delete_single_product',
                    product_id: productId,
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 30000, // 30 seconds timeout
                success: function(response) {
                    console.log('Delete single product response:', response);
                    console.log('Response data:', response.data);
                    
                    if (response.success) {
                        var message = '<?php _e( "Successfully deleted product:", "easycms-wp" ) ?> "' + productName + '".';
                        alert(message);
                        
                        // Refresh statistics after deletion
                        setTimeout(function() {
                            loadTranslationStatistics();
                        }, 1000);
                    } else {
                        alert('<?php _e( "Error deleting product:", "easycms-wp" ) ?> ' + (response.data || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Delete single product error:', status, error);
                    var errorMessage = '<?php _e( "Error deleting product. Please try again.", "easycms-wp" ) ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = '<?php _e( "Error:", "easycms-wp" ) ?> ' + xhr.responseJSON.data;
                    }
                    alert(errorMessage);
                },
                complete: function() {
                    $button.prop('disabled', false).text('<?php _e( "Delete", "easycms-wp" ) ?>');
                }
            });
        }

        // Orphaned PIDs JavaScript
        function scanOrphanedPids() {
            $('.orphaned-loading').show();
            $('.orphaned-content').hide();
            $('#cleanup_actions').hide();
            $('#cleanup_results').hide();

            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_orphaned_pids',
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 120000, // 2 minutes timeout
                success: function(response) {
                    if (response.success) {
                        displayOrphanedPids(response.data.orphaned_pids);
                    } else {
                        alert('<?php _e( "Error scanning for orphaned products: ", "easycms-wp" ) ?>' + (response.data || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = '<?php _e( "Error scanning for orphaned products. Please try again.", "easycms-wp" ) ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = '<?php _e( "Error: ", "easycms-wp" ) ?>' + xhr.responseJSON.data;
                    }
                    alert(errorMessage);
                },
                complete: function() {
                    $('.orphaned-loading').hide();
                }
            });
        }

        function displayOrphanedPids(orphanedPids) {
            var html = '';
            
            if (Object.keys(orphanedPids).length === 0) {
                html = '<div class="orphaned-summary">';
                html += '<p><strong><?php _e( "Good news! No orphaned products found.", "easycms-wp" ) ?></strong></p>';
                html += '<p><?php _e( "All products exist in the main language (Finnish).", "easycms-wp" ) ?></p>';
                html += '</div>';
            } else {
                html = '<div class="orphaned-summary">';
                html += '<p><strong><?php _e( "Orphaned Products Found", "easycms-wp" ) ?></strong></p>';
                html += '<p><span class="orphaned-count">' + Object.keys(orphanedPids).length + '</span> <?php _e( "products exist in non-main languages but not in Finnish.", "easycms-wp" ) ?></p>';
                html += '<p><?php _e( "These products will cause translation equalization to fail.", "easycms-wp" ) ?></p>';
                html += '</div>';
                
                html += '<div class="orphaned-details">';
                html += '<h4><?php _e( "Orphaned Product Details:", "easycms-wp" ) ?></h4>';
                for (var pid in orphanedPids) {
                    var languages = orphanedPids[pid];
                    html += '<div class="orphaned-pid-item">';
                    html += '<strong>PID ' + pid + '</strong>: <?php _e( "exists in", "easycms-wp" ) ?> ' + languages.join(', ');
                    html += '</div>';
                }
                html += '</div>';
                
                $('#cleanup_actions').show();
            }

            $('.orphaned-content').html(html);
            $('.orphaned-content').show();
        }

        function cleanupOrphanedPids(dryRun) {
            var confirmMessage = dryRun ?
                '<?php _e( "This will preview what orphaned products would be deleted. Continue?", "easycms-wp" ) ?>' :
                '<?php _e( "WARNING: This will permanently delete orphaned products that don\\'t exist in the main language. This action cannot be undone. Continue?", "easycms-wp" ) ?>';
                
            if (!confirm(confirmMessage)) {
                return;
            }
            
            $('#cleanup_progress').show();
            $('#cleanup_actions').hide();
            $('#cleanup_results').hide();
            $('#cleanup_message').html(dryRun ?
                '<?php _e( "Previewing cleanup...", "easycms-wp" ) ?>' :
                '<?php _e( "Cleaning up orphaned products...", "easycms-wp" ) ?>');

            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'cleanup_orphaned_pids',
                    dry_run: dryRun,
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 300000, // 5 minutes timeout for cleanup
                success: function(response) {
                    if (response.success) {
                        displayCleanupResults(response.data.results, dryRun);
                        if (!dryRun && response.data.results.products_deleted > 0) {
                            // Refresh statistics after cleanup
                            setTimeout(function() {
                                loadTranslationStatistics();
                                scanOrphanedPids();
                            }, 2000);
                        }
                    } else {
                        alert('<?php _e( "Error during cleanup: ", "easycms-wp" ) ?>' + (response.data || 'Unknown error'));
                        $('#cleanup_progress').hide();
                        $('#cleanup_actions').show();
                    }
                },
                error: function() {
                    alert('<?php _e( "AJAX error occurred. Please try again.", "easycms-wp" ) ?>');
                    $('#cleanup_progress').hide();
                    $('#cleanup_actions').show();
                }
            });
        }

        function displayCleanupResults(results, dryRun) {
            var html = '<h4>' + (dryRun ?
                '<?php _e( "Cleanup Preview Results", "easycms-wp" ) ?>' :
                '<?php _e( "Cleanup Results", "easycms-wp" ) ?>') + '</h4>';
            
            html += '<div class="cleanup-summary">';
            html += '<div class="cleanup-stat"><?php _e( "Orphaned PIDs found:", "easycms-wp" ) ?> <strong>' + results.orphaned_count + '</strong></div>';
            html += '<div class="cleanup-stat"><?php _e( "Products to delete:", "easycms-wp" ) ?> <strong>' + results.products_to_delete + '</strong></div>';
            
            if (!dryRun) {
                html += '<div class="cleanup-stat success"><?php _e( "Products deleted:", "easycms-wp" ) ?> <strong>' + results.products_deleted + '</strong></div>';
                if (results.errors > 0) {
                    html += '<div class="cleanup-stat error"><?php _e( "Errors:", "easycms-wp" ) ?> <strong>' + results.errors + '</strong></div>';
                }
            }
            html += '</div>';

            if (dryRun) {
                html += '<p><strong><?php _e( "This was a preview. No products were actually deleted.", "easycms-wp" ) ?></strong></p>';
                html += '<p><?php _e( "To actually delete these orphaned products, click the \"Clean Up Orphaned Products\" button.", "easycms-wp" ) ?></p>';
            }

            $('#cleanup_results').html(html);
            $('#cleanup_progress').hide();
            $('#cleanup_results').show();
            
            // Always show cleanup actions after results are displayed
            $('#cleanup_actions').show();
        }

        // Event handlers
        $('#scan_orphaned_pids').on('click', function() {
            scanOrphanedPids();
        });

        $('#dry_run_cleanup').on('click', function() {
            cleanupOrphanedPids(true);
        });

        $('#actual_cleanup').on('click', function() {
            cleanupOrphanedPids(false);
        });

        // Corrupted Data Cleanup JavaScript
        function scanCorruptedData() {
            $('.corrupted-loading').show();
            $('.corrupted-content').hide();
            $('#corrupted_cleanup_actions').hide();
            $('#corrupted_results').hide();

            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'cleanup_corrupted_data',
                    dry_run: true, // Always do dry run first for scanning
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 120000, // 2 minutes timeout
                success: function(response) {
                    if (response.success) {
                        displayCorruptedDataResults(response.data.results, true);
                    } else {
                        alert('<?php _e( "Error scanning for corrupted data: ", "easycms-wp" ) ?>' + (response.data || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = '<?php _e( "Error scanning for corrupted data. Please try again.", "easycms-wp" ) ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = '<?php _e( "Error: ", "easycms-wp" ) ?>' + xhr.responseJSON.data;
                    }
                    alert(errorMessage);
                },
                complete: function() {
                    $('.corrupted-loading').hide();
                }
            });
        }

        function displayCorruptedDataResults(results, dryRun) {
            var html = '';
            var totalIssues = results.orphaned_meta_cleaned + results.incorrect_post_types_cleaned +
                             results.trashed_products_cleaned + results.wpml_orphans_cleaned;
            
            if (totalIssues === 0) {
                html = '<div class="corrupted-summary">';
                html += '<p><strong><?php _e( "Good news! No corrupted data found.", "easycms-wp" ) ?></strong></p>';
                html += '<p><?php _e( "All product data appears to be clean and consistent.", "easycms-wp" ) ?></p>';
                html += '</div>';
            } else {
                html = '<div class="corrupted-summary">';
                html += '<p><strong><?php _e( "Corrupted Data Found", "easycms-wp" ) ?></strong></p>';
                html += '<p><span class="corrupted-count">' + totalIssues + '</span> <?php _e( "data issues found that may cause translation problems.", "easycms-wp" ) ?></p>';
                html += '</div>';
                
                html += '<div class="corrupted-details">';
                html += '<h4><?php _e( "Issues Found:", "easycms-wp" ) ?></h4>';
                
                if (results.orphaned_meta_cleaned > 0) {
                    html += '<div class="corrupted-item">';
                    html += '<strong><?php _e( "Orphaned postmeta records:", "easycms-wp" ) ?></strong> ' + results.orphaned_meta_cleaned;
                    html += ' <small>(<?php _e( "easycms_pid records without corresponding posts", "easycms-wp" ) ?>)</small>';
                    html += '</div>';
                }
                
                if (results.incorrect_post_types_cleaned > 0) {
                    html += '<div class="corrupted-item">';
                    html += '<strong><?php _e( "Incorrect post types:", "easycms-wp" ) ?></strong> ' + results.incorrect_post_types_cleaned;
                    html += ' <small>(<?php _e( "easycms_pid on non-product posts", "easycms-wp" ) ?>)</small>';
                    html += '</div>';
                }
                
                if (results.trashed_products_cleaned > 0) {
                    html += '<div class="corrupted-item">';
                    html += '<strong><?php _e( "Trashed products:", "easycms-wp" ) ?></strong> ' + results.trashed_products_cleaned;
                    html += ' <small>(<?php _e( "products in trash with easycms_pid", "easycms-wp" ) ?>)</small>';
                    html += '</div>';
                }
                
                if (results.wpml_orphans_cleaned > 0) {
                    html += '<div class="corrupted-item">';
                    html += '<strong><?php _e( "Orphaned WPML translations:", "easycms-wp" ) ?></strong> ' + results.wpml_orphans_cleaned;
                    html += ' <small>(<?php _e( "translation records without corresponding posts", "easycms-wp" ) ?>)</small>';
                    html += '</div>';
                }
                
                html += '</div>';
                
                $('#corrupted_cleanup_actions').show();
            }

            $('.corrupted-content').html(html);
            $('.corrupted-content').show();
        }

        function cleanupCorruptedData(dryRun) {
            var confirmMessage = dryRun ?
                '<?php _e( "This will preview what corrupted data would be cleaned up. Continue?", "easycms-wp" ) ?>' :
                '<?php _e( "WARNING: This will permanently clean up corrupted product data. This action cannot be undone. Continue?", "easycms-wp" ) ?>';
                
            if (!confirm(confirmMessage)) {
                return;
            }
            
            $('#corrupted_progress').show();
            $('#corrupted_cleanup_actions').hide();
            $('#corrupted_results').hide();
            $('#corrupted_message').html(dryRun ?
                '<?php _e( "Previewing cleanup...", "easycms-wp" ) ?>' :
                '<?php _e( "Cleaning up corrupted data...", "easycms-wp" ) ?>');

            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'cleanup_corrupted_data',
                    dry_run: dryRun,
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 300000, // 5 minutes timeout for cleanup
                success: function(response) {
                    if (response.success) {
                        displayCorruptedCleanupResults(response.data.results, dryRun);
                        if (!dryRun) {
                            // Refresh statistics after cleanup
                            setTimeout(function() {
                                loadTranslationStatistics();
                                scanOrphanedPids();
                            }, 2000);
                        }
                    } else {
                        alert('<?php _e( "Error during cleanup: ", "easycms-wp" ) ?>' + (response.data || 'Unknown error'));
                        $('#corrupted_progress').hide();
                        $('#corrupted_cleanup_actions').show();
                    }
                },
                error: function() {
                    alert('<?php _e( "AJAX error occurred. Please try again.", "easycms-wp" ) ?>');
                    $('#corrupted_progress').hide();
                    $('#corrupted_cleanup_actions').show();
                }
            });
        }

        function displayCorruptedCleanupResults(results, dryRun) {
            var html = '<h4>' + (dryRun ?
                '<?php _e( "Corrupted Data Cleanup Preview", "easycms-wp" ) ?>' :
                '<?php _e( "Corrupted Data Cleanup Results", "easycms-wp" ) ?>') + '</h4>';
            
            html += '<div class="corrupted-cleanup-summary">';
            
            if (results.orphaned_meta_cleaned > 0) {
                html += '<div class="corrupted-cleanup-stat"><?php _e( "Orphaned postmeta cleaned:", "easycms-wp" ) ?> <strong>' + results.orphaned_meta_cleaned + '</strong></div>';
            }
            
            if (results.incorrect_post_types_cleaned > 0) {
                html += '<div class="corrupted-cleanup-stat"><?php _e( "Incorrect post types cleaned:", "easycms-wp" ) ?> <strong>' + results.incorrect_post_types_cleaned + '</strong></div>';
            }
            
            if (results.trashed_products_cleaned > 0) {
                html += '<div class="corrupted-cleanup-stat"><?php _e( "Trashed products cleaned:", "easycms-wp" ) ?> <strong>' + results.trashed_products_cleaned + '</strong></div>';
            }
            
            if (results.wpml_orphans_cleaned > 0) {
                html += '<div class="corrupted-cleanup-stat"><?php _e( "WPML orphans cleaned:", "easycms-wp" ) ?> <strong>' + results.wpml_orphans_cleaned + '</strong></div>';
            }
            
            if (!dryRun && results.errors > 0) {
                html += '<div class="corrupted-cleanup-stat error"><?php _e( "Errors:", "easycms-wp" ) ?> <strong>' + results.errors + '</strong></div>';
            }
            html += '</div>';

            if (dryRun) {
                html += '<p><strong><?php _e( "This was a preview. No data was actually cleaned up.", "easycms-wp" ) ?></strong></p>';
                html += '<p><?php _e( "To actually clean up this corrupted data, click the \"Clean Up Corrupted Data\" button.", "easycms-wp" ) ?></p>';
            } else {
                var totalCleaned = results.orphaned_meta_cleaned + results.incorrect_post_types_cleaned +
                                  results.trashed_products_cleaned + results.wpml_orphans_cleaned;
                if (totalCleaned > 0) {
                    html += '<p><strong><?php _e( "Cleanup completed successfully!", "easycms-wp" ) ?></strong></p>';
                    html += '<p><?php _e( "Translation statistics and orphaned product scans have been refreshed.", "easycms-wp" ) ?></p>';
                } else {
                    html += '<p><strong><?php _e( "No issues were found that needed cleaning.", "easycms-wp" ) ?></strong></p>';
                }
            }

            $('#corrupted_results').html(html);
            $('#corrupted_progress').hide();
            $('#corrupted_results').show();
            
            // Always show cleanup actions after results are displayed
            $('#corrupted_cleanup_actions').show();
        }

        // Event handlers for corrupted data cleanup
        $('#scan_corrupted_data').on('click', function() {
            scanCorruptedData();
        });

        $('#dry_run_corrupted_cleanup').on('click', function() {
            cleanupCorruptedData(true);
        });

        $('#actual_corrupted_cleanup').on('click', function() {
            cleanupCorruptedData(false);
        });

        // Stale PIDs Cleanup JavaScript
        function scanStalePids() {
            $('.stale-loading').show();
            $('.stale-content').hide();
            $('#stale_cleanup_actions').hide();
            $('#stale_results').hide();

            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'cleanup_stale_pids',
                    dry_run: true, // Always do dry run first for scanning
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 120000, // 2 minutes timeout
                success: function(response) {
                    if (response.success) {
                        displayStalePidsResults(response.data.results, true);
                    } else {
                        alert('<?php _e( "Error scanning for stale PIDs: ", "easycms-wp" ) ?>' + (response.data || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = '<?php _e( "Error scanning for stale PIDs. Please try again.", "easycms-wp" ) ?>';
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = '<?php _e( "Error: ", "easycms-wp" ) ?>' + xhr.responseJSON.data;
                    }
                    alert(errorMessage);
                },
                complete: function() {
                    $('.stale-loading').hide();
                }
            });
        }

        function displayStalePidsResults(results, dryRun) {
            var html = '';
            
            if (results.stale_pids_cleaned === 0) {
                html = '<div class="stale-summary">';
                html += '<p><strong><?php _e( "Good news! No stale PIDs found.", "easycms-wp" ) ?></strong></p>';
                html += '<p><?php _e( "All PIDs in translation statistics exist in the database.", "easycms-wp" ) ?></p>';
                html += '</div>';
            } else {
                html = '<div class="stale-summary">';
                html += '<p><strong><?php _e( "Stale PIDs Found", "easycms-wp" ) ?></strong></p>';
                html += '<p><span class="stale-count">' + results.stale_pids_cleaned + '</span> <?php _e( "stale PIDs found that no longer exist in the database.", "easycms-wp" ) ?></p>';
                html += '<p><?php _e( "These stale PIDs are causing translation equalization to fail even when statistics show 0 missing translations.", "easycms-wp" ) ?></p>';
                html += '</div>';
                
                html += '<div class="stale-details">';
                html += '<h4><?php _e( "Stale PID Details:", "easycms-wp" ) ?></h4>';
                if (results.details && results.details.length > 0) {
                    for (var i = 0; i < Math.min(results.details.length, 10); i++) {
                        var detail = results.details[i];
                        html += '<div class="stale-pid-item">';
                        html += '<strong>PID ' + detail.pid + '</strong>: ' + detail.action;
                        if (detail.success) {
                            html += ' <span style="color: #46b450;">(<?php _e( "would be cleaned up", "easycms-wp" ) ?>)</span>';
                        }
                        html += '</div>';
                    }
                    if (results.details.length > 10) {
                        html += '<div class="stale-pid-item"><em><?php _e( "... and more", "easycms-wp" ) ?></em></div>';
                    }
                }
                html += '</div>';
                
                $('#stale_cleanup_actions').show();
            }

            $('.stale-content').html(html);
            $('.stale-content').show();
        }

        function cleanupStalePids(dryRun) {
            var confirmMessage = dryRun ?
                '<?php _e( "This will preview what stale PIDs would be cleaned up. Continue?", "easycms-wp" ) ?>' :
                '<?php _e( "WARNING: This will permanently clean up stale PIDs that no longer exist in the database. This action cannot be undone. Continue?", "easycms-wp" ) ?>';
                
            if (!confirm(confirmMessage)) {
                return;
            }
            
            $('#stale_progress').show();
            $('#stale_cleanup_actions').hide();
            $('#stale_results').hide();
            $('#stale_message').html(dryRun ?
                '<?php _e( "Previewing cleanup...", "easycms-wp" ) ?>' :
                '<?php _e( "Cleaning up stale PIDs...", "easycms-wp" ) ?>');

            $.ajax({
                url: EASYCMS_WP.ajax_url,
                type: 'POST',
                data: {
                    action: 'cleanup_stale_pids',
                    dry_run: dryRun,
                    nonce: EASYCMS_WP.nonce
                },
                timeout: 300000, // 5 minutes timeout for cleanup
                success: function(response) {
                    if (response.success) {
                        displayStaleCleanupResults(response.data.results, dryRun);
                        if (!dryRun) {
                            // Refresh statistics after cleanup
                            setTimeout(function() {
                                loadTranslationStatistics();
                                scanStalePids();
                            }, 2000);
                        }
                    } else {
                        alert('<?php _e( "Error during cleanup: ", "easycms-wp" ) ?>' + (response.data || 'Unknown error'));
                        $('#stale_progress').hide();
                        $('#stale_cleanup_actions').show();
                    }
                },
                error: function() {
                    alert('<?php _e( "AJAX error occurred. Please try again.", "easycms-wp" ) ?>');
                    $('#stale_progress').hide();
                    $('#stale_cleanup_actions').show();
                }
            });
        }

        function displayStaleCleanupResults(results, dryRun) {
            var html = '<h4>' + (dryRun ?
                '<?php _e( "Stale PID Cleanup Preview", "easycms-wp" ) ?>' :
                '<?php _e( "Stale PID Cleanup Results", "easycms-wp" ) ?>') + '</h4>';
            
            html += '<div class="stale-cleanup-summary">';
            
            if (results.stale_pids_cleaned > 0) {
                html += '<div class="stale-cleanup-stat"><?php _e( "Stale PIDs cleaned:", "easycms-wp" ) ?> <strong>' + results.stale_pids_cleaned + '</strong></div>';
            }
            
            if (!dryRun && results.errors > 0) {
                html += '<div class="stale-cleanup-stat error"><?php _e( "Errors:", "easycms-wp" ) ?> <strong>' + results.errors + '</strong></div>';
            }
            html += '</div>';

            if (dryRun) {
                html += '<p><strong><?php _e( "This was a preview. No PIDs were actually cleaned up.", "easycms-wp" ) ?></strong></p>';
                html += '<p><?php _e( "To actually clean up these stale PIDs, click the \"Clean Up Stale PIDs\" button.", "easycms-wp" ) ?></p>';
            } else {
                if (results.stale_pids_cleaned > 0) {
                    html += '<p><strong><?php _e( "Cleanup completed successfully!", "easycms-wp" ) ?></strong></p>';
                    html += '<p><?php _e( "Translation statistics have been refreshed. The translation equalizer should now work properly.", "easycms-wp" ) ?></p>';
                } else {
                    html += '<p><strong><?php _e( "No stale PIDs were found that needed cleaning.", "easycms-wp" ) ?></strong></p>';
                }
            }

            $('#stale_results').html(html);
            $('#stale_progress').hide();
            $('#stale_results').show();
            
            // Always show cleanup actions after results are displayed
            $('#stale_cleanup_actions').show();
        }

        // Event handlers for stale PIDs cleanup
        $('#scan_stale_pids').on('click', function() {
            scanStalePids();
        });

        $('#dry_run_stale_cleanup').on('click', function() {
            cleanupStalePids(true);
        });

        $('#actual_stale_cleanup').on('click', function() {
            cleanupStalePids(false);
        });

        // Load statistics on page load
        loadTranslationStatistics();
    });
    </script>
</div>