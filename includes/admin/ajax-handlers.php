<?php
/**
 * AJAX handlers for admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Validate and get site ID from POST request
 *
 * @return int|null Site ID or null (sends JSON error and exits on failure)
 */
function lm_monitor_ajax_get_site_id() {
    if (empty($_POST['id'])) {
        wp_send_json_error(array(
            'message' => __('Site ID is required.', 'lm-monitor')
        ), 400);
    }

    $id = lm_monitor_sanitize_id($_POST['id']);

    if (!$id) {
        wp_send_json_error(array(
            'message' => __('Invalid site ID.', 'lm-monitor')
        ), 400);
    }

    return $id;
}

/**
 * AJAX: Check website now
 */
add_action('wp_ajax_lm_monitor_check_now', 'lm_monitor_ajax_check_now');

function lm_monitor_ajax_check_now() {
    lm_monitor_verify_admin_access(true);
    lm_monitor_verify_ajax_nonce();

    $id = lm_monitor_ajax_get_site_id();

    $site = lm_monitor_get_site($id);
    if (!$site) {
        wp_send_json_error(array(
            'message' => __('Website not found in database.', 'lm-monitor')
        ), 404);
    }

    $check_result = lm_monitor_check_and_update_site($id, $site->url);

    if ($check_result === false) {
        wp_send_json_error(array(
            'message' => __('Error checking website.', 'lm-monitor')
        ), 500);
    }

    wp_send_json_success(array(
        'status' => $check_result['status'],
        'response_time' => $check_result['response_time'],
        'ssl_days_remaining' => $check_result['ssl_days_remaining'],
        'ssl_expiry_date' => $check_result['ssl_expiry_date'],
        'checked' => current_time('mysql'),
        'status_html' => lm_monitor_render_status($check_result['status']),
        'response_time_html' => lm_monitor_render_response_time($check_result['response_time']),
        'ssl_html' => lm_monitor_render_ssl_expiry($check_result['ssl_days_remaining'], $check_result['ssl_expiry_date']),
        'message' => __('Website checked successfully!', 'lm-monitor')
    ));
}

/**
 * AJAX: Delete website
 */
add_action('wp_ajax_lm_monitor_delete', 'lm_monitor_ajax_delete');

function lm_monitor_ajax_delete() {
    lm_monitor_verify_admin_access(true);
    lm_monitor_verify_ajax_nonce();

    $id = lm_monitor_ajax_get_site_id();

    $site = lm_monitor_get_site($id);
    if (!$site) {
        wp_send_json_error(array(
            'message' => __('Website not found.', 'lm-monitor')
        ), 404);
    }

    if (!lm_monitor_delete_site($id)) {
        wp_send_json_error(array(
            'message' => __('Database error: Could not delete website.', 'lm-monitor')
        ), 500);
    }

    lm_monitor_log(sprintf(
        'Site deleted by user %d - ID: %d, URL: %s',
        get_current_user_id(),
        $id,
        $site->url
    ));

    wp_send_json_success(array(
        'message' => __('Website deleted successfully.', 'lm-monitor')
    ));
}

/**
 * AJAX: Test webhook
 */
add_action('wp_ajax_lm_monitor_test_webhook', 'lm_monitor_ajax_test_webhook');

function lm_monitor_ajax_test_webhook() {
    lm_monitor_verify_admin_access(true);
    lm_monitor_verify_ajax_nonce();

    $webhook_url = lm_monitor_get_setting('webhook_url');

    if (empty($webhook_url)) {
        wp_send_json_error(array(
            'message' => __('No webhook URL configured. Please save a webhook URL in settings first.', 'lm-monitor')
        ), 400);
    }

    $result = lm_monitor_test_webhook($webhook_url);

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ), 500);
    }

    wp_send_json_success(array(
        'message' => __('Test notification sent successfully!', 'lm-monitor')
    ));
}

/**
 * AJAX: Bulk check all sites
 */
add_action('wp_ajax_lm_monitor_bulk_check', 'lm_monitor_ajax_bulk_check');

function lm_monitor_ajax_bulk_check() {
    lm_monitor_verify_admin_access(true);
    lm_monitor_verify_ajax_nonce();

    $result = lm_monitor_manual_check_all();

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ), 500);
    }

    if (!$result['success']) {
        wp_send_json_error(array(
            'message' => $result['message']
        ), 400);
    }

    wp_send_json_success($result);
}
