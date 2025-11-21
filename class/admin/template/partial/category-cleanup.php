<?php
defined( 'ABSPATH' ) || exit;

$default_language = \EasyCMS_WP\Util::get_default_language();
?>

<div class="category-cleanup-page">
	<div class="category-cleanup-header">
		<h2><?php _e( 'Category Cleanup & Management', 'easycms-wp' ) ?></h2>
		<p class="description">
			<?php _e( 'This tool helps you identify and clean up duplicate or orphaned categories across different languages. It ensures your category structure remains consistent with the main WPML language.', 'easycms-wp' ) ?>
		</p>
	</div>

	<div class="category-cleanup-content">
		<!-- Analysis Section -->
		<div class="category-analysis-section">
			<h3><?php _e( 'Current Category Analysis', 'easycms-wp' ) ?></h3>
			<div id="category-analysis-container">
				<div class="loading-spinner">
					<span class="spinner"></span>
					<?php _e( 'Analyzing categories...', 'easycms-wp' ) ?>
				</div>
			</div>
		</div>

		<!-- Cleanup Options Section -->
		<div class="category-cleanup-options-section" style="display: none;">
			<h3><?php _e( 'Cleanup Options', 'easycms-wp' ) ?></h3>
			<form id="category-cleanup-form">
				<?php wp_nonce_field( 'easycms_wp_check_req', 'nonce' ); ?>
				
				<div class="cleanup-options">
					<fieldset>
						<legend><?php _e( 'What should be cleaned up?', 'easycms-wp' ) ?></legend>
						
						<label class="option-checkbox">
							<input type="checkbox" name="delete_orphaned" value="1" checked>
							<span><?php _e( 'Delete orphaned translations (categories that don\'t exist in main language)', 'easycms-wp' ) ?></span>
							<small class="option-description">
								<?php _e( 'These are categories in secondary languages that don\'t have a corresponding main category. Safe to delete if they have no products.', 'easycms-wp' ) ?>
							</small>
						</label>
						
						<label class="option-checkbox">
							<input type="checkbox" name="delete_duplicates" value="1">
							<span><?php _e( 'Delete duplicate categories (same CID in same language)', 'easycms-wp' ) ?></span>
							<small class="option-description">
								<?php _e( 'These are duplicate entries for the same category. Only the first occurrence will be kept.', 'easycms-wp' ) ?>
							</small>
						</label>
						
						<label class="option-checkbox">
							<input type="checkbox" name="preserve_images" value="1" checked>
							<span><?php _e( 'Preserve category images', 'easycms-wp' ) ?></span>
							<small class="option-description">
								<?php _e( 'Category images will not be deleted even if the category is removed, as they might be used by the main category.', 'easycms-wp' ) ?>
							</small>
						</label>
					</fieldset>
				</div>
				
				<div class="mode-selection">
					<h4><?php _e( 'Cleanup Mode', 'easycms-wp' ) ?></h4>
					<label class="mode-option">
						<input type="radio" name="mode" value="dry_run" checked>
						<span class="mode-label"><?php _e( 'Dry Run (Preview Only)', 'easycms-wp' ) ?></span>
						<small><?php _e( 'Show what would be deleted without making changes', 'easycms-wp' ) ?></small>
					</label>
					
					<label class="mode-option">
						<input type="radio" name="mode" value="execute">
						<span class="mode-label"><?php _e( 'Execute Cleanup', 'easycms-wp' ) ?></span>
						<small><?php _e( 'Actually perform the cleanup operations', 'easycms-wp' ) ?></small>
					</label>
				</div>
				
				<div class="action-buttons">
					<button type="button" id="run-category-cleanup" class="button button-primary">
						<?php _e( 'Run Category Cleanup', 'easycms-wp' ) ?>
					</button>
					<button type="button" id="refresh-analysis" class="button">
						<?php _e( 'Refresh Analysis', 'easycms-wp' ) ?>
					</button>
				</div>
			</form>
		</div>

		<!-- Results Section -->
		<div class="category-cleanup-results-section" style="display: none;">
			<h3><?php _e( 'Cleanup Results', 'easycms-wp' ) ?></h3>
			<div id="category-cleanup-results"></div>
		</div>

		<!-- Statistics Summary -->
		<div class="category-stats-summary" style="display: none;">
			<h3><?php _e( 'Category Statistics Summary', 'easycms-wp' ) ?></h3>
			<div id="category-stats-container" class="stats-grid">
				<!-- Will be populated by JavaScript -->
			</div>
		</div>
	</div>
</div>

<style>
.category-cleanup-page {
	max-width: 1200px;
	margin: 0 auto;
}

.category-cleanup-header {
	margin-bottom: 30px;
}

.category-cleanup-header h2 {
	margin: 0 0 10px 0;
	color: #23282d;
}

.category-cleanup-header .description {
	color: #666;
	font-size: 14px;
}

.category-analysis-section,
.category-cleanup-options-section,
.category-cleanup-results-section,
.category-stats-summary {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	padding: 20px;
	margin-bottom: 20px;
	box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.loading-spinner {
	text-align: center;
	padding: 40px;
	color: #666;
}

.loading-spinner .spinner {
	float: none;
	margin-right: 10px;
}

.cleanup-options {
	margin-bottom: 20px;
}

.cleanup-options fieldset {
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 15px;
	margin: 0;
}

.cleanup-options legend {
	padding: 0 8px;
	font-weight: 600;
	color: #23282d;
}

.option-checkbox {
	display: block;
	margin-bottom: 15px;
	padding: 10px;
	background: #f9f9f9;
	border-radius: 4px;
	border-left: 4px solid #0073aa;
}

.option-checkbox input[type="checkbox"] {
	margin-right: 10px;
}

.option-checkbox span {
	font-weight: 600;
	display: block;
	margin-bottom: 5px;
}

.option-description {
	color: #666;
	font-size: 13px;
	margin-left: 0;
	display: block;
}

.mode-selection {
	margin-bottom: 20px;
}

.mode-selection h4 {
	margin: 0 0 10px 0;
	color: #23282d;
}

.mode-option {
	display: block;
	margin-bottom: 10px;
	padding: 10px;
	border: 1px solid #ddd;
	border-radius: 4px;
	cursor: pointer;
}

.mode-option:hover {
	background: #f9f9f9;
}

.mode-option input[type="radio"] {
	margin-right: 10px;
}

.mode-label {
	font-weight: 600;
	display: block;
	margin-bottom: 3px;
}

.mode-option small {
	color: #666;
	margin-left: 0;
}

.action-buttons {
	text-align: right;
	padding-top: 15px;
	border-top: 1px solid #eee;
}

.action-buttons .button {
	margin-left: 10px;
}

.stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 20px;
	margin-top: 20px;
}

.stat-card {
	background: #f9f9f9;
	padding: 15px;
	border-radius: 4px;
	border-left: 4px solid #0073aa;
}

.stat-card h4 {
	margin: 0 0 10px 0;
	color: #23282d;
}

.stat-card .stat-value {
	font-size: 24px;
	font-weight: 600;
	color: #0073aa;
}

.stat-card .stat-label {
	color: #666;
	font-size: 12px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.category-list {
	margin: 15px 0;
}

.category-item {
	background: #f9f9f9;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 10px;
	margin-bottom: 8px;
}

.category-item.danger {
	border-left: 4px solid #dc3232;
	background: #fef7f7;
}

.category-item.warning {
	border-left: 4px solid #ffb900;
	background: #fffbf0;
}

.category-item.info {
	border-left: 4px solid #00a0d2;
	background: #f0f6fc;
}

.category-item h5 {
	margin: 0 0 5px 0;
	font-size: 14px;
}

.category-item .category-meta {
	font-size: 12px;
	color: #666;
}

.category-item .category-actions {
	margin-top: 8px;
}

.result-summary {
	background: #e7f3ff;
	border: 1px solid #b3d9ff;
	border-radius: 4px;
	padding: 15px;
	margin-bottom: 20px;
}

.result-summary h4 {
	margin: 0 0 10px 0;
	color: #1d2327;
}

.result-summary .summary-stats {
	display: flex;
	gap: 20px;
	flex-wrap: wrap;
}

.result-summary .stat {
	text-align: center;
}

.result-summary .stat-value {
	font-size: 20px;
	font-weight: 600;
	color: #0073aa;
	display: block;
}

.result-summary .stat-label {
	font-size: 12px;
	color: #666;
	text-transform: uppercase;
}

.notice {
	padding: 10px 15px;
	border-left: 4px solid #00a0d2;
	background: #f0f6fc;
	margin: 15px 0;
}

.notice.error {
	border-left-color: #dc3232;
	background: #fef7f7;
}

.notice.success {
	border-left-color: #46b450;
	background: #f0f6fc;
}

.notice.warning {
	border-left-color: #ffb900;
	background: #fffbf0;
}

.error-message {
	color: #dc3232;
	font-weight: 600;
}

.success-message {
	color: #46b450;
	font-weight: 600;
}

.warning-message {
	color: #ffb900;
	font-weight: 600;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
	'use strict';
	
	let currentAnalysis = null;
	
	// Initialize the page
	initCategoryCleanupPage();
	
	function initCategoryCleanupPage() {
		// Load initial analysis
		loadCategoryAnalysis();
		
		// Bind event handlers
		bindEventHandlers();
	}
	
	function bindEventHandlers() {
		$('#run-category-cleanup').on('click', runCategoryCleanup);
		$('#refresh-analysis').on('click', loadCategoryAnalysis);
	}
	
	function loadCategoryAnalysis() {
		$('#category-analysis-container').html('<div class="loading-spinner"><span class="spinner"></span><?php _e( "Analyzing categories...", "easycms-wp" ); ?></div>');
		$('.category-cleanup-options-section, .category-cleanup-results-section, .category-stats-summary').hide();
		
		$.ajax({
			url: EASYCMS_WP.ajax_url,
			type: 'POST',
			data: {
				action: 'get_category_cleanup_analysis',
				nonce: EASYCMS_WP.nonce
			},
			success: function(response) {
				if (response.success) {
					currentAnalysis = response.data;
					displayCategoryAnalysis(response.data);
				} else {
					displayError('<?php _e( "Failed to load category analysis:", "easycms-wp" ); ?> ' + response.data);
				}
			},
			error: function() {
				displayError('<?php _e( "An error occurred while loading the category analysis.", "easycms-wp" ); ?>');
			}
		});
	}
	
	function displayCategoryAnalysis(analysis) {
		let html = '';
		
		// Statistics summary
		const stats = analysis.statistics;
		let statsHtml = '<div class="stats-grid">';
		statsHtml += '<div class="stat-card"><div class="stat-value">' + stats.total_main_categories + '</div><div class="stat-label"><?php _e( "Main Categories", "easycms-wp" ); ?></div></div>';
		statsHtml += '<div class="stat-card"><div class="stat-value">' + stats.total_translations + '</div><div class="stat-label"><?php _e( "Total Translations", "easycms-wp" ); ?></div></div>';
		statsHtml += '<div class="stat-card"><div class="stat-value">' + analysis.abandoned_categories.length + '</div><div class="stat-label"><?php _e( "Orphaned Categories", "easycms-wp" ); ?></div></div>';
		
		// Language breakdown
		for (let lang in stats.category_counts) {
			if (stats.category_counts.hasOwnProperty(lang)) {
				statsHtml += '<div class="stat-card"><div class="stat-value">' + stats.category_counts[lang] + '</div><div class="stat-label">' + lang.toUpperCase() + ' <?php _e( "Categories", "easycms-wp" ); ?></div></div>';
			}
		}
		statsHtml += '</div>';
		
		$('#category-stats-container').html(statsHtml);
		$('.category-stats-summary').show();
		
		// Detailed analysis
		html += '<div class="result-summary">';
		html += '<h4><?php _e( "Analysis Summary", "easycms-wp" ); ?></h4>';
		html += '<div class="summary-stats">';
		html += '<div class="stat"><span class="stat-value">' + stats.total_main_categories + '</span><div class="stat-label"><?php _e( "Main Categories", "easycms-wp" ); ?></div></div>';
		html += '<div class="stat"><span class="stat-value">' + stats.total_translations + '</span><div class="stat-label"><?php _e( "Translations", "easycms-wp" ); ?></div></div>';
		html += '<div class="stat"><span class="stat-value">' + analysis.abandoned_categories.length + '</span><div class="stat-label"><?php _e( "Orphaned", "easycms-wp" ); ?></div></div>';
		html += '<div class="stat"><span class="stat-value">' + getDuplicateCount(analysis.duplicate_categories) + '</span><div class="stat-label"><?php _e( "Duplicates", "easycms-wp" ); ?></div></div>';
		html += '</div>';
		html += '</div>';
		
		// Orphaned categories section
		if (analysis.abandoned_categories.length > 0) {
			html += '<h4><?php _e( "Orphaned Categories", "easycms-wp" ); ?></h4>';
			html += '<div class="category-list">';
			analysis.abandoned_categories.forEach(function(category) {
				html += '<div class="category-item danger">';
				html += '<h5>' + category.name + ' (' + category.language.toUpperCase() + ')</h5>';
				html += '<div class="category-meta">CID: ' + category.cid + ' | <?php _e( "Products:", "easycms-wp" ); ?> ' + category.count + ' | <?php _e( "Language:", "easycms-wp" ); ?> ' + category.language + '</div>';
				if (category.count > 0) {
					html += '<div class="notice warning"><?php _e( "This category has products and will not be deleted.", "easycms-wp" ); ?></div>';
				}
				html += '</div>';
			});
			html += '</div>';
		}
		
		// Duplicate categories section
		if (Object.keys(analysis.duplicate_categories).length > 0) {
			html += '<h4><?php _e( "Duplicate Categories", "easycms-wp" ); ?></h4>';
			html += '<div class="category-list">';
			for (let lang in analysis.duplicate_categories) {
				if (analysis.duplicate_categories.hasOwnProperty(lang)) {
					html += '<h5>' + lang.toUpperCase() + '</h5>';
					analysis.duplicate_categories[lang].forEach(function(duplicate, index) {
						html += '<div class="category-item ' + (index === 0 ? 'info' : 'warning') + '">';
						html += '<h5>' + duplicate.name + (index === 0 ? ' (<?php _e( "Keep", "easycms-wp" ); ?>)' : ' (<?php _e( "Delete", "easycms-wp" ); ?>)') + '</h5>';
						html += '<div class="category-meta">CID: ' + duplicate.cid + ' | <?php _e( "Products:", "easycms-wp" ); ?> ' + duplicate.count + '</div>';
						html += '</div>';
					});
				}
			}
			html += '</div>';
		}
		
		// Show options section if there are categories to clean up
		if (analysis.abandoned_categories.length > 0 || getDuplicateCount(analysis.duplicate_categories) > 0) {
			$('.category-cleanup-options-section').show();
		} else {
			html += '<div class="notice success"><?php _e( "No categories found that need cleanup. Your category structure is clean!", "easycms-wp" ); ?></div>';
		}
		
		$('#category-analysis-container').html(html);
	}
	
	function getDuplicateCount(duplicateCategories) {
		let count = 0;
		for (let lang in duplicateCategories) {
			if (duplicateCategories.hasOwnProperty(lang) && duplicateCategories[lang].length > 1) {
				count += duplicateCategories[lang].length - 1; // Subtract 1 to keep the first occurrence
			}
		}
		return count;
	}
	
	function runCategoryCleanup() {
		const formData = $('#category-cleanup-form').serialize();
		const isDryRun = $('input[name="mode"]:checked').val() === 'dry_run';
		
		if (isDryRun) {
			if (!confirm('<?php _e( "This will show you what categories would be deleted. Continue?", "easycms-wp" ); ?>')) {
				return;
			}
		} else {
			if (!confirm('<?php _e( "WARNING: This will actually delete categories. Are you sure you want to proceed?", "easycms-wp" ); ?>')) {
				return;
			}
		}
		
		$('#run-category-cleanup').prop('disabled', true).text('<?php _e( "Processing...", "easycms-wp" ); ?>');
		$('#category-cleanup-results').html('<div class="loading-spinner"><span class="spinner"></span><?php _e( "Running cleanup...", "easycms-wp" ); ?></div>');
		$('.category-cleanup-results-section').show();
		
		$.ajax({
			url: EASYCMS_WP.ajax_url,
			type: 'POST',
			data: formData + '&action=run_category_cleanup&nonce=' + EASYCMS_WP.nonce,
			success: function(response) {
				if (response.success) {
					displayCleanupResults(response.data);
				} else {
					displayError('<?php _e( "Cleanup failed:", "easycms-wp" ); ?> ' + response.data);
				}
			},
			error: function() {
				displayError('<?php _e( "An error occurred during cleanup.", "easycms-wp" ); ?>');
			},
			complete: function() {
				$('#run-category-cleanup').prop('disabled', false).text('<?php _e( "Run Category Cleanup", "easycms-wp" ); ?>');
			}
		});
	}
	
	function displayCleanupResults(results) {
		let html = '';
		
		// Summary
		html += '<div class="result-summary">';
		html += '<h4><?php _e( "Cleanup Results", "easycms-wp" ); ?></h4>';
		html += '<div class="summary-stats">';
		html += '<div class="stat"><span class="stat-value">' + results.summary.to_delete + '</span><div class="stat-label"><?php _e( "Deleted", "easycms-wp" ); ?></div></div>';
		html += '<div class="stat"><span class="stat-value">' + results.summary.errors + '</span><div class="stat-label"><?php _e( "Errors", "easycms-wp" ); ?></div></div>';
		html += '<div class="stat"><span class="stat-value">' + results.summary.warnings + '</span><div class="stat-label"><?php _e( "Warnings", "easycms-wp" ); ?></div></div>';
		html += '</div>';
		html += '</div>';
		
		// Success/Error messages
		if (results.deleted_categories.length > 0) {
			html += '<div class="notice success">';
			html += '<h4><?php _e( "Categories Processed", "easycms-wp" ); ?></h4>';
			html += '<div class="category-list">';
			results.deleted_categories.forEach(function(category) {
				html += '<div class="category-item">';
				html += '<h5>' + category.name + ' (' + category.language.toUpperCase() + ')</h5>';
				html += '<div class="category-meta">' + category.action + ' - CID: ' + category.cid + '</div>';
				html += '</div>';
			});
			html += '</div>';
			html += '</div>';
		}
		
		if (results.warnings.length > 0) {
			html += '<div class="notice warning">';
			html += '<h4><?php _e( "Warnings", "easycms-wp" ); ?></h4>';
			html += '<ul>';
			results.warnings.forEach(function(warning) {
				html += '<li>' + warning + '</li>';
			});
			html += '</ul>';
			html += '</div>';
		}
		
		if (results.errors.length > 0) {
			html += '<div class="notice error">';
			html += '<h4><?php _e( "Errors", "easycms-wp" ); ?></h4>';
			html += '<ul>';
			results.errors.forEach(function(error) {
				html += '<li>' + error + '</li>';
			});
			html += '</ul>';
			html += '</div>';
		}
		
		// Refresh analysis button
		html += '<div class="action-buttons">';
		html += '<button type="button" class="button" onclick="location.reload();"><?php _e( "Refresh Page", "easycms-wp" ); ?></button>';
		html += '</div>';
		
		$('#category-cleanup-results').html(html);
	}
	
	function displayError(message) {
		const errorHtml = '<div class="notice error"><strong><?php _e( "Error:", "easycms-wp" ); ?></strong> ' + message + '</div>';
		$('#category-analysis-container').html(errorHtml);
	}
});
</script>