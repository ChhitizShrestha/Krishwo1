<?php
/**
 * Front-end output: assets, floating buttons, analytics, structured data, Nepali dates.
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		$locale = determine_locale();
		// WordPress 6.5+ reads the sibling .l10n.php file, so no .mo compile step is needed.
		load_textdomain( 'kriswa-core', KRISWA_CORE_DIR . 'languages/kriswa-core-' . $locale . '.mo', $locale );
	},
	1
);

add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'kriswa-core', KRISWA_CORE_URL . 'assets/kriswa-core.css', array(), KRISWA_CORE_VERSION );
		wp_enqueue_script( 'kriswa-core', KRISWA_CORE_URL . 'assets/kriswa-core.js', array(), KRISWA_CORE_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_localize_script(
			'kriswa-core',
			'kriswaCore',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'sending'  => __( 'Sending...', 'kriswa-core' ),
					'error'    => __( 'Sorry, something went wrong. Please call or WhatsApp us.', 'kriswa-core' ),
					'required' => __( 'This field is required.', 'kriswa-core' ),
					'phone'    => __( 'Please enter a valid phone number.', 'kriswa-core' ),
					'email'    => __( 'Please enter a valid email address.', 'kriswa-core' ),
					'submit'   => __( 'Request my free quote', 'kriswa-core' ),
				),
			)
		);
	}
);

/* Floating WhatsApp and Call buttons. */
add_action(
	'wp_footer',
	function () {
		if ( '1' !== (string) kriswa_opt( 'show_float_btns', false ) ) {
			return;
		}
		$wa = kriswa_whatsapp_url( kriswa_is_nepali() ? 'नमस्ते, म निर्माण सम्बन्धी जानकारी लिन चाहन्छु।' : 'Hello, I would like to know more about your construction services.' );
		echo '<div class="kr-float" aria-label="' . esc_attr__( 'Quick contact', 'kriswa-core' ) . '">';
		echo '<a class="kr-float__btn kr-float__btn--call" href="' . esc_url( kriswa_tel_url() ) . '" aria-label="' . esc_attr__( 'Call us', 'kriswa-core' ) . '">' . kriswa_icon( 'phone', 26 ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<a class="kr-float__btn kr-float__btn--wa" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr__( 'Chat on WhatsApp', 'kriswa-core' ) . '">' . kriswa_social_icon( 'whatsapp' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>';
	}
);

/* Search Console verification and Google Analytics 4 (both free, both optional). */
add_action(
	'wp_head',
	function () {
		$gsc = kriswa_opt( 'gsc_verification', false );
		if ( $gsc ) {
			echo '<meta name="google-site-verification" content="' . esc_attr( $gsc ) . '">' . "\n";
		}
		$ga = kriswa_opt( 'ga4_id', false );
		if ( $ga && preg_match( '/^G-[A-Z0-9]+$/', $ga ) && ! is_user_logged_in() ) {
			echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr( $ga ) . '"></script>' . "\n";
			echo '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config",' . wp_json_encode( $ga ) . ');</script>' . "\n";
		}
	},
	2
);

/* LocalBusiness structured data (helps Google show the company in local results). */
add_action(
	'wp_head',
	function () {
		if ( ! is_front_page() ) {
			return;
		}
		$same = array();
		foreach ( array( 'facebook', 'instagram', 'youtube', 'tiktok', 'linkedin' ) as $n ) {
			$u = kriswa_opt( $n, false );
			if ( $u ) {
				$same[] = $u;
			}
		}
		$data = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'GeneralContractor',
			'name'        => kriswa_opt( 'company_name', false ),
			'url'         => home_url( '/' ),
			'logo'        => get_stylesheet_directory_uri() . '/assets/images/logo.png',
			'image'       => get_stylesheet_directory_uri() . '/assets/images/logo.png',
			'description' => kriswa_opt( 'tagline', false ),
			'telephone'   => kriswa_opt( 'phone', false ),
			'email'       => kriswa_opt( 'email', false ),
			'address'     => array(
				'@type'          => 'PostalAddress',
				'streetAddress'  => kriswa_opt( 'address', false ),
				'addressCountry' => 'NP',
			),
			'areaServed'  => 'Nepal',
			'foundingDate'=> kriswa_opt( 'founded_year', false ),
			'sameAs'      => $same,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . "</script>\n";
	},
	3
);

/* Meta description fallback (only if no SEO plugin is active). */
add_action(
	'wp_head',
	function () {
		if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) || defined( 'AIOSEO_VERSION' ) ) {
			return;
		}
		$desc = '';
		if ( is_singular() ) {
			$desc = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( strip_shortcodes( get_post_field( 'post_content', get_the_ID() ) ) ), 30, '' );
		}
		if ( ! $desc ) {
			$desc = kriswa_opt( 'tagline' );
		}
		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $desc ) ) . '">' . "\n";
	},
	1
);

/* Show Bikram Sambat dates with Devanagari numerals on the Nepali version of the site. */
add_filter(
	'get_the_date',
	function ( $the_date, $format, $post ) {
		if ( ! kriswa_is_nepali() || is_admin() ) {
			return $the_date;
		}
		// Leave machine-readable formats (datetime attributes) alone.
		if ( in_array( $format, array( 'c', 'Y-m-d', 'Y-m-d\TH:i:sP', 'U', 'r' ), true ) ) {
			return $the_date;
		}
		$post = get_post( $post );
		if ( ! $post ) {
			return $the_date;
		}
		$bs = Kriswa_Nepali_Date::format( substr( $post->post_date, 0, 10 ), 'ne' );
		return $bs ? $bs : $the_date;
	},
	10,
	3
);

/* Body classes for language-specific typography. */
add_filter(
	'body_class',
	function ( $classes ) {
		$classes[] = 'kr-lang-' . kriswa_lang();
		return $classes;
	}
);

/* Never leak the WordPress version. Small hardening. */
remove_action( 'wp_head', 'wp_generator' );

/* Shortcode blocks placed in templates and patterns are not run through the_content, so expand them here. */
add_filter(
	'render_block_core/shortcode',
	function ( $content ) {
		return do_shortcode( $content );
	}
);
