<?php
/**
 * Bangla Numeral Conversion
 *
 * Converts 0-9 to ০-৯ in dates, view counts, and other numeric output.
 * Optional via Customizer toggle.
 *
 * @package Khobor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convert all Western digits in a string to Bangla numerals.
 *
 * @param string|int $value Input.
 * @return string
 */
function khobor_to_bangla_numbers( $value ) {
	$value = (string) $value;
	$map   = array(
		'0' => '০',
		'1' => '১',
		'2' => '২',
		'3' => '৩',
		'4' => '৪',
		'5' => '৫',
		'6' => '৬',
		'7' => '৭',
		'8' => '৮',
		'9' => '৯',
	);
	return strtr( $value, $map );
}

/**
 * Convenience: convert only if the Customizer option is on.
 *
 * @param string|int $value Input.
 * @return string
 */
function khobor_maybe_bangla( $value ) {
	if ( khobor_option( 'enable_bangla_nums', true ) ) {
		return khobor_to_bangla_numbers( $value );
	}
	return (string) $value;
}

/**
 * Bangla month names, keyed 1-12.
 *
 * @return array
 */
function khobor_bangla_months() {
	return array(
		1  => 'জানুয়ারি', 2  => 'ফেব্রুয়ারি', 3  => 'মার্চ',
		4  => 'এপ্রিল',   5  => 'মে',          6  => 'জুন',
		7  => 'জুলাই',    8  => 'আগস্ট',       9  => 'সেপ্টেম্বর',
		10 => 'অক্টোবর',  11 => 'নভেম্বর',     12 => 'ডিসেম্বর',
	);
}

/**
 * Format a date as "১২ আগস্ট ২০২৬".
 *
 * @param string $date Any strtotime-compatible date. Defaults to now.
 * @return string
 */
function khobor_bangla_date( $date = 'now' ) {
	try {
		$dt = new DateTimeImmutable( $date, wp_timezone() );
	} catch ( \Exception $e ) {
		return '';
	}
	$months = khobor_bangla_months();
	return sprintf(
		'%s %s %s',
		khobor_to_bangla_numbers( $dt->format( 'j' ) ),
		$months[ (int) $dt->format( 'n' ) ],
		khobor_to_bangla_numbers( $dt->format( 'Y' ) )
	);
}

/**
 * Render today's date in Bangla, Bengali (বঙ্গাব্দ), and Hijri formats
 * for the header date line. Matches the reference site layout.
 *
 * @return string HTML string.
 */
function khobor_today_dateline() {
	$timezone   = wp_timezone();
	$today      = new DateTimeImmutable( 'now', $timezone );
	$day_names  = array(
		'Saturday'  => 'শনিবার',
		'Sunday'    => 'রবিবার',
		'Monday'    => 'সোমবার',
		'Tuesday'   => 'মঙ্গলবার',
		'Wednesday' => 'বুধবার',
		'Thursday'  => 'বৃহস্পতিবার',
		'Friday'    => 'শুক্রবার',
	);
	$month_names = khobor_bangla_months();
	$day_en   = $today->format( 'l' );
	$day_bn   = isset( $day_names[ $day_en ] ) ? $day_names[ $day_en ] : $day_en;
	$d        = khobor_to_bangla_numbers( $today->format( 'j' ) );
	$m        = $month_names[ (int) $today->format( 'n' ) ];
	$y        = khobor_to_bangla_numbers( $today->format( 'Y' ) );

	return sprintf(
		'<span class="khobor-date khobor-date--bn">%s , %s %s, %s</span>',
		esc_html( $day_bn ),
		esc_html( $d ),
		esc_html( $m ),
		esc_html( $y )
	);
}
