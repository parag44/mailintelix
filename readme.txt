=== MailIntelix ===
Contributors: mailintelix
Tags: email log, mail logging, wp mail, email debugging, email monitor
Requires at least: 5.9
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern email logging, debugging, and delivery insights for WordPress.

== Description ==

MailIntelix logs emails sent through WordPress `wp_mail()` so administrators can debug email content, recipients, headers, attachments, and failures.

MailIntelix uses the WordPress `wp_mail_succeeded` and `wp_mail_failed` hooks. A successful status means WordPress and PHPMailer processed the send request; it does not guarantee inbox delivery.

Features:

* Log every WordPress email attempt.
* Track sent and failed statuses.
* Store recipients, subject, message, headers, attachments, and error messages.
* Filter logs by status, date range, and search terms.
* Preview email content in a modal with HTML and source tabs.
* Resend individual emails.
* Bulk delete logs.
* Send a test email.
* Clear all logs.
* Export logs as CSV.
* Configure logging, retention, uninstall cleanup, and maximum logs.

== Installation ==

1. Upload the `mailintelix` folder to `/wp-content/plugins/`.
2. Activate MailIntelix from the Plugins screen.
3. Open MailIntelix in the WordPress admin menu.

== Frequently Asked Questions ==

= Does MailIntelix configure SMTP? =

No. MailIntelix logs and debugs WordPress emails only. SMTP configuration may be added separately in the future.

= Does “Sent” mean delivered to inbox? =

No. “Sent” means WordPress/PHPMailer processed the send request successfully. Mail servers and inbox providers can still reject or filter email later.

== Changelog ==

= 1.0.0 =
* Initial release.
