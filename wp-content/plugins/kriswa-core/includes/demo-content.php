<?php
/**
 * One-click setup: creates the pages, menu-free navigation targets, sample services, projects,
 * testimonials, FAQs and posts, and applies sensible site settings.
 * Everything created is clearly marked as sample content to be replaced.
 * Run from Tools > Kriswa Setup, or with WP-CLI: wp kriswa setup
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_menu',
	function () {
		add_management_page( 'Kriswa Setup', 'Kriswa Setup', 'manage_options', 'kriswa-setup', 'kriswa_setup_page' );
	}
);

function kriswa_setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap"><h1>Kriswa Setup</h1>';
	if ( isset( $_POST['kriswa_setup'] ) && check_admin_referer( 'kriswa_setup' ) ) {
		$log = kriswa_run_setup( ! empty( $_POST['with_samples'] ) );
		echo '<div class="notice notice-success"><p><strong>Setup finished.</strong></p><ul style="list-style:disc;margin-left:2em">';
		foreach ( $log as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul></div>';
	}
	echo '<p>Creates the site pages (Home, About, Services, Get a Quote, FAQ, Contact, Blog), applies recommended settings (static front page, pretty links, Kathmandu timezone) and optionally adds <strong>sample</strong> services, projects, testimonials, FAQs and posts. It is safe to run again: existing items are skipped.</p>';
	echo '<form method="post">';
	wp_nonce_field( 'kriswa_setup' );
	echo '<p><label><input type="checkbox" name="with_samples" value="1" checked> Also create sample content (replace it with your real content)</label></p>';
	echo '<p><button class="button button-primary" name="kriswa_setup" value="1">Run setup</button></p></form>';
	if ( ! function_exists( 'pll_languages_list' ) ) {
		echo '<p><strong>Nepali:</strong> install and activate the free <em>Polylang</em> plugin, add English and Nepali under Languages, then run this setup again to also create the Nepali pages.</p>';
	}
	echo '</div>';
}

/**
 * Find an existing post by type and slug.
 */
function kriswa_find_post( $type, $slug ) {
	$found = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => $type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'lang'           => '', // Polylang: search all languages.
		)
	);
	return $found ? (int) $found[0] : 0;
}

/**
 * Create a post once. Returns the ID (new or existing).
 */
function kriswa_seed( $type, $slug, $title, $content = '', $meta = array(), $extra = array(), &$log = array() ) {
	$id = kriswa_find_post( $type, $slug );
	if ( $id ) {
		return $id;
	}
	$id = wp_insert_post(
		array_merge(
			array(
				'post_type'    => $type,
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_title'   => $title,
				'post_content' => $content,
			),
			$extra
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		$log[] = 'Could not create ' . $type . ' ' . $slug . ': ' . $id->get_error_message();
		return 0;
	}
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	$log[] = 'Created ' . $type . ': ' . $title;
	return $id;
}

/**
 * Create an English post and, when Polylang is active, its Nepali counterpart linked as a translation.
 */
function kriswa_seed_pair( $type, $slug, $en, $ne, $meta = array(), &$log = array() ) {
	$en_id = kriswa_seed( $type, $slug, $en['title'], isset( $en['content'] ) ? $en['content'] : '', $meta, isset( $en['extra'] ) ? $en['extra'] : array(), $log );
	if ( $en_id && function_exists( 'pll_set_post_language' ) && function_exists( 'pll_languages_list' ) ) {
		$langs = pll_languages_list();
		$en_l  = in_array( 'en', $langs, true ) ? 'en' : $langs[0];
		if ( ! pll_get_post_language( $en_id ) ) {
			pll_set_post_language( $en_id, $en_l );
		}
		if ( $ne && in_array( 'ne', $langs, true ) ) {
			$ne_id = kriswa_seed( $type, $slug . '-ne', $ne['title'], isset( $ne['content'] ) ? $ne['content'] : '', $meta, isset( $ne['extra'] ) ? $ne['extra'] : array(), $log );
			if ( $ne_id ) {
				if ( 'ne' !== pll_get_post_language( $ne_id ) ) {
					pll_set_post_language( $ne_id, 'ne' );
				}
				pll_save_post_translations( array( $en_l => $en_id, 'ne' => $ne_id ) );
			}
			return $en_id;
		}
	}
	return $en_id;
}

function kriswa_run_setup( $with_samples = true ) {
	$log = array();

	/* ---- Pages ---- */
	$pages = array(
		'home'        => array( 'Home', 'गृहपृष्ठ', '' ),
		'about'       => array( 'About Us', 'हाम्रो बारेमा', 'page-about' ),
		'services'    => array( 'Our Services', 'हाम्रा सेवाहरू', 'page-services' ),
		'get-a-quote' => array( 'Get a Free Quote', 'निःशुल्क कोटेसन', 'page-quote' ),
		'faq'         => array( 'FAQ', 'बारम्बार सोधिने प्रश्नहरू', 'page-faq' ),
		'contact'     => array( 'Contact Us', 'सम्पर्क', 'page-contact' ),
		'blog'        => array( 'Blog', 'ब्लग', '' ),
	);
	$ids = array();
	foreach ( $pages as $slug => $p ) {
		$meta = $p[2] ? array( '_wp_page_template' => $p[2] ) : array();
		$ids[ $slug ] = kriswa_seed_pair(
			'page',
			$slug,
			array( 'title' => $p[0] ),
			array( 'title' => $p[1] ),
			$meta,
			$log
		);
	}
	if ( $ids['home'] ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
		$log[] = 'Set static front page.';
	}
	if ( $ids['blog'] ) {
		update_option( 'page_for_posts', $ids['blog'] );
		$log[] = 'Set Blog as the posts page.';
	}

	/* ---- Site settings ---- */
	update_option( 'blogname', 'Kriswa Consultant and Construction Pvt. Ltd.' );
	update_option( 'blogdescription', 'Building trusted homes and spaces across Nepal' );
	update_option( 'timezone_string', 'Asia/Kathmandu' );
	update_option( 'permalink_structure', '/%postname%/' );
	update_option( 'start_of_week', 0 );
	update_option( 'default_comment_status', 'closed' );
	$log[] = 'Applied site title, Kathmandu timezone, pretty permalinks, Sunday week start.';

	/* ---- Taxonomy terms ---- */
	foreach ( array( 'Residential', 'Interior', 'Renovation', 'Architecture' ) as $t ) {
		if ( ! term_exists( $t, 'kriswa_project_type' ) ) {
			wp_insert_term( $t, 'kriswa_project_type' );
		}
	}

	if ( $with_samples ) {
		kriswa_seed_samples( $log );
	}

	flush_rewrite_rules();
	$log[] = 'Refreshed permalinks. Visit Settings > Kriswa to replace the placeholder contact details.';
	return $log;
}

function kriswa_seed_samples( &$log ) {
	/* ---- Services ---- */
	$services = array(
		array( 'new-house-construction', 'home', 0, 'New House Construction', 'नयाँ घर निर्माण',
			'Complete turnkey construction of your home, from site preparation and foundation to structure, finishing and handover.',
			'जग्गाको तयारीदेखि अन्तिम फिनिसिङसम्म, तपाईंको घरको सम्पूर्ण निर्माण (टर्नकी) सेवा।' ),
		array( 'apartment-commercial-buildings', 'building', 1, 'Apartment and Commercial Buildings', 'अपार्टमेन्ट तथा व्यावसायिक भवन',
			'Safe, efficient construction of apartments, offices, shops and mixed-use buildings, delivered on schedule.',
			'अपार्टमेन्ट, कार्यालय, पसल र मिश्रित प्रयोगका भवनको सुरक्षित र समयमै निर्माण।' ),
		array( 'renovation-extension', 'tools', 2, 'Renovation and Extension', 'नवीकरण तथा थप निर्माण',
			'Give an older home or building new life: structural checks, extra floors, layout changes and modern finishes.',
			'पुराना घर र भवनलाई नयाँ जीवन दिने सेवा: संरचनात्मक जाँच, थप तला, कोठाको पुनर्संरचना र आधुनिक फिनिसिङ।' ),
		array( 'interior-design-fit-out', 'sofa', 3, 'Interior Design and Fit-out', 'आन्तरिक सजावट तथा फिट-आउट',
			'Beautiful, practical interiors for homes and offices, including kitchens, wardrobes, false ceilings and furniture.',
			'घर र कार्यालयका लागि सुन्दर र व्यावहारिक इन्टेरियर: किचन, वार्डरोब, फल्स सिलिङ र फर्निचरसहित।' ),
		array( 'architectural-design-drawings', 'ruler', 4, 'Architectural Design and Drawings', 'वास्तुकला डिजाइन तथा नक्सा',
			'Architectural and structural drawings, 3D views and municipality drawing approval support.',
			'वास्तुकला तथा संरचनात्मक नक्सा, ३डी दृश्य र नगरपालिकाबाट नक्सा पास गर्न सहयोग।' ),
		array( 'consultancy-estimation-supervision', 'clipboard', 5, 'Consultancy, Estimation and Supervision', 'परामर्श, लागत अनुमान तथा सुपरिवेक्षण',
			'Independent cost estimates, quality inspection and site supervision, even if someone else is building.',
			'लागत अनुमान, गुणस्तर निरीक्षण र निर्माण स्थलको सुपरिवेक्षण, अरूले बनाइरहेको भए पनि।' ),
	);
	foreach ( $services as $s ) {
		kriswa_seed_pair(
			'kriswa_service',
			$s[0],
			array( 'title' => $s[3], 'content' => "<!-- wp:paragraph -->\n<p>{$s[5]}</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Sample text: describe your process, materials, guarantees and typical timelines for this service here.</p>\n<!-- /wp:paragraph -->", 'extra' => array( 'post_excerpt' => $s[5], 'menu_order' => $s[2] ) ),
			array( 'title' => $s[4], 'content' => "<!-- wp:paragraph -->\n<p>{$s[6]}</p>\n<!-- /wp:paragraph -->", 'extra' => array( 'post_excerpt' => $s[6], 'menu_order' => $s[2] ) ),
			array( '_kr_icon' => $s[1] ),
			$log
		);
	}

	/* ---- Projects ---- */
	$projects = array(
		array( 'modern-family-home-budhanilkantha', 'Residential', 'Modern Family Home (sample)', 'आधुनिक पारिवारिक घर (नमुना)', 'Budhanilkantha, Kathmandu', '2025', '3,200 sq. ft.', 'completed' ),
		array( 'lakeside-villa-pokhara', 'Residential', 'Lakeside Villa (sample)', 'तालकिनारको भिल्ला (नमुना)', 'Lakeside, Pokhara', '2024', '4,100 sq. ft.', 'completed' ),
		array( 'apartment-living-room-makeover', 'Interior', 'Apartment Interior (sample)', 'अपार्टमेन्ट इन्टेरियर (नमुना)', 'Lalitpur', '2025', '1,450 sq. ft.', 'completed' ),
		array( 'heritage-house-renovation-bhaktapur', 'Renovation', 'Heritage House Renovation (sample)', 'पुरानो घर नवीकरण (नमुना)', 'Bhaktapur', '2024', '2,800 sq. ft.', 'completed' ),
		array( 'office-building-baneshwor', 'Architecture', 'Office Building Design (sample)', 'कार्यालय भवन डिजाइन (नमुना)', 'New Baneshwor, Kathmandu', '2025', '9,500 sq. ft.', 'ongoing' ),
		array( 'duplex-homes-bhaisepati', 'Residential', 'Duplex Homes (sample)', 'डुप्लेक्स घर (नमुना)', 'Bhaisepati, Lalitpur', '2023', '2 x 2,200 sq. ft.', 'completed' ),
	);
	foreach ( $projects as $p ) {
		$body_en = "<!-- wp:paragraph -->\n<p>Sample project. Replace this with the story of the project: the brief, the challenges, the materials used and the result. Add photos using the Gallery block.</p>\n<!-- /wp:paragraph -->";
		$body_ne = "<!-- wp:paragraph -->\n<p>नमुना परियोजना। यहाँ परियोजनाको विवरण, चुनौती, प्रयोग गरिएका सामग्री र नतिजा लेख्नुहोस्। तस्बिरहरू ग्यालरी ब्लकबाट थप्नुहोस्।</p>\n<!-- /wp:paragraph -->";
		$id      = kriswa_seed_pair(
			'kriswa_project',
			$p[0],
			array( 'title' => $p[2], 'content' => $body_en, 'extra' => array( 'post_excerpt' => 'Sample project in ' . $p[4] . '.' ) ),
			array( 'title' => $p[3], 'content' => $body_ne, 'extra' => array( 'post_excerpt' => $p[4] . ' मा नमुना परियोजना।' ) ),
			array( '_kr_location' => $p[4], '_kr_year' => $p[5], '_kr_area' => $p[6], '_kr_status' => $p[7] ),
			$log
		);
		if ( $id ) {
			wp_set_object_terms( $id, $p[1], 'kriswa_project_type' );
		}
	}

	/* ---- Testimonials (clearly marked samples) ---- */
	$tests = array(
		array( 'sample-client-1', 'Sample Client A', 'नमुना ग्राहक क', 'Sample testimonial: the team explained every cost upfront and finished on time. Replace this with a real client quote.', 'नमुना प्रतिक्रिया: टोलीले सुरुमै सबै खर्च बुझायो र समयमै काम सक्यो। यहाँ वास्तविक ग्राहकको प्रतिक्रिया राख्नुहोस्।', 'Homeowner, Kathmandu (sample)', 'घरधनी, काठमाडौं (नमुना)' ),
		array( 'sample-client-2', 'Sample Client B', 'नमुना ग्राहक ख', 'Sample testimonial: our renovation was handled with great care and the quality of finishing was excellent.', 'नमुना प्रतिक्रिया: हाम्रो नवीकरण निकै सावधानीपूर्वक गरियो र फिनिसिङको गुणस्तर उत्कृष्ट थियो।', 'Renovation client, Lalitpur (sample)', 'नवीकरण ग्राहक, ललितपुर (नमुना)' ),
		array( 'sample-client-3', 'Sample Client C', 'नमुना ग्राहक ग', 'Sample testimonial: professional engineers, regular updates and a beautiful interior at our budget.', 'नमुना प्रतिक्रिया: व्यावसायिक इन्जिनियर, नियमित जानकारी र हाम्रो बजेटमै सुन्दर इन्टेरियर।', 'Business owner, Pokhara (sample)', 'व्यवसायी, पोखरा (नमुना)' ),
	);
	foreach ( $tests as $i => $t ) {
		kriswa_seed_pair(
			'kriswa_testimonial',
			$t[0],
			array( 'title' => $t[1], 'content' => "<!-- wp:paragraph -->\n<p>{$t[3]}</p>\n<!-- /wp:paragraph -->", 'extra' => array( 'menu_order' => $i ) ),
			array( 'title' => $t[2], 'content' => "<!-- wp:paragraph -->\n<p>{$t[4]}</p>\n<!-- /wp:paragraph -->", 'extra' => array( 'menu_order' => $i ) ),
			array( '_kr_role' => $t[5], '_kr_rating' => '5' ),
			$log
		);
	}

	/* ---- FAQs ---- */
	$faqs = array(
		array( 'faq-cost', 'How much does it cost to build a house?', 'घर बनाउन कति खर्च लाग्छ?',
			'The cost depends on the design, size, location, structure and the quality of finishing you choose. After a site visit and your requirements we prepare a detailed, itemised estimate so you know exactly where every rupee goes.',
			'लागत डिजाइन, आकार, स्थान, संरचना र रोजिएको फिनिसिङको गुणस्तरमा निर्भर गर्छ। जग्गा हेरेपछि र तपाईंको आवश्यकता बुझेपछि हामी विस्तृत लागत अनुमान तयार गर्छौं, जसले प्रत्येक रुपैयाँ कहाँ खर्च हुन्छ भन्ने स्पष्ट देखाउँछ।' ),
		array( 'faq-time', 'How long does construction take?', 'निर्माण सम्पन्न हुन कति समय लाग्छ?',
			'A typical family home takes several months from foundation to handover, depending on size, design and weather. We agree a realistic schedule with you before work begins and share regular progress updates.',
			'सामान्य पारिवारिक घर आकार, डिजाइन र मौसमअनुसार जग्गा तयारीदेखि हस्तान्तरणसम्म केही महिना लाग्छ। काम सुरु गर्नुअघि हामी तपाईंसँग यथार्थपरक तालिका तय गर्छौं र नियमित जानकारी दिन्छौं।' ),
		array( 'faq-permit', 'Do you help with municipality drawing approval (naksha pass)?', 'के तपाईंहरूले नगरपालिकाको नक्सा पास गर्न सहयोग गर्नुहुन्छ?',
			'Yes. Our architects and engineers prepare the required drawings and support you through the municipality approval process.',
			'हुन्छ। हाम्रा वास्तुकार र इन्जिनियरले आवश्यक नक्सा तयार गर्छन् र नगरपालिकाको स्वीकृति प्रक्रियामा सहयोग गर्छन्।' ),
		array( 'faq-earthquake', 'Are your buildings earthquake-resistant?', 'तपाईंहरूका भवन भूकम्प प्रतिरोधी हुन्छन्?',
			'We design and supervise construction following the Nepal National Building Code with qualified structural engineers, and we use quality-tested materials. (Sample answer: please confirm and edit to match your practice.)',
			'हामी योग्य संरचना इन्जिनियरसहित नेपाल राष्ट्रिय भवन संहिता अनुसार डिजाइन र निर्माण सुपरिवेक्षण गर्छौं र गुणस्तर जाँचिएका सामग्री प्रयोग गर्छौं। (नमुना उत्तर: कृपया आफ्नो अभ्यासअनुसार पुष्टि गरी सम्पादन गर्नुहोस्।)' ),
		array( 'faq-payment', 'How do payments work?', 'भुक्तानी कसरी हुन्छ?',
			'Payments are made in stages linked to completed milestones, so you only pay for work that is done. The schedule is written into your agreement.',
			'भुक्तानी सम्पन्न भएका चरणहरूसँग जोडिएर किस्तामा हुन्छ, त्यसैले तपाईंले भएको कामको मात्र भुक्तानी गर्नुहुन्छ। तालिका सम्झौतामा लेखिएको हुन्छ।' ),
		array( 'faq-renovation', 'Can you renovate or extend my old house?', 'के तपाईंहरूले मेरो पुरानो घर नवीकरण वा थप निर्माण गर्न सक्नुहुन्छ?',
			'Yes. We begin with a structural assessment to check what can safely be kept, extended or strengthened, then give you options and a clear estimate.',
			'सक्छौं। के सुरक्षित रूपमा राख्न, थप्न वा बलियो बनाउन सकिन्छ भनी संरचनात्मक मूल्याङ्कनबाट सुरु गरी तपाईंलाई विकल्प र स्पष्ट अनुमान दिन्छौं।' ),
		array( 'faq-visit', 'Can I visit your ongoing sites or see past work?', 'के म तपाईंहरूका चलिरहेका साइट वा पुराना काम हेर्न सक्छु?',
			'Of course. Contact us and we will arrange a visit to a completed or ongoing project so you can see our quality first-hand.',
			'अवश्य। हामीलाई सम्पर्क गर्नुहोस्, पूरा भएका वा चलिरहेका परियोजना हेर्न भ्रमणको व्यवस्था गर्छौं।' ),
		array( 'faq-quote', 'How do I get a quote?', 'कोटेसन कसरी पाउने?',
			'Fill in the quote form on this website, call us, or message us on WhatsApp. We will reply within one working day.',
			'यस वेबसाइटको कोटेसन फारम भर्नुहोस्, फोन गर्नुहोस् वा ह्वाट्सएपमा सन्देश पठाउनुहोस्। हामी एक कार्यदिनभित्र जवाफ दिन्छौं।' ),
	);
	foreach ( $faqs as $i => $f ) {
		kriswa_seed_pair(
			'kriswa_faq',
			$f[0],
			array( 'title' => $f[1], 'content' => "<!-- wp:paragraph -->\n<p>{$f[3]}</p>\n<!-- /wp:paragraph -->", 'extra' => array( 'menu_order' => $i ) ),
			array( 'title' => $f[2], 'content' => "<!-- wp:paragraph -->\n<p>{$f[4]}</p>\n<!-- /wp:paragraph -->", 'extra' => array( 'menu_order' => $i ) ),
			array(),
			$log
		);
	}

	/* ---- Blog posts ---- */
	$posts = array(
		array( 'planning-your-first-home', 'Planning your first home: 7 questions to ask before you build', 'पहिलो घर बनाउनु अघि सोध्नुपर्ने ७ प्रश्न',
			'Sample article. Replace with your own advice: budget, plot checks, design brief, permits, contractors, schedule and handover.',
			'नमुना लेख। यहाँ आफ्नै सल्लाह लेख्नुहोस्: बजेट, जग्गा जाँच, डिजाइन, अनुमति, ठेकेदार, तालिका र हस्तान्तरण।' ),
		array( 'renovation-vs-rebuild', 'Renovation or rebuild: how to decide', 'नवीकरण कि पुनर्निर्माण: कसरी निर्णय गर्ने',
			'Sample article. Explain how a structural assessment helps owners choose between renovating and rebuilding.',
			'नमुना लेख। संरचनात्मक मूल्याङ्कनले घर नवीकरण वा पुनर्निर्माण रोज्न कसरी मद्दत गर्छ भनी व्याख्या गर्नुहोस्।' ),
	);
	foreach ( $posts as $p ) {
		kriswa_seed_pair(
			'post',
			$p[0],
			array( 'title' => $p[1], 'content' => "<!-- wp:paragraph -->\n<p>{$p[3]}</p>\n<!-- /wp:paragraph -->", 'extra' => array( 'post_excerpt' => $p[3] ) ),
			array( 'title' => $p[2], 'content' => "<!-- wp:paragraph -->\n<p>{$p[4]}</p>\n<!-- /wp:paragraph -->", 'extra' => array( 'post_excerpt' => $p[4] ) ),
			array(),
			$log
		);
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'kriswa setup',
		function ( $args, $assoc ) {
			foreach ( kriswa_run_setup( ! isset( $assoc['no-samples'] ) ) as $line ) {
				WP_CLI::log( $line );
			}
			WP_CLI::success( 'Kriswa setup complete.' );
		}
	);
}
