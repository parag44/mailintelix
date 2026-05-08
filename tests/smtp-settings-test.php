<?php
/**
 * Lightweight SMTP settings test.
 *
 * @package Simple_Mail_Logger
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

function sanitize_text_field( $value ) {
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', wp_strip_all_tags( (string) $value ) ) );
}

function wp_strip_all_tags( $value ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Test stub for WordPress' wp_strip_all_tags().
	return preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', strip_tags( $value ) );
}

function sanitize_email( $value ) {
	return filter_var( trim( (string) $value ), FILTER_SANITIZE_EMAIL );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function absint( $value ) {
	return abs( (int) $value );
}

require dirname( __DIR__ ) . '/includes/class-simple-mail-logger-smtp.php';

$raw = array(
	'smtp_enabled'    => '1',
	'smtp_host'       => " smtp.example.com \n",
	'smtp_port'       => '999999',
	'smtp_encryption' => 'bad',
	'smtp_auth'       => '1',
	'smtp_username'   => " user@example.com \n",
	'smtp_password'   => " secret \n",
	'smtp_from_email' => ' sender@example.com ',
	'smtp_from_name'  => ' Example Site ',
);

$settings = Simple_Mail_Logger_SMTP::sanitize_settings( $raw, array() );

assert( 1 === $settings['smtp_enabled'] );
assert( 'smtp.example.com' === $settings['smtp_host'] );
assert( 587 === $settings['smtp_port'] );
assert( 'none' === $settings['smtp_encryption'] );
assert( 1 === $settings['smtp_auth'] );
assert( 'user@example.com' === $settings['smtp_username'] );
assert( 'secret' === $settings['smtp_password'] );
assert( 'sender@example.com' === $settings['smtp_from_email'] );
assert( 'Example Site' === $settings['smtp_from_name'] );

echo "smtp settings test passed\n";
