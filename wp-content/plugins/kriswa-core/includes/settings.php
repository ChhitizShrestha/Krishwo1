<?php
/**
 * Kriswa Site > Site Details: one friendly place to change numbers, contact details and links.
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tabs and fields: tab => [ title, intro, fields ]. Field: key => [ label, type, help ].
 */
function kriswa_settings_schema() {
	return array(
		'numbers'  => array(
			'Numbers',
			'These four numbers count up on the home page and appear on the About page. Just type new numbers and press Save. A "+" is added automatically.',
			array(
				'stat_years'    => array( 'Years of experience', 'number', 'e.g. 10' ),
				'stat_projects' => array( 'Projects completed', 'number', 'e.g. 100' ),
				'stat_clients'  => array( 'Happy clients', 'number', 'e.g. 80' ),
				'stat_team'     => array( 'Engineers and staff', 'number', 'e.g. 25' ),
			),
		),
		'contact'  => array(
			'Phone, email, address',
			'Shown in the footer, on the Contact page, in the floating call/WhatsApp buttons and in Google results.',
			array(
				'phone'        => array( 'Phone number', 'text', 'Include the country code, e.g. +977-1-4123456' ),
				'whatsapp'     => array( 'WhatsApp number', 'text', 'Digits only with country code, e.g. 9779812345678. Used by the green WhatsApp button.' ),
				'email'        => array( 'Public email address', 'email', 'Shown on the website.' ),
				'notify_email' => array( 'Send quote requests to', 'email', 'Where new quote requests are emailed. Leave empty to use the WordPress admin email.' ),
				'address'      => array( 'Address (English)', 'text', '' ),
				'address_ne'   => array( 'Address (नेपाली)', 'text', 'Optional. Shown on the Nepali site.' ),
				'hours'        => array( 'Office hours (English)', 'text', 'e.g. Sun - Fri, 9:00 AM - 6:00 PM' ),
				'hours_ne'     => array( 'Office hours (नेपाली)', 'text', 'Optional.' ),
			),
		),
		'links'    => array(
			'Social media links',
			'Paste the full web address of each page (for example https://www.facebook.com/yourpage). Empty ones are hidden.',
			array(
				'facebook'  => array( 'Facebook', 'url', '' ),
				'instagram' => array( 'Instagram', 'url', 'e.g. https://www.instagram.com/yourname' ),
				'tiktok'    => array( 'TikTok', 'url', 'e.g. https://www.tiktok.com/@yourname' ),
				'youtube'   => array( 'YouTube', 'url', '' ),
				'linkedin'  => array( 'LinkedIn', 'url', '' ),
			),
		),
		'offices'  => array(
			'Offices and map',
			'The Contact page lists your offices and shows a map.',
			array(
				'offices'       => array( 'Offices', 'textarea', 'One office per line in this form: Name | Address | Phone | Map link' ),
				'map_embed_url' => array( 'Map embed address', 'url', 'On Google Maps: Share > Embed a map > copy the address inside src="...". Works without any key.' ),
			),
		),
		'company'  => array(
			'Company and registration',
			'Name, tagline and the registration numbers shown as trust badges.',
			array(
				'company_name'    => array( 'Company name (English)', 'text', '' ),
				'company_name_ne' => array( 'Company name (नेपाली)', 'text', '' ),
				'tagline'         => array( 'Tagline (English)', 'text', '' ),
				'tagline_ne'      => array( 'Tagline (नेपाली)', 'text', '' ),
				'founded_year'    => array( 'Year founded', 'text', 'e.g. 2015' ),
				'registration_no' => array( 'Company registration number', 'text', '' ),
				'pan_vat'         => array( 'PAN / VAT number', 'text', '' ),
				'nec_no'          => array( 'Engineering Council registration', 'text', '' ),
				'license_class'   => array( 'Contractor license class', 'text', '' ),
			),
		),
		'feeds'    => array(
			'Instagram and TikTok',
			'Show your latest photos and videos on the website. See the README for the free 5-minute Instagram token steps.',
			array(
				'instagram_token' => array( 'Instagram access token', 'secret', 'Stored privately and refreshed automatically each week.' ),
				'tiktok_videos'   => array( 'TikTok video links', 'textarea', 'One link per line, e.g. https://www.tiktok.com/@yourname/video/1234567890' ),
			),
		),
		'extras'   => array(
			'Extras',
			'Optional tools. All free.',
			array(
				'show_float_btns'  => array( 'Floating WhatsApp and Call buttons', 'checkbox', 'Show the round buttons at the bottom-right of every page.' ),
				'newsletter_url'   => array( 'Newsletter form address', 'url', 'Optional. Paste the form action URL from a free Mailchimp or Brevo form. Empty = subscribers are saved in WordPress under Leads.' ),
				'newsletter_field' => array( 'Newsletter email field name', 'text', 'Mailchimp and Brevo use EMAIL.' ),
				'ga4_id'           => array( 'Google Analytics ID', 'text', 'Optional, e.g. G-XXXXXXXXXX' ),
				'gsc_verification' => array( 'Google Search Console code', 'text', 'Optional. The content value of the verification meta tag.' ),
			),
		),
	);
}

/** Flat list of fields: key => [label, type, help]. */
function kriswa_settings_fields() {
	$all = array();
	foreach ( kriswa_settings_schema() as $tab ) {
		foreach ( $tab[2] as $k => $def ) {
			$all[ $k ] = $def;
		}
	}
	return $all;
}

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
	$old = get_option( 'kriswa_options', array() );
	$old = is_array( $old ) ? $old : array();
	$out = $old; // Anything not in the form keeps its saved value.
	foreach ( kriswa_settings_fields() as $key => $def ) {
		$present = isset( $input[ $key ] );
		$val     = $present ? wp_unslash( $input[ $key ] ) : '';
		switch ( $def[1] ) {
			case 'secret':
				if ( ! empty( $input[ $key . '_clear' ] ) ) {
					$out[ $key ] = '';
				} elseif ( '' !== trim( $val ) ) {
					$out[ $key ] = preg_replace( '/[^A-Za-z0-9_\-.]/', '', $val );
				}
				break;
			case 'checkbox':
				$out[ $key ] = empty( $input[ $key ] ) ? '0' : '1';
				break;
			case 'number':
				if ( $present ) {
					$out[ $key ] = '' === trim( $val ) ? '0' : (string) absint( preg_replace( '/[^0-9]/', '', $val ) );
				}
				break;
			case 'url':
				if ( $present ) {
					$out[ $key ] = esc_url_raw( trim( $val ) );
				}
				break;
			case 'email':
				if ( $present ) {
					$out[ $key ] = sanitize_email( $val );
				}
				break;
			case 'textarea':
				if ( $present ) {
					$out[ $key ] = sanitize_textarea_field( $val );
				}
				break;
			default:
				if ( $present ) {
					$out[ $key ] = sanitize_text_field( $val );
				}
		}
	}
	return $out;
}

function kriswa_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$saved = get_option( 'kriswa_options', array() );
	$opts  = wp_parse_args( is_array( $saved ) ? $saved : array(), kriswa_default_options() );
	$tabs  = kriswa_settings_schema();

	echo '<div class="wrap kriswa-admin"><h1 class="wp-heading-inline">Site Details</h1>';
	echo ' <a class="page-title-action" href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">View website</a><hr class="wp-header-end">';
	settings_errors();
	echo '<p class="description" style="font-size:14px;max-width:760px">Everything the website shows about the company lives here. Change a value, press <strong>Save changes</strong> at the bottom, and it updates on every page.</p>';

	echo '<div class="kriswa-quick">';
	foreach ( array(
		array( 'Add a project', admin_url( 'post-new.php?post_type=kriswa_project' ) ),
		array( 'Add a testimonial', admin_url( 'post-new.php?post_type=kriswa_testimonial' ) ),
		array( 'Quote requests', admin_url( 'edit.php?post_type=kriswa_lead' ) ),
		array( 'Setup & help', admin_url( 'admin.php?page=kriswa-setup' ) ),
	) as $q ) {
		echo '<a class="button" href="' . esc_url( $q[1] ) . '">' . esc_html( $q[0] ) . '</a> ';
	}
	echo '</div>';

	if ( function_exists( 'kriswa_social_status_box' ) ) {
		kriswa_social_status_box();
	}

	echo '<h2 class="nav-tab-wrapper kriswa-tabs" style="margin-top:20px">';
	$i = 0;
	foreach ( $tabs as $id => $t ) {
		echo '<a href="#tab-' . esc_attr( $id ) . '" class="nav-tab' . ( 0 === $i ? ' nav-tab-active' : '' ) . '" data-tab="' . esc_attr( $id ) . '">' . esc_html( $t[0] ) . '</a>';
		++$i;
	}
	echo '</h2>';

	echo '<form method="post" action="options.php">';
	settings_fields( 'kriswa_settings' );
	foreach ( $tabs as $id => $t ) {
		echo '<section class="kriswa-panel" id="tab-' . esc_attr( $id ) . '" data-panel="' . esc_attr( $id ) . '"><h2>' . esc_html( $t[0] ) . '</h2><p class="description" style="max-width:760px">' . esc_html( $t[1] ) . '</p><table class="form-table" role="presentation">';
		foreach ( $t[2] as $key => $def ) {
			$name = 'kriswa_options[' . $key . ']';
			$fid  = 'kr_' . $key;
			echo '<tr><th scope="row"><label for="' . esc_attr( $fid ) . '">' . esc_html( $def[0] ) . '</label></th><td>';
			switch ( $def[1] ) {
				case 'textarea':
					echo '<textarea class="large-text" rows="4" id="' . esc_attr( $fid ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $opts[ $key ] ) . '</textarea>';
					break;
				case 'checkbox':
					echo '<label><input type="checkbox" value="1" id="' . esc_attr( $fid ) . '" name="' . esc_attr( $name ) . '"' . checked( '1', $opts[ $key ], false ) . ' /> Yes</label>';
					break;
				case 'number':
					echo '<input class="small-text" style="width:8em;font-size:18px" type="number" min="0" step="1" inputmode="numeric" id="' . esc_attr( $fid ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $opts[ $key ] ) . '" />';
					break;
				case 'secret':
					$has = '' !== trim( (string) $opts[ $key ] );
					echo '<input class="large-text" type="password" autocomplete="new-password" id="' . esc_attr( $fid ) . '" name="' . esc_attr( $name ) . '" value="" placeholder="' . esc_attr( $has ? 'A token is saved (hidden). Paste a new one to replace it.' : 'Paste token here' ) . '" />';
					if ( $has ) {
						echo '<p><label><input type="checkbox" value="1" name="kriswa_options[' . esc_attr( $key ) . '_clear]"> Disconnect (remove the saved token)</label></p>';
					}
					break;
				default:
					echo '<input class="large-text" type="' . esc_attr( $def[1] ) . '" id="' . esc_attr( $fid ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $opts[ $key ] ) . '" />';
			}
			if ( ! empty( $def[2] ) ) {
				echo '<p class="description">' . esc_html( $def[2] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table></section>';
	}
	submit_button( 'Save changes' );
	echo '</form></div>';
	?>
	<style>
		.kriswa-quick{margin:14px 0 4px}
		.kriswa-panel{display:none;background:#fff;border:1px solid #c3c4c7;border-top:0;padding:8px 24px 8px}
		.kriswa-panel.is-active{display:block}
		html.no-kr-js .kriswa-panel{display:block}
	</style>
	<script>
	( function () {
		var tabs = document.querySelectorAll( '.kriswa-tabs .nav-tab' ), panels = document.querySelectorAll( '.kriswa-panel' );
		function show( id ) {
			tabs.forEach( function ( t ) { t.classList.toggle( 'nav-tab-active', t.dataset.tab === id ); } );
			panels.forEach( function ( p ) { p.classList.toggle( 'is-active', p.dataset.panel === id ); } );
			try { sessionStorage.setItem( 'kriswaTab', id ); } catch ( e ) {}
		}
		tabs.forEach( function ( t ) { t.addEventListener( 'click', function ( e ) { e.preventDefault(); show( t.dataset.tab ); } ); } );
		var start = ( location.hash || '' ).replace( '#tab-', '' ) || ( function () { try { return sessionStorage.getItem( 'kriswaTab' ); } catch ( e ) { return ''; } }() );
		if ( location.hash === '#kriswa-social' ) { start = 'feeds'; }
		show( document.querySelector( '[data-panel="' + start + '"]' ) ? start : 'numbers' );
	}() );
	</script>
	<?php
}
