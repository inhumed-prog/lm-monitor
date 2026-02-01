<?php
/**
 * Main page view template with improved UI and security
 */

if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="wrap lm-monitor-wrap">
	<h1>
		<?php esc_html_e('LM Monitor', 'lm-monitor'); ?>
		<span class="lm-monitor-version">v<?php echo esc_html(LM_MONITOR_VERSION); ?></span>
	</h1>

	<!-- DEBUG INFO -->
	<?php if (isset($debug_info) && !empty($debug_info)): ?>
		<div class="notice notice-info" style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 15px; margin: 20px 0;">
			<p style="margin: 0 0 10px 0; font-weight: bold; font-size: 16px;">🔍 DEBUG INFO</p>
			<pre style="background: #fff; padding: 12px; border: 1px solid #ccc; border-radius: 4px; overflow: auto; max-height: 500px; font-size: 13px; line-height: 1.6; margin: 0; font-family: 'Courier New', monospace; white-space: pre-wrap;"><?php
				foreach ($debug_info as $line) {
					echo esc_html($line) . "\n";
				}
				?></pre>
		</div>
	<?php endif; ?>

	<?php if (!empty($error_message)): ?>
		<div class="notice notice-error is-dismissible">
			<p><strong><?php esc_html_e('Error:', 'lm-monitor'); ?></strong> <?php echo esc_html($error_message); ?></p>
		</div>
	<?php endif; ?>

	<?php if (!empty($success_message)): ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html($success_message); ?></p>
		</div>
	<?php endif; ?>

	<?php
	// Display cron warning if WP Cron is disabled
	if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON):
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e('Warning:', 'lm-monitor'); ?></strong>
				<?php esc_html_e('WordPress Cron is disabled on this site. Automatic monitoring checks will not run.', 'lm-monitor'); ?>
				<a href="<?php echo esc_url(admin_url('admin.php?page=lm-monitor-help#cron-issues')); ?>">
					<?php esc_html_e('Learn how to fix this', 'lm-monitor'); ?>
				</a>
			</p>
		</div>
	<?php endif; ?>

	<!-- Statistics Dashboard -->
	<?php if (!empty($sites)): ?>
		<div class="lm-monitor-stats">
			<div class="lm-monitor-stat-card">
				<h3><?php esc_html_e('Total Sites', 'lm-monitor'); ?></h3>
				<div class="stat-value"><?php echo intval($stats['total']); ?></div>
			</div>

			<div class="lm-monitor-stat-card stat-up">
				<h3><?php esc_html_e('Online', 'lm-monitor'); ?></h3>
				<div class="stat-value" style="color: #10b981;">
					<?php echo intval($stats['up']); ?>
				</div>
			</div>

			<div class="lm-monitor-stat-card stat-down">
				<h3><?php esc_html_e('Offline', 'lm-monitor'); ?></h3>
				<div class="stat-value" style="color: #ef4444;">
					<?php echo intval($stats['down']); ?>
				</div>
			</div>

			<?php if ($stats['ssl_expiring_soon'] > 0): ?>
				<div class="lm-monitor-stat-card stat-warning">
					<h3><?php esc_html_e('SSL Expiring', 'lm-monitor'); ?></h3>
					<div class="stat-value" style="color: #f59e0b;">
						<?php echo intval($stats['ssl_expiring_soon']); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- Add Website Form -->
	<h2><?php esc_html_e('Add Website', 'lm-monitor'); ?></h2>
	<form method="post" id="lm-monitor-add-form" class="lm-monitor-form" action="">
		<?php wp_nonce_field('lm_monitor_add_site'); ?>
		<input type="hidden" name="lm_monitor_action" value="add_site">

		<div class="form-row">
			<label for="lm_monitor_url" class="screen-reader-text"><?php esc_html_e('Website URL', 'lm-monitor'); ?></label>
			<input
					type="url"
					name="url"
					id="lm_monitor_url"
					required
					placeholder="https://example.com"
					class="regular-text"
					value=""
			>

			<label for="lm_monitor_email" class="screen-reader-text"><?php esc_html_e('Notification email', 'lm-monitor'); ?></label>
			<input
					type="email"
					name="email"
					id="lm_monitor_email"
					placeholder="<?php esc_attr_e('alert@email.com (optional)', 'lm-monitor'); ?>"
					class="regular-text"
					value=""
			>

			<button class="button button-primary" name="add_site" type="submit" value="1">
				<?php esc_html_e('Add Website', 'lm-monitor'); ?>
			</button>
		</div>

		<p class="description">
			<?php esc_html_e('Enter the full URL including http:// or https://. The optional email will receive alerts for this specific website.', 'lm-monitor'); ?>
		</p>
	</form>

	<hr>

	<!-- Monitored Websites -->
	<div class="lm-monitor-header">
		<h2>
			<?php
			printf(
					esc_html__('Monitored Websites (%d)', 'lm-monitor'),
					count($sites)
			);
			?>
		</h2>

		<?php if (!empty($sites)): ?>
			<button
					class="button lm-monitor-bulk-check"
					type="button"
					title="<?php esc_attr_e('Check all websites now', 'lm-monitor'); ?>"
			>
				<?php esc_html_e('Check All Now', 'lm-monitor'); ?>
			</button>
		<?php endif; ?>
	</div>

	<?php if (!empty($cron_status) && $cron_status['is_scheduled']): ?>
		<p class="description lm-monitor-cron-info">
			<?php
			printf(
					esc_html__('Next automatic check: %s', 'lm-monitor'),
					'<strong>' . esc_html($cron_status['next_run_formatted']) . '</strong>'
			);
			?>
		</p>
	<?php endif; ?>

	<div class="table-container">
		<table class="widefat striped lm-monitor-table">
			<thead>
			<tr>
				<th><?php esc_html_e('URL', 'lm-monitor'); ?></th>
				<th><?php esc_html_e('Status', 'lm-monitor'); ?></th>
				<th><?php esc_html_e('Performance', 'lm-monitor'); ?></th>
				<th><?php esc_html_e('SSL Certificate', 'lm-monitor'); ?></th>
				<th><?php esc_html_e('Uptime', 'lm-monitor'); ?></th>
				<th><?php esc_html_e('Notify Email', 'lm-monitor'); ?></th>
				<th><?php esc_html_e('Last Checked', 'lm-monitor'); ?></th>
				<th><?php esc_html_e('Actions', 'lm-monitor'); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if (!empty($sites)): ?>
				<?php foreach ($sites as $site): ?>
					<tr data-id="<?php echo absint($site->id); ?>">
						<td class="site-url">
							<a href="<?php echo esc_url($site->url); ?>"
							   target="_blank"
							   rel="noopener noreferrer"
							   title="<?php esc_attr_e('Open website in new tab', 'lm-monitor'); ?>">
								<?php echo esc_html($site->url); ?>
							</a>
						</td>

						<td class="status">
							<?php echo lm_monitor_render_status($site->status); ?>
						</td>

						<td class="response-time">
							<?php echo lm_monitor_render_response_time($site->response_time); ?>
						</td>

						<td class="ssl-expiry">
							<?php echo lm_monitor_render_ssl_expiry($site->ssl_days_remaining, $site->ssl_expiry_date); ?>
						</td>

						<td class="uptime">
							<?php
							$uptime = lm_monitor_calculate_uptime($site);
							echo lm_monitor_render_uptime($uptime);
							?>
						</td>

						<td class="notify-email">
							<?php
							if (!empty($site->notify_email)) {
								printf(
										'<span style="color: #4f46e5;" title="%s">✉ %s</span>',
										esc_attr($site->notify_email),
										esc_html($site->notify_email)
								);
							} else {
								echo '<span style="color: #9ca3af;">—</span>';
							}
							?>
						</td>

						<td class="last-checked">
							<?php
							if ($site->last_checked) {
								echo '<span title="' . esc_attr($site->last_checked) . '">' .
										esc_html(lm_monitor_time_ago($site->last_checked)) . '</span>';
							} else {
								esc_html_e('Never', 'lm-monitor');
							}
							?>
						</td>

						<td class="actions">
							<div class="lm-monitor-action-buttons">
								<button
										class="button lm-monitor-check"
										data-id="<?php echo absint($site->id); ?>"
										type="button"
										title="<?php esc_attr_e('Check this website now', 'lm-monitor'); ?>"
								>
									<?php esc_html_e('Check now', 'lm-monitor'); ?>
								</button>
								<button
										class="button lm-monitor-delete"
										data-id="<?php echo absint($site->id); ?>"
										type="button"
										title="<?php esc_attr_e('Remove from monitoring', 'lm-monitor'); ?>"
								>
									<?php esc_html_e('Delete', 'lm-monitor'); ?>
								</button>
							</div>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="8" class="no-sites">
						<?php esc_html_e('No websites added yet. Add your first website above!', 'lm-monitor'); ?>
					</td>
				</tr>
			<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if (!empty($sites)): ?>
		<div class="lm-monitor-footer-info">
			<p class="description">
				<?php
				printf(
						esc_html__('Monitoring %d websites. Total checks performed: %d', 'lm-monitor'),
						count($sites),
						array_sum(array_column($sites, 'check_count'))
				);
				?>
			</p>
		</div>
	<?php endif; ?>
</div>

<style>
	.lm-monitor-version {
		font-size: 14px;
		color: #666;
		font-weight: normal;
		margin-left: 10px;
	}

	.lm-monitor-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 10px;
	}

	.lm-monitor-header h2 {
		margin: 0;
	}

	.lm-monitor-cron-info {
		margin-top: -10px;
		margin-bottom: 15px;
	}

	.lm-monitor-form {
		background: white;
		padding: 20px;
		border-radius: 8px;
		box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		border: 1px solid #e5e7eb;
	}

	.lm-monitor-form .form-row {
		display: flex;
		gap: 10px;
		align-items: center;
		flex-wrap: wrap;
	}

	.lm-monitor-form input.regular-text {
		min-width: 300px;
		flex: 1;
	}

	.lm-monitor-footer-info {
		margin-top: 20px;
		padding-top: 20px;
		border-top: 1px solid #e5e7eb;
	}

	.lm-monitor-table .site-url {
		font-weight: 500;
	}

	.no-sites {
		text-align: center;
		padding: 40px 20px !important;
		color: #6b7280;
		font-style: italic;
	}

	@media screen and (max-width: 782px) {
		.lm-monitor-form .form-row {
			flex-direction: column;
		}

		.lm-monitor-form input.regular-text {
			width: 100%;
			min-width: 100%;
		}

		.lm-monitor-header {
			flex-direction: column;
			align-items: flex-start;
			gap: 10px;
		}
	}
</style>

<script>
	jQuery(document).ready(function($) {
		'use strict';

		/**
		 * Bulk check all sites
		 */
		$('.lm-monitor-bulk-check').on('click', function(e) {
			e.preventDefault();

			var $btn = $(this);
			var originalText = $btn.text();

			if ($btn.prop('disabled')) {
				return;
			}

			if (!confirm('<?php esc_attr_e('Check all websites now? This may take a few minutes.', 'lm-monitor'); ?>')) {
				return;
			}

			$btn.prop('disabled', true).text('<?php esc_attr_e('Checking...', 'lm-monitor'); ?>');

			$.ajax({
				url: LMMonitor.ajax_url,
				type: 'POST',
				data: {
					action: 'lm_monitor_bulk_check',
					nonce: LMMonitor.nonce
				},
				timeout: 120000, // 2 minutes
				success: function(response) {
					if (response.success) {
						alert(response.data.message);
						location.reload();
					} else {
						alert('<?php esc_attr_e('Error:', 'lm-monitor'); ?> ' + (response.data.message || '<?php esc_attr_e('Unknown error', 'lm-monitor'); ?>'));
					}
				},
				error: function() {
					alert('<?php esc_attr_e('Network error. Please try again.', 'lm-monitor'); ?>');
				},
				complete: function() {
					$btn.prop('disabled', false).text(originalText);
				}
			});
		});
	});
</script>
