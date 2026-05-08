=== Simple Mail Logger – Log & Debug Emails ===
Contributors: parag44
Tags: email log, mail logger, email debug, email testing, wp mail
Requires at least: 5.9
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simple Mail Logger helps you log, inspect, and debug outgoing emails from your WordPress site.

== Description ==

Simple Mail Logger helps site administrators log outgoing emails sent through WordPress `wp_mail()`.

It is built for email debugging and troubleshooting. You can inspect recipients, subjects, message content, headers, attachments, send status, and error messages from a clean WordPress admin interface.

Simple Mail Logger uses the WordPress `wp_mail_succeeded` and `wp_mail_failed` hooks. A successful status means WordPress and PHPMailer processed the send request. It does not guarantee inbox delivery.

Features include:

* Log outgoing WordPress email attempts.
* Track sent and failed statuses.
* Store recipients, subject, message, headers, attachments, and error messages.
* Filter logs by status, date range, and search terms.
* Preview email content with HTML and source tabs.
* Resend individual emails.
* Bulk delete logs.
* Send a test HTML email.
* Send emails through your own SMTP server when enabled.
* Check whether the configured SMTP connection can be established.
* Clear all logs.
* Export logs as CSV.
* Configure logging, retention, uninstall cleanup, and maximum logs.

Email logging works whether SMTP sending is enabled or WordPress is using its default mail flow.

== Installation ==

1. Upload the `simple-mail-logger` folder to the `/wp-content/plugins/` directory.
2. Activate `Simple Mail Logger – Log & Debug Emails` through the Plugins screen in WordPress.
3. Open `Simple Mail Logger` in the WordPress admin menu.

== Frequently Asked Questions ==

= Does this plugin support SMTP? =

Yes. SMTP sending can be enabled from the Settings screen. When SMTP is disabled, WordPress continues using its default mail flow. In both cases, Simple Mail Logger continues logging outgoing email attempts.

= Does "Sent" mean delivered to the inbox? =

No. "Sent" means WordPress and PHPMailer processed the send request successfully. Mail servers and inbox providers can still reject, bounce, or filter email later.

= Can I export email logs? =

Yes. The Tools screen includes a CSV export for stored email logs.

== Screenshots ==

1. Email logs screen with filters, statuses, and actions.
2. Email preview modal with HTML and source views.
3. Settings screen for logging, retention, and cleanup.
4. Tools screen for test email, CSV export, and clearing logs.

== Changelog ==

= 1.0.0 =
* Initial release.
