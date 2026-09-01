<?php
/**
 * Security Hardening
 *
 * Sensible defaults. None of these are silver bullets, but together
 * they reduce attack surface without breaking compatibility.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove WP version from <head> and RSS to avoid leaking exact version.
 */
function khobor_remove_version() {
	return '';
}
add_filter( 'the_generator', 'khobor_remove_version' );
remove_action( 'wp_head', 'wp_generator' );

/**
 * Strip version query strings from theme CSS/JS to improve caching.
 *
 * @param string $src Asset source.
 * @return string
 */
function khobor_strip_asset_versions( $src ) {
	if ( strpos( $src, 'ver=' ) ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
}
add_filter( 'style_loader_src', 'khobor_strip_asset_versions', 9999 );
add_filter( 'script_loader_src', 'khobor_strip_asset_versions', 9999 );

/**
 * Optionally disable XML-RPC. Off by default; admin can enable in Customizer.
 */
function khobor_maybe_disable_xmlrpc( $enabled ) {
	if ( khobor_option( 'disable_xmlrpc', false ) ) {
		return false;
	}
	return $enabled;
}
add_filter( 'xmlrpc_enabled', 'khobor_maybe_disable_xmlrpc' );

/**
 * Restrict REST API access to authenticated users for sensitive endpoints,
 * but keep public read access for news content. Themes shouldn't break the
 * site - we only restrict /users for unauthenticated requests.
 *
 * @param mixed $result Result of authentication check.
 * @return mixed
 */
function khobor_restrict_rest_users( $result ) {
	if ( ! empty( $result ) ) {
		return $result;
	}

	// Only act on /wp/v2/users requests by unauthenticated users.
	if ( ! is_user_logged_in() ) {
		$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? $GLOBALS['wp']->query_vars['rest_route'] : '';
		if ( false !== strpos( $route, '/wp/v2/users' ) ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You are not currently logged in.', 'khobor' ),
				array( 'status' => 401 )
			);
		}
	}
	return $result;
}
add_filter( 'rest_authentication_errors', 'khobor_restrict_rest_users' );

/**
 * Add basic security headers via wp_headers filter.
 *
 * @param array $headers Existing headers.
 * @return array
 */
function khobor_security_headers( $headers ) {
	$headers['X-Content-Type-Options']   = 'nosniff';
	$headers['X-Frame-Options']          = 'SAMEORIGIN';
	$headers['Referrer-Policy']          = 'strict-origin-when-cross-origin';
	$headers['Permissions-Policy']       = 'interest-cohort=()';
	return $headers;
}
add_filter( 'wp_headers', 'khobor_security_headers' );

/**
 * Disable file editing from admin (prevents an attacker with stolen
 * admin creds from injecting code via the theme/plugin editor).
 * Site owners can override by setting DISALLOW_FILE_EDIT to false in wp-config.
 */
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}
