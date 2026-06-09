=== MailTally – Email Logger ===
Contributors: parag44
Tags: email log, mail logger, email debug, email testing, wp mail
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

MailTally helps you log, inspect, and debug outgoing emails from your WordPress site.

== Description ==

MailTally helps site administrators log outgoing emails sent through WordPress `wp_mail()`.

It is built for email debugging and troubleshooting. You can inspect recipients, subjects, message content, headers, attachments, send status, and error messages from a clean WordPress admin interface.

MailTally uses the WordPress `wp_mail_succeeded` and `wp_mail_failed` hooks. A successful status means WordPress and PHPMailer processed the send request. It does not guarantee inbox delivery.

Features include:

* Log outgoing WordPress email attempts.
* Track sent and failed statuses.
* Store recipients, subject, message, headers, attachments, and error messages.
* Filter logs by status, date range, and search terms.
* Preview email content with HTML and source tabs.
* Resend individual emails.
* Bulk delete logs.
* Send a test HTML email.
* Clear all logs.
* Export logs as CSV.
* Configure logging, retention, uninstall cleanup, and maximum logs.

This plugin does not configure SMTP settings. It logs and debugs emails sent through the standard WordPress mail flow.

== Installation ==

1. Upload the `parag-mail-inspector` folder to the `/wp-content/plugins/` directory.
2. Activate `MailTally – Email Logger` through the Plugins screen in WordPress.
3. Open `MailTally` in the WordPress admin menu.

== Frequently Asked Questions ==

= Does this plugin configure SMTP? =

No. MailTally logs and debugs WordPress emails only. It does not add SMTP sending configuration.

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
