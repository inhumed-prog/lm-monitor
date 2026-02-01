<?php
/**
 * Help & Info page view template
 */

if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="wrap lm-monitor-wrap">
	<h1>LM Monitor Documentation</h1>

	<div class="lm-monitor-help-container">

		<div class="lm-monitor-help-card">
			<h2>Overview</h2>
			<p>LM Monitor is a website monitoring plugin for agencies and web professionals. It tracks uptime, response times, and SSL certificate expiration for multiple websites from a single WordPress installation.</p>
			<p>When a monitored site goes down or returns an error, you receive notifications via email and optional webhook integrations (Slack, Discord, Microsoft Teams, etc.).</p>
		</div>

		<div class="lm-monitor-help-card">
			<h2>Features</h2>
			<table class="lm-monitor-info-table">
				<tr>
					<th>Uptime Monitoring</th>
					<td>Automatic checks every 5 minutes with UP/DOWN/ERROR status reporting</td>
				</tr>
				<tr>
					<th>Response Time</th>
					<td>Measures server response time in milliseconds for performance tracking</td>
				</tr>
				<tr>
					<th>SSL Monitoring</th>
					<td>Tracks certificate expiration and warns before certificates expire</td>
				</tr>
				<tr>
					<th>Email Alerts</th>
					<td>Sends notifications when status changes, with per-site email configuration</td>
				</tr>
				<tr>
					<th>Webhook Integration</th>
					<td>Native support for Slack, Discord, and generic webhooks (Zapier, Make, etc.)</td>
				</tr>
				<tr>
					<th>Manual Checks</th>
					<td>Test any website immediately without waiting for scheduled checks</td>
				</tr>
			</table>
		</div>

		<div class="lm-monitor-help-card">
			<h2>Getting Started</h2>

			<h3>Adding a Website</h3>
			<ol>
				<li>Navigate to <strong>LM Monitor</strong> in the admin menu</li>
				<li>Enter the full URL including protocol (e.g., <code>https://example.com</code>)</li>
				<li>Optionally specify an email address for notifications</li>
				<li>Click <strong>Add Website</strong></li>
			</ol>
			<p>The site will be checked automatically within 5 minutes. Use the <strong>Check now</strong> button for immediate testing.</p>

			<h3>Understanding Status Indicators</h3>
			<table class="lm-monitor-info-table">
				<tr>
					<th><span class="lm-monitor-badge up">UP</span></th>
					<td>Website responded with HTTP 2xx or 3xx status code</td>
				</tr>
				<tr>
					<th><span class="lm-monitor-badge down">DOWN</span></th>
					<td>Website is unreachable (connection failed, timeout, DNS error)</td>
				</tr>
				<tr>
					<th><span class="lm-monitor-badge error">ERROR 4xx/5xx</span></th>
					<td>Website responded but returned a client or server error</td>
				</tr>
				<tr>
					<th><span class="lm-monitor-badge unknown">UNKNOWN</span></th>
					<td>Website has not been checked yet</td>
				</tr>
			</table>

			<h3>Response Time Indicators</h3>
			<table class="lm-monitor-info-table">
				<tr>
					<th style="color: #10b981;">Green</th>
					<td>Fast response, under 1 second</td>
				</tr>
				<tr>
					<th style="color: #f59e0b;">Orange</th>
					<td>Slow response, 1-5 seconds</td>
				</tr>
				<tr>
					<th style="color: #ef4444;">Red</th>
					<td>Very slow response, over 5 seconds</td>
				</tr>
			</table>

			<h3>SSL Certificate Status</h3>
			<table class="lm-monitor-info-table">
				<tr>
					<th style="color: #10b981;">Green</th>
					<td>Certificate valid for more than 30 days</td>
				</tr>
				<tr>
					<th style="color: #f59e0b;">Orange</th>
					<td>Certificate expires within 30 days</td>
				</tr>
				<tr>
					<th style="color: #ef4444;">Red</th>
					<td>Certificate expires within 7 days or has expired</td>
				</tr>
			</table>
		</div>

		<div class="lm-monitor-help-card">
			<h2>Notifications</h2>

			<h3>Email Notifications</h3>
			<p>When a website's status changes, LM Monitor sends an email to:</p>
			<ol>
				<li>The site-specific email address (if configured when adding the site)</li>
				<li>The WordPress admin email (as fallback)</li>
			</ol>
			<p>Notifications are sent for status changes only, not for every check. This prevents inbox flooding.</p>

			<h3>Webhook Notifications</h3>
			<p>Configure a webhook URL in <strong>Settings</strong> to receive notifications in external services. LM Monitor automatically detects and formats messages for:</p>
			<ul>
				<li><strong>Discord</strong> - Rich embeds with color-coded severity</li>
				<li><strong>Slack</strong> - Formatted attachments with fields</li>
				<li><strong>Generic webhooks</strong> - Raw JSON for Zapier, Make, custom endpoints</li>
			</ul>
		</div>

		<div class="lm-monitor-help-card">
			<h2>Webhook Setup</h2>

			<h3>Discord</h3>
			<ol>
				<li>Open Server Settings in your Discord server</li>
				<li>Go to <strong>Integrations</strong> and click <strong>Webhooks</strong></li>
				<li>Click <strong>New Webhook</strong> and select a channel</li>
				<li>Copy the webhook URL</li>
				<li>Paste into <strong>LM Monitor &gt; Settings &gt; Webhook URL</strong></li>
				<li>Click <strong>Send Test Notification</strong> to verify</li>
			</ol>

			<h3>Slack</h3>
			<ol>
				<li>Go to <a href="https://api.slack.com/apps" target="_blank" rel="noopener">api.slack.com/apps</a></li>
				<li>Create a new app or select an existing one</li>
				<li>Enable <strong>Incoming Webhooks</strong></li>
				<li>Add a new webhook to your workspace and select a channel</li>
				<li>Copy the webhook URL and paste into LM Monitor settings</li>
			</ol>

			<h3>Generic Webhooks (Zapier, Make, etc.)</h3>
			<p>LM Monitor sends a POST request with JSON body. Create a webhook trigger in your automation tool and use the provided URL.</p>

			<h3>Webhook Payload</h3>
			<p>Status change notifications include the following data:</p>
			<pre><code>{
	"type": "status_change",
	"severity": "CRITICAL",
	"site_id": 1,
	"url": "https://example.com",
	"old_status": "UP",
	"new_status": "DOWN",
	"timestamp": "2025-01-31 12:30:00",
	"unix_timestamp": 1738327800,
	"site_name": "My WordPress Site"
}</code></pre>
			<p>Severity levels: <code>CRITICAL</code> (site down), <code>ERROR</code> (4xx/5xx response), <code>RESOLVED</code> (site recovered), <code>INFO</code> (other changes)</p>
		</div>

		<div class="lm-monitor-help-card">
			<h2>WordPress Cron</h2>
			<p>LM Monitor uses WordPress Cron for scheduled checks. Understanding how this works is important for reliable monitoring.</p>

			<h3>How WordPress Cron Works</h3>
			<p>WordPress Cron is not a true cron system. It only runs when someone visits your website. If your site has no visitors for an hour, no checks will run during that time.</p>
			<p>For production monitoring, you should set up a real server cron job.</p>

			<h3>Setting Up Server Cron (Recommended)</h3>
			<p>Step 1: Disable WordPress Cron by adding this to <code>wp-config.php</code>:</p>
			<pre><code>define('DISABLE_WP_CRON', true);</code></pre>

			<p>Step 2: Add a server cron job to trigger WordPress Cron every 5 minutes:</p>
			<pre><code>*/5 * * * * wget -q -O /dev/null https://your-site.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1</code></pre>

			<p>Alternative using curl:</p>
			<pre><code>*/5 * * * * curl -s https://your-site.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1</code></pre>

			<h3>Managed Hosting</h3>
			<p>Many managed WordPress hosts (WP Engine, Kinsta, Flywheel, etc.) automatically handle cron. Check your host's documentation or contact support to verify cron is running reliably.</p>

			<h3>External Cron Services</h3>
			<p>If you cannot set up server cron, use a free external service to ping your site regularly:</p>
			<ul>
				<li><a href="https://cron-job.org" target="_blank" rel="noopener">cron-job.org</a> (free)</li>
				<li><a href="https://www.easycron.com" target="_blank" rel="noopener">EasyCron</a> (free tier available)</li>
				<li>UptimeRobot (can double as external monitoring)</li>
			</ul>
			<p>Set the service to request <code>https://your-site.com/wp-cron.php?doing_wp_cron</code> every 5 minutes.</p>
		</div>

		<div class="lm-monitor-help-card">
			<h2>Troubleshooting</h2>

			<h3>Website shows DOWN but is actually online</h3>
			<ul>
				<li>The website may block automated requests or specific user agents</li>
				<li>A firewall or security plugin might be blocking the monitoring server</li>
				<li>The site may require authentication or have geographic restrictions</li>
				<li>Verify the URL is correct and accessible from your server</li>
			</ul>

			<h3>Not receiving email notifications</h3>
			<ul>
				<li>Check spam/junk folders</li>
				<li>Verify WordPress can send emails (test with a plugin like WP Mail SMTP)</li>
				<li>Confirm the notification email address is correct</li>
				<li>Check your server's mail logs for delivery errors</li>
			</ul>

			<h3>Webhook notifications not arriving</h3>
			<ul>
				<li>Use the <strong>Send Test Notification</strong> button to verify connectivity</li>
				<li>Check that the webhook URL is correct and the service is active</li>
				<li>Ensure your server can make outbound HTTPS requests</li>
				<li>Check for firewall rules blocking outbound connections</li>
			</ul>

			<h3>Automatic checks not running</h3>
			<ul>
				<li>Verify the cron event is scheduled (shown in the dashboard footer)</li>
				<li>Check if <code>DISABLE_WP_CRON</code> is set without a server cron replacement</li>
				<li>Low-traffic sites may have infrequent cron execution (see Cron section above)</li>
				<li>Some hosts limit or disable WordPress Cron</li>
			</ul>

			<h3>Cannot add a website</h3>
			<ul>
				<li>URL must include protocol (<code>http://</code> or <code>https://</code>)</li>
				<li>Localhost and private IP addresses are blocked for security</li>
				<li>Each URL can only be monitored once</li>
				<li>URL must be under 255 characters</li>
			</ul>
		</div>

		<div class="lm-monitor-help-card">
			<h2>Best Practices</h2>
			<ul>
				<li><strong>Monitor the homepage</strong> - Use the root domain rather than specific pages unless you need to monitor a particular endpoint</li>
				<li><strong>Use HTTPS URLs</strong> - This enables SSL certificate monitoring and is more representative of user experience</li>
				<li><strong>Set up server cron</strong> - Do not rely on WordPress Cron for production monitoring</li>
				<li><strong>Configure webhooks</strong> - Email can be slow or filtered; webhooks provide faster, more reliable alerts</li>
				<li><strong>Use site-specific emails</strong> - Route alerts for different clients to the appropriate contacts</li>
				<li><strong>Test after setup</strong> - Always verify webhooks work using the test button</li>
				<li><strong>Review regularly</strong> - Remove sites that are no longer active to keep your dashboard clean</li>
			</ul>
		</div>

		<div class="lm-monitor-help-card">
			<h2>Technical Reference</h2>
			<table class="lm-monitor-info-table">
				<tr>
					<th>Check Interval</th>
					<td>5 minutes (300 seconds)</td>
				</tr>
				<tr>
					<th>HTTP Timeout</th>
					<td>30 seconds</td>
				</tr>
				<tr>
					<th>Webhook Timeout</th>
					<td>10 seconds</td>
				</tr>
				<tr>
					<th>SSL Check Timeout</th>
					<td>10 seconds</td>
				</tr>
				<tr>
					<th>Success Codes</th>
					<td>HTTP 200-299 and 300-399</td>
				</tr>
				<tr>
					<th>SSL Warning Threshold</th>
					<td>30 days before expiration</td>
				</tr>
				<tr>
					<th>SSL Critical Threshold</th>
					<td>7 days before expiration</td>
				</tr>
				<tr>
					<th>Database Table</th>
					<td><code><?php echo esc_html($GLOBALS['wpdb']->prefix); ?>lm_monitor_sites</code></td>
				</tr>
				<tr>
					<th>Cron Hook</th>
					<td><code>lm_monitor_cron_event</code></td>
				</tr>
				<tr>
					<th>User Agent</th>
					<td><code>LM-Monitor/<?php echo esc_html(LM_MONITOR_VERSION); ?></code></td>
				</tr>
			</table>
		</div>

		<div class="lm-monitor-help-card">
			<h2>URL Restrictions</h2>
			<p>For security reasons, the following URLs cannot be monitored:</p>
			<ul>
				<li><code>localhost</code> and <code>127.0.0.1</code></li>
				<li>Private IP ranges (192.168.x.x, 10.x.x.x, 172.16-31.x.x)</li>
				<li>Reserved IP addresses</li>
			</ul>
			<p>This prevents the plugin from being used to probe internal networks.</p>
		</div>

		<div class="lm-monitor-help-card">
			<h2>Debug Information</h2>
			<?php
			$cron_status = lm_monitor_get_cron_status();
			?>
			<table class="lm-monitor-info-table">
				<tr>
					<th>Plugin Version</th>
					<td><?php echo esc_html(LM_MONITOR_VERSION); ?></td>
				</tr>
				<tr>
					<th>WordPress Version</th>
					<td><?php echo esc_html(get_bloginfo('version')); ?></td>
				</tr>
				<tr>
					<th>PHP Version</th>
					<td><?php echo esc_html(PHP_VERSION); ?></td>
				</tr>
				<tr>
					<th>Cron Scheduled</th>
					<td><?php echo $cron_status['is_scheduled'] ? 'Yes' : 'No'; ?></td>
				</tr>
				<tr>
					<th>Next Scheduled Check</th>
					<td><?php echo esc_html($cron_status['next_run_formatted']); ?></td>
				</tr>
				<tr>
					<th>WordPress Cron Enabled</th>
					<td><?php echo $cron_status['cron_enabled'] ? 'Yes' : 'No (DISABLE_WP_CRON is set)'; ?></td>
				</tr>
				<tr>
					<th>Timezone</th>
					<td><?php echo esc_html(wp_timezone_string()); ?></td>
				</tr>
			</table>
		</div>

	</div>
</div>

<style>
	.lm-monitor-help-container {
		max-width: 900px;
	}

	.lm-monitor-help-card {
		background: #fff;
		padding: 24px 30px;
		margin-bottom: 20px;
		border-radius: 6px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
		border: 1px solid #e0e0e0;
	}

	.lm-monitor-help-card h2 {
		margin: 0 0 20px 0;
		padding: 0 0 12px 0;
		color: #1d2327;
		font-size: 20px;
		font-weight: 600;
		border-bottom: 1px solid #e0e0e0;
	}

	.lm-monitor-help-card h3 {
		color: #1d2327;
		font-size: 15px;
		font-weight: 600;
		margin: 24px 0 12px 0;
	}

	.lm-monitor-help-card h3:first-of-type {
		margin-top: 0;
	}

	.lm-monitor-help-card p {
		color: #50575e;
		line-height: 1.6;
		margin: 0 0 12px 0;
	}

	.lm-monitor-help-card ul,
	.lm-monitor-help-card ol {
		color: #50575e;
		line-height: 1.6;
		margin: 0 0 12px 0;
		padding-left: 24px;
	}

	.lm-monitor-help-card li {
		margin-bottom: 6px;
	}

	.lm-monitor-help-card code {
		background: #f0f0f1;
		padding: 2px 6px;
		border-radius: 3px;
		font-size: 13px;
		font-family: Consolas, Monaco, monospace;
	}

	.lm-monitor-help-card pre {
		background: #f6f7f7;
		border: 1px solid #e0e0e0;
		border-left: 3px solid #2271b1;
		padding: 16px;
		margin: 12px 0;
		overflow-x: auto;
		border-radius: 0 4px 4px 0;
	}

	.lm-monitor-help-card pre code {
		background: none;
		padding: 0;
		font-size: 13px;
		line-height: 1.5;
	}

	.lm-monitor-help-card a {
		color: #2271b1;
		text-decoration: none;
	}

	.lm-monitor-help-card a:hover {
		color: #135e96;
		text-decoration: underline;
	}

	.lm-monitor-info-table {
		width: 100%;
		border-collapse: collapse;
		margin: 12px 0;
	}

	.lm-monitor-info-table th,
	.lm-monitor-info-table td {
		padding: 10px 12px;
		text-align: left;
		border-bottom: 1px solid #e0e0e0;
		vertical-align: top;
	}

	.lm-monitor-info-table th {
		font-weight: 600;
		color: #1d2327;
		width: 200px;
		background: #f9f9f9;
	}

	.lm-monitor-info-table td {
		color: #50575e;
	}

	.lm-monitor-info-table tr:last-child th,
	.lm-monitor-info-table tr:last-child td {
		border-bottom: none;
	}

	.lm-monitor-info-table code {
		font-size: 12px;
	}
</style>
