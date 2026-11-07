<?php
/**
 * Front-end components exposed as shortcodes (usable in any page, pattern or template).
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Small inline SVG icon set (no icon font or external request needed).
 */
function kriswa_icon( $name, $size = 28 ) {
	$paths = array(
		'home'      => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
		'building'  => '<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2"/>',
		'sofa'      => '<path d="M5 11V8a3 3 0 0 1 3-3h8a3 3 0 0 1 3 3v3"/><path d="M3 13a2 2 0 0 1 4 0v2h10v-2a2 2 0 0 1 4 0v5H3z"/><path d="M6 18v2M18 18v2"/>',
		'ruler'     => '<path d="m4 16 12-12 4 4L8 20z"/><path d="m7 13 2 2M10 10l2 2M13 7l2 2"/>',
		'tools'     => '<path d="M14.5 6.5a4 4 0 0 0 5 5L10 21l-3-3 9.5-9.5"/><path d="m4 20 3-3"/><path d="M5 5l3 3"/>',
		'clipboard' => '<rect x="6" y="4" width="12" height="17" rx="1"/><path d="M9 4h6v3H9zM9 12h6M9 16h4"/>',
		'phone'     => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'pin'       => '<path d="M12 21s7-6.2 7-11a7 7 0 0 0-14 0c0 4.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'check'     => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
		'shield'    => '<path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6z"/><path d="m9 12 2 2 4-4"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="kr-icon" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
		(int) $size,
		$paths[ $name ]
	);
}

function kriswa_social_icon( $name ) {
	$p = array(
		'facebook'  => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v8h4v-8h3l1-4h-4V8z"/>',
		'instagram' => '<rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="3.5"/><circle cx="17" cy="7" r=".8" fill="currentColor"/>',
		'youtube'   => '<rect x="3" y="6" width="18" height="12" rx="3"/><path d="m10 9.5 5 2.5-5 2.5z" fill="currentColor"/>',
		'tiktok'    => '<path d="M14 4v10.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 4c.4 2.5 2 4 4.5 4.2"/>',
		'linkedin'  => '<rect x="4" y="9" width="3.5" height="11"/><circle cx="5.7" cy="5.5" r="1.7"/><path d="M11 20V9h3.4v1.6c.7-1.2 1.9-1.9 3.4-1.9 2.6 0 3.7 1.7 3.7 4.3V20H18v-6c0-1.4-.5-2.2-1.7-2.2S14.5 12.7 14.5 14v6z"/>',
		'whatsapp'  => '<path d="M4 20l1.3-4.2A8 8 0 1 1 8.4 18.8z"/><path d="M9 9c0 3 3 6 6 6l1-1.5-2-1-1 .7c-.8-.4-1.6-1.2-2-2l.7-1-1-2z"/>',
	);
	if ( ! isset( $p[ $name ] ) ) {
		return '';
	}
	return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p[ $name ] . '</svg>';
}

/* ------------------------------------------------------------------------- *
 * [kriswa_services count="6" link="1"]
 * ------------------------------------------------------------------------- */
add_shortcode(
	'kriswa_services',
	function ( $atts ) {
		$a = shortcode_atts( array( 'count' => 6 ), $atts );
		$q = new WP_Query(
			array(
				'post_type'      => 'kriswa_service',
				'posts_per_page' => (int) $a['count'],
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
				'no_found_rows'  => true,
			)
		);
		if ( ! $q->have_posts() ) {
			return '';
		}
		ob_start();
		echo '<div class="kr-grid kr-grid--services">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$icon = get_post_meta( get_the_ID(), '_kr_icon', true );
			echo '<article class="kr-card kr-service">';
			echo '<span class="kr-service__icon">' . kriswa_icon( $icon ? $icon : 'home', 30 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<h3 class="kr-card__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
			echo '<p class="kr-card__text">' . esc_html( wp_trim_words( get_the_excerpt(), 24 ) ) . '</p>';
			echo '<a class="kr-link" href="' . esc_url( get_permalink() ) . '">' . esc_html__( 'Learn more', 'kriswa-core' ) . ' <span aria-hidden="true">&rarr;</span></a>';
			echo '</article>';
		}
		echo '</div>';
		wp_reset_postdata();
		return ob_get_clean();
	}
);

/* ------------------------------------------------------------------------- *
 * [kriswa_projects count="9" filter="1" type="residential"]
 * ------------------------------------------------------------------------- */
add_shortcode(
	'kriswa_projects',
	function ( $atts ) {
		$a    = shortcode_atts( array( 'count' => 9, 'filter' => '0', 'type' => '' ), $atts );
		$args = array(
			'post_type'      => 'kriswa_project',
			'posts_per_page' => (int) $a['count'],
			'no_found_rows'  => true,
		);
		if ( $a['type'] ) {
			$args['tax_query'] = array( array( 'taxonomy' => 'kriswa_project_type', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a['type'] ) ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}
		$q = new WP_Query( $args );
		if ( ! $q->have_posts() ) {
			return '<p>' . esc_html__( 'Projects will appear here soon.', 'kriswa-core' ) . '</p>';
		}
		ob_start();
		echo '<div class="kr-projects" data-kr-projects>';
		if ( '1' === (string) $a['filter'] ) {
			$terms = get_terms( array( 'taxonomy' => 'kriswa_project_type', 'hide_empty' => true ) );
			if ( ! is_wp_error( $terms ) && $terms ) {
				echo '<div class="kr-filter" role="group" aria-label="' . esc_attr__( 'Filter projects', 'kriswa-core' ) . '" hidden>';
				echo '<button type="button" class="kr-filter__btn is-active" data-filter="*" aria-pressed="true">' . esc_html__( 'All', 'kriswa-core' ) . '</button>';
				foreach ( $terms as $t ) {
					echo '<button type="button" class="kr-filter__btn" data-filter="' . esc_attr( $t->slug ) . '" aria-pressed="false">' . esc_html( $t->name ) . '</button>';
				}
				echo '</div>';
			}
		}
		echo '<div class="kr-grid kr-grid--projects">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$id    = get_the_ID();
			$types = wp_get_post_terms( $id, 'kriswa_project_type' );
			$slugs = is_wp_error( $types ) ? array() : wp_list_pluck( $types, 'slug' );
			$tname = ( ! is_wp_error( $types ) && $types ) ? $types[0]->name : '';
			$loc   = get_post_meta( $id, '_kr_location', true );
			$stat  = get_post_meta( $id, '_kr_status', true );
			echo '<article class="kr-card kr-project" data-types="' . esc_attr( implode( ' ', $slugs ) ) . '">';
			echo '<a class="kr-project__media" href="' . esc_url( get_permalink() ) . '" tabindex="-1" aria-hidden="true">';
			if ( has_post_thumbnail() ) {
				the_post_thumbnail( 'large', array( 'loading' => 'lazy', 'alt' => '' ) );
			} else {
				echo '<span class="kr-project__ph">' . kriswa_icon( 'building', 48 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			if ( 'ongoing' === $stat ) {
				echo '<span class="kr-badge kr-badge--ongoing">' . esc_html__( 'Ongoing', 'kriswa-core' ) . '</span>';
			}
			echo '</a><div class="kr-project__body">';
			if ( $tname ) {
				echo '<span class="kr-tag">' . esc_html( $tname ) . '</span>';
			}
			echo '<h3 class="kr-card__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
			if ( $loc ) {
				echo '<p class="kr-meta">' . kriswa_icon( 'pin', 16 ) . ' ' . esc_html( $loc ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</div></article>';
		}
		echo '</div></div>';
		wp_reset_postdata();
		return ob_get_clean();
	}
);

/* ------------------------------------------------------------------------- *
 * [kriswa_testimonials count="3"]
 * ------------------------------------------------------------------------- */
add_shortcode(
	'kriswa_testimonials',
	function ( $atts ) {
		$a = shortcode_atts( array( 'count' => 3 ), $atts );
		$q = new WP_Query(
			array(
				'post_type'      => 'kriswa_testimonial',
				'posts_per_page' => (int) $a['count'],
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'no_found_rows'  => true,
			)
		);
		if ( ! $q->have_posts() ) {
			return '';
		}
		ob_start();
		echo '<div class="kr-grid kr-grid--testimonials">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$rating = max( 1, min( 5, (int) get_post_meta( get_the_ID(), '_kr_rating', true ) ) );
			$role   = get_post_meta( get_the_ID(), '_kr_role', true );
			echo '<figure class="kr-card kr-testimonial">';
			/* translators: %d: rating out of 5 */
			echo '<div class="kr-stars" role="img" aria-label="' . esc_attr( sprintf( __( 'Rated %d out of 5', 'kriswa-core' ), $rating ) ) . '">' . esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) ) . '</div>';
			echo '<blockquote>' . wp_kses_post( wpautop( get_the_content() ) ) . '</blockquote>';
			echo '<figcaption><strong>' . esc_html( get_the_title() ) . '</strong>';
			if ( $role ) {
				echo '<span>' . esc_html( $role ) . '</span>';
			}
			echo '</figcaption></figure>';
		}
		echo '</div>';
		wp_reset_postdata();
		return ob_get_clean();
	}
);

/* ------------------------------------------------------------------------- *
 * [kriswa_faqs]
 * ------------------------------------------------------------------------- */
add_shortcode(
	'kriswa_faqs',
	function () {
		$q = new WP_Query(
			array(
				'post_type'      => 'kriswa_faq',
				'posts_per_page' => 50,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
				'no_found_rows'  => true,
			)
		);
		if ( ! $q->have_posts() ) {
			return '';
		}
		$schema = array();
		ob_start();
		echo '<div class="kr-faq">';
		while ( $q->have_posts() ) {
			$q->the_post();
			$answer   = apply_filters( 'the_content', get_the_content() );
			$schema[] = array(
				'@type'          => 'Question',
				'name'           => get_the_title(),
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $answer ) ),
			);
			echo '<details class="kr-faq__item"><summary>' . esc_html( get_the_title() ) . '</summary><div class="kr-faq__answer">' . wp_kses_post( $answer ) . '</div></details>';
		}
		echo '</div>';
		wp_reset_postdata();
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $schema ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>';
		return ob_get_clean();
	}
);

/* ------------------------------------------------------------------------- *
 * [kriswa_contact], [kriswa_offices], [kriswa_map], [kriswa_social], [kriswa_trust]
 * ------------------------------------------------------------------------- */
add_shortcode(
	'kriswa_contact',
	function () {
		$items = array(
			array( 'phone', __( 'Call us', 'kriswa-core' ), kriswa_opt( 'phone', false ), kriswa_tel_url() ),
			array( 'mail', __( 'Email', 'kriswa-core' ), kriswa_opt( 'email', false ), 'mailto:' . antispambot( kriswa_opt( 'email', false ) ) ),
			array( 'pin', __( 'Visit us', 'kriswa-core' ), kriswa_opt( 'address' ), '' ),
			array( 'clock', __( 'Office hours', 'kriswa-core' ), kriswa_opt( 'hours' ), '' ),
		);
		ob_start();
		echo '<ul class="kr-contact">';
		foreach ( $items as $it ) {
			if ( '' === trim( (string) $it[2] ) ) {
				continue;
			}
			echo '<li><span class="kr-contact__icon">' . kriswa_icon( $it[0], 22 ) . '</span><span><small>' . esc_html( $it[1] ) . '</small>'; // phpcs:ignore WordPress.Security.EscapeOutput
			if ( $it[3] ) {
				echo '<a href="' . esc_url( $it[3] ) . '">' . esc_html( $it[2] ) . '</a>';
			} else {
				echo '<span>' . esc_html( $it[2] ) . '</span>';
			}
			echo '</span></li>';
		}
		echo '<li><span class="kr-contact__icon">' . kriswa_social_icon( 'whatsapp' ) . '</span><span><small>WhatsApp</small><a href="' . esc_url( kriswa_whatsapp_url() ) . '" rel="noopener" target="_blank">' . esc_html__( 'Chat on WhatsApp', 'kriswa-core' ) . '</a></span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</ul>';
		return ob_get_clean();
	}
);

add_shortcode(
	'kriswa_offices',
	function () {
		$lines = preg_split( '/\r\n|\r|\n/', (string) kriswa_opt( 'offices', false ) );
		ob_start();
		echo '<div class="kr-offices">';
		foreach ( $lines as $line ) {
			$p = array_map( 'trim', explode( '|', $line ) );
			if ( empty( $p[0] ) ) {
				continue;
			}
			echo '<div class="kr-card kr-office"><h3 class="kr-card__title">' . esc_html( $p[0] ) . '</h3>';
			if ( ! empty( $p[1] ) ) {
				echo '<p>' . esc_html( $p[1] ) . '</p>';
			}
			if ( ! empty( $p[2] ) ) {
				echo '<p><a href="tel:+' . esc_attr( kriswa_digits( $p[2] ) ) . '">' . esc_html( $p[2] ) . '</a></p>';
			}
			if ( ! empty( $p[3] ) ) {
				echo '<a class="kr-link" target="_blank" rel="noopener" href="' . esc_url( $p[3] ) . '">' . esc_html__( 'View on map', 'kriswa-core' ) . ' <span aria-hidden="true">&rarr;</span></a>';
			}
			echo '</div>';
		}
		echo '</div>';
		return ob_get_clean();
	}
);

add_shortcode(
	'kriswa_map',
	function () {
		$src = kriswa_opt( 'map_embed_url', false );
		if ( ! $src ) {
			return '';
		}
		return '<div class="kr-map"><iframe src="' . esc_url( $src ) . '" title="' . esc_attr__( 'Office location map', 'kriswa-core' ) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>';
	}
);

add_shortcode(
	'kriswa_social',
	function () {
		$out = '<ul class="kr-social">';
		foreach ( array( 'facebook', 'instagram', 'youtube', 'tiktok', 'linkedin' ) as $net ) {
			$url = kriswa_opt( $net, false );
			if ( $url ) {
				$out .= '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( ucfirst( $net ) ) . '">' . kriswa_social_icon( $net ) . '</a></li>';
			}
		}
		$out .= '<li><a href="' . esc_url( kriswa_whatsapp_url() ) . '" target="_blank" rel="noopener" aria-label="WhatsApp">' . kriswa_social_icon( 'whatsapp' ) . '</a></li>';
		return $out . '</ul>';
	}
);

add_shortcode(
	'kriswa_trust',
	function () {
		$items = array(
			array( __( 'Company registration', 'kriswa-core' ), kriswa_opt( 'registration_no', false ) ),
			array( __( 'PAN / VAT', 'kriswa-core' ), kriswa_opt( 'pan_vat', false ) ),
			array( __( 'Engineering Council', 'kriswa-core' ), kriswa_opt( 'nec_no', false ) ),
			array( __( 'Contractor license', 'kriswa-core' ), kriswa_opt( 'license_class', false ) ),
		);
		$out = '<ul class="kr-trust">';
		foreach ( $items as $it ) {
			if ( '' === trim( (string) $it[1] ) ) {
				continue;
			}
			$out .= '<li>' . kriswa_icon( 'shield', 26 ) . '<span><small>' . esc_html( $it[0] ) . '</small><strong>' . esc_html( $it[1] ) . '</strong></span></li>';
		}
		return $out . '</ul>';
	}
);

/* ------------------------------------------------------------------------- *
 * [kriswa_lang_switcher]  (needs the free Polylang plugin; prints nothing otherwise)
 * ------------------------------------------------------------------------- */
add_shortcode(
	'kriswa_lang_switcher',
	function () {
		if ( ! function_exists( 'pll_the_languages' ) ) {
			return '';
		}
		$langs = pll_the_languages( array( 'raw' => 1, 'hide_if_empty' => 0 ) );
		if ( ! is_array( $langs ) || count( $langs ) < 2 ) {
			return '';
		}
		$out = '<nav class="kr-lang" aria-label="' . esc_attr__( 'Language', 'kriswa-core' ) . '">';
		foreach ( $langs as $l ) {
			$label = ( 'ne' === substr( $l['slug'], 0, 2 ) ) ? 'नेपाली' : 'EN';
			$out  .= '<a href="' . esc_url( $l['url'] ) . '" hreflang="' . esc_attr( $l['locale'] ) . '" lang="' . esc_attr( $l['slug'] ) . '"' . ( $l['current_lang'] ? ' aria-current="true" class="is-current"' : '' ) . '>' . esc_html( $label ) . '</a>';
		}
		return $out . '</nav>';
	}
);

/* ------------------------------------------------------------------------- *
 * [kriswa_project_facts]  - used on single project pages
 * ------------------------------------------------------------------------- */
add_shortcode(
	'kriswa_project_facts',
	function () {
		$id = get_the_ID();
		if ( ! $id || 'kriswa_project' !== get_post_type( $id ) ) {
			return '';
		}
		$stat  = get_post_meta( $id, '_kr_status', true );
		$rows  = array(
			__( 'Location', 'kriswa-core' )   => get_post_meta( $id, '_kr_location', true ),
			__( 'Client', 'kriswa-core' )     => get_post_meta( $id, '_kr_client', true ),
			__( 'Built-up area', 'kriswa-core' ) => get_post_meta( $id, '_kr_area', true ),
			__( 'Status', 'kriswa-core' )     => 'ongoing' === $stat ? __( 'Ongoing', 'kriswa-core' ) : __( 'Completed', 'kriswa-core' ),
		);
		$year = get_post_meta( $id, '_kr_year', true );
		if ( $year ) {
			$rows[ __( 'Year', 'kriswa-core' ) ] = kriswa_is_nepali() ? Kriswa_Nepali_Date::numerals( $year ) . ' ' . __( '(AD)', 'kriswa-core' ) : $year;
		}
		$types = get_the_terms( $id, 'kriswa_project_type' );
		if ( $types && ! is_wp_error( $types ) ) {
			$rows[ __( 'Type', 'kriswa-core' ) ] = implode( ', ', wp_list_pluck( $types, 'name' ) );
		}
		ob_start();
		echo '<dl class="kr-facts">';
		foreach ( $rows as $label => $val ) {
			if ( '' === trim( (string) $val ) ) {
				continue;
			}
			echo '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $val ) . '</dd></div>';
		}
		echo '</dl>';
		$before = get_post_meta( $id, '_kr_before', true );
		$after  = get_post_meta( $id, '_kr_after', true );
		if ( $before && $after ) {
			echo '<h2 class="kr-subhead">' . esc_html__( 'Before and after', 'kriswa-core' ) . '</h2>';
			echo kriswa_before_after_html( $before, $after ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		$video = get_post_meta( $id, '_kr_video', true );
		if ( $video ) {
			$embed = wp_oembed_get( $video, array( 'width' => 900 ) );
			if ( $embed ) {
				echo '<h2 class="kr-subhead">' . esc_html__( 'Project video', 'kriswa-core' ) . '</h2><div class="kr-video">' . $embed . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
		return ob_get_clean();
	}
);

function kriswa_before_after_html( $before, $after ) {
	return '<div class="kr-ba" data-kr-ba><img class="kr-ba__img" src="' . esc_url( $after ) . '" alt="' . esc_attr__( 'After', 'kriswa-core' ) . '" loading="lazy"><div class="kr-ba__before"><img class="kr-ba__img" src="' . esc_url( $before ) . '" alt="' . esc_attr__( 'Before', 'kriswa-core' ) . '" loading="lazy"></div><input class="kr-ba__range" type="range" min="0" max="100" value="50" aria-label="' . esc_attr__( 'Drag to compare before and after', 'kriswa-core' ) . '"><span class="kr-ba__label kr-ba__label--b">' . esc_html__( 'Before', 'kriswa-core' ) . '</span><span class="kr-ba__label kr-ba__label--a">' . esc_html__( 'After', 'kriswa-core' ) . '</span></div>';
}

/* ------------------------------------------------------------------------- *
 * [kriswa_newsletter]
 * ------------------------------------------------------------------------- */
add_shortcode(
	'kriswa_newsletter',
	function () {
		$action = kriswa_opt( 'newsletter_url', false );
		$field  = kriswa_opt( 'newsletter_field', false );
		ob_start();
		if ( $action ) {
			// External free provider (Mailchimp / Brevo hosted form action).
			echo '<form class="kr-news" action="' . esc_url( $action ) . '" method="post" target="_blank" novalidate>';
			echo '<label class="screen-reader-text" for="kr-news-email">' . esc_html__( 'Email address', 'kriswa-core' ) . '</label>';
			echo '<input id="kr-news-email" type="email" name="' . esc_attr( $field ? $field : 'EMAIL' ) . '" placeholder="' . esc_attr__( 'Your email address', 'kriswa-core' ) . '" required>';
			echo '<button type="submit" class="kr-btn">' . esc_html__( 'Subscribe', 'kriswa-core' ) . '</button></form>';
		} else {
			echo '<form class="kr-news" data-kr-news method="post" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" novalidate>';
			echo '<input type="hidden" name="action" value="kriswa_newsletter">';
			echo '<div class="kr-hp" aria-hidden="true"><label>Website<input type="text" name="kr_website" tabindex="-1" autocomplete="off"></label></div>';
			echo '<label class="screen-reader-text" for="kr-news-email">' . esc_html__( 'Email address', 'kriswa-core' ) . '</label>';
			echo '<input id="kr-news-email" type="email" name="kr_email" placeholder="' . esc_attr__( 'Your email address', 'kriswa-core' ) . '" required>';
			echo '<button type="submit" class="kr-btn">' . esc_html__( 'Subscribe', 'kriswa-core' ) . '</button>';
			echo '<p class="kr-news__msg" role="status" aria-live="polite"></p></form>';
		}
		return ob_get_clean();
	}
);

/* ------------------------------------------------------------------------- *
 * [kriswa_quote_form]  - three-step request-a-quote form
 * ------------------------------------------------------------------------- */
add_shortcode(
	'kriswa_quote_form',
	function () {
		$preset = isset( $_GET['service'] ) ? sanitize_key( wp_unslash( $_GET['service'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$sent   = isset( $_GET['kr_sent'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$err    = isset( $_GET['kr_err'] ) ? sanitize_text_field( wp_unslash( $_GET['kr_err'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		ob_start();
		echo '<div class="kr-quote" id="kriswa-quote" data-kr-quote>';

		echo '<div class="kr-quote__done" role="status" aria-live="polite"' . ( $sent ? '' : ' hidden' ) . '>';
		echo kriswa_icon( 'check', 44 ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<h3>' . esc_html__( 'Thank you! We have your request.', 'kriswa-core' ) . '</h3>';
		echo '<p>' . esc_html__( 'Our team will contact you shortly to discuss your project.', 'kriswa-core' ) . '</p>';
		echo '<a class="kr-btn kr-btn--wa" data-kr-wa href="' . esc_url( kriswa_whatsapp_url() ) . '" target="_blank" rel="noopener">' . esc_html__( 'Chat with us on WhatsApp', 'kriswa-core' ) . '</a>';
		echo '</div>';

		if ( $sent ) {
			echo '</div>';
			return ob_get_clean();
		}

		echo '<form class="kr-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" novalidate>';
		echo '<input type="hidden" name="action" value="kriswa_quote">';
		echo '<div class="kr-hp" aria-hidden="true"><label>Website<input type="text" name="kr_website" tabindex="-1" autocomplete="off"></label></div>';
		echo '<div class="kr-form__error" role="alert" aria-live="assertive"' . ( $err ? '' : ' hidden' ) . '>' . esc_html( $err ) . '</div>';

		echo '<ol class="kr-steps" aria-hidden="true"><li class="is-active">1. ' . esc_html__( 'Your project', 'kriswa-core' ) . '</li><li>2. ' . esc_html__( 'Details', 'kriswa-core' ) . '</li><li>3. ' . esc_html__( 'Contact', 'kriswa-core' ) . '</li></ol>';

		// Step 1.
		echo '<fieldset class="kr-step is-active" data-step="1"><legend>' . esc_html__( 'What do you need?', 'kriswa-core' ) . '</legend>';
		echo '<div class="kr-field"><label for="kr_service">' . esc_html__( 'Service', 'kriswa-core' ) . ' *</label><select id="kr_service" name="kr_service" required>';
		echo '<option value="">' . esc_html__( 'Choose a service', 'kriswa-core' ) . '</option>';
		foreach ( kriswa_quote_services() as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $preset, $k, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select><span class="kr-field__err" data-err="kr_service"></span></div>';
		echo '<div class="kr-field"><label for="kr_location">' . esc_html__( 'Project location', 'kriswa-core' ) . '</label><input id="kr_location" name="kr_location" type="text" autocomplete="off" placeholder="' . esc_attr__( 'e.g. Budhanilkantha, Kathmandu', 'kriswa-core' ) . '"></div>';
		echo '<div class="kr-field"><label for="kr_area">' . esc_html__( 'Plot or building size (optional)', 'kriswa-core' ) . '</label><input id="kr_area" name="kr_area" type="text" placeholder="' . esc_attr__( 'e.g. 4 aana, or 2,500 sq. ft.', 'kriswa-core' ) . '"></div>';
		echo '</fieldset>';

		// Step 2.
		echo '<fieldset class="kr-step" data-step="2"><legend>' . esc_html__( 'Budget and timing', 'kriswa-core' ) . '</legend>';
		echo '<div class="kr-field"><label for="kr_budget">' . esc_html__( 'Estimated budget', 'kriswa-core' ) . '</label><select id="kr_budget" name="kr_budget">';
		foreach ( kriswa_quote_budgets() as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</option>';
		}
		echo '</select></div>';
		echo '<div class="kr-field"><label for="kr_timeline">' . esc_html__( 'When do you want to start?', 'kriswa-core' ) . '</label><select id="kr_timeline" name="kr_timeline">';
		foreach ( kriswa_quote_timelines() as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</option>';
		}
		echo '</select></div>';
		echo '<div class="kr-field"><label for="kr_message">' . esc_html__( 'Tell us about your project', 'kriswa-core' ) . '</label><textarea id="kr_message" name="kr_message" rows="4"></textarea></div>';
		echo '</fieldset>';

		// Step 3.
		echo '<fieldset class="kr-step" data-step="3"><legend>' . esc_html__( 'How can we reach you?', 'kriswa-core' ) . '</legend>';
		echo '<div class="kr-field"><label for="kr_name">' . esc_html__( 'Full name', 'kriswa-core' ) . ' *</label><input id="kr_name" name="kr_name" type="text" autocomplete="name" required><span class="kr-field__err" data-err="kr_name"></span></div>';
		echo '<div class="kr-field"><label for="kr_phone">' . esc_html__( 'Phone / WhatsApp number', 'kriswa-core' ) . ' *</label><input id="kr_phone" name="kr_phone" type="tel" autocomplete="tel" inputmode="tel" required><span class="kr-field__err" data-err="kr_phone"></span></div>';
		echo '<div class="kr-field"><label for="kr_email">' . esc_html__( 'Email (optional)', 'kriswa-core' ) . '</label><input id="kr_email" name="kr_email" type="email" autocomplete="email"><span class="kr-field__err" data-err="kr_email"></span></div>';
		echo '<div class="kr-field"><label for="kr_pref">' . esc_html__( 'Preferred contact method', 'kriswa-core' ) . '</label><select id="kr_pref" name="kr_pref">';
		foreach ( kriswa_quote_contact_prefs() as $k => $l ) {
			echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</option>';
		}
		echo '</select></div>';
		echo '<div class="kr-field kr-field--check"><label><input type="checkbox" name="kr_consent" value="1" required> ' . esc_html__( 'I agree to be contacted about my request.', 'kriswa-core' ) . '</label><span class="kr-field__err" data-err="kr_consent"></span></div>';
		echo '</fieldset>';

		echo '<div class="kr-form__nav"><button type="button" class="kr-btn kr-btn--ghost" data-prev hidden>' . esc_html__( 'Back', 'kriswa-core' ) . '</button><button type="button" class="kr-btn" data-next hidden>' . esc_html__( 'Next', 'kriswa-core' ) . '</button><button type="submit" class="kr-btn" data-submit>' . esc_html__( 'Request my free quote', 'kriswa-core' ) . '</button></div>';
		echo '</form></div>';
		return ob_get_clean();
	}
);
