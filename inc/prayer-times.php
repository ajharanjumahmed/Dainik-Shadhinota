<?php
/**
 * Prayer Times
 *
 * Fetches daily prayer times from the free Aladhan public API
 * and caches them for 12 hours. Widget reads from this cache.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch today's prayer times for the configured city.
 *
 * @param string $city    City. Defaults to Customizer value.
 * @param string $country Country. Defaults to Customizer value.
 * @param int    $method  Calculation method. Defaults to Customizer value.
 * @return array|WP_Error
 */
function khobor_fetch_prayer_times( $city = '', $country = '', $method = 0 ) {
	$city    = $city    ?: khobor_option( 'prayer_city', 'Dhaka' );
	$country = $country ?: khobor_option( 'prayer_country', 'Bangladesh' );
	$method  = $method  ?: (int) khobor_option( 'prayer_method', 1 );
	$date    = current_time( 'd-m-Y' );

	$cache_key = 'khobor_prayer_' . md5( $city . $country . $method . $date );
	$cached    = get_transient( $cache_key );
	if ( false !== $cached ) {
		return $cached;
	}

	$url = add_query_arg(
		array(
			'city'    => rawurlencode( $city ),
			'country' => rawurlencode( $country ),
			'method'  => $method,
		),
		"https://api.aladhan.com/v1/timingsByCity/{$date}"
	);

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 8,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== $code ) {
		return new WP_Error( 'api_error', "Prayer API returned HTTP {$code}" );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) || empty( $body['data']['timings'] ) ) {
		return new WP_Error( 'bad_response', 'Malformed response from prayer API' );
	}

	$timings = $body['data']['timings'];
	$keys    = array( 'Fajr', 'Sunrise', 'Dhuhr', 'Asr', 'Maghrib', 'Isha' );
	$result  = array();
	foreach ( $keys as $k ) {
		if ( isset( $timings[ $k ] ) ) {
			$result[ $k ] = $timings[ $k ];
		}
	}

	// 12-hour cache; API updates daily.
	set_transient( $cache_key, $result, 12 * HOUR_IN_SECONDS );
	return $result;
}

/**
 * Convert an API time to 12-hour display format.
 *
 * Aladhan returns 24-hour "HH:MM", sometimes with a zone suffix such as
 * "05:12 (+06)". Formatting happens at render time rather than on fetch so
 * already-cached responses keep working.
 *
 * @param string $time Time from the API.
 * @return string e.g. "5:12 AM". Returned unchanged if it can't be parsed.
 */
function khobor_format_prayer_time( $time ) {
	if ( ! preg_match( '/(\d{1,2}):(\d{2})/', (string) $time, $m ) ) {
		return (string) $time;
	}

	$hour   = (int) $m[1];
	$minute = $m[2];

	// Midnight and noon are 12, not 0.
	$hour12 = $hour % 12;
	if ( 0 === $hour12 ) {
		$hour12 = 12;
	}

	$meridiem = ( $hour < 12 ) ? __( 'AM', 'khobor' ) : __( 'PM', 'khobor' );

	return sprintf( '%d:%s %s', $hour12, $minute, $meridiem );
}

/**
 * Localized Bangla labels for prayer names.
 *
 * @return array
 */
function khobor_prayer_labels() {
	return array(
		'Fajr'    => 'ফজর',
		'Sunrise' => 'সূর্যোদয়',
		'Dhuhr'   => 'যোহর',
		'Asr'     => 'আসর',
		'Maghrib' => 'মাগরিব',
		'Isha'    => 'এশা',
	);
}
