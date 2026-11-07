<?php
/**
 * Quote-request form, newsletter sign-up, lead storage, notification email and admin tools.
 * No third-party service is needed: leads are stored in WordPress and emailed.
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------------- *
 * Form option lists
 * ------------------------------------------------------------------------- */

function kriswa_quote_services() {
	return array(
		'new-house'    => __( 'New house construction', 'kriswa-core' ),
		'apartment'    => __( 'Apartment or commercial building', 'kriswa-core' ),
		'renovation'   => __( 'Renovation or extension', 'kriswa-core' ),
		'interior'     => __( 'Interior design and fit-out', 'kriswa-core' ),
		'architecture' => __( 'Architectural design and drawings', 'kriswa-core' ),
		'consultancy'  => __( 'Consultancy, estimation or supervision', 'kriswa-core' ),
		'other'        => __( 'Something else', 'kriswa-core' ),
	);
}

function kriswa_quote_budgets() {
	return array(
		''         => __( 'Not sure yet', 'kriswa-core' ),
		'lt-50'    => __( 'Below NPR 50 lakh', 'kriswa-core' ),
		'50-1cr'   => __( 'NPR 50 lakh to 1 crore', 'kriswa-core' ),
		'1-3cr'    => __( 'NPR 1 to 3 crore', 'kriswa-core' ),
		'gt-3cr'   => __( 'Above NPR 3 crore', 'kriswa-core' ),
	);
}

function kriswa_quote_timelines() {
	return array(
		''        => __( 'Flexible', 'kriswa-core' ),
		'asap'    => __( 'As soon as possible', 'kriswa-core' ),
		'1-3m'    => __( 'Within 1 to 3 months', 'kriswa-core' ),
		'3-6m'    => __( 'In 3 to 6 months', 'kriswa-core' ),
		'planning' => __( 'Just planning for now', 'kriswa-core' ),
	);
}

function kriswa_quote_contact_prefs() {
	return array(
		'call'     => __( 'Phone call', 'kriswa-core' ),
		'whatsapp' => __( 'WhatsApp', 'kriswa-core' ),
		'email'    => __( 'Email', 'kriswa-core' ),
	);
}

/* ------------------------------------------------------------------------- *
 * Quote form processing
 * ------------------------------------------------------------------------- */

function kriswa_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

/**
 * Returns true when this visitor has sent too many forms recently.
 */
function kriswa_rate_limited( $bucket ) {
	$key   = 'kr_rl_' . $bucket . '_' . md5( kriswa_client_ip() );
	$count = (int) get_transient( $key );
	if ( $count >= 6 ) {
		return true;
	}
	set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	return false;
}

/**
 * Validate and store a quote request.
 *
 * @param array $data Raw $_POST.
 * @return array { ok: bool, errors: array, id?: int, wa?: string }
 */
function kriswa_process_quote( $data ) {
	$data = wp_unslash( $data );

	// Honeypot: real visitors never fill this in. Pretend success to bots.
	if ( ! empty( $data['kr_website'] ) ) {
		return array( 'ok' => true, 'errors' => array() );
	}

	$services = kriswa_quote_services();
	$budgets  = kriswa_quote_budgets();
	$times    = kriswa_quote_timelines();
	$prefs    = kriswa_quote_contact_prefs();

	$name    = isset( $data['kr_name'] ) ? sanitize_text_field( $data['kr_name'] ) : '';
	$phone   = isset( $data['kr_phone'] ) ? sanitize_text_field( $data['kr_phone'] ) : '';
	$email   = isset( $data['kr_email'] ) ? sanitize_email( $data['kr_email'] ) : '';
	$service = isset( $data['kr_service'] ) ? sanitize_key( $data['kr_service'] ) : '';
	$loc     = isset( $data['kr_location'] ) ? sanitize_text_field( $data['kr_location'] ) : '';
	$area    = isset( $data['kr_area'] ) ? sanitize_text_field( $data['kr_area'] ) : '';
	$budget  = isset( $data['kr_budget'] ) ? sanitize_text_field( $data['kr_budget'] ) : '';
	$time    = isset( $data['kr_timeline'] ) ? sanitize_text_field( $data['kr_timeline'] ) : '';
	$pref    = isset( $data['kr_pref'] ) ? sanitize_key( $data['kr_pref'] ) : 'call';
	$msg     = isset( $data['kr_message'] ) ? sanitize_textarea_field( $data['kr_message'] ) : '';
	$consent = ! empty( $data['kr_consent'] );

	$errors = array();
	if ( strlen( $name ) < 2 ) {
		$errors['kr_name'] = __( 'Please enter your name.', 'kriswa-core' );
	}
	$digits = kriswa_digits( $phone );
	if ( strlen( $digits ) < 7 || strlen( $digits ) > 15 ) {
		$errors['kr_phone'] = __( 'Please enter a valid phone number.', 'kriswa-core' );
	}
	if ( ! empty( $data['kr_email'] ) && ! is_email( $email ) ) {
		$errors['kr_email'] = __( 'Please enter a valid email address.', 'kriswa-core' );
	}
	if ( ! isset( $services[ $service ] ) ) {
		$errors['kr_service'] = __( 'Please choose what you need.', 'kriswa-core' );
	}
	if ( ! $consent ) {
		$errors['kr_consent'] = __( 'Please agree to be contacted about your request.', 'kriswa-core' );
	}
	if ( ! isset( $budgets[ $budget ] ) ) {
		$budget = '';
	}
	if ( ! isset( $times[ $time ] ) ) {
		$time = '';
	}
	if ( ! isset( $prefs[ $pref ] ) ) {
		$pref = 'call';
	}
	if ( $errors ) {
		return array( 'ok' => false, 'errors' => $errors );
	}
	if ( kriswa_rate_limited( 'quote' ) ) {
		return array( 'ok' => false, 'errors' => array( '_form' => __( 'Too many requests. Please call or WhatsApp us instead.', 'kriswa-core' ) ) );
	}

	$id = wp_insert_post(
		array(
			'post_type'   => 'kriswa_lead',
			'post_status' => 'publish',
			'post_title'  => $name . ' - ' . $services[ $service ],
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		return array( 'ok' => false, 'errors' => array( '_form' => __( 'Sorry, something went wrong. Please call or WhatsApp us.', 'kriswa-core' ) ) );
	}

	$meta = array(
		'_lead_type'     => 'quote',
		'_lead_status'   => 'new',
		'_lead_name'     => $name,
		'_lead_phone'    => $phone,
		'_lead_email'    => $email,
		'_lead_service'  => $services[ $service ],
		'_lead_location' => $loc,
		'_lead_area'     => $area,
		'_lead_budget'   => $budgets[ $budget ],
		'_lead_timeline' => $times[ $time ],
		'_lead_pref'     => $prefs[ $pref ],
		'_lead_message'  => $msg,
		'_lead_lang'     => kriswa_lang(),
	);
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}

	kriswa_notify_lead( $id, $meta );

	$wa_text = sprintf(
		/* translators: 1: service, 2: name */
		__( 'Hello, I just sent a quote request for: %1$s. My name is %2$s.', 'kriswa-core' ),
		$services[ $service ],
		$name
	);

	return array(
		'ok'     => true,
		'errors' => array(),
		'id'     => $id,
		'wa'     => kriswa_whatsapp_url( $wa_text ),
	);
}

function kriswa_notify_lead( $lead_id, $meta ) {
	$to = kriswa_opt( 'notify_email', false );
	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}
	$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject = sprintf( '[%s] New %s: %s', $site, 'quote' === $meta['_lead_type'] ? 'quote request' : 'subscriber', $meta['_lead_name'] ? $meta['_lead_name'] : $meta['_lead_email'] );

	$lines = array();
	$map   = array(
		'_lead_name'     => 'Name',
		'_lead_phone'    => 'Phone',
		'_lead_email'    => 'Email',
		'_lead_service'  => 'Service',
		'_lead_location' => 'Project location',
		'_lead_area'     => 'Size / plot area',
		'_lead_budget'   => 'Budget',
		'_lead_timeline' => 'Timeline',
		'_lead_pref'     => 'Preferred contact',
		'_lead_message'  => 'Message',
		'_lead_lang'     => 'Site language',
	);
	foreach ( $map as $k => $label ) {
		if ( ! empty( $meta[ $k ] ) ) {
			$lines[] = $label . ': ' . $meta[ $k ];
		}
	}
	$lines[] = '';
	$lines[] = 'View in admin: ' . admin_url( 'post.php?post=' . (int) $lead_id . '&action=edit' );

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
	if ( ! empty( $meta['_lead_email'] ) && is_email( $meta['_lead_email'] ) ) {
		$headers[] = 'Reply-To: ' . $meta['_lead_email'];
	}
	wp_mail( $to, $subject, implode( "\n", $lines ), $headers );
}

// AJAX (JavaScript) path.
add_action( 'wp_ajax_nopriv_kriswa_quote', 'kriswa_ajax_quote' );
add_action( 'wp_ajax_kriswa_quote', 'kriswa_ajax_quote' );
function kriswa_ajax_quote() {
	$result = kriswa_process_quote( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification -- public form, protected by honeypot and rate limit.
	wp_send_json( $result, $result['ok'] ? 200 : 422 );
}

// Plain POST fallback when JavaScript is unavailable.
add_action( 'admin_post_nopriv_kriswa_quote', 'kriswa_post_quote' );
add_action( 'admin_post_kriswa_quote', 'kriswa_post_quote' );
function kriswa_post_quote() {
	$result = kriswa_process_quote( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification
	$back   = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	$back   = remove_query_arg( array( 'kr_sent', 'kr_err' ), $back );
	if ( $result['ok'] ) {
		wp_safe_redirect( add_query_arg( 'kr_sent', '1', $back ) . '#kriswa-quote' );
	} else {
		wp_safe_redirect( add_query_arg( 'kr_err', rawurlencode( implode( ' ', $result['errors'] ) ), $back ) . '#kriswa-quote' );
	}
	exit;
}

/* ------------------------------------------------------------------------- *
 * Newsletter (stored locally unless an external form action URL is configured)
 * ------------------------------------------------------------------------- */

add_action( 'wp_ajax_nopriv_kriswa_newsletter', 'kriswa_ajax_newsletter' );
add_action( 'wp_ajax_kriswa_newsletter', 'kriswa_ajax_newsletter' );
function kriswa_ajax_newsletter() {
	$post = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! empty( $post['kr_website'] ) ) {
		wp_send_json( array( 'ok' => true ) );
	}
	$email = isset( $post['kr_email'] ) ? sanitize_email( $post['kr_email'] ) : '';
	if ( ! is_email( $email ) ) {
		wp_send_json( array( 'ok' => false, 'message' => __( 'Please enter a valid email address.', 'kriswa-core' ) ), 422 );
	}
	if ( kriswa_rate_limited( 'news' ) ) {
		wp_send_json( array( 'ok' => false, 'message' => __( 'Too many attempts. Please try again later.', 'kriswa-core' ) ), 429 );
	}
	$existing = get_posts(
		array(
			'post_type'      => 'kriswa_lead',
			'post_status'    => 'any',
			'meta_key'       => '_lead_email', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $email, // phpcs:ignore WordPress.DB.SlowDBQuery
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! $existing ) {
		$id = wp_insert_post(
			array(
				'post_type'   => 'kriswa_lead',
				'post_status' => 'publish',
				'post_title'  => $email . ' (newsletter)',
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_lead_type', 'newsletter' );
			update_post_meta( $id, '_lead_status', 'new' );
			update_post_meta( $id, '_lead_email', $email );
			update_post_meta( $id, '_lead_name', '' );
			update_post_meta( $id, '_lead_lang', kriswa_lang() );
		}
	}
	wp_send_json( array( 'ok' => true, 'message' => __( 'Thank you for subscribing!', 'kriswa-core' ) ) );
}

/* ------------------------------------------------------------------------- *
 * Admin: lead list columns, details box, status, CSV export
 * ------------------------------------------------------------------------- */

function kriswa_lead_statuses() {
	return array(
		'new'       => __( 'New', 'kriswa-core' ),
		'contacted' => __( 'Contacted', 'kriswa-core' ),
		'quoted'    => __( 'Quote sent', 'kriswa-core' ),
		'won'       => __( 'Won', 'kriswa-core' ),
		'lost'      => __( 'Lost', 'kriswa-core' ),
	);
}

add_filter(
	'manage_kriswa_lead_posts_columns',
	function () {
		return array(
			'cb'      => '<input type="checkbox" />',
			'title'   => __( 'Lead', 'kriswa-core' ),
			'type'    => __( 'Type', 'kriswa-core' ),
			'contact' => __( 'Contact', 'kriswa-core' ),
			'service' => __( 'Service', 'kriswa-core' ),
			'status'  => __( 'Status', 'kriswa-core' ),
			'date'    => __( 'Date', 'kriswa-core' ),
		);
	}
);

add_action(
	'manage_kriswa_lead_posts_custom_column',
	function ( $col, $id ) {
		switch ( $col ) {
			case 'type':
				echo esc_html( ucfirst( (string) get_post_meta( $id, '_lead_type', true ) ) );
				break;
			case 'contact':
				$p = get_post_meta( $id, '_lead_phone', true );
				$e = get_post_meta( $id, '_lead_email', true );
				if ( $p ) {
					echo '<a href="tel:' . esc_attr( $p ) . '">' . esc_html( $p ) . '</a><br>';
				}
				if ( $e ) {
					echo '<a href="mailto:' . esc_attr( $e ) . '">' . esc_html( $e ) . '</a>';
				}
				break;
			case 'service':
				echo esc_html( get_post_meta( $id, '_lead_service', true ) );
				break;
			case 'status':
				$st = kriswa_lead_statuses();
				$k  = get_post_meta( $id, '_lead_status', true );
				echo esc_html( isset( $st[ $k ] ) ? $st[ $k ] : $k );
				break;
		}
	},
	10,
	2
);

add_action(
	'add_meta_boxes_kriswa_lead',
	function () {
		add_meta_box( 'kriswa_lead_details', __( 'Lead details', 'kriswa-core' ), 'kriswa_lead_meta_box', 'kriswa_lead', 'normal', 'high' );
	}
);

function kriswa_lead_meta_box( $post ) {
	wp_nonce_field( 'kriswa_lead_' . $post->ID, 'kriswa_lead_nonce' );
	$rows = array(
		'_lead_name'     => __( 'Name', 'kriswa-core' ),
		'_lead_phone'    => __( 'Phone', 'kriswa-core' ),
		'_lead_email'    => __( 'Email', 'kriswa-core' ),
		'_lead_service'  => __( 'Service', 'kriswa-core' ),
		'_lead_location' => __( 'Project location', 'kriswa-core' ),
		'_lead_area'     => __( 'Size / plot area', 'kriswa-core' ),
		'_lead_budget'   => __( 'Budget', 'kriswa-core' ),
		'_lead_timeline' => __( 'Timeline', 'kriswa-core' ),
		'_lead_pref'     => __( 'Preferred contact', 'kriswa-core' ),
		'_lead_message'  => __( 'Message', 'kriswa-core' ),
		'_lead_lang'     => __( 'Site language', 'kriswa-core' ),
	);
	echo '<table class="form-table" role="presentation">';
	foreach ( $rows as $k => $label ) {
		$v = get_post_meta( $post->ID, $k, true );
		if ( '' === $v ) {
			continue;
		}
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $v ) ) . '</td></tr>';
	}
	$cur = get_post_meta( $post->ID, '_lead_status', true );
	echo '<tr><th scope="row"><label for="kr_lead_status">' . esc_html__( 'Status', 'kriswa-core' ) . '</label></th><td><select name="kr_lead_status" id="kr_lead_status">';
	foreach ( kriswa_lead_statuses() as $k => $label ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $cur, $k, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></td></tr></table>';
}

add_action(
	'save_post_kriswa_lead',
	function ( $post_id ) {
		if ( ! isset( $_POST['kriswa_lead_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kriswa_lead_nonce'] ) ), 'kriswa_lead_' . $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['kr_lead_status'] ) ) {
			return;
		}
		$st = sanitize_key( wp_unslash( $_POST['kr_lead_status'] ) );
		if ( isset( kriswa_lead_statuses()[ $st ] ) ) {
			update_post_meta( $post_id, '_lead_status', $st );
		}
	}
);

add_action(
	'manage_posts_extra_tablenav',
	function ( $which ) {
		if ( 'top' !== $which || 'kriswa_lead' !== get_current_screen()->post_type ) {
			return;
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=kriswa_export_leads' ), 'kriswa_export_leads' );
		echo '<div class="alignleft actions"><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Export CSV', 'kriswa-core' ) . '</a></div>';
	}
);

add_action(
	'admin_post_kriswa_export_leads',
	function () {
		if ( ! current_user_can( 'edit_others_posts' ) || ! check_admin_referer( 'kriswa_export_leads' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'kriswa-core' ), 403 );
		}
		$ids = get_posts(
			array(
				'post_type'      => 'kriswa_lead',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=kriswa-leads-' . gmdate( 'Y-m-d' ) . '.csv' );
		$out  = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM so Excel shows Nepali text correctly.
		$keys = array( '_lead_type', '_lead_status', '_lead_name', '_lead_phone', '_lead_email', '_lead_service', '_lead_location', '_lead_area', '_lead_budget', '_lead_timeline', '_lead_pref', '_lead_message', '_lead_lang' );
		fputcsv( $out, array_merge( array( 'date' ), array_map( fn( $k ) => ltrim( $k, '_' ), $keys ) ) );
		foreach ( $ids as $id ) {
			$row = array( get_the_date( 'Y-m-d H:i', $id ) );
			foreach ( $keys as $k ) {
				$v     = (string) get_post_meta( $id, $k, true );
				// Neutralise spreadsheet formula injection.
				$row[] = preg_match( '/^[=+\-@\t\r]/', $v ) ? "'" . $v : $v;
			}
			fputcsv( $out, $row );
		}
		fclose( $out );
		exit;
	}
);
