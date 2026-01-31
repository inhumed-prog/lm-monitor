<?php
/**
 * Help & Info page controller
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render help page
 */
function lm_monitor_help_page() {
    // Security check
    lm_monitor_verify_admin_access();

    // Simply include the view
    include LM_MONITOR_PLUGIN_DIR . 'includes/admin/views/help-page-view.php';
}
