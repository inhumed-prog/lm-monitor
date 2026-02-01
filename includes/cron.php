<?php
/**
 * Cron scheduling and execution with batch processing
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Register custom cron intervals
 */
add_filter('cron_schedules', 'lm_monitor_cron_schedules');

function lm_monitor_cron_schedules($schedules) {
	// 5 minutes interval
	if (!isset($schedules['five_minutes'])) {
		$schedules['five_minutes'] = array(
				'interval' => LM_MONITOR_CRON_FIVE_MINUTES,
				'display' => __('Every 5 Minutes', 'lm-monitor')
		);
	}

	// 1 minute interval (for Pro plans in future)
	if (!isset($schedules['one_minute'])) {
		$schedules['one_minute'] = array(
				'interval' => LM_MONITOR_CRON_ONE_MINUTE,
				'display' => __('Every Minute', 'lm-monitor')
		);
	}

	// 30 seconds interval (for Agency plans in future)
	if (!isset($schedules['thirty_seconds'])) {
		$schedules['thirty_seconds'] = array(
				'interval' => LM_MONITOR_CRON_THIRTY_SECONDS,
				'display' => __('Every 30 Seconds', 'lm-monitor')
		);
	}

	return $schedules;
}

/**
 * Schedule cron on activation
 */
function lm_monitor_schedule_cron() {
	// Clear existing schedule first
	lm_monitor_clear_cron();

	// Get check interval from settings
	$interval = lm_monitor_get_setting('check_interval', LM_MONITOR_DEFAULT_CHECK_INTERVAL);

	// Determine schedule name
	$schedule = 'five_minutes'; // default
	if ($interval <= 0.5) {
		$schedule = 'thirty_seconds';
	} elseif ($interval == 1) {
		$schedule = 'one_minute';
	}

	// Schedule event
	if (!wp_next_scheduled('lm_monitor_cron_event')) {
		$scheduled = wp_schedule_event(time(), $schedule, 'lm_monitor_cron_event');

		if ($scheduled === false) {
			lm_monitor_log('Failed to schedule cron event', 'error');
		} else {
			lm_monitor_log('Cron scheduled with interval: ' . $schedule);
		}
	}
}

/**
 * Clear cron on deactivation
 */
function lm_monitor_clear_cron() {
	$timestamp = wp_next_scheduled('lm_monitor_cron_event');

	if ($timestamp) {
		wp_unschedule_event($timestamp, 'lm_monitor_cron_event');
	}

	// Also clear any lingering scheduled events
	wp_clear_scheduled_hook('lm_monitor_cron_event');

	lm_monitor_log('Cron events cleared');
}

/**
 * Cron callback - runs every interval
 */
add_action('lm_monitor_cron_event', 'lm_monitor_run_checks');

/**
 * Run monitoring checks for sites (with batch processing)
 */
function lm_monitor_run_checks() {
	// Prevent concurrent cron runs
	if (lm_monitor_is_cron_running()) {
		lm_monitor_log('Cron already running, skipping this execution');
		return;
	}

	// Set lock
	lm_monitor_set_cron_lock();

	try {
		// Get settings
		$check_interval = lm_monitor_get_setting('check_interval', LM_MONITOR_DEFAULT_CHECK_INTERVAL);

		// Calculate batch size based on check interval
		$batch_size = LM_MONITOR_BATCH_SIZE_DEFAULT;
		if ($check_interval <= 1) {
			$batch_size = LM_MONITOR_BATCH_SIZE_FAST;
		} elseif ($check_interval <= 0.5) {
			$batch_size = LM_MONITOR_BATCH_SIZE_VERY_FAST;
		}

		// Get sites that need checking
		$sites = lm_monitor_get_sites_for_checking($batch_size, $check_interval);

		if (empty($sites)) {
			lm_monitor_log('No sites need checking at this time');
			lm_monitor_release_cron_lock();
			return;
		}

		lm_monitor_log(sprintf(
				'Starting check of %d sites (batch size: %d)',
				count($sites),
				$batch_size
		));

		$start_time = microtime(true);
		$checked_count = 0;
		$failed_count = 0;

		foreach ($sites as $site) {
			try {
				// Check the website
				$check_result = lm_monitor_check_website($site->url);

				// Validate result
				if (!lm_monitor_is_valid_check_result($check_result)) {
					lm_monitor_log('Invalid check result for site ID ' . $site->id, 'error');
					$failed_count++;
					continue;
				}

				// Update database with all data
				$update_success = lm_monitor_update_status($site->id, $check_result['status'], array(
						'response_time' => $check_result['response_time'],
						'ssl_expiry_date' => $check_result['ssl_expiry_date'],
						'ssl_issuer' => $check_result['ssl_issuer'],
						'ssl_days_remaining' => $check_result['ssl_days_remaining']
				));

				if ($update_success) {
					$checked_count++;
				} else {
					$failed_count++;
				}

				// Log result (only in debug mode to avoid log spam)
				if (lm_monitor_is_debug()) {
					lm_monitor_log(sprintf(
							'Checked %s - Status: %s, Time: %sms, SSL: %s days',
							$site->url,
							$check_result['status'],
							$check_result['response_time'] !== null ? round($check_result['response_time']) : 'N/A',
							$check_result['ssl_days_remaining'] !== null ? $check_result['ssl_days_remaining'] : 'N/A'
					));
				}

			} catch (Exception $e) {
				lm_monitor_log('Exception while checking site ID ' . $site->id . ' - ' . $e->getMessage(), 'error');
				$failed_count++;
			}
		}

		$end_time = microtime(true);
		$duration = round(($end_time - $start_time), 2);

		// Log summary
		lm_monitor_log(sprintf(
				'Batch complete - Checked: %d, Failed: %d, Duration: %ss',
				$checked_count,
				$failed_count,
				$duration
		));

		// Store last run info
		update_option('lm_monitor_last_cron_run', array(
				'timestamp' => current_time('mysql'),
				'checked' => $checked_count,
				'failed' => $failed_count,
				'duration' => $duration
		), false);

	} catch (Exception $e) {
		lm_monitor_log('Critical error in cron execution - ' . $e->getMessage(), 'error');
	} finally {
		// Always release lock
		lm_monitor_release_cron_lock();
	}
}

/**
 * Check if cron is currently running
 *
 * @return bool True if running
 */
function lm_monitor_is_cron_running() {
	return lm_monitor_get_transient('cron_lock') !== false;
}

/**
 * Set cron lock to prevent concurrent runs
 */
function lm_monitor_set_cron_lock() {
	lm_monitor_set_transient('cron_lock', time(), LM_MONITOR_CRON_LOCK_DURATION);
}

/**
 * Release cron lock
 */
function lm_monitor_release_cron_lock() {
	lm_monitor_delete_transient('cron_lock');
}

/**
 * Manual trigger for all checks (admin function)
 *
 * @return array Results summary
 */
function lm_monitor_manual_check_all() {
	// Security check
	lm_monitor_verify_admin_access();

	// Get all sites
	$sites = lm_monitor_get_sites();

	if (empty($sites)) {
		return array(
				'success' => false,
				'message' => __('No sites to check', 'lm-monitor')
		);
	}

	$total = count($sites);
	$checked = 0;
	$failed = 0;

	foreach ($sites as $site) {
		try {
			$check_result = lm_monitor_check_website($site->url);

			if (lm_monitor_is_valid_check_result($check_result)) {
				lm_monitor_update_status($site->id, $check_result['status'], array(
						'response_time' => $check_result['response_time'],
						'ssl_expiry_date' => $check_result['ssl_expiry_date'],
						'ssl_issuer' => $check_result['ssl_issuer'],
						'ssl_days_remaining' => $check_result['ssl_days_remaining']
				));
				$checked++;
			} else {
				$failed++;
			}
		} catch (Exception $e) {
			$failed++;
		}
	}

	return array(
			'success' => true,
			'total' => $total,
			'checked' => $checked,
			'failed' => $failed,
			'message' => sprintf(
					__('Checked %d of %d sites. Failed: %d', 'lm-monitor'),
					$checked,
					$total,
					$failed
			)
	);
}

/**
 * Get cron status information
 *
 * @return array Status information
 */
function lm_monitor_get_cron_status() {
	$next_run = wp_next_scheduled('lm_monitor_cron_event');
	$last_run = get_option('lm_monitor_last_cron_run', null);
	$is_running = lm_monitor_is_cron_running();

	// Format next run time - wp_next_scheduled returns UTC timestamp
	// We need to convert it to local time for display
	$next_run_formatted = __('Not scheduled', 'lm-monitor');
	if ($next_run !== false) {
		// Convert UTC timestamp to local time using WordPress timezone
		$next_run_formatted = wp_date(
			get_option('date_format') . ' ' . get_option('time_format'),
			$next_run
		);
	}

	return array(
			'is_scheduled' => $next_run !== false,
			'next_run' => $next_run ? $next_run : null,
			'next_run_formatted' => $next_run_formatted,
			'last_run' => $last_run,
			'is_running' => $is_running,
			'cron_enabled' => !defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON
	);
}

/**
 * Display cron status in admin (for debugging)
 */
add_action('admin_notices', 'lm_monitor_cron_status_notice');

function lm_monitor_cron_status_notice() {
	// Only show on LM Monitor pages
	$screen = get_current_screen();
	if (!$screen || strpos($screen->id, 'lm-monitor') === false) {
		return;
	}

	// Only show to admins
	if (!current_user_can('manage_options')) {
		return;
	}

	// Check if WP Cron is disabled
	if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e('LM Monitor Warning:', 'lm-monitor'); ?></strong>
				<?php esc_html_e('WordPress Cron is disabled. Automatic monitoring checks will not run.', 'lm-monitor'); ?>
				<a href="<?php echo esc_url(lm_monitor_admin_url('help')); ?>">
					<?php esc_html_e('Learn more', 'lm-monitor'); ?>
				</a>
			</p>
		</div>
		<?php
	}

	// Check if cron is scheduled
	if (!wp_next_scheduled('lm_monitor_cron_event')) {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e('LM Monitor Error:', 'lm-monitor'); ?></strong>
				<?php esc_html_e('Monitoring cron is not scheduled. Automatic checks are disabled.', 'lm-monitor'); ?>
				<a href="#" onclick="location.reload(); return false;">
					<?php esc_html_e('Reload page to fix', 'lm-monitor'); ?>
				</a>
			</p>
		</div>
		<?php
	}
}

/**
 * Add cron status to admin footer (for debugging)
 */
add_filter('admin_footer_text', 'lm_monitor_admin_footer_cron_info', 20);

function lm_monitor_admin_footer_cron_info($text) {
	// Only on LM Monitor pages
	$screen = get_current_screen();
	if (!$screen || strpos($screen->id, 'lm-monitor') === false) {
		return $text;
	}

	// Only for admins
	if (!current_user_can('manage_options')) {
		return $text;
	}

	$status = lm_monitor_get_cron_status();

	if (!$status['is_scheduled']) {
		return $text;
	}

	$cron_info = sprintf(
			__('Next check: %s', 'lm-monitor'),
			$status['next_run_formatted']
	);

	if ($status['last_run']) {
		$cron_info .= ' | ' . sprintf(
						__('Last run: %s (%d checked, %d failed)', 'lm-monitor'),
						$status['last_run']['timestamp'],
						$status['last_run']['checked'],
						$status['last_run']['failed']
				);
	}

	return $text . ' | ' . $cron_info;
}
