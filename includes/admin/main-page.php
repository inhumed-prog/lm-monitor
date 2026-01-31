<?php
/**
 * Main admin page (Dashboard) with security and error handling
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render main admin page
 */
function lm_monitor_main_page() {
    lm_monitor_verify_admin_access();

    $error_message = '';
    $success_message = '';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
        if (!check_admin_referer('lm_monitor_add_site', '_wpnonce', false)) {
            $error_message = __('Security check failed. Please try again.', 'lm-monitor');
        } else {
            $url = isset($_POST['url']) ? trim($_POST['url']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';

            // Validate URL
            $url_validation = lm_monitor_validate_url($url);

            if (!$url_validation['valid']) {
                $error_message = $url_validation['message'];
            } else {
                // Validate email
                $email_validation = lm_monitor_validate_email($email);

                if (!$email_validation['valid']) {
                    $error_message = $email_validation['message'];
                } else {
                    // Add site using database function
                    $new_id = lm_monitor_add_site(
                        $url_validation['url'],
                        $email_validation['email']
                    );

                    if ($new_id) {
                        $success_message = sprintf(
                            __('Website added successfully! (ID: %d)', 'lm-monitor'),
                            $new_id
                        );
                        lm_monitor_log(sprintf(
                            'Site added by user %d - ID: %d, URL: %s',
                            get_current_user_id(),
                            $new_id,
                            $url_validation['url']
                        ));
                    } else {
                        $error_message = __('Database error: Could not add website.', 'lm-monitor');
                    }
                }
            }
        }
    }

    // Get data for view
    $sites = lm_monitor_get_sites(array('orderby' => 'id', 'order' => 'DESC'));
    $stats = lm_monitor_get_stats();
    $cron_status = lm_monitor_get_cron_status();

    include LM_MONITOR_PLUGIN_DIR . 'includes/admin/views/main-page-view.php';
}
