<?php
/**
 * Settings page: Settings > Kriswa.
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

function kriswa_settings_schema() {
	return array(
		'Company'                 => array(
			'company_name'    => array( 'Company name (English)', 'text' ),
			'company_name_ne' => array( 'Company name (नेपाली)', 'text' ),
			'tagline'         => array( 'Tagline (English)', 'text' ),
			'tagline_ne'      => array( 'Tagline (नेपाली)', 'text' ),
			'founded_year'    => array( 'Year founded (AD)', 'text' ),
		),
		'Homepage statistics (numbers only)' => array(
			'stat_years'    => array( 'Years of experience', 'text' ),
			'stat_projects' => array( 'Projects completed', 'text' ),
			'stat_clients'  => array( 'Happy clients', 'text' ),
			'stat_team'     => array( 'Engineers and staff', 'text' ),
		),
		'Contact'                 => array(
			'phone'      => array( 'Phone (with country code)', 'text' ),
			'whatsapp'   => array( 'WhatsApp number (digits only, with country code, e.g. 9779812345678)', 'text' ),
			'email'      => array( 'Public email', 'email' ),
			'address'    => array( 'Address (English)', 'text' ),
			'address_ne' => array( 'Address (नेपाली)', 'text' ),
			'hours'      => array( 'Office hours (English)', 'text' ),
			'hours_ne'   => array( 'Office hours (नेपाली)', 'text' ),
			'notify_email' => array( 'Send quote requests to (leave empty to use the WordPress admin email)', 'email' ),
		),
		'Offices and map'         => array(
			'offices'       => array( 'Offices, one per line: Name | Address | Phone | Map link', 'textarea' ),
			'map_embed_url' => array( 'Map embed URL (OpenStreetMap or Google Maps "Embed a map" src; no API key needed)', 'url' ),
		),
		'Registration and trust'  => array(
			'registration_no' => array( 'Company registration no.', 'text' ),
			'pan_vat'         => array( 'PAN / VAT no.', 'text' ),
			'nec_no'          => array( 'Nepal Engineering Council registration', 'text' ),
			'license_class'   => array( 'Contractor license class', 'text' ),
		),
		'Social media'            => array(
			'facebook'  => array( 'Facebook URL', 'url' ),
			'instagram' => array( 'Instagram URL', 'url' ),
			'youtube'   => array( 'YouTube URL', 'url' ),
			'tiktok'    => array( 'TikTok URL', 'url' ),
			'linkedin'  => array( 'LinkedIn URL', 'url' ),
		),
		'Instagram and TikTok feed' => array(
			'instagram_token' => array( 'Instagram access token (long-lived). Stored privately; leave empty to keep the saved one', 'secret' ),
			'tiktok_videos'   => array( 'TikTok video links, one per line (e.g. https://www.tiktok.com/@yourname/video/1234567890)', 'textarea' ),
		),
		'Newsletter and analytics' => array(
			'newsletter_url'   => array( 'Newsletter form action URL (free Mailchimp or Brevo embedded-form "action"); leave empty to store subscribers in WordPress under Leads', 'url' ),
			'newsletter_field' => array( 'Newsletter email field name (Mailchimp: EMAIL, Brevo: EMAIL)', 'text' ),
			'ga4_id'           => array( 'Google Analytics 4 measurement ID (e.g. G-XXXXXXXXXX), free', 'text' ),
			'gsc_verification' => array( 'Google Search Console verification code (content value of the meta tag)', 'text' ),
		),
		'Display'                 => array(
			'show_float_btns' => array( 'Show floating WhatsApp and Call buttons', 'checkbox' ),
		),
	);
}

add_action(
	'admin_menu',
	function () {
		add_options_page( 'Kriswa', 'Kriswa', 'manage_options', 'kriswa-settings', 'kriswa_settings_page' );
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'kriswa_settings',
			'kriswa_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'kriswa_sanitize_options',
				'default'           => array(),
			)
		);
	}
);

function kriswa_sanitize_options( $input ) {
	$out = array();
	foreach ( kriswa_settings_schema() as $fields ) {
		foreach ( $fields as $key => $def ) {
			$val = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : '';
			switch ( $def[1] ) {
				case 'secret':
					$old = get_option( 'kriswa_options', array() );
					if ( ! empty( $input[ $key . '_clear' ] ) ) {
						$out[ $key ] = '';
					} elseif ( '' === trim( $val ) ) {
						$out[ $key ] = isset( $old[ $key ] ) ? $old[ $key ] : '';
					} else {
						$out[ $key ] = preg_replace( '/[^A-Za-z0-9_\-.]/', '', $val );
					}
					break;
				case 'url':
					$out[ $key ] = esc_url_raw( $val );
					break;
				case 'email':
					$out[ $key ] = sanitize_email( $val );
					break;
				case 'textarea':
					$out[ $key ] = sanitize_textarea_field( $val );
					break;
				case 'checkbox':
					$out[ $key ] = empty( $val ) ? '0' : '1';
					break;
				default:
					$out[ $key ] = sanitize_text_field( $val );
			}
		}
	}
	// Checkboxes missing from a POST mean "unchecked".
	return $out;
}

function kriswa_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$saved = get_option( 'kriswa_options', array() );
	$opts  = wp_parse_args( is_array( $saved ) ? $saved : array(), kriswa_default_options() );
	echo '<div class="wrap"><h1>Kriswa site settings</h1>';
	echo '<p>All contact details start as placeholders. Replace them here and they update everywhere on the site.</p>';
	if ( function_exists( 'kriswa_social_status_box' ) ) {
		kriswa_social_status_box();
	}
	echo '<form method="post" action="options.php">';
	settings_fields( 'kriswa_settings' );
	foreach ( kriswa_settings_schema() as $section => $fields ) {
		echo '<h2>' . esc_html( $section ) . '</h2><table class="form-table" role="presentation">';
		foreach ( $fields as $key => $def ) {
			$name = 'kriswa_options[' . $key . ']';
			echo '<tr><th scope="row"><label for="kr_' . esc_attr( $key ) . '">' . esc_html( $def[0] ) . '</label></th><td>';
			if ( 'textarea' === $def[1] ) {
				echo '<textarea class="large-text" rows="4" id="kr_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $opts[ $key ] ) . '</textarea>';
			} elseif ( 'secret' === $def[1] ) {
				$has = '' !== trim( (string) $opts[ $key ] );
				echo '<input class="large-text" type="password" autocomplete="new-password" id="kr_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="" placeholder="' . esc_attr( $has ? 'A token is saved (hidden). Paste a new one to replace it.' : 'Paste token here' ) . '" />';
				if ( $has ) {
					echo '<p><label><input type="checkbox" value="1" name="kriswa_options[' . esc_attr( $key ) . '_clear]"> Disconnect (remove the saved token)</label></p>';
				}
			} elseif ( 'checkbox' === $def[1] ) {
				echo '<input type="checkbox" value="1" id="kr_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '"' . checked( '1', $opts[ $key ], false ) . ' />';
			} else {
				echo '<input class="large-text" type="' . esc_attr( $def[1] ) . '" id="kr_' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $opts[ $key ] ) . '" />';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	}
	submit_button();
	echo '</form></div>';
}
