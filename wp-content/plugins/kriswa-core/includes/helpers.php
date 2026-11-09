<?php
/**
 * Shared helpers: options, language, URLs.
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default site settings. Every value is a placeholder that is edited under Settings > Kriswa.
 */
function kriswa_default_options() {
	return array(
		'company_name'     => 'Kriswa Consultant and Construction Pvt. Ltd.',
		'company_name_ne'  => 'क्रिस्वा कन्सल्टेन्ट एण्ड कन्स्ट्रक्सन प्रा. लि.',
		'tagline'          => 'Building trusted homes and spaces across Nepal',
		'tagline_ne'       => 'नेपालभर भरपर्दा घर र भवन निर्माण',
		'phone'            => '+977-1-0000000',
		'whatsapp'         => '9779800000000',
		'email'            => 'info@example.com',
		'address'          => 'Your Street, Kathmandu, Nepal',
		'address_ne'       => 'तपाईंको ठेगाना, काठमाडौं, नेपाल',
		'hours'            => 'Sun - Fri, 9:00 AM - 6:00 PM',
		'hours_ne'         => 'आइतबार - शुक्रबार, बिहान ९:०० - साँझ ६:००',
		'map_embed_url'    => 'https://www.openstreetmap.org/export/embed.html?bbox=85.2900%2C27.6800%2C85.3500%2C27.7250&layer=mapnik&marker=27.7030%2C85.3200',
		'offices'          => "Head Office | Your Street, Kathmandu | +977-1-0000000 | https://www.openstreetmap.org/?mlat=27.703&mlon=85.32#map=14/27.703/85.32\nSite Office | Your Street, Lalitpur | +977-1-0000001 | https://www.openstreetmap.org/?mlat=27.667&mlon=85.324#map=14/27.667/85.324",
		'pan_vat'          => '000000000',
		'registration_no'  => '00000/000/000',
		'nec_no'           => 'NEC Reg. No. 0000',
		'license_class'    => 'Class A Contractor (placeholder)',
		'founded_year'     => '2015',
		'stat_years'       => '10',
		'stat_projects'    => '100',
		'stat_clients'     => '80',
		'stat_team'        => '25',
		'facebook'         => 'https://facebook.com/',
		'instagram'        => 'https://instagram.com/',
		'youtube'          => 'https://youtube.com/',
		'tiktok'           => 'https://tiktok.com/',
		'linkedin'         => '',
		'notify_email'     => '',
		'instagram_token'  => '',
		'tiktok_videos'    => '',
		'newsletter_url'   => '',
		'newsletter_field' => 'EMAIL',
		'ga4_id'           => '',
		'gsc_verification' => '',
		'show_float_btns'  => '1',
	);
}

/**
 * Current language code: 'ne' or 'en'.
 */
function kriswa_lang() {
	if ( function_exists( 'pll_current_language' ) ) {
		$l = pll_current_language();
		if ( $l ) {
			return substr( $l, 0, 2 );
		}
	}
	return substr( determine_locale(), 0, 2 );
}

function kriswa_is_nepali() {
	return 'ne' === kriswa_lang();
}

/**
 * Get a setting. In Nepali, `<key>_ne` is preferred when present.
 */
function kriswa_opt( $key, $localized = true ) {
	$defaults = kriswa_default_options();
	$saved    = get_option( 'kriswa_options', array() );
	$opts     = wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );
	if ( $localized && kriswa_is_nepali() && isset( $opts[ $key . '_ne' ] ) && '' !== trim( (string) $opts[ $key . '_ne' ] ) ) {
		return $opts[ $key . '_ne' ];
	}
	return isset( $opts[ $key ] ) ? $opts[ $key ] : '';
}

/**
 * Digits only, for tel: and wa.me links.
 */
function kriswa_digits( $str ) {
	return preg_replace( '/\D+/', '', (string) $str );
}

function kriswa_whatsapp_url( $message = '' ) {
	$url = 'https://wa.me/' . kriswa_digits( kriswa_opt( 'whatsapp', false ) );
	if ( $message ) {
		$url .= '?text=' . rawurlencode( $message );
	}
	return $url;
}

function kriswa_tel_url() {
	return 'tel:+' . ltrim( kriswa_digits( kriswa_opt( 'phone', false ) ), '+' );
}

/**
 * Page URL by English slug, mapped to the current language when Polylang is active.
 */
function kriswa_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	if ( ! $page || 'publish' !== $page->post_status ) {
		// Never link to a page that does not exist: fall back to the home page (the admin sees a notice to fix it).
		return function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
	}
	$id = $page->ID;
	if ( function_exists( 'pll_get_post' ) ) {
		$tr = pll_get_post( $id );
		if ( $tr ) {
			$id = $tr;
		}
	}
	return get_permalink( $id );
}

/**
 * Format a date for display. In Nepali, show the Bikram Sambat date with Devanagari numerals.
 *
 * @param int|string $post_or_ymd Post ID/object, or a Y-m-d string.
 */
function kriswa_display_date( $post_or_ymd = null ) {
	if ( is_string( $post_or_ymd ) && preg_match( '/^\d{4}-\d{2}-\d{2}/', $post_or_ymd ) ) {
		$ymd = substr( $post_or_ymd, 0, 10 );
	} else {
		$ymd = get_the_date( 'Y-m-d', $post_or_ymd );
	}
	if ( kriswa_is_nepali() ) {
		$out = Kriswa_Nepali_Date::format( $ymd, 'ne' );
		if ( $out ) {
			return $out;
		}
	}
	return date_i18n( get_option( 'date_format' ), strtotime( $ymd ) );
}
