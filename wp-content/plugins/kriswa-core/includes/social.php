<?php
/**
 * Instagram and TikTok feeds: photos and videos shown (and played) on the website.
 *
 * Design rules
 * - Visitors NEVER trigger a request to Instagram or TikTok. Pages only read cached data. A single background
 *   refresh (hourly, or one deduplicated event when the cache is stale) talks to the APIs, with a lock and backoff.
 * - If an API fails, the last good posts keep showing and the problem is reported in the admin only.
 * - Pictures are copied to this site (uploads/kriswa-social) so they do not expire and visitors do not contact
 *   Meta or TikTok servers just by scrolling past them.
 * - Instagram: "Instagram API with Instagram Login", long-lived token (60 days), refreshed weekly once it is
 *   older than 24 hours. TikTok: free public oEmbed for links you paste; players load only when tapped.
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

const KRISWA_IG_API = 'https://graph.instagram.com';

/* ========================================================================= *
 * Local image cache
 * ========================================================================= */

function kriswa_social_dir() {
	$u = wp_upload_dir( null, false );
	if ( ! empty( $u['error'] ) ) {
		return false;
	}
	$path = trailingslashit( $u['basedir'] ) . 'kriswa-social/';
	if ( ! wp_mkdir_p( $path ) ) {
		return false;
	}
	if ( ! file_exists( $path . 'index.php' ) ) {
		file_put_contents( $path . 'index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return array( 'path' => $path, 'url' => trailingslashit( $u['baseurl'] ) . 'kriswa-social/' );
}

/**
 * Copy a remote picture into the uploads folder. Returns the local URL, or '' on failure.
 * Only real images up to 5 MB are kept.
 */
function kriswa_cache_remote_image( $url, $name ) {
	$dir = kriswa_social_dir();
	if ( ! $dir || ! $url || ! preg_match( '#^https?://#i', $url ) ) {
		return '';
	}
	$name = preg_replace( '/[^a-z0-9_-]/i', '', $name );
	foreach ( glob( $dir['path'] . $name . '.*' ) ?: array() as $f ) {
		return $dir['url'] . basename( $f ); // Already cached: post pictures never change.
	}
	$res = wp_safe_remote_get( $url, array( 'timeout' => 6, 'limit_response_size' => 5 * MB_IN_BYTES ) );
	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}
	$body = wp_remote_retrieve_body( $res );
	$info = $body ? @getimagesizefromstring( $body ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$exts = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' );
	if ( ! $info || empty( $exts[ $info['mime'] ] ) || strlen( $body ) > 5 * MB_IN_BYTES ) {
		return '';
	}
	$file = $name . '.' . $exts[ $info['mime'] ];
	if ( false === file_put_contents( $dir['path'] . $file, $body ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions
		return '';
	}
	return $dir['url'] . $file;
}

/** Delete cached pictures that no longer belong to any post we show. */
function kriswa_prune_social_images() {
	$dir = kriswa_social_dir();
	if ( ! $dir ) {
		return;
	}
	$keep = array();
	foreach ( (array) get_option( 'kriswa_ig_last_good', array() ) as $it ) {
		foreach ( array( 'thumb', 'src' ) as $k ) {
			if ( ! empty( $it[ $k ] ) ) {
				$keep[ basename( wp_parse_url( $it[ $k ], PHP_URL_PATH ) ) ] = true;
			}
		}
	}
	foreach ( (array) get_option( 'kriswa_tt_cache', array() ) as $c ) {
		if ( ! empty( $c['thumb'] ) ) {
			$keep[ basename( wp_parse_url( $c['thumb'], PHP_URL_PATH ) ) ] = true;
		}
	}
	foreach ( glob( $dir['path'] . '*.{jpg,png,webp}', GLOB_BRACE ) ?: array() as $f ) {
		if ( ! isset( $keep[ basename( $f ) ] ) ) {
			@unlink( $f ); // phpcs:ignore WordPress.WP.AlternativeFunctions, WordPress.PHP.NoSilencedErrors
		}
	}
}

/* ========================================================================= *
 * Alerts to the site owner (rate limited so nobody gets spammed)
 * ========================================================================= */

function kriswa_social_notify( $key, $subject, $message ) {
	if ( get_transient( 'kriswa_notified_' . $key ) ) {
		return;
	}
	set_transient( 'kriswa_notified_' . $key, 1, 3 * DAY_IN_SECONDS );
	$to = kriswa_opt( 'notify_email', false );
	$to = is_email( $to ) ? $to : get_option( 'admin_email' );
	wp_mail( $to, '[' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . '] ' . $subject, $message . "\n\n" . admin_url( 'admin.php?page=kriswa-settings#kriswa-social' ) );
}

/* ========================================================================= *
 * Instagram: token
 * ========================================================================= */

/** Token facts. Expiry is exact after the first successful refresh, otherwise an estimate (60 days from saving). */
function kriswa_ig_token_info() {
	$i = get_option( 'kriswa_ig_token_info', array() );
	return is_array( $i ) ? $i : array();
}

/** Called when a new token is pasted in Site Details. */
function kriswa_ig_token_saved() {
	update_option(
		'kriswa_ig_token_info',
		array(
			'saved_at'     => time(),
			'refreshed_at' => 0,
			'expires_at'   => time() + 60 * DAY_IN_SECONDS,
			'estimated'    => true,
			'last_error'   => '',
			'state'        => 'ok',
		),
		false
	);
	delete_transient( 'kriswa_ig_backoff' );
	delete_transient( 'kriswa_ig_fresh' );
}

/**
 * Refresh the long-lived token. Instagram only allows it when the token is at least 24 hours old and not yet expired;
 * a refreshed token is valid for another 60 days. An expired token cannot be refreshed and must be re-generated.
 *
 * @return array { ok: bool, message: string }
 */
function kriswa_refresh_instagram_token() {
	$opts  = get_option( 'kriswa_options', array() );
	$token = isset( $opts['instagram_token'] ) ? $opts['instagram_token'] : '';
	if ( ! $token ) {
		return array( 'ok' => false, 'message' => 'No Instagram token is saved.' );
	}
	$info  = kriswa_ig_token_info();
	$since = max( (int) ( isset( $info['saved_at'] ) ? $info['saved_at'] : 0 ), (int) ( isset( $info['refreshed_at'] ) ? $info['refreshed_at'] : 0 ) );
	if ( $since && time() - $since < DAY_IN_SECONDS ) {
		return array( 'ok' => false, 'message' => 'Instagram only allows a token refresh once the token is 24 hours old. Try again tomorrow.' );
	}
	if ( ! empty( $info['expires_at'] ) && empty( $info['estimated'] ) && (int) $info['expires_at'] < time() ) {
		$info['state'] = 'expired';
		update_option( 'kriswa_ig_token_info', $info, false );
		return array( 'ok' => false, 'message' => 'The token has expired and cannot be refreshed. Generate a new token and paste it in Site Details.' );
	}

	$res  = wp_remote_get( add_query_arg( array( 'grant_type' => 'ig_refresh_token', 'access_token' => $token ), KRISWA_IG_API . '/refresh_access_token' ), array( 'timeout' => 10 ) );
	$body = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );

	if ( is_array( $body ) && ! empty( $body['access_token'] ) ) {
		$GLOBALS['kriswa_token_refreshing'] = true; // Our own save must not look like a newly pasted token.
		$opts['instagram_token'] = sanitize_text_field( $body['access_token'] );
		update_option( 'kriswa_options', $opts );
		unset( $GLOBALS['kriswa_token_refreshing'] );
		$ttl = ! empty( $body['expires_in'] ) ? (int) $body['expires_in'] : 60 * DAY_IN_SECONDS;
		update_option(
			'kriswa_ig_token_info',
			array(
				'saved_at'     => isset( $info['saved_at'] ) ? $info['saved_at'] : time(),
				'refreshed_at' => time(),
				'expires_at'   => time() + $ttl,
				'estimated'    => false,
				'last_error'   => '',
				'state'        => 'ok',
			),
			false
		);
		return array( 'ok' => true, 'message' => 'Token refreshed. Valid for another ' . round( $ttl / DAY_IN_SECONDS ) . ' days.' );
	}

	$msg  = is_wp_error( $res ) ? $res->get_error_message() : ( isset( $body['error']['message'] ) ? $body['error']['message'] : 'Instagram returned an unexpected answer.' );
	$code = isset( $body['error']['code'] ) ? (int) $body['error']['code'] : 0;
	$info['last_error'] = $msg;
	if ( 190 === $code ) {
		$info['state'] = 'expired';
	}
	update_option( 'kriswa_ig_token_info', $info, false );
	kriswa_social_notify( 'token_refresh', 'Instagram token could not be refreshed', "The weekly Instagram token refresh failed: {$msg}\nIf the token has expired, generate a new one in Meta for Developers and paste it in Site Details." );
	return array( 'ok' => false, 'message' => $msg );
}
add_action( 'kriswa_refresh_instagram_token', 'kriswa_refresh_instagram_token' );

/* ========================================================================= *
 * Instagram: feed (network side, runs in the background or from the admin)
 * ========================================================================= */

function kriswa_instagram_fetch( $force = false ) {
	$token = kriswa_opt( 'instagram_token', false );
	if ( ! $token ) {
		return;
	}
	if ( ! $force && get_transient( 'kriswa_ig_backoff' ) ) {
		return;
	}
	$fail = function ( $msg, $code, $wait ) {
		update_option( 'kriswa_ig_status', array( 'ok' => false, 'message' => $msg, 'code' => $code, 'time' => time() ), false );
		set_transient( 'kriswa_ig_backoff', 1, $wait );
		if ( 190 === (int) $code ) {
			$info = kriswa_ig_token_info();
			$info['state'] = 'expired';
			update_option( 'kriswa_ig_token_info', $info, false );
			kriswa_social_notify( 'token_invalid', 'Instagram feed stopped: token is no longer valid', "Instagram rejected the access token ({$msg}). The website keeps showing the last posts, but no new ones will appear until you paste a new token." );
		}
	};

	$res = wp_remote_get(
		add_query_arg(
			array(
				'fields'       => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
				'limit'        => 24,
				'access_token' => $token,
			),
			KRISWA_IG_API . '/me/media'
		),
		array( 'timeout' => 8 )
	);
	if ( is_wp_error( $res ) ) {
		$fail( 'Could not reach Instagram: ' . $res->get_error_message(), 0, 10 * MINUTE_IN_SECONDS );
		return;
	}
	$http = (int) wp_remote_retrieve_response_code( $res );
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( 200 !== $http || ! is_array( $body ) || ! isset( $body['data'] ) ) {
		$code = isset( $body['error']['code'] ) ? (int) $body['error']['code'] : 0;
		$msg  = isset( $body['error']['message'] ) ? $body['error']['message'] : 'Instagram returned HTTP ' . $http;
		// Meta rate-limit codes: wait longer before trying again.
		$wait = in_array( $code, array( 4, 17, 32, 613 ), true ) ? 30 * MINUTE_IN_SECONDS : ( 190 === $code ? HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
		$fail( $msg, $code, $wait );
		return;
	}

	$deadline = time() + 40; // Never spend more than ~40 s copying pictures.
	$items    = array();
	foreach ( $body['data'] as $m ) {
		if ( empty( $m['media_url'] ) || empty( $m['permalink'] ) ) {
			continue;
		}
		$type  = isset( $m['media_type'] ) ? $m['media_type'] : 'IMAGE';
		$video = 'VIDEO' === $type;
		$pic   = $video ? ( ! empty( $m['thumbnail_url'] ) ? $m['thumbnail_url'] : '' ) : $m['media_url'];
		$local = ( $pic && time() < $deadline ) ? kriswa_cache_remote_image( $pic, 'ig-' . $m['id'] ) : '';
		$thumb = $local ? $local : esc_url_raw( $pic );
		$items[] = array(
			'id'        => (string) $m['id'],
			'kind'      => $video ? 'video' : 'image',
			'src'       => $video ? esc_url_raw( $m['media_url'] ) : $thumb, // Photos are shown from our own copy.
			'thumb'     => $thumb,
			'link'      => esc_url_raw( $m['permalink'] ),
			'caption'   => isset( $m['caption'] ) ? wp_strip_all_tags( $m['caption'] ) : '',
			'album'     => 'CAROUSEL_ALBUM' === $type,
			'timestamp' => isset( $m['timestamp'] ) ? $m['timestamp'] : '',
		);
	}
	update_option( 'kriswa_ig_last_good', $items, false );
	update_option( 'kriswa_ig_status', array( 'ok' => true, 'message' => count( $items ) . ' posts loaded', 'count' => count( $items ), 'time' => time() ), false );
	set_transient( 'kriswa_ig_fresh', 1, HOUR_IN_SECONDS );
	delete_transient( 'kriswa_ig_backoff' );
}

/** What pages use: cached data only. Never contacts Instagram. */
function kriswa_instagram_feed() {
	$token = kriswa_opt( 'instagram_token', false );
	if ( ! $token ) {
		return array( 'status' => 'not_connected', 'message' => '', 'items' => array() );
	}
	if ( ! get_transient( 'kriswa_ig_fresh' ) ) {
		kriswa_schedule_social_refresh(); // One deduplicated background job, never a request per visitor.
	}
	$st    = get_option( 'kriswa_ig_status', array() );
	$items = get_option( 'kriswa_ig_last_good', array() );
	$ok    = ! is_array( $st ) || ! isset( $st['ok'] ) || $st['ok'];
	return array(
		'status'  => $ok ? 'ok' : 'error',
		'message' => is_array( $st ) && isset( $st['message'] ) ? $st['message'] : '',
		'items'   => is_array( $items ) ? $items : array(),
	);
}

/* ========================================================================= *
 * TikTok: link validation, oEmbed lookup (background), cached read
 * ========================================================================= */

/**
 * Validate pasted TikTok links.
 *
 * @return array { valid: string[] canonical URLs, invalid: array of [line, reason] }
 */
function kriswa_tiktok_validate( $text ) {
	$valid   = array();
	$invalid = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		$p    = wp_parse_url( $line );
		$host = isset( $p['host'] ) ? strtolower( $p['host'] ) : '';
		$path = isset( $p['path'] ) ? $p['path'] : '';
		if ( ! isset( $p['scheme'] ) || 'https' !== strtolower( $p['scheme'] ) ) {
			$invalid[] = array( $line, 'must start with https://' );
		} elseif ( in_array( $host, array( 'tiktok.com', 'www.tiktok.com', 'm.tiktok.com' ), true ) ) {
			if ( preg_match( '#^/(@[A-Za-z0-9._-]+)/video/(\d{8,25})/?$#', $path, $m ) ) {
				$valid[] = 'https://www.tiktok.com/' . $m[1] . '/video/' . $m[2]; // Tracking parameters removed.
			} else {
				$invalid[] = array( $line, 'is not a video link (expected https://www.tiktok.com/@name/video/123...)' );
			}
		} elseif ( in_array( $host, array( 'vm.tiktok.com', 'vt.tiktok.com' ), true ) ) {
			if ( preg_match( '#^/([A-Za-z0-9]{5,20})/?$#', $path, $m ) ) {
				$valid[] = 'https://' . $host . '/' . $m[1] . '/';
			} else {
				$invalid[] = array( $line, 'is not a valid short link' );
			}
		} else {
			$invalid[] = array( $line, 'is not a tiktok.com link' );
		}
	}
	$valid = array_slice( array_values( array_unique( $valid ) ), 0, 30 );
	return array( 'valid' => $valid, 'invalid' => $invalid );
}

function kriswa_tiktok_urls() {
	$v = kriswa_tiktok_validate( kriswa_opt( 'tiktok_videos', false ) );
	return $v['valid'];
}

/** Look one link up through TikTok's public oEmbed and store the result (background or admin only). */
function kriswa_tiktok_resolve( $url, $force = false ) {
	$cache = get_option( 'kriswa_tt_cache', array() );
	$cache = is_array( $cache ) ? $cache : array();
	$k     = md5( $url );
	$c     = isset( $cache[ $k ] ) ? $cache[ $k ] : null;
	if ( ! $force && $c ) {
		$ttl = ! empty( $c['ok'] ) ? 12 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS;
		if ( time() - (int) $c['time'] < $ttl ) {
			return $c;
		}
	}
	$res  = wp_remote_get( 'https://www.tiktok.com/oembed?url=' . rawurlencode( $url ), array( 'timeout' => 6 ) );
	$http = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	$body = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
	if ( 200 === $http && is_array( $body ) && ! empty( $body['embed_product_id'] ) && ctype_digit( (string) $body['embed_product_id'] ) ) {
		$id    = (string) $body['embed_product_id'];
		$thumb = ! empty( $body['thumbnail_url'] ) ? kriswa_cache_remote_image( $body['thumbnail_url'], 'tt-' . $id ) : '';
		$new   = array(
			'url'     => $url,
			'id'      => $id,
			'title'   => isset( $body['title'] ) ? wp_strip_all_tags( $body['title'] ) : '',
			'author'  => isset( $body['author_name'] ) ? wp_strip_all_tags( $body['author_name'] ) : '',
			'thumb'   => $thumb,
			'ok'      => true,
			'message' => 'OK',
			'time'    => time(),
		);
	} else {
		$why = is_wp_error( $res ) ? $res->get_error_message() : ( 200 === $http ? 'TikTok gave no video id (video may be private or removed)' : 'TikTok answered HTTP ' . $http . ' (video may be private or removed)' );
		$new = array_merge( $c ? $c : array( 'url' => $url, 'id' => '', 'title' => '', 'author' => '', 'thumb' => '' ), array( 'ok' => false, 'message' => $why, 'time' => time() ) );
	}
	$cache[ $k ] = $new;
	update_option( 'kriswa_tt_cache', $cache, false );
	return $new;
}

/** What pages use: cached data only. Never contacts TikTok. */
function kriswa_tiktok_items( $limit = 6 ) {
	$cache = get_option( 'kriswa_tt_cache', array() );
	$cache = is_array( $cache ) ? $cache : array();
	$items = array();
	$stale = false;
	foreach ( array_slice( kriswa_tiktok_urls(), 0, $limit ) as $url ) {
		$c = isset( $cache[ md5( $url ) ] ) ? $cache[ md5( $url ) ] : null;
		if ( ! $c || time() - (int) $c['time'] > 12 * HOUR_IN_SECONDS ) {
			$stale = true;
		}
		if ( $c && ! empty( $c['id'] ) ) {
			$items[] = array_merge( $c, array( 'link' => $url ) );
		} elseif ( preg_match( '#/video/(\d+)#', $url, $m ) ) {
			$items[] = array( 'id' => $m[1], 'title' => '', 'thumb' => '', 'link' => $url ); // Playable even before the lookup finishes.
		}
	}
	if ( $stale ) {
		kriswa_schedule_social_refresh();
	}
	return $items;
}

/* ========================================================================= *
 * Background refresh: hourly job, plus one deduplicated event when something is stale
 * ========================================================================= */

function kriswa_schedule_social_refresh() {
	if ( get_transient( 'kriswa_social_lock' ) || ( get_transient( 'kriswa_ig_backoff' ) && ! kriswa_tiktok_urls() ) ) {
		return;
	}
	if ( ! wp_next_scheduled( 'kriswa_social_refresh_event' ) ) {
		wp_schedule_single_event( time() + 10, 'kriswa_social_refresh_event' );
	}
}

/**
 * @param bool $force Ignore cache lifetimes and backoff (admin "Refresh now").
 */
function kriswa_social_refresh_all( $force = false ) {
	if ( get_transient( 'kriswa_social_lock' ) ) {
		return;
	}
	set_transient( 'kriswa_social_lock', 1, 3 * MINUTE_IN_SECONDS );
	if ( kriswa_opt( 'instagram_token', false ) && ( $force || ! get_transient( 'kriswa_ig_fresh' ) ) ) {
		kriswa_instagram_fetch( $force );
	}
	$urls  = kriswa_tiktok_urls();
	$cache = get_option( 'kriswa_tt_cache', array() );
	$cache = is_array( $cache ) ? $cache : array();
	$keep  = array();
	foreach ( $urls as $u ) {
		$keep[ md5( $u ) ] = true;
		kriswa_tiktok_resolve( $u, $force );
	}
	// Forget links that were removed from the settings.
	$cache = array_intersect_key( (array) get_option( 'kriswa_tt_cache', array() ), $keep );
	update_option( 'kriswa_tt_cache', $cache, false );
	kriswa_prune_social_images();
	update_option( 'kriswa_social_last_run', time(), false );
	delete_transient( 'kriswa_social_lock' );
}
add_action( 'kriswa_social_refresh_event', function () { kriswa_social_refresh_all( false ); } );
add_action( 'kriswa_social_hourly', function () { kriswa_social_refresh_all( false ); } );

/** Keep the background jobs in step with what is connected. */
function kriswa_sync_social_schedules() {
	$has_ig = (bool) kriswa_opt( 'instagram_token', false );
	$has_tt = (bool) kriswa_tiktok_urls();
	$jobs   = array(
		'kriswa_social_hourly'           => array( $has_ig || $has_tt, 'hourly' ),
		'kriswa_refresh_instagram_token' => array( $has_ig, 'weekly' ),
	);
	foreach ( $jobs as $hook => $j ) {
		$ts = wp_next_scheduled( $hook );
		if ( $j[0] && ! $ts ) {
			wp_schedule_event( time() + ( 'weekly' === $j[1] ? DAY_IN_SECONDS : HOUR_IN_SECONDS ), $j[1], $hook );
		} elseif ( ! $j[0] && $ts ) {
			wp_unschedule_event( $ts, $hook );
		}
	}
}
add_action( 'init', 'kriswa_sync_social_schedules' );

/**
 * After Site Details is saved: note a newly pasted token, then load the feeds right away so the preview is live.
 * WordPress fires "add_option_*" the very first time (no saved row yet) and "update_option_*" afterwards, so both are handled.
 */
function kriswa_on_options_saved( $old, $new ) {
	if ( ! empty( $GLOBALS['kriswa_token_refreshing'] ) ) {
		return;
	}
	$old   = is_array( $old ) ? $old : array();
	$new   = is_array( $new ) ? $new : array();
	kriswa_sync_social_schedules();
	$old_t = isset( $old['instagram_token'] ) ? $old['instagram_token'] : '';
	$new_t = isset( $new['instagram_token'] ) ? $new['instagram_token'] : '';
	if ( $new_t !== $old_t ) {
		if ( $new_t ) {
			kriswa_ig_token_saved();
		} else {
			delete_option( 'kriswa_ig_token_info' );
			delete_option( 'kriswa_ig_last_good' );
			delete_option( 'kriswa_ig_status' );
		}
	}
	$old_v = isset( $old['tiktok_videos'] ) ? $old['tiktok_videos'] : '';
	$new_v = isset( $new['tiktok_videos'] ) ? $new['tiktok_videos'] : '';
	if ( $new_t !== $old_t || $new_v !== $old_v ) {
		delete_transient( 'kriswa_social_lock' );
		kriswa_social_refresh_all( true );
	}
}
add_action( 'update_option_kriswa_options', 'kriswa_on_options_saved', 10, 2 );
add_action(
	'add_option_kriswa_options',
	function ( $option, $value ) {
		kriswa_on_options_saved( array(), $value );
	},
	10,
	2
);

/* ========================================================================= *
 * Health summary (dashboard, Site Details)
 * ========================================================================= */

function kriswa_social_health() {
	$out = array();
	if ( kriswa_opt( 'instagram_token', false ) ) {
		$st   = get_option( 'kriswa_ig_status', array() );
		$info = kriswa_ig_token_info();
		if ( is_array( $st ) && isset( $st['ok'] ) && ! $st['ok'] ) {
			$out[] = array( 'error', 'Instagram is not loading: ' . $st['message'] . ' The website keeps showing the last posts.' );
		}
		if ( ! empty( $info['state'] ) && 'expired' === $info['state'] ) {
			$out[] = array( 'error', 'The Instagram token has expired or is invalid. Paste a new token in Site Details > Instagram and TikTok.' );
		} elseif ( ! empty( $info['expires_at'] ) ) {
			$days = (int) floor( ( $info['expires_at'] - time() ) / DAY_IN_SECONDS );
			if ( $days < 10 && $days >= 0 ) {
				$out[] = array( 'warn', 'The Instagram token expires in about ' . $days . ' day(s) and automatic refresh has not succeeded. Check the status in Site Details.' );
			}
		}
	}
	$bad = 0;
	foreach ( kriswa_tiktok_urls() as $u ) {
		$c = get_option( 'kriswa_tt_cache', array() );
		if ( isset( $c[ md5( $u ) ] ) && empty( $c[ md5( $u ) ]['ok'] ) ) {
			++$bad;
		}
	}
	if ( $bad ) {
		$out[] = array( 'warn', $bad . ' TikTok link(s) could not be loaded (private, removed or mistyped). See the list in Site Details.' );
	}
	return $out;
}

/* ========================================================================= *
 * Front end: [kriswa_social_feed ig="9" tt="6"]
 * ========================================================================= */

function kriswa_handle_from_url( $url ) {
	$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	$last = $path ? basename( $path ) : '';
	return $last ? '@' . ltrim( $last, '@' ) : '';
}

/** Lightbox player markup (filled by JavaScript; every tile is still a normal link without it). */
function kriswa_lightbox_html() {
	return '<dialog class="kr-lightbox" aria-label="' . esc_attr__( 'Media viewer', 'kriswa-core' ) . '"><button type="button" class="kr-lightbox__close" aria-label="' . esc_attr__( 'Close', 'kriswa-core' ) . '">&times;</button><div class="kr-lightbox__stage"><div class="kr-lightbox__body"></div><button type="button" class="kr-lightbox__nav kr-lightbox__nav--prev" aria-label="' . esc_attr__( 'Previous', 'kriswa-core' ) . '"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></button><button type="button" class="kr-lightbox__nav kr-lightbox__nav--next" aria-label="' . esc_attr__( 'Next', 'kriswa-core' ) . '"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></button></div><div class="kr-lightbox__meta"><p class="kr-lightbox__caption"></p><p class="kr-lightbox__count" aria-live="polite"></p><a class="kr-link kr-lightbox__link" target="_blank" rel="noopener" hidden></a></div></dialog>';
}

add_shortcode(
	'kriswa_social_feed',
	function ( $atts ) {
		$a      = shortcode_atts( array( 'ig' => 9, 'tt' => 6 ), $atts );
		$ig     = kriswa_instagram_feed();
		$ig_it  = array_slice( $ig['items'], 0, max( 0, (int) $a['ig'] ) );
		$tt_it  = kriswa_tiktok_items( max( 0, (int) $a['tt'] ) );
		$ig_url = kriswa_opt( 'instagram', false );
		$tt_url = kriswa_opt( 'tiktok', false );

		$panels = array();
		if ( $ig_it ) {
			$panels['ig'] = 'Instagram';
		}
		if ( $tt_it ) {
			$panels['tt'] = 'TikTok';
		}

		ob_start();
		echo '<div class="kr-social-feed" data-kr-social>';

		// Admins only: a short note when something needs attention.
		if ( current_user_can( 'manage_options' ) ) {
			foreach ( kriswa_social_health() as $h ) {
				echo '<p class="kr-social-feed__admin">' . esc_html( $h[1] ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=kriswa-settings#kriswa-social' ) ) . '">' . esc_html__( 'Open settings', 'kriswa-core' ) . '</a> <em>(' . esc_html__( 'only admins see this note', 'kriswa-core' ) . ')</em></p>';
			}
		}

		if ( ! $panels ) {
			echo '<div class="kr-card kr-social-empty"><h3>' . esc_html__( 'Follow our work', 'kriswa-core' ) . '</h3><p>' . esc_html__( 'Photos and videos from our sites and finished projects.', 'kriswa-core' ) . '</p><div class="kr-social-empty__links">';
			if ( $ig_url ) {
				echo '<a class="kr-btn" href="' . esc_url( $ig_url ) . '" target="_blank" rel="noopener">Instagram</a>';
			}
			if ( $tt_url ) {
				echo '<a class="kr-btn" href="' . esc_url( $tt_url ) . '" target="_blank" rel="noopener">TikTok</a>';
			}
			echo '</div></div></div>';
			return ob_get_clean();
		}

		if ( count( $panels ) > 1 ) {
			echo '<div class="kr-seg" role="tablist" aria-label="' . esc_attr__( 'Social channel', 'kriswa-core' ) . '">';
			$first = true;
			foreach ( $panels as $k => $label ) {
				echo '<button type="button" role="tab" id="kr-tab-' . esc_attr( $k ) . '" aria-controls="kr-panel-' . esc_attr( $k ) . '" aria-selected="' . ( $first ? 'true' : 'false' ) . '" tabindex="' . ( $first ? '0' : '-1' ) . '">' . esc_html( $label ) . '</button>';
				$first = false;
			}
			echo '</div>';
		}

		$first = true;
		foreach ( $panels as $k => $label ) {
			echo '<div class="kr-feed" role="tabpanel" id="kr-panel-' . esc_attr( $k ) . '" aria-labelledby="kr-tab-' . esc_attr( $k ) . '"' . ( $first ? '' : ' hidden' ) . '>';
			if ( 'ig' === $k ) {
				foreach ( $ig_it as $it ) {
					$alt = $it['caption'] ? wp_trim_words( $it['caption'], 16, '...' ) : __( 'Instagram post', 'kriswa-core' );
					echo '<a class="kr-tile" href="' . esc_url( $it['link'] ) . '" target="_blank" rel="noopener" data-kind="' . esc_attr( $it['kind'] ) . '" data-src="' . esc_url( $it['src'] ) . '" data-poster="' . esc_url( $it['thumb'] ) . '" data-caption="' . esc_attr( wp_trim_words( $it['caption'], 40, '...' ) ) . '" data-platform="Instagram">';
					echo '<img src="' . esc_url( $it['thumb'] ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async" referrerpolicy="no-referrer">';
					if ( 'video' === $it['kind'] ) {
						echo '<span class="kr-tile__badge" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span><span class="screen-reader-text">' . esc_html__( 'Video', 'kriswa-core' ) . '</span>';
					} elseif ( ! empty( $it['album'] ) ) {
						echo '<span class="kr-tile__badge kr-tile__badge--album" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M4 16V6a2 2 0 0 1 2-2h10"/></svg></span>';
					}
					echo '</a>';
				}
			} else {
				foreach ( $tt_it as $it ) {
					$title = ! empty( $it['title'] ) ? $it['title'] : __( 'TikTok video', 'kriswa-core' );
					echo '<a class="kr-tile kr-tile--tall" href="' . esc_url( $it['link'] ) . '" target="_blank" rel="noopener" data-kind="tiktok" data-id="' . esc_attr( $it['id'] ) . '" data-caption="' . esc_attr( wp_trim_words( $title, 40, '...' ) ) . '" data-platform="TikTok">';
					if ( ! empty( $it['thumb'] ) ) {
						echo '<img src="' . esc_url( $it['thumb'] ) . '" alt="' . esc_attr( wp_trim_words( $title, 16, '...' ) ) . '" loading="lazy" decoding="async">';
					} else {
						echo '<span class="kr-tile__ph" aria-hidden="true">TikTok</span>';
					}
					echo '<span class="kr-tile__badge" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span><span class="screen-reader-text">' . esc_html__( 'Video', 'kriswa-core' ) . '</span></a>';
				}
			}
			echo '</div>';
			$first = false;
		}

		echo '<p class="kr-feed__follow">';
		if ( $ig_url && isset( $panels['ig'] ) ) {
			echo '<a class="kr-link" href="' . esc_url( $ig_url ) . '" target="_blank" rel="noopener">' . esc_html( sprintf( /* translators: %s: Instagram handle */ __( 'Follow %s on Instagram', 'kriswa-core' ), kriswa_handle_from_url( $ig_url ) ) ) . ' <span aria-hidden="true">&rsaquo;</span></a>';
		}
		if ( $tt_url && isset( $panels['tt'] ) ) {
			echo '<a class="kr-link" href="' . esc_url( $tt_url ) . '" target="_blank" rel="noopener">' . esc_html( sprintf( /* translators: %s: TikTok handle */ __( 'Follow %s on TikTok', 'kriswa-core' ), kriswa_handle_from_url( $tt_url ) ) ) . ' <span aria-hidden="true">&rsaquo;</span></a>';
		}
		echo '</p>';
		echo kriswa_lightbox_html(); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>';
		return ob_get_clean();
	}
);

/* ========================================================================= *
 * Admin: live preview and manual refresh
 * ========================================================================= */

add_action(
	'admin_post_kriswa_refresh_social',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'kriswa_refresh_social' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'kriswa-core' ), 403 );
		}
		delete_transient( 'kriswa_social_lock' );
		kriswa_social_refresh_all( true );
		wp_safe_redirect( admin_url( 'admin.php?page=kriswa-settings&refreshed=1#kriswa-social' ) );
		exit;
	}
);

add_action(
	'admin_post_kriswa_refresh_token',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'kriswa_refresh_token' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'kriswa-core' ), 403 );
		}
		$r = kriswa_refresh_instagram_token();
		set_transient( 'kriswa_token_msg_' . get_current_user_id(), $r, 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=kriswa-settings&token=1#kriswa-social' ) );
		exit;
	}
);

function kriswa_ago( $ts ) {
	return $ts ? human_time_diff( (int) $ts ) . ' ago' : 'never';
}

/** The "are my posts loading?" panel shown at the top of Site Details > Instagram and TikTok. */
function kriswa_social_admin_panel() {
	$ig_on = (bool) kriswa_opt( 'instagram_token', false );
	$urls  = kriswa_tiktok_urls();
	$st    = get_option( 'kriswa_ig_status', array() );
	$info  = kriswa_ig_token_info();
	$items = get_option( 'kriswa_ig_last_good', array() );
	$cache = get_option( 'kriswa_tt_cache', array() );

	echo '<div id="kriswa-social" style="margin:8px 0 18px;padding:16px 20px;background:#fff;border:1px solid #c3c4c7;border-radius:4px">';
	echo '<h3 style="margin-top:0">Live preview and health</h3>';

	foreach ( kriswa_social_health() as $h ) {
		echo '<div class="notice notice-' . ( 'error' === $h[0] ? 'error' : 'warning' ) . ' inline"><p>' . esc_html( $h[1] ) . '</p></div>';
	}
	if ( isset( $_GET['refreshed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success inline"><p>Feeds refreshed just now. The preview below shows exactly what visitors see.</p></div>';
	}
	if ( isset( $_GET['token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$m = get_transient( 'kriswa_token_msg_' . get_current_user_id() );
		if ( $m ) {
			echo '<div class="notice notice-' . ( $m['ok'] ? 'success' : 'warning' ) . ' inline"><p>' . esc_html( $m['message'] ) . '</p></div>';
		}
	}

	// Instagram.
	echo '<h4 style="margin-bottom:6px">Instagram</h4>';
	if ( ! $ig_on ) {
		echo '<p>Not connected. Paste an access token below to show your photos and videos (the README has the free steps).</p>';
	} else {
		$ok = is_array( $st ) && ! empty( $st['ok'] );
		echo '<p><strong style="color:' . ( $ok ? '#00a32a' : '#d63638' ) . '">' . ( $ok ? '&#10003; Loading correctly' : '&#10007; Not loading' ) . '</strong> &ndash; ' . esc_html( is_array( $st ) && isset( $st['message'] ) ? $st['message'] : 'Waiting for the first load.' ) . ' <small>(checked ' . esc_html( kriswa_ago( is_array( $st ) && isset( $st['time'] ) ? $st['time'] : 0 ) ) . ')</small></p>';
		if ( ! empty( $info['expires_at'] ) ) {
			$days = (int) floor( ( $info['expires_at'] - time() ) / DAY_IN_SECONDS );
			echo '<p>Token: ' . ( 'expired' === ( isset( $info['state'] ) ? $info['state'] : '' ) ? '<strong style="color:#d63638">expired or invalid</strong>' : 'valid for about <strong>' . max( 0, $days ) . ' more days</strong>' . ( ! empty( $info['estimated'] ) ? ' (estimate; becomes exact after the first automatic refresh)' : '' ) ) . '. It refreshes itself every week' . ( ! empty( $info['refreshed_at'] ) ? '; last refreshed ' . esc_html( kriswa_ago( $info['refreshed_at'] ) ) : '' ) . '.';
			if ( ! empty( $info['last_error'] ) ) {
				echo ' <span style="color:#d63638">Last refresh problem: ' . esc_html( $info['last_error'] ) . '</span>';
			}
			echo '</p>';
		}
		if ( is_array( $items ) && $items ) {
			echo '<div style="display:flex;flex-wrap:wrap;gap:6px;margin:8px 0">';
			foreach ( array_slice( $items, 0, 8 ) as $it ) {
				echo '<span style="position:relative;display:block;width:84px;height:84px;background:#f0f0f1;border-radius:6px;overflow:hidden"><img src="' . esc_url( $it['thumb'] ) . '" alt="" style="width:100%;height:100%;object-fit:cover">' . ( 'video' === $it['kind'] ? '<span style="position:absolute;right:4px;bottom:4px;background:rgba(0,0,0,.6);color:#fff;border-radius:3px;font-size:10px;padding:1px 4px">video</span>' : '' ) . '</span>';
			}
			echo '</div>';
		}
	}

	// TikTok.
	echo '<h4 style="margin:16px 0 6px">TikTok</h4>';
	if ( ! $urls ) {
		echo '<p>No valid links yet. Paste video links below, one per line.</p>';
	} else {
		echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th style="width:70px">Preview</th><th>Link</th><th>Status</th></tr></thead><tbody>';
		foreach ( $urls as $u ) {
			$c  = is_array( $cache ) && isset( $cache[ md5( $u ) ] ) ? $cache[ md5( $u ) ] : null;
			$ok = $c && ! empty( $c['ok'] );
			echo '<tr><td>' . ( $c && ! empty( $c['thumb'] ) ? '<img src="' . esc_url( $c['thumb'] ) . '" alt="" style="width:48px;height:64px;object-fit:cover;border-radius:4px">' : '&mdash;' ) . '</td><td><a href="' . esc_url( $u ) . '" target="_blank" rel="noopener">' . esc_html( $u ) . '</a>' . ( $c && ! empty( $c['title'] ) ? '<br><small>' . esc_html( wp_trim_words( $c['title'], 12 ) ) . '</small>' : '' ) . '</td><td>';
			echo $c ? '<strong style="color:' . ( $ok ? '#00a32a' : '#d63638' ) . '">' . ( $ok ? '&#10003; OK' : '&#10007; Problem' ) . '</strong>' . ( $ok ? '' : '<br><small>' . esc_html( $c['message'] ) . '</small>' ) : 'Waiting for first check';
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}

	$next = wp_next_scheduled( 'kriswa_social_hourly' );
	echo '<p style="margin-top:14px">';
	echo '<a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kriswa_refresh_social' ), 'kriswa_refresh_social' ) ) . '">Refresh feeds now</a> ';
	if ( $ig_on ) {
		echo '<a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=kriswa_refresh_token' ), 'kriswa_refresh_token' ) ) . '">Refresh Instagram token now</a> ';
	}
	echo '<span class="description">Last background update: ' . esc_html( kriswa_ago( get_option( 'kriswa_social_last_run', 0 ) ) ) . ( $next ? '; next automatic check ' . esc_html( human_time_diff( $next ) ) . ' from now' : '' ) . '. Visitors never trigger these requests.</span></p>';
	echo '</div>';
}
