<?php
/**
 * Admin menu registration with proper localization
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Register admin menu
 */
add_action('admin_menu', 'lm_monitor_register_menu');

function lm_monitor_register_menu() {
	// Main menu
	$main_page = add_menu_page(
		__('LM Monitor', 'lm-monitor'),
		__('LM Monitor', 'lm-monitor'),
		'manage_options',
		'lm-monitor',
		'lm_monitor_main_page',
		'dashicons-visibility',
		26
	);

	// Settings submenu
	$settings_page = add_submenu_page(
		'lm-monitor',
		__('Settings', 'lm-monitor'),
		__('Settings', 'lm-monitor'),
		'manage_options',
		'lm-monitor-settings',
		'lm_monitor_settings_page'
	);

	// Help submenu
	$help_page = add_submenu_page(
		'lm-monitor',
		__('Help & Info', 'lm-monitor'),
		__('Help & Info', 'lm-monitor'),
		'manage_options',
		'lm-monitor-help',
		'lm_monitor_help_page'
	);

	// Load assets only on our pages
	add_action('load-' . $main_page, 'lm_monitor_load_admin_assets');
	add_action('load-' . $settings_page, 'lm_monitor_load_admin_assets');
	add_action('load-' . $help_page, 'lm_monitor_load_admin_assets');
}

/**
 * Load admin assets (called on page load)
 */
function lm_monitor_load_admin_assets() {
	add_action('admin_enqueue_scripts', 'lm_monitor_enqueue_admin_assets');
}

/**
 * Enqueue admin assets
 */
function lm_monitor_enqueue_admin_assets($hook) {
	// Only load on LM Monitor pages
	if (strpos($hook, 'lm-monitor') === false) {
		return;
	}

	// CSS
	wp_enqueue_style(
		'lm-monitor-admin',
		LM_MONITOR_PLUGIN_URL . 'assets/css/admin.css',
		array(),
		LM_MONITOR_VERSION
	);

	// JavaScript
	wp_enqueue_script(
		'lm-monitor-admin',
		LM_MONITOR_PLUGIN_URL . 'assets/js/admin.js',
		array('jquery'),
		LM_MONITOR_VERSION,
		true
	);

	// Localize script with translations and settings
	wp_localize_script('lm-monitor-admin', 'LMMonitor', array(
		'ajax_url' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('lm_monitor_nonce'),
		'debug' => defined('WP_DEBUG') && WP_DEBUG,
		'i18n' => array(
			'checking' => __('Checking...', 'lm-monitor'),
			'checkNow' => __('Check now', 'lm-monitor'),
			'checked' => __('Checked', 'lm-monitor'),
			'deleting' => __('Deleting...', 'lm-monitor'),
			'deleteBtn' => __('Delete', 'lm-monitor'),
			'confirmDelete' => __('Are you sure you want to delete this website from monitoring?\n\nThis action cannot be undone.', 'lm-monitor'),
			'networkError' => __('Network error - please try again', 'lm-monitor'),
			'timeout' => __('Request timeout - website may be slow or down', 'lm-monitor'),
			'noSites' => __('No websites added yet. Add your first website above!', 'lm-monitor'),
			'success' => __('Success!', 'lm-monitor'),
			'error' => __('Error', 'lm-monitor')
		)
	));
}

/**
 * Add custom admin header
 */
add_action('in_admin_header', 'lm_monitor_admin_header');

function lm_monitor_admin_header() {
	$screen = get_current_screen();

	if (!$screen || strpos($screen->id, 'lm-monitor') === false) {
		return;
	}

	// Don't show duplicate header
	remove_all_actions('in_admin_header');
}

/**
 * Register admin AJAX actions
 */
add_action('admin_init', 'lm_monitor_register_ajax_actions');

function lm_monitor_register_ajax_actions() {
	// These are registered in ajax-handlers.php
	// This function exists for potential future use
}

/**
 * Add contextual help
 */
add_action('load-toplevel_page_lm-monitor', 'lm_monitor_add_contextual_help');

function lm_monitor_add_contextual_help() {
	$screen = get_current_screen();

	if (!$screen) {
		return;
	}

	// Overview tab
	$screen->add_help_tab(array(
		'id' => 'lm_monitor_overview',
		'title' => __('Overview', 'lm-monitor'),
		'content' => sprintf(
			'<p>%s</p><p>%s</p>',
			__('LM Monitor helps you track the uptime and performance of multiple websites from one dashboard.', 'lm-monitor'),
			sprintf(
				__('For more detailed information, visit the %s page.', 'lm-monitor'),
				'<a href="' . esc_url(admin_url('admin.php?page=lm-monitor-help')) . '">' . __('Help & Info', 'lm-monitor') . '</a>'
			)
		)
	));

	// Quick start tab
	$screen->add_help_tab(array(
		'id' => 'lm_monitor_quickstart',
		'title' => __('Quick Start', 'lm-monitor'),
		'content' => sprintf(
			'<ol><li>%s</li><li>%s</li><li>%s</li></ol>',
			__('Enter a website URL (must start with http:// or https://)', 'lm-monitor'),
			__('Optionally add an email address for notifications', 'lm-monitor'),
			__('Click "Add Website" to start monitoring', 'lm-monitor')
		)
	));

	// Sidebar
	$screen->set_help_sidebar(
		'<p><strong>' . __('For More Information:', 'lm-monitor') . '</strong></p>' .
		'<p><a href="' . esc_url(admin_url('admin.php?page=lm-monitor-help')) . '">' . __('Documentation', 'lm-monitor') . '</a></p>' .
		'<p><a href="https://github.com/inhumed-prog/lm-monitor/issues" target="_blank">' . __('Support', 'lm-monitor') . '</a></p>'
	);
}
