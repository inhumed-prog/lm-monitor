<?php
/**
 * Database functions with proper error handling
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create database table on activation
 *
 * @return bool True on success, false on failure
 */
function lm_monitor_create_table() {
    global $wpdb;

    $table = lm_monitor_get_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `url` varchar(255) NOT NULL,
        `notify_email` varchar(100) DEFAULT NULL,
        `status` varchar(50) DEFAULT NULL,
        `last_checked` datetime DEFAULT NULL,
        `response_time` decimal(10,2) DEFAULT NULL,
        `ssl_expiry_date` datetime DEFAULT NULL,
        `ssl_issuer` varchar(255) DEFAULT NULL,
        `ssl_days_remaining` int(11) DEFAULT NULL,
        `check_count` int(11) NOT NULL DEFAULT 0,
        `down_count` int(11) NOT NULL DEFAULT 0,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `url` (`url`),
        KEY `status` (`status`),
        KEY `last_checked` (`last_checked`)
    ) {$charset_collate};";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $success = ($wpdb->last_error === '');

    if (!$success) {
        lm_monitor_log('Failed to create database table: ' . $wpdb->last_error, 'error');
    }

    return $success;
}

/**
 * Get site by ID
 *
 * @param int $id Site ID
 * @return object|null Site object or null
 */
function lm_monitor_get_site($id) {
    global $wpdb;

    $id = lm_monitor_sanitize_id($id);
    if (!$id) {
        return null;
    }

    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM " . lm_monitor_get_table_name() . " WHERE id = %d LIMIT 1",
        $id
    ));
}

/**
 * Get all monitored sites with optional filters
 *
 * @param array $args Query arguments
 * @return array Array of site objects
 */
function lm_monitor_get_sites($args = array()) {
    global $wpdb;

    $defaults = array(
        'orderby' => 'created_at',
        'order' => 'DESC',
        'limit' => null,
        'status' => null
    );

    $args = wp_parse_args($args, $defaults);
    $table = lm_monitor_get_table_name();

    $sql = "SELECT * FROM `{$table}`";

    // Add WHERE clause if status filter
    if ($args['status']) {
        $sql .= $wpdb->prepare(" WHERE `status` = %s", $args['status']);
    }

    // Add ORDER BY
    $allowed_orderby = array('id', 'url', 'status', 'last_checked', 'created_at');
    $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
    $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
    $sql .= " ORDER BY `{$orderby}` {$order}";

    // Add LIMIT
    if ($args['limit']) {
        $sql .= $wpdb->prepare(" LIMIT %d", absint($args['limit']));
    }

    return $wpdb->get_results($sql);
}

/**
 * Get sites that need checking based on interval
 *
 * @param int $limit Number of sites to get
 * @param int $check_interval Check interval in minutes
 * @return array Array of site objects
 */
function lm_monitor_get_sites_for_checking($limit = 10, $check_interval = 5) {
    global $wpdb;

    $table = lm_monitor_get_table_name();
    $minutes_ago = date('Y-m-d H:i:s', strtotime("-{$check_interval} minutes"));

    $sql = $wpdb->prepare(
        "SELECT * FROM `{$table}` 
         WHERE `last_checked` IS NULL 
         OR `last_checked` < %s 
         ORDER BY `last_checked` ASC, `id` ASC 
         LIMIT %d",
        $minutes_ago,
        absint($limit)
    );

    return $wpdb->get_results($sql);
}

/**
 * Update site status and related data
 *
 * @param int $id Site ID
 * @param string $new_status New status
 * @param array $data Additional data to update
 * @return bool True on success
 */
function lm_monitor_update_status($id, $new_status, $data = array()) {
    global $wpdb;

    $id = lm_monitor_sanitize_id($id);
    if (!$id) {
        return false;
    }

    $table = lm_monitor_get_table_name();

    // Get current site data
    $site = lm_monitor_get_site($id);
    if (!$site) {
        return false;
    }

    $old_status = $site->status;

    // Prepare update data
    $update_data = array(
        'status' => $new_status,
        'last_checked' => current_time('mysql'),
        'updated_at' => current_time('mysql'),
        'check_count' => intval($site->check_count) + 1
    );

    // Add optional data
    if (isset($data['response_time'])) {
        $update_data['response_time'] = $data['response_time'];
    }
    if (isset($data['ssl_expiry_date'])) {
        $update_data['ssl_expiry_date'] = $data['ssl_expiry_date'];
    }
    if (isset($data['ssl_issuer'])) {
        $update_data['ssl_issuer'] = $data['ssl_issuer'];
    }
    if (isset($data['ssl_days_remaining'])) {
        $update_data['ssl_days_remaining'] = $data['ssl_days_remaining'];
    }

    // Increment down_count if site is down
    if ($new_status === 'DOWN' || strpos($new_status, 'ERROR') !== false) {
        $update_data['down_count'] = intval($site->down_count) + 1;
    }

    // Update database
    $updated = $wpdb->update(
        $table,
        $update_data,
        array('id' => $id),
        array('%s', '%s', '%s', '%d', '%f', '%s', '%s', '%d', '%d'),
        array('%d')
    );

    // Trigger status change action if status changed
    // Also trigger on first check if site is DOWN or has ERROR status
    $is_problem_status = ($new_status === 'DOWN' || strpos($new_status, 'ERROR') !== false);
    $status_actually_changed = ($old_status !== $new_status);
    $is_first_check_with_problem = ($old_status === null && $is_problem_status);
    
    if ($status_actually_changed && ($old_status !== null || $is_first_check_with_problem)) {
        do_action('lm_monitor_status_changed', $id, $site->url, $old_status, $new_status);
    }

    // Trigger SSL expiring action if needed
    if (isset($data['ssl_days_remaining']) && $data['ssl_days_remaining'] !== null) {
        $days = intval($data['ssl_days_remaining']);
        if ($days <= LM_MONITOR_SSL_WARNING_DAYS && $days > 0) {
            do_action('lm_monitor_ssl_expiring_soon', $id, $site->url, $days);
        }
    }

    return $updated !== false;
}

/**
 * Delete site from monitoring
 *
 * @param int $id Site ID
 * @return bool True on success
 */
function lm_monitor_delete_site($id) {
    global $wpdb;

    $id = lm_monitor_sanitize_id($id);
    if (!$id) {
        return false;
    }

    $table = lm_monitor_get_table_name();

    $deleted = $wpdb->delete(
        $table,
        array('id' => $id),
        array('%d')
    );

    return $deleted !== false;
}

/**
 * Add a new site to monitoring
 *
 * @param string $url Site URL (must be validated before calling)
 * @param string $email Optional notification email
 * @return int|false Insert ID on success, false on failure
 */
function lm_monitor_add_site($url, $email = '') {
    global $wpdb;

    $table = lm_monitor_get_table_name();

    $inserted = $wpdb->insert(
        $table,
        array(
            'url' => $url,
            'notify_email' => $email,
            'status' => null,
            'last_checked' => null,
            'response_time' => null,
            'ssl_expiry_date' => null,
            'ssl_issuer' => null,
            'ssl_days_remaining' => null,
            'check_count' => 0,
            'down_count' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%d', '%d', '%s', '%s')
    );

    if ($inserted === false) {
        lm_monitor_log('Database insert failed: ' . $wpdb->last_error, 'error');
        return false;
    }

    return $wpdb->insert_id;
}

/**
 * Check site and update status in one operation
 *
 * @param int $site_id Site ID
 * @param string $url Site URL
 * @return array|false Check result or false on failure
 */
function lm_monitor_check_and_update_site($site_id, $url) {
    $check_result = lm_monitor_check_website($url);

    if (!lm_monitor_is_valid_check_result($check_result)) {
        return false;
    }

    $updated = lm_monitor_update_status($site_id, $check_result['status'], array(
        'response_time' => $check_result['response_time'],
        'ssl_expiry_date' => $check_result['ssl_expiry_date'],
        'ssl_issuer' => $check_result['ssl_issuer'],
        'ssl_days_remaining' => $check_result['ssl_days_remaining']
    ));

    if (!$updated) {
        return false;
    }

    return $check_result;
}

/**
 * Check if URL already exists
 *
 * @param string $url URL to check
 * @return bool True if exists
 */
function lm_monitor_url_exists($url) {
    global $wpdb;

    $table = lm_monitor_get_table_name();
    $url = esc_url_raw($url);

    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `{$table}` WHERE `url` = %s",
        $url
    ));

    return intval($count) > 0;
}

/**
 * Get statistics
 *
 * @return array Statistics array
 */
function lm_monitor_get_stats() {
    global $wpdb;

    $table = lm_monitor_get_table_name();

    $stats = array(
        'total' => 0,
        'up' => 0,
        'down' => 0,
        'error' => 0,
        'unknown' => 0,
        'ssl_expiring_soon' => 0
    );

    // Get total count
    $stats['total'] = intval($wpdb->get_var("SELECT COUNT(*) FROM `{$table}`"));

    if ($stats['total'] === 0) {
        return $stats;
    }

    // Get status counts
    $stats['up'] = intval($wpdb->get_var(
        "SELECT COUNT(*) FROM `{$table}` WHERE `status` = 'UP'"
    ));

    $stats['down'] = intval($wpdb->get_var(
        "SELECT COUNT(*) FROM `{$table}` WHERE `status` = 'DOWN'"
    ));

    $stats['error'] = intval($wpdb->get_var(
        "SELECT COUNT(*) FROM `{$table}` WHERE `status` LIKE 'ERROR%'"
    ));

    $stats['unknown'] = intval($wpdb->get_var(
        "SELECT COUNT(*) FROM `{$table}` WHERE `status` IS NULL"
    ));

    // Get SSL expiring soon count
    $stats['ssl_expiring_soon'] = intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM `{$table}` 
         WHERE `ssl_days_remaining` IS NOT NULL 
         AND `ssl_days_remaining` <= %d 
         AND `ssl_days_remaining` > 0",
        LM_MONITOR_SSL_WARNING_DAYS
    )));

    return $stats;
}

/**
 * Maybe update table structure (for plugin updates)
 *
 * @return bool True on success
 */
function lm_monitor_maybe_update_table_structure() {
    // For now, just recreate table (safe because of IF NOT EXISTS)
    return lm_monitor_create_table();
}
