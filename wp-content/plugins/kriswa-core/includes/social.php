<?php
/**
 * Instagram and TikTok feed: photos and videos shown (and played) on the website.
 *
 * Instagram: uses the free "Instagram API with Instagram Login" with a long-lived access token
 *            (Business or Creator account). The token is refreshed automatically every week.
 * TikTok:    uses TikTok's free public oEmbed endpoint for the video links you paste into
 *            Settings > Kriswa. (Listing a whole TikTok profile automatically needs TikTok's
 *            approved Display API, which is not free of review; pasted links need nothing.)
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

const KRISWA_IG_API = 'https://graph.instagram.com';

/* ------------------------------------------------------------------------- *
 * Instagram
 * ------------------------------------------------------------------------- */

/**
 * Fetch the latest Instagram posts.
 *
 * @return array { status: ok|not_connected|error, message: string, items: array }
 */
function kriswa_instagram_feed( $force = false ) {
	$token = kriswa_opt( 'instagram_token', false );
	if ( ! $token ) {
		return array( 'status' => 'not_connected', 'message' => '', 'items' => array() );
	}
	if ( ! $force ) {
		$cached = get_transient( 'kriswa_ig_feed' );
		if ( is_array( $cached ) ) {
			return array( 'status' => 'ok', 'message' => '', 'items' => $cached );
		}
	}

	$res = wp_remote_get(
		add_query_arg(
			array(
				'fields'       => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
				'limit'        => 30,
				'access_token' => $token,
			),
			KRISWA_IG_API . '/me/media'
		),
		array( 'timeout' => 8 )
	);

	$fail = function ( $msg ) {
		update_option( 'kriswa_ig_status', array( 'ok' => false, 'message' => $msg, 'time' => time() ), false );
		// Keep showing the last good posts rather than an empty section.
		$last = get_option( 'kriswa_ig_last_good', array() );
		set_transient( 'kriswa_ig_feed', is_array( $last ) ? $last : array(), 10 * MINUTE_IN_SECONDS );
		return array( 'status' => 'error', 'message' => $msg, 'items' => is_array( $last ) ? $last : array() );
	};

	if ( is_wp_error( $res ) ) {
		return $fail( $res->get_error_message() );
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( 200 !== $code || ! is_array( $body ) || empty( $body['data'] ) ) {
		$msg = isset( $body['error']['message'] ) ? $body['error']['message'] : 'Instagram returned HTTP ' . $code;
		return $fail( $msg );
	}

	$items = array();
	foreach ( $body['data'] as $m ) {
		if ( empty( $m['media_url'] ) || empty( $m['permalink'] ) ) {
			continue;
		}
		$type    = isset( $m['media_type'] ) ? $m['media_type'] : 'IMAGE';
		$items[] = array(
			'id'        => (string) $m['id'],
			'kind'      => 'VIDEO' === $type ? 'video' : 'image',
			'src'       => esc_url_raw( $m['media_url'] ),
			'thumb'     => esc_url_raw( 'VIDEO' === $type && ! empty( $m['thumbnail_url'] ) ? $m['thumbnail_url'] : $m['media_url'] ),
			'link'      => esc_url_raw( $m['permalink'] ),
			'caption'   => isset( $m['caption'] ) ? wp_strip_all_tags( $m['caption'] ) : '',
			'album'     => 'CAROUSEL_ALBUM' === $type,
			'timestamp' => isset( $m['timestamp'] ) ? $m['timestamp'] : '',
		);
	}
	set_transient( 'kriswa_ig_feed', $items, HOUR_IN_SECONDS );
	update_option( 'kriswa_ig_last_good', $items, false );
	update_option( 'kriswa_ig_status', array( 'ok' => true, 'message' => count( $items ) . ' posts loaded', 'time' => time() ), false );
	return array( 'status' => 'ok', 'message' => '', 'items' => $items );
}

/**
 * Long-lived Instagram tokens last 60 days. Refreshing weekly keeps them alive.
 */
function kriswa_refresh_instagram_token() {
	$opts  = get_option( 'kriswa_options', array() );
	$token = isset( $opts['instagram_token'] ) ? $opts['instagram_token'] : '';
	if ( ! $token ) {
		return false;
	}
	$res  = wp_remote_get(
		add_query_arg( array( 'grant_type' => 'ig_refresh_token', 'access_token' => $token ), KRISWA_IG_API . '/refresh_access_token' ),
		array( 'timeout' => 10 )
	);
	$body = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
	if ( is_array( $body ) && ! empty( $body['access_token'] ) ) {
		$opts['instagram_token'] = sanitize_text_field( $body['access_token'] );
		update_option( 'kriswa_options', $opts );
		update_option( 'kriswa_ig_token_refreshed', time(), false );
		return true;
	}
	update_option( 'kriswa_ig_status', array( 'ok' => false, 'message' => 'Token refresh failed: ' . ( isset( $body['error']['message'] ) ? $body['error']['message'] : 'no response' ), 'time' => time() ), false );
	return false;
}
add_action( 'kriswa_refresh_instagram_token', 'kriswa_refresh_instagram_token' );

add_action(
	'init',
	function () {
		$has = (bool) kriswa_opt( 'instagram_token', false );
		$ts  = wp_next_scheduled( 'kriswa_refresh_instagram_token' );
		if ( $has && ! $ts ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'weekly', 'kriswa_refresh_instagram_token' );
		} elseif ( ! $has && $ts ) {
			wp_unschedule_event( $ts, 'kriswa_refresh_instagram_token' );
		}
	}
);

/* ------------------------------------------------------------------------- *
 * TikTok (oEmbed, free and keyless)
 * ------------------------------------------------------------------------- */

function kriswa_tiktok_urls() {
	$lines = preg_split( '/\r\n|\r|\n/', (string) kriswa_opt( 'tiktok_videos', false ) );
	$out   = array();
	foreach ( $lines as $l ) {
		$u = esc_url_raw( trim( $l ), array( 'https' ) );
		if ( $u && preg_match( '#^https://([a-z0-9-]+\.)?tiktok\.com/#i', $u ) ) {
			$out[] = $u;
		}
	}
	return array_values( array_unique( $out ) );
}

/**
 * @param bool $allow_fetch Whether uncached items may be fetched now.
 */
function kriswa_tiktok_item( $url, &$budget ) {
	$key  = 'kriswa_tt_' . md5( $url );
	$data = get_transient( $key );
	if ( false === $data ) {
		if ( $budget <= 0 ) {
			$data = array( 'pending' => true );
		} else {
			--$budget;
			$res  = wp_remote_get( add_query_arg( 'url', rawurlencode( $url ), 'https://www.tiktok.com/oembed' ), array( 'timeout' => 4 ) );
			$body = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
			if ( is_array( $body ) && ! empty( $body['embed_product_id'] ) ) {
				$data = array(
					'id'     => preg_replace( '/\D/', '', $body['embed_product_id'] ),
					'title'  => isset( $body['title'] ) ? wp_strip_all_tags( $body['title'] ) : '',
					'author' => isset( $body['author_name'] ) ? wp_strip_all_tags( $body['author_name'] ) : '',
					'thumb'  => ! empty( $body['thumbnail_url'] ) ? esc_url_raw( $body['thumbnail_url'] ) : '',
				);
				set_transient( $key, $data, 12 * HOUR_IN_SECONDS );
			} else {
				// Remember the failure briefly so a slow response never delays every page view.
				$data = array( 'failed' => true );
				set_transient( $key, $data, 15 * MINUTE_IN_SECONDS );
			}
		}
	}
	if ( empty( $data['id'] ) && preg_match( '#/video/(\d+)#', $url, $m ) ) {
		$data['id'] = $m[1];
	}
	if ( empty( $data['id'] ) ) {
		return null;
	}
	$data['link'] = $url;
	return $data;
}

function kriswa_tiktok_items( $limit = 6 ) {
	$budget = 3; // At most three live lookups per page view; the rest arrive from cache next time.
	$items  = array();
	foreach ( array_slice( kriswa_tiktok_urls(), 0, $limit ) as $url ) {
		$it = kriswa_tiktok_item( $url, $budget );
		if ( $it ) {
			$items[] = $it;
		}
	}
	return $items;
}

/** Warm the caches right after settings are saved. */
add_action(
	'update_option_kriswa_options',
	function () {
		delete_transient( 'kriswa_ig_feed' );
		if ( kriswa_opt( 'instagram_token', false ) ) {
			kriswa_instagram_feed( true );
		}
		foreach ( kriswa_tiktok_urls() as $u ) {
			delete_transient( 'kriswa_tt_' . md5( $u ) );
		}
		$budget = 12;
		foreach ( kriswa_tiktok_urls() as $u ) {
			kriswa_tiktok_item( $u, $budget );
		}
	}
);

/* ------------------------------------------------------------------------- *
 * Shortcode: [kriswa_social_feed ig="9" tt="6"]
 * ------------------------------------------------------------------------- */

function kriswa_handle_from_url( $url ) {
	$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	$last = $path ? basename( $path ) : '';
	return $last ? '@' . ltrim( $last, '@' ) : '';
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

		// Admin-only hint when something needs attention.
		if ( current_user_can( 'manage_options' ) && ( 'error' === $ig['status'] || 'not_connected' === $ig['status'] ) ) {
			$msg = 'error' === $ig['status'] ? 'Instagram: ' . $ig['message'] : 'Instagram is not connected yet.';
			echo '<p class="kr-social-feed__admin">' . esc_html( $msg ) . ' <a href="' . esc_url( admin_url( 'options-general.php?page=kriswa-settings#kriswa-social' ) ) . '">' . esc_html__( 'Open settings', 'kriswa-core' ) . '</a> <em>(' . esc_html__( 'only admins see this note', 'kriswa-core' ) . ')</em></p>';
		}

		if ( ! $panels ) {
			// Nothing connected yet: a tidy "follow us" card so the section never looks broken.
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
						echo '<img src="' . esc_url( $it['thumb'] ) . '" alt="' . esc_attr( wp_trim_words( $title, 16, '...' ) ) . '" loading="lazy" decoding="async" referrerpolicy="no-referrer">';
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

		// Lightbox player (filled by JavaScript; each tile is a normal link without it).
		echo '<dialog class="kr-lightbox" aria-label="' . esc_attr__( 'Media viewer', 'kriswa-core' ) . '"><button type="button" class="kr-lightbox__close" aria-label="' . esc_attr__( 'Close', 'kriswa-core' ) . '">&times;</button><div class="kr-lightbox__body"></div><div class="kr-lightbox__meta"><p class="kr-lightbox__caption"></p><a class="kr-link kr-lightbox__link" href="#" target="_blank" rel="noopener"></a></div></dialog>';
		echo '</div>';
		return ob_get_clean();
	}
);

/* ------------------------------------------------------------------------- *
 * Settings page extras: connection status and "Refresh now"
 * ------------------------------------------------------------------------- */

add_action(
	'admin_post_kriswa_refresh_social',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'kriswa_refresh_social' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'kriswa-core' ), 403 );
		}
		delete_transient( 'kriswa_ig_feed' );
		if ( kriswa_opt( 'instagram_token', false ) ) {
			kriswa_instagram_feed( true );
		}
		$budget = 12;
		foreach ( kriswa_tiktok_urls() as $u ) {
			delete_transient( 'kriswa_tt_' . md5( $u ) );
			kriswa_tiktok_item( $u, $budget );
		}
		wp_safe_redirect( admin_url( 'options-general.php?page=kriswa-settings&refreshed=1#kriswa-social' ) );
		exit;
	}
);

function kriswa_social_status_box() {
	$st = get_option( 'kriswa_ig_status' );
	echo '<div id="kriswa-social" style="margin:12px 0 0;padding:12px 16px;background:#fff;border:1px solid #c3c4c7;border-left:4px solid ' . ( is_array( $st ) && ! empty( $st['ok'] ) ? '#00a32a' : '#dba617' ) . '">';
	if ( ! kriswa_opt( 'instagram_token', false ) ) {
		echo '<strong>Instagram:</strong> not connected. Paste an access token below (see the README for the free, 5-minute steps).';
	} elseif ( is_array( $st ) ) {
		echo '<strong>Instagram:</strong> ' . esc_html( $st['message'] ) . ' <small>(' . esc_html( human_time_diff( $st['time'] ) ) . ' ago)</small>';
	} else {
		echo '<strong>Instagram:</strong> token saved, feed not loaded yet.';
	}
	$urls = kriswa_tiktok_urls();
	echo '<br><strong>TikTok:</strong> ' . (int) count( $urls ) . ' video link(s) saved.';
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=kriswa_refresh_social' ), 'kriswa_refresh_social' );
	echo ' <a class="button button-small" href="' . esc_url( $url ) . '">Refresh feeds now</a>';
	if ( isset( $_GET['refreshed'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo ' <span style="color:#00a32a">Refreshed.</span>';
	}
	echo '</div>';
}
