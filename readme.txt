
=== LM Monitor ===
Contributors: lukmeyer
Donate link: https://paypal.me/LukMeyer030
Tags: monitoring, uptime, ssl, alerts, webhook
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Monitor multiple websites for uptime, performance, and SSL certificate expiration. Get instant alerts via email, Slack, or Discord when issues occur.

== Description ==

LM Monitor is a lightweight yet powerful website monitoring solution built for agencies, freelancers, and web professionals who manage multiple websites.

Track uptime, response times, and SSL certificate expiration for all your client sites from a single WordPress dashboard. When something goes wrong, you will know immediately through email notifications or webhook integrations with Slack, Discord, and other services.

= Key Features =

* **Uptime Monitoring** - Automatic checks every 5 minutes to detect downtime
* **Response Time Tracking** - Monitor server performance with millisecond precision
* **SSL Certificate Monitoring** - Get warned before certificates expire
* **Email Alerts** - Receive notifications when a site goes down or recovers
* **Webhook Support** - Native integration with Slack, Discord, Microsoft Teams, and generic webhooks
* **Per-Site Notifications** - Set different alert recipients for each website
* **Manual Checks** - Test any site instantly with one click
* **Uptime Statistics** - Track reliability over time with uptime percentages
* **Clean Dashboard** - See the status of all your sites at a glance

= Who Is This For? =

* **Web Agencies** managing client websites
* **Freelancers** maintaining multiple projects
* **Site Owners** who want peace of mind
* **DevOps Teams** needing a simple monitoring solution

= How It Works =

1. Add a website URL to monitor
2. LM Monitor checks the site every 5 minutes
3. If the site goes down or returns an error, you get notified
4. When the site recovers, you get a recovery notification

= Webhook Integrations =

LM Monitor automatically formats notifications for popular services:

* **Slack** - Rich message attachments with color-coded severity
* **Discord** - Embedded messages with status information
* **Microsoft Teams** - Via generic webhook connector
* **Zapier / Make** - JSON payload for custom automations

= Privacy =

LM Monitor only stores the URLs you choose to monitor and their status data. No personal information is collected or transmitted to external servers except for the webhook notifications you configure.

== Installation ==

= Automatic Installation =

1. Go to Plugins > Add New in your WordPress admin
2. Search for "LM Monitor"
3. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Go to Plugins > Add New > Upload Plugin
3. Select the ZIP file and click "Install Now"
4. Activate the plugin

= After Activation =

1. Go to LM Monitor in the admin menu
2. Add your first website URL
3. (Optional) Configure webhook notifications in Settings
4. Click "Check now" to verify monitoring is working

== Frequently Asked Questions ==

= How often are websites checked? =

By default, all websites are checked every 5 minutes using WordPress Cron.

= Can I change the check interval? =

The current version uses a fixed 5-minute interval. Custom intervals will be available in a future update.

= Why does my site show as DOWN when it is actually online? =

This can happen if:

* Your site blocks automated requests or specific user agents
* A firewall or security plugin is blocking the monitoring server
* The site requires authentication
* There are geographic restrictions

Try adding your monitoring server IP to any whitelist or firewall rules.

= Will this slow down my WordPress site? =

No. The monitoring checks run in the background via WordPress Cron and do not affect your site frontend performance.

= Can I monitor non-WordPress sites? =

Yes. LM Monitor can monitor any publicly accessible website, regardless of the platform.

= How do I set up Slack notifications? =

1. Create an Incoming Webhook in your Slack workspace
2. Copy the webhook URL
3. Paste it into LM Monitor > Settings > Webhook URL
4. Click "Send Test Notification" to verify

= How do I set up Discord notifications? =

1. Go to your Discord server settings
2. Navigate to Integrations > Webhooks
3. Create a new webhook and copy the URL
4. Paste it into LM Monitor > Settings > Webhook URL

= What does the SSL monitoring do? =

LM Monitor checks the SSL certificate of HTTPS sites and tracks the expiration date. You will receive warnings when a certificate is within 30 days of expiring, with critical alerts at 7 days.

= Why are automatic checks not running? =

WordPress Cron only runs when someone visits your site. If your site has low traffic, checks may be delayed. For reliable monitoring, set up a real server cron job. See the Help page in the plugin for instructions.

= Can I monitor localhost or internal sites? =

No. For security reasons, localhost (127.0.0.1) and private IP ranges are blocked from monitoring.

= Is there a limit to how many sites I can monitor? =

There is no hard limit. However, monitoring many sites from a shared hosting environment may cause performance issues. For large-scale monitoring, consider using a VPS or dedicated server.

= Does this plugin send any data externally? =

The plugin only makes outbound requests to:

* The websites you choose to monitor (to check their status)
* Your configured webhook URL (to send notifications)

No data is sent to the plugin developer or any third parties.

== Screenshots ==

1. Main dashboard showing all monitored websites with status, response time, SSL info, and uptime
2. Adding a new website to monitor
3. Settings page with webhook configuration
4. Discord notification example
5. Slack notification example
6. Help and documentation page

== Changelog ==

= 2.0.0 =
Release Date: January 31, 2026

* Initial public release
* Uptime monitoring with 5-minute intervals
* Response time tracking
* SSL certificate expiration monitoring
* Email notifications for status changes
* Webhook support for Slack, Discord, and generic endpoints
* Per-site notification email configuration
* Manual check functionality
* Bulk check all sites
* Uptime percentage tracking
* Comprehensive help documentation
* Full internationalization support

== Upgrade Notice ==

= 2.0.0 =
Initial release of LM Monitor. Start monitoring your websites today.

== Additional Information ==

= Requirements =

* WordPress 5.8 or higher
* PHP 7.4 or higher
* PHP extensions: curl, openssl, json

= Support =

For support questions, please use the WordPress.org support forum for this plugin.

= Credits =

Developed by Luk Meyer.
