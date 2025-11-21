<?php
defined( 'ABSPATH' ) || exit;
?>

<div class="cleanup-page">
	<div class="cleanup-header">
		<h2><?php _e( 'Data Cleanup & Management', 'easycms-wp' ) ?></h2>
		<p class="description">
			<?php _e( 'Tools to clean up and manage orphaned data, duplicate entries, and maintain data consistency across your multilingual WooCommerce store.', 'easycms-wp' ) ?>
		</p>
	</div>

	<div class="cleanup-tabs">
		<nav class="cleanup-tab-nav">
			<a href="#products-cleanup" class="cleanup-tab-link active" data-tab="products">
				<?php _e( 'Products', 'easycms-wp' ) ?>
			</a>
			<a href="#categories-cleanup" class="cleanup-tab-link" data-tab="categories">
				<?php _e( 'Categories', 'easycms-wp' ) ?>
			</a>
		</nav>

		<!-- PRODUCTS CLEANUP SECTION -->
		<div id="products-cleanup" class="cleanup-tab-content active">
			<div class="cleanup-section">
				<h3><?php _e( 'Product Management & Cleanup', 'easycms-wp' ) ?></h3>
				
				<div class='warning-notice'>
					<div class='notice notice-warning inline'>
						<p><strong><?php _e( 'WARNING:', 'easycms-wp' ) ?></strong> <?php _e( 'This action cannot be undone. Please be careful when deleting products and their associated data.', 'easycms-wp' ) ?></p>
					</div>
				</div>
				
				<div class='delete-options-section'>
					<h4><?php _e( 'Deletion Options', 'easycms-wp' ) ?></h4>
					
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
					<h4><?php _e( 'Deletion Preview', 'easycms-wp' ) ?></h4>
					<div class='preview-content' id='preview_content'>
						<!-- Preview will be loaded here -->
					</div>
				</div>
				
				<div class='deletion-progress' id='deletion_progress' style='display: none;'>
					<h4><?php _e( 'Deletion Progress', 'easycms-wp' ) ?></h4>
					
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
					<h4><?php _e( 'Deletion Complete', 'easycms-wp' ) ?></h4>
					<div class='results-content' id='results_content'>
						<!-- Final results will be displayed here -->
					</div>
				</div>
			</div>
		</div>

		<!-- CATEGORIES CLEANUP SECTION -->
		<div id="categories-cleanup" class="cleanup-tab-content">
			<div class="cleanup-section">
				<h3><?php _e( 'Category Cleanup & Management', 'easycms-wp' ) ?></h3>
				<p class="description">
					<?php _e( 'This tool helps you identify and clean up duplicate or orphaned categories across different languages. It ensures your category structure remains consistent with the main WPML language.', 'easycms-wp' ) ?>
				</p>
				
				<!-- Analysis Section -->
				<div class="category-analysis-section">
					<h4><?php _e( 'Current Category Analysis', 'easycms-wp' ) ?></h4>
					<div id="category-analysis-container">
						<div class="loading-spinner">
							<span class="spinner"></span>
							<?php _e( 'Analyzing categories...', 'easycms-wp' ) ?>
						</div>
					</div>
				</div>

				<!-- Cleanup Options Section -->
				<div class="category-cleanup-options-section" style="display: none;">
					<h4><?php _e( 'Cleanup Options', 'easycms-wp' ) ?></h4>
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
							<h5><?php _e( 'Cleanup Mode', 'easycms-wp' ) ?></h5>
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
					<h4><?php _e( 'Cleanup Results', 'easycms-wp' ) ?></h4>
					<div id="category-cleanup-results"></div>
				</div>

				<!-- Statistics Summary -->
				<div class="category-stats-summary" style="display: none;">
					<h4><?php _e( 'Category Statistics Summary', 'easycms-wp' ) ?></h4>
					<div id="category-stats-container" class="stats-grid">
						<!-- Will be populated by JavaScript -->
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
/* Main Layout - Fix for WordPress admin integration */
.cleanup-page {
	/* Remove centering and auto margin to align with other tabs */
	/* max-width: 1200px;
	margin: 0 auto; */
}

.cleanup-header {
	/* Reduce excessive top margin */
	margin-bottom: 20px;
	margin-top: 10px;
}

.cleanup-header h2 {
	/* Fix title positioning to match other tabs */
	margin: 0 0 10px 0;
	color: #23282d;
	font-size: 1.3em;
	line-height: 1.4;
}

.cleanup-header .description {
	color: #666;
	font-size: 14px;
	margin-top: 5px;
}

/* Tab Navigation */
.cleanup-tabs {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	overflow: hidden;
}

.cleanup-tab-nav {
	background: #f1f1f1;
	border-bottom: 1px solid #ccd0d4;
	display: flex;
}

.cleanup-tab-link {
	display: block;
	padding: 15px 20px;
	text-decoration: none;
	color: #555;
	border-right: 1px solid #ccd0d4;
	font-weight: 500;
	transition: all 0.3s ease;
}

.cleanup-tab-link:hover {
	background: #e1e1e1;
	color: #000;
}

.cleanup-tab-link.active {
	background: #fff;
	color: #000;
	border-bottom: 2px solid #0073aa;
}

/* Tab Content */
.cleanup-tab-content {
	display: none;
	padding: 30px;
}

.cleanup-tab-content.active {
	display: block;
}

.cleanup-section {
	margin-bottom: 20px;
}

.cleanup-section h3,
.cleanup-section h4 {
	margin: 0 0 15px 0;
	color: #23282d;
	border-bottom: 1px solid #eee;
	padding-bottom: 10px;
}

.cleanup-section h5 {
	margin: 0 0 10px 0;
	color: #23282d;
}

/* Product Deletion Styles */
.warning-notice {
	margin: 20px 0;
}

.delete-options-section {
	background: #f9f9f9;
	padding: 20px;
	border: 1px solid #ddd;
	border-radius: 4px;
	margin: 20px 0;
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

.preview-section,
.deletion-progress,
.deletion-results {
	margin: 20px 0;
	padding: 15px;
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

/* Category Cleanup Styles */
.category-analysis-section,
.category-cleanup-options-section,
.category-cleanup-results-section,
.category-stats-summary {
	background: #f9f9f9;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 20px;
	margin-bottom: 20px;
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
	background: #fff;
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

/* Statistics */
.stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
	margin-top: 15px;
}

.stat-card {
	background: #fff;
	padding: 15px;
	border-radius: 4px;
	border-left: 4px solid #0073aa;
}

.stat-card h4 {
	margin: 0 0 10px 0;
	color: #23282d;
	font-size: 14px;
}

.stat-card .stat-value {
	font-size: 20px;
	font-weight: 600;
	color: #0073aa;
}

.stat-card .stat-label {
	color: #666;
	font-size: 11px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

/* Common Elements */
.checkbox {
	display: flex;
	align-items: center;
	gap: 5px;
}

.form-table td:first-child {
	width: 250px;
}

.form-table td:last-child {
	vertical-align: middle;
}

.button:disabled {
	opacity: 0.6;
	cursor: not-allowed;
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

/* Category Specific Styles */
.category-list {
	margin: 15px 0;
}

.category-item {
	background: #fff;
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
	font-size: 18px;
	font-weight: 600;
	color: #0073aa;
	display: block;
}

.result-summary .stat-label {
	font-size: 11px;
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
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
	'use strict';
	
	// Global variables for product deletion
	let currentAnalysis = null;
	let deletionInProgress = false;
	let currentOffset = 0;
	let batchSize = 100; // Increased batch size for better performance
	let cumulativeProducts = 0;
	let cumulativeTranslations = 0;
	let cumulativeImages = 0;
	let cumulativeErrors = 0;
	
	// Initialize the page
	initCleanupPage();
	
	function initCleanupPage() {
		bindTabNavigation();
		bindEventHandlers();
		loadInitialData();
		// Restore the last active inner tab on page load
		restoreActiveInnerTab();
	}
	
	function bindTabNavigation() {
		$('.cleanup-tab-link').on('click', function(e) {
			e.preventDefault();
			
			const targetTab = $(this).data('tab');
			
			// Update tab navigation
			$('.cleanup-tab-link').removeClass('active');
			$(this).addClass('active');
			
			// Update tab content
			$('.cleanup-tab-content').removeClass('active');
			$('#' + targetTab + '-cleanup').addClass('active');
			
			// Save the active inner tab to localStorage
			localStorage.setItem('easycms_cleanup_active_tab', targetTab);
			
			// Load data for the active tab
			if (targetTab === 'categories' && !currentAnalysis) {
				loadCategoryAnalysis();
			}
		});
	}
	
	// Function to restore the active inner tab
	function restoreActiveInnerTab() {
		const savedTab = localStorage.getItem('easycms_cleanup_active_tab');
		if (savedTab && (savedTab === 'products' || savedTab === 'categories')) {
			// Activate the saved tab
			$('.cleanup-tab-link').removeClass('active');
			$('.cleanup-tab-link[data-tab="' + savedTab + '"]').addClass('active');
			
			$('.cleanup-tab-content').removeClass('active');
			$('#' + savedTab + '-cleanup').addClass('active');
			
			// Load data if categories tab is restored
			if (savedTab === 'categories' && !currentAnalysis) {
				loadCategoryAnalysis();
			}
		}
	}
	
	function bindEventHandlers() {
		// Product deletion handlers
		$('#delete_mode').on('change', handleDeleteModeChange);
		$('#preview_deletion').on('click', previewProductDeletion);
		$('#start_deletion').on('click', startProductDeletion);
		
		// Category cleanup handlers
		$('#run-category-cleanup').on('click', runCategoryCleanup);
		$('#refresh-analysis').on('click', loadCategoryAnalysis);
	}
	
	function loadInitialData() {
		// Load categories for product deletion
		loadCategories();
	}
	
	// ============= PRODUCT DELETION FUNCTIONS =============
	
	function handleDeleteModeChange() {
		var mode = $(this).val();
		
		if (mode === 'category') {
			$('#category_selection_row').show();
			$('#delete_category_row').show();
		} else {
			$('#category_selection_row').hide();
			$('#delete_category_row').hide();
		}
		
		// Reset preview and start button
		$('#preview_section').hide();
		$('#start_deletion').prop('disabled', true);
	}
	
	function loadCategories() {
		$.ajax({
			url: EASYCMS_WP.ajax_url,
			type: 'POST',
			data: {
				action: 'get_product_categories',
				nonce: EASYCMS_WP.nonce
			},
			success: function(response) {
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
				}
			}
		});
	}
	
	function previewProductDeletion() {
		var mode = $('#delete_mode').val();
		var deleteTranslations = $('#delete_translations').is(':checked');
		var deleteImages = $('#delete_images').is(':checked');
		
		var requestData = {
			action: 'preview_deletion',
			mode: mode,
			delete_translations: deleteTranslations,
			delete_images: deleteImages,
			nonce: EASYCMS_WP.nonce
		};
		
		if (mode === 'category') {
			var selectedCategories = $('#category_selection').val() || [];
			if (selectedCategories.length === 0) {
				alert('<?php _e( "Please select at least one category.", "easycms-wp" ) ?>');
				return;
			}
			requestData.category_ids = selectedCategories;
			requestData.delete_category = $('#delete_category').is(':checked');
		}
		
		$('#preview_deletion').prop('disabled', true).text('<?php _e( "Loading...", "easycms-wp" ) ?>');
		
		$.ajax({
			url: EASYCMS_WP.ajax_url,
			type: 'POST',
			data: requestData,
			success: function(response) {
				$('#preview_deletion').prop('disabled', false).text('<?php _e( "Preview Deletion", "easycms-wp" ) ?>');
				
				if (response.success) {
					displayProductPreview(response.data);
					$('#start_deletion').prop('disabled', false);
				} else {
					alert('<?php _e( "Error generating preview. Please try again.", "easycms-wp" ) ?>');
				}
			},
			error: function() {
				$('#preview_deletion').prop('disabled', false).text('<?php _e( "Preview Deletion", "easycms-wp" ) ?>');
				alert('<?php _e( "AJAX error occurred. Please try again.", "easycms-wp" ) ?>');
			}
		});
	}
	
	function displayProductPreview(data) {
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
		
		$('#preview_content').html(html);
		$('#preview_section').show();
		
		// Store total products for progress tracking
		window.totalProducts = data.product_count;
	}
	
	function startProductDeletion() {
		if (!confirm('<?php _e( "Are you sure you want to delete these products? This action cannot be undone.", "easycms-wp" ) ?>')) {
			return;
		}
		
		// Initialize deletion process
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
		logMessage('<?php _e( "Total products to process: ", "easycms-wp" ) ?>' + window.totalProducts, 'info');
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
				
				if (response.success) {
					// Accumulate counts
					cumulativeProducts += (response.data.products_deleted || 0);
					cumulativeTranslations += (response.data.translations_deleted || 0);
					cumulativeImages += (response.data.images_deleted || 0);
					cumulativeErrors += (response.data.errors || 0);
					
					updateProgress(response.data);
					
					if (response.data.more_data !== false) {
						currentOffset += batchSize;
						logMessage('<?php _e( "Continuing to next batch at offset: ", "easycms-wp" ) ?>' + currentOffset, 'info');
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
				
				if (response.success) {
					// Accumulate counts
					cumulativeProducts += (response.data.products_deleted || 0);
					cumulativeTranslations += (response.data.translations_deleted || 0);
					cumulativeImages += (response.data.images_deleted || 0);
					cumulativeErrors += (response.data.errors || 0);
					
					updateProgress(response.data);
					
					if (response.data.more_data !== false) {
						currentOffset += batchSize;
						logMessage('<?php _e( "Continuing to next category batch at offset: ", "easycms-wp" ) ?>' + currentOffset, 'info');
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
		var progress = window.totalProducts > 0 ? Math.min(100, Math.round((cumulativeProducts / window.totalProducts) * 100)) : 0;
		$('#progress_fill').css('width', progress + '%');
		$('#progress_text').text(progress + '%');
		
		// Update details with batch info
		var batchInfo = '<?php _e( "Batch completed: ", "easycms-wp" ) ?>' + (data.products_deleted || 0) + '<?php _e( " products in this batch", "easycms-wp" ) ?>';
		var cumulativeInfo = '<?php _e( "Total progress: ", "easycms-wp" ) ?>' + cumulativeProducts + '<?php _e( " / ", "easycms-wp" ) ?>' + window.totalProducts + '<?php _e( " products", "easycms-wp" ) ?>';
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
		var successRate = window.totalProducts > 0 ? Math.round((productsDeleted / window.totalProducts) * 100) : 0;
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
	
	// ============= CATEGORY CLEANUP FUNCTIONS =============
	
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
		
		// DEBUG: Add detailed analysis information
		html += '<div class="debug-info" style="background: #f0f0f0; padding: 15px; margin: 10px 0; border: 1px solid #ccc;">';
		html += '<h5>🔍 Debug Information:</h5>';
		html += '<p><strong>Main language:</strong> ' + stats.main_language + '</p>';
		html += '<p><strong>Total main categories:</strong> ' + stats.total_main_categories + '</p>';
		html += '<p><strong>Total translations:</strong> ' + stats.total_translations + '</p>';
		html += '<p><strong>Orphaned categories found:</strong> ' + analysis.abandoned_categories.length + '</p>';
		html += '<p><strong>Languages:</strong> ' + (stats.languages ? stats.languages.join(', ') : 'N/A') + '</p>';
		html += '<p><strong>Category counts by language:</strong></p><ul>';
		for (let lang in stats.category_counts) {
			if (stats.category_counts.hasOwnProperty(lang)) {
				html += '<li>' + lang.toUpperCase() + ': ' + stats.category_counts[lang] + ' categories</li>';
			}
		}
		html += '</ul>';
		
		// Check for category count discrepancies
		let hasCategoryMismatch = false;
		let mismatchDetails = [];
		if (stats.category_counts && stats.main_language) {
			const mainCount = stats.category_counts[stats.main_language] || 0;
			for (let lang in stats.category_counts) {
				if (lang !== stats.main_language) {
					const langCount = stats.category_counts[lang];
					if (langCount !== mainCount) {
						hasCategoryMismatch = true;
						mismatchDetails.push(`${lang.toUpperCase()}: ${langCount} vs Main: ${mainCount}`);
					}
				}
			}
		}
		
		html += '<p><strong>Category count analysis:</strong></p>';
		if (hasCategoryMismatch) {
			html += '<div style="color: #d63638; font-weight: bold;">❌ MISMATCH DETECTED!</div>';
			html += '<ul style="color: #d63638;">';
			mismatchDetails.forEach(detail => {
				html += '<li>' + detail + '</li>';
			});
			html += '</ul>';
			html += '<p style="color: #d63638;"><strong>This indicates that some categories exist in translations but not in the main language, or vice versa.</strong></p>';
		} else {
			html += '<div style="color: #46b450;">✅ All language category counts match</div>';
		}
		html += '</div>';
		
		// Determine if cleanup options should be shown
		const shouldShowCleanup = analysis.abandoned_categories.length > 0 || hasCategoryMismatch;
		
		if (shouldShowCleanup) {
			$('.category-cleanup-options-section').show();
			
			if (hasCategoryMismatch && analysis.abandoned_categories.length === 0) {
				html += '<div class="notice warning"><strong>⚠️ Category Count Mismatch Detected!</strong><br>';
				html += 'There is a discrepancy in category counts between languages. Main language has ' + stats.total_main_categories + ' categories ';
				html += 'but secondary languages have different counts. This suggests orphaned categories that may not have been detected properly by the system.<br><br>';
				html += '<strong>Recommended action:</strong> Run the category cleanup to identify and remove orphaned categories.</div>';
			}
			
			if (analysis.abandoned_categories.length > 0) {
				html += '<div class="notice warning"><strong>⚠️ ' + analysis.abandoned_categories.length + ' orphaned categories found!</strong><br>';
				html += 'These categories exist in secondary languages but have no corresponding main language category. They can be safely removed if they contain no products.</div>';
			}
		} else {
			html += '<div class="notice success">✅ No categories found that need cleanup. Your category structure is clean!</div>';
		}
		
		$('#category-analysis-container').html(html);
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
		
		$('#category-cleanup-results').html(html);
	}
	
	function displayError(message) {
		const errorHtml = '<div class="notice error"><strong><?php _e( "Error:", "easycms-wp" ); ?></strong> ' + message + '</div>';
		$('#category-analysis-container').html(errorHtml);
	}
});
</script>