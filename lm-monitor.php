<?php
/**
 * Plugin Name: LM Monitor
 * Plugin URI: https://github.com/inhumed-prog/lm-monitor
 * Description: Professional website monitoring plugin for agencies with uptime tracking, SSL monitoring, performance metrics, and instant alerts
 * Version: 2.0.0
 * Author: Luk Meyer
 * Author URI: https://github.com/inhumed-prog
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lm-monitor
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LM_MONITOR_VERSION', '2.0.0');
define('LM_MONITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LM_MONITOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LM_MONITOR_PLUGIN_FILE', __FILE__);
define('LM_MONITOR_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('LM_MONITOR_MIN_PHP_VERSION', '7.4');
define('LM_MONITOR_MIN_WP_VERSION', '5.8');

/**
 * Check minimum requirements
 *
 * @return array Array of error messages, empty if all requirements met
 */
function lm_monitor_check_requirements() {
    $errors = array();

    // Check PHP version
    if (version_compare(PHP_VERSION, LM_MONITOR_MIN_PHP_VERSION, '<')) {
        $errors[] = sprintf(
                __('LM Monitor requires PHP version %s or higher. You are running version %s.', 'lm-monitor'),
                LM_MONITOR_MIN_PHP_VERSION,
                PHP_VERSION
        );
    }

    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, LM_MONITOR_MIN_WP_VERSION, '<')) {
        $errors[] = sprintf(
                __('LM Monitor requires WordPress version %s or higher. You are running version %s.', 'lm-monitor'),
                LM_MONITOR_MIN_WP_VERSION,
                $wp_version
        );
    }

    // Check required PHP extensions
    $required_extensions = array('curl', 'openssl', 'json');
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = sprintf(
                    __('LM Monitor requires the PHP %s extension to be installed.', 'lm-monitor'),
                    $extension
            );
        }
    }

    return $errors;
}

/**
 * Display admin notice for requirement errors
 */
function lm_monitor_requirement_error_notice() {
    $errors = lm_monitor_check_requirements();

    if (empty($errors)) {
        return;
    }

    echo '<div class="notice notice-error"><p><strong>' . esc_html__('LM Monitor Error:', 'lm-monitor') . '</strong></p><ul>';
    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }
    echo '</ul></div>';

    deactivate_plugins(LM_MONITOR_PLUGIN_BASENAME);
}

// Check requirements before loading
$requirement_errors = lm_monitor_check_requirements();
if (!empty($requirement_errors)) {
    add_action('admin_notices', 'lm_monitor_requirement_error_notice');
    return;
}

// Load constants and helpers
require_once LM_MONITOR_PLUGIN_DIR . 'includes/constants.php';
require_once LM_MONITOR_PLUGIN_DIR . 'includes/helpers.php';

// Core includes
require_once LM_MONITOR_PLUGIN_DIR . 'includes/core/database.php';
require_once LM_MONITOR_PLUGIN_DIR . 'includes/core/validator.php';
require_once LM_MONITOR_PLUGIN_DIR . 'includes/core/monitor.php';

// Feature includes
require_once LM_MONITOR_PLUGIN_DIR . 'includes/cron.php';
require_once LM_MONITOR_PLUGIN_DIR . 'includes/alerts.php';

// Admin includes
if (is_admin()) {
    require_once LM_MONITOR_PLUGIN_DIR . 'includes/admin/menu.php';
    require_once LM_MONITOR_PLUGIN_DIR . 'includes/admin/main-page.php';
    require_once LM_MONITOR_PLUGIN_DIR . 'includes/admin/settings-page.php';
    require_once LM_MONITOR_PLUGIN_DIR . 'includes/admin/help-page.php';
    require_once LM_MONITOR_PLUGIN_DIR . 'includes/admin/ajax-handlers.php';
}

// Activation / deactivation / uninstall hooks
register_activation_hook(__FILE__, 'lm_monitor_activate');
register_deactivation_hook(__FILE__, 'lm_monitor_deactivate');
register_uninstall_hook(__FILE__, 'lm_monitor_uninstall');

/**
 * Plugin activation
 */
function lm_monitor_activate() {
    $errors = lm_monitor_check_requirements();
    if (!empty($errors)) {
        wp_die(
                implode('<br>', array_map('esc_html', $errors)),
                esc_html__('Plugin Activation Error', 'lm-monitor'),
                array('back_link' => true)
        );
    }

    lm_monitor_create_table();
    lm_monitor_schedule_cron();

    // Set default options
    if (!get_option('lm_monitor_settings')) {
        update_option('lm_monitor_settings', array(
                'webhook_url' => '',
                'check_interval' => LM_MONITOR_DEFAULT_CHECK_INTERVAL,
                'notification_cooldown' => LM_MONITOR_DEFAULT_COOLDOWN,
                'version' => LM_MONITOR_VERSION
        ), false);
    }

    set_transient('lm_monitor_activation_notice', true, MINUTE_IN_SECONDS);
    flush_rewrite_rules();

    lm_monitor_log(sprintf('Plugin v%s activated by user %d', LM_MONITOR_VERSION, get_current_user_id()));
}

/**
 * Plugin deactivation
 */
function lm_monitor_deactivate() {
    lm_monitor_clear_cron();

    // Clear transients
    global $wpdb;
    $wpdb->query(
            "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_lm_monitor_%' 
         OR option_name LIKE '_transient_timeout_lm_monitor_%'"
    );

    flush_rewrite_rules();
    lm_monitor_log(sprintf('Plugin deactivated by user %d', get_current_user_id()));
}

/**
 * Plugin uninstall
 */
function lm_monitor_uninstall() {
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        return;
    }

    global $wpdb;

    $wpdb->query("DROP TABLE IF EXISTS " . lm_monitor_get_table_name());
    delete_option('lm_monitor_settings');
    delete_option('lm_monitor_version');

    $wpdb->query(
            "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_lm_monitor_%' 
         OR option_name LIKE '_transient_timeout_lm_monitor_%'"
    );

    wp_clear_scheduled_hook('lm_monitor_cron_event');
    error_log('LM Monitor: Plugin uninstalled - all data removed');
}

/**
 * Load textdomain
 */
add_action('plugins_loaded', 'lm_monitor_load_textdomain');
function lm_monitor_load_textdomain() {
    load_plugin_textdomain('lm-monitor', false, dirname(LM_MONITOR_PLUGIN_BASENAME) . '/languages');
}

/**
 * Activation notice
 */
add_action('admin_notices', 'lm_monitor_activation_notice');
function lm_monitor_activation_notice() {
    if (!current_user_can('manage_options') || !get_transient('lm_monitor_activation_notice')) {
        return;
    }

    delete_transient('lm_monitor_activation_notice');
    ?>
    <div class="notice notice-success is-dismissible">
        <p><strong><?php esc_html_e('LM Monitor activated successfully!', 'lm-monitor'); ?></strong></p>
        <p>
            <?php
            printf(
                    esc_html__('Get started by adding your first website in %s.', 'lm-monitor'),
                    '<a href="' . esc_url(lm_monitor_admin_url()) . '">' . esc_html__('LM Monitor Dashboard', 'lm-monitor') . '</a>'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Plugin action links
 */
add_filter('plugin_action_links_' . LM_MONITOR_PLUGIN_BASENAME, 'lm_monitor_action_links');
function lm_monitor_action_links($links) {
    $plugin_links = array(
            '<a href="' . esc_url(lm_monitor_admin_url()) . '">' . esc_html__('Dashboard', 'lm-monitor') . '</a>',
            '<a href="' . esc_url(lm_monitor_admin_url('settings')) . '">' . esc_html__('Settings', 'lm-monitor') . '</a>',
    );
    return array_merge($plugin_links, $links);
}

/**
 * Plugin row meta
 */
add_filter('plugin_row_meta', 'lm_monitor_row_meta', 10, 2);
function lm_monitor_row_meta($links, $file) {
    if ($file !== LM_MONITOR_PLUGIN_BASENAME) {
        return $links;
    }

    $row_meta = array(
            'docs' => '<a href="' . esc_url(lm_monitor_admin_url('help')) . '">' . esc_html__('Documentation', 'lm-monitor') . '</a>',
            'support' => '<a href="https://lm-monitor.com/support" target="_blank">' . esc_html__('Support', 'lm-monitor') . '</a>',
    );

    return array_merge($links, $row_meta);
}

/**
 * Check version and update if needed
 */
add_action('plugins_loaded', 'lm_monitor_check_version');
function lm_monitor_check_version() {
    $saved_version = get_option('lm_monitor_version', '0');

    if (version_compare($saved_version, LM_MONITOR_VERSION, '<')) {
        lm_monitor_update_plugin($saved_version);
    }
}

/**
 * Update plugin
 *
 * @param string $old_version Previous version number
 */
function lm_monitor_update_plugin($old_version) {
    lm_monitor_maybe_update_table_structure();

    if (version_compare($old_version, '2.0.0', '<')) {
        lm_monitor_migrate_to_2_0_0();
    }

    update_option('lm_monitor_version', LM_MONITOR_VERSION);
    lm_monitor_log(sprintf('Updated from v%s to v%s', $old_version, LM_MONITOR_VERSION));
}

/**
 * Migrate to 2.0.0
 */
function lm_monitor_migrate_to_2_0_0() {
    lm_monitor_maybe_update_table_structure();
}

/**
 * Add admin body class
 */
add_filter('admin_body_class', 'lm_monitor_admin_body_class');
function lm_monitor_admin_body_class($classes) {
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'lm-monitor') !== false) {
        $classes .= ' lm-monitor-admin-page';
    }
    return $classes;
}
