<?php
/**
 * Site setup: creates every page the menus and buttons point to (so no link ever leads to "page not found"),
 * applies sensible defaults and adds clearly marked SAMPLE content (services, projects, testimonials, FAQs, posts).
 *
 * - Runs automatically the first time the plugin is activated.
 * - Missing or trashed core pages are restored automatically (checked once per plugin version, and whenever an
 *   administrator opens Kriswa Site > Setup & Help and presses the button).
 * - WP-CLI: wp kriswa setup
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

function kriswa_setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	echo '<div class="wrap"><h1>Setup &amp; Help</h1>';
	if ( isset( $_POST['kriswa_setup'] ) && check_admin_referer( 'kriswa_setup' ) ) {
		$log = kriswa_run_setup( ! empty( $_POST['with_samples'] ), true );
		echo '<div class="notice notice-success"><p><strong>Done.</strong></p><ul style="list-style:disc;margin-left:2em">';
		foreach ( $log as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul></div>';
	}
	echo '<div class="card" style="max-width:820px"><h2>Fix missing pages</h2><p>The menu, footer and buttons link to these pages: Home, About, Services, Get a Free Quote, FAQ, Social, Contact and Blog. This button re-creates any that are missing or were deleted, and restores any that are in the Trash. It never changes pages that already exist.</p>';
	echo '<form method="post">';
	wp_nonce_field( 'kriswa_setup' );
	echo '<p><label><input type="checkbox" name="with_samples" value="1"> Also (re)add the sample services, projects, testimonials, FAQs and posts that were deleted</label></p>';
	echo '<p><button class="button button-primary" name="kriswa_setup" value="1">Check and fix pages</button></p></form></div>';
	echo '<div class="card" style="max-width:820px"><h2>How to update things</h2><ul style="list-style:disc;margin-left:1.5em">';
	echo '<li><strong>Phone, email, address, social links, homepage numbers:</strong> <a href="' . esc_url( admin_url( 'admin.php?page=kriswa-settings' ) ) . '">Kriswa Site &gt; Site Details</a>.</li>';
	echo '<li><strong>Add a project:</strong> <a href="' . esc_url( admin_url( 'post-new.php?post_type=kriswa_project' ) ) . '">Projects &gt; Add New</a>. Give it a title, a short description, a Featured image and choose a Project Type.</li>';
	echo '<li><strong>Services, testimonials, FAQs:</strong> the menus of the same name in the left sidebar.</li>';
	echo '<li><strong>Quote requests:</strong> the <a href="' . esc_url( admin_url( 'edit.php?post_type=kriswa_lead' ) ) . '">Leads</a> menu.</li>';
	echo '<li><strong>Page wording and layout:</strong> Appearance &gt; Editor.</li></ul></div>';
	if ( ! function_exists( 'pll_languages_list' ) ) {
		echo '<p><strong>Nepali:</strong> install and activate the free <em>Polylang</em> plugin, add English and Nepali under Languages, then press the button above to also create the Nepali pages.</p>';
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
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ),
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
		// A page that was trashed by mistake would leave menu links broken: bring it back.
		if ( 'trash' === get_post_status( $id ) ) {
			wp_untrash_post( $id );
			wp_publish_post( $id );
			$log[] = 'Restored ' . $type . ': ' . get_the_title( $id );
		}
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

/**
 * Core pages every menu item and button points to: slug => [English title, Nepali title, template].
 */
function kriswa_core_pages() {
	return array(
		'home'        => array( 'Home', 'गृहपृष्ठ', '' ),
		'about'       => array( 'About Us', 'हाम्रो बारेमा', 'page-about' ),
		'services'    => array( 'Our Services', 'हाम्रा सेवाहरू', 'page-services' ),
		'get-a-quote' => array( 'Get a Free Quote', 'निःशुल्क कोटेसन', 'page-quote' ),
		'faq'         => array( 'FAQ', 'बारम्बार सोधिने प्रश्नहरू', 'page-faq' ),
		'social'      => array( 'Social', 'सामाजिक सञ्जाल', 'page-social' ),
		'contact'     => array( 'Contact Us', 'सम्पर्क', 'page-contact' ),
		'blog'        => array( 'Blog', 'ब्लग', '' ),
	);
}

/**
 * Create any missing core page and make sure Home and Blog are wired up. Never edits existing pages.
 */
function kriswa_ensure_pages( &$log = array() ) {
	$ids = array();
	foreach ( kriswa_core_pages() as $slug => $p ) {
		$meta         = $p[2] ? array( '_wp_page_template' => $p[2] ) : array();
		$ids[ $slug ] = kriswa_seed_pair( 'page', $slug, array( 'title' => $p[0] ), array( 'title' => $p[1] ), $meta, $log );
	}
	$front = (int) get_option( 'page_on_front' );
	if ( $ids['home'] && ( 'page' !== get_option( 'show_on_front' ) || ! $front || 'publish' !== get_post_status( $front ) ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
		$log[] = 'Set Home as the front page.';
	}
	$posts = (int) get_option( 'page_for_posts' );
	if ( $ids['blog'] && ( ! $posts || 'publish' !== get_post_status( $posts ) ) ) {
		update_option( 'page_for_posts', $ids['blog'] );
		$log[] = 'Set Blog as the posts page.';
	}
	foreach ( array( 'Residential', 'Renovation', 'Valuation', 'Interior', 'Architecture' ) as $t ) {
		if ( ! term_exists( $t, 'kriswa_project_type' ) ) {
			wp_insert_term( $t, 'kriswa_project_type' );
		}
	}
	return $ids;
}

/**
 * @param bool $with_samples Add the sample content.
 * @param bool $manual       True when an administrator pressed the button (re-adds samples without the "already done" flag).
 */
function kriswa_run_setup( $with_samples = true, $manual = false ) {
	$log = array();
	kriswa_ensure_pages( $log );

	// Site defaults: only the very first time, so later changes by the client are never overwritten.
	if ( ! get_option( 'kriswa_setup_done' ) ) {
		update_option( 'blogname', 'Kriswa Consultant and Construction Pvt. Ltd.' );
		update_option( 'blogdescription', 'Building trusted homes and spaces across Nepal' );
		update_option( 'timezone_string', 'Asia/Kathmandu' );
		update_option( 'start_of_week', 0 );
		update_option( 'default_comment_status', 'closed' );
		$log[] = 'Applied site title, Kathmandu timezone and Sunday week start.';
	}
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
		$log[] = 'Switched to pretty links (/about/ instead of ?page_id=2).';
	}

	if ( $with_samples && ( $manual || ! get_option( 'kriswa_samples_done' ) ) ) {
		kriswa_seed_samples( $log );
		update_option( 'kriswa_samples_done', 1, false );
	}

	update_option( 'kriswa_setup_done', 1, false );
	update_option( 'kriswa_pages_ver', KRISWA_CORE_VERSION, false );
	flush_rewrite_rules();
	$log[] = 'Refreshed links. Next: open Kriswa Site > Site Details and replace the placeholder phone, email, address and numbers.';
	return $log;
}

/** Automatic safety net: first run after activation, then a once-per-version page check. */
add_action(
	'admin_init',
	function () {
		if ( ! current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
			return;
		}
		if ( ! get_option( 'kriswa_setup_done' ) ) {
			kriswa_run_setup( true );
		} elseif ( get_option( 'kriswa_pages_ver' ) !== KRISWA_CORE_VERSION ) {
			$log = array();
			kriswa_ensure_pages( $log );
			update_option( 'kriswa_pages_ver', KRISWA_CORE_VERSION, false );
			flush_rewrite_rules();
		}
	},
	20
);

/** Sample bodies for projects, by type (English, Nepali). */
function kriswa_sample_project_body( $type, $lang ) {
	$en = array(
		'Residential'  => 'Sample project. Describe the home here: plot size, number of floors, design style, structure and finishes, and how the family uses the space. Add site and finished photos with the Photos box.',
		'Renovation'   => 'Sample project. Describe the condition before renovation, what was repaired, strengthened or extended, and the result. Use the Before and After photo fields for a before/after slider.',
		'Valuation'    => 'Sample valuation case. Describe the purpose (for example a bank loan, sale or partition), the property inspected, the documents reviewed, the method used and the outcome. Do not publish client names or confidential figures without permission.',
		'Interior'     => 'Sample project. Describe the brief, the layout changes, the materials and furniture chosen, and the finished result.',
		'Architecture' => 'Sample project. Describe the design concept, drawings and approvals handled, and the current stage of the project.',
	);
	$ne = array(
		'Residential'  => 'नमुना परियोजना। यहाँ घरको विवरण लेख्नुहोस्: जग्गाको आकार, तला, डिजाइन शैली, संरचना र फिनिसिङ। फोटो बक्सबाट साइट र सम्पन्न घरका तस्बिर थप्नुहोस्।',
		'Renovation'   => 'नमुना परियोजना। नवीकरणअघिको अवस्था, के मर्मत, बलियो वा थप गरियो र नतिजा लेख्नुहोस्। अघि/पछिको स्लाइडरका लागि अघि र पछिको फोटो थप्नुहोस्।',
		'Valuation'    => 'नमुना मूल्याङ्कन विवरण। उद्देश्य (जस्तै बैंक ऋण, खरिद-बिक्री वा बाँडफाँड), निरीक्षण गरिएको सम्पत्ति, जाँचिएका कागजात, प्रयोग गरिएको विधि र नतिजा लेख्नुहोस्। अनुमतिबिना ग्राहकको नाम वा गोप्य रकम प्रकाशित नगर्नुहोस्।',
		'Interior'     => 'नमुना परियोजना। आवश्यकता, कोठाको संरचना, रोजिएका सामग्री र फर्निचर तथा सम्पन्न नतिजा लेख्नुहोस्।',
		'Architecture' => 'नमुना परियोजना। डिजाइन अवधारणा, तयार गरिएका नक्सा र स्वीकृति तथा परियोजनाको हालको चरण लेख्नुहोस्।',
	);
	$t = 'ne' === $lang ? $ne : $en;
	return "<!-- wp:paragraph -->\n<p>" . $t[ $type ] . "</p>\n<!-- /wp:paragraph -->";
}

function kriswa_seed_samples( &$log ) {
	/* ---- Services ---- */
	$services = array(
		array( 'residential-construction', 'home', 0, 'Residential Construction', 'आवासीय निर्माण',
			'Complete turnkey construction of your home, from site preparation and foundation to structure, finishing and handover.',
			'जग्गाको तयारीदेखि अन्तिम फिनिसिङसम्म, तपाईंको घरको सम्पूर्ण निर्माण (टर्नकी) सेवा।' ),
		array( 'land-building-valuation', 'scale', 1, 'Land and Building Valuation', 'जग्गा तथा भवन मूल्याङ्कन',
			'Independent valuation reports for land and buildings after a site inspection and document review, for bank loans, buying, selling, partition and insurance.',
			'साइट निरीक्षण र कागजात जाँचपछि बैंक ऋण, खरिद-बिक्री, बाँडफाँड र बीमाका लागि जग्गा तथा भवनको स्वतन्त्र मूल्याङ्कन प्रतिवेदन।' ),
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
			array( 'title' => $s[3], 'content' => "<!-- wp:paragraph -->\n<p>{$s[5]}</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Sample text: describe your process, what the client receives and typical timelines for this service here.</p>\n<!-- /wp:paragraph -->", 'extra' => array( 'post_excerpt' => $s[5], 'menu_order' => $s[2] ) ),
			array( 'title' => $s[4], 'content' => "<!-- wp:paragraph -->\n<p>{$s[6]}</p>\n<!-- /wp:paragraph -->", 'extra' => array( 'post_excerpt' => $s[6], 'menu_order' => $s[2] ) ),
			array( '_kr_icon' => $s[1] ),
			$log
		);
	}

	/* ---- Projects: residential, renovation and valuation work, plus interior and architecture ---- */
	$projects = array(
		// slug, type, title, ne title, location, year, area, status.
		array( 'modern-family-home-budhanilkantha', 'Residential', 'Modern Family Home, Budhanilkantha (sample)', 'आधुनिक पारिवारिक घर, बुढानीलकण्ठ (नमुना)', 'Budhanilkantha, Kathmandu', '2025', '3,200 sq. ft.', 'completed' ),
		array( 'duplex-homes-bhaisepati', 'Residential', 'Duplex Homes, Bhaisepati (sample)', 'डुप्लेक्स घर, भैसेपाटी (नमुना)', 'Bhaisepati, Lalitpur', '2024', '2 x 2,200 sq. ft.', 'completed' ),
		array( 'three-storey-residence-tokha', 'Residential', 'Three-storey Residence, Tokha (sample)', 'तीन तले आवास, टोखा (नमुना)', 'Tokha, Kathmandu', '2024', '2,900 sq. ft.', 'completed' ),
		array( 'family-house-imadol', 'Residential', 'Family House, Imadol (sample)', 'पारिवारिक घर, इमाडोल (नमुना)', 'Imadol, Lalitpur', '2026', '2,400 sq. ft.', 'ongoing' ),
		array( 'heritage-house-renovation-bhaktapur', 'Renovation', 'Heritage House Renovation, Bhaktapur (sample)', 'पुरानो घर नवीकरण, भक्तपुर (नमुना)', 'Bhaktapur', '2024', '2,800 sq. ft.', 'completed' ),
		array( 'second-floor-extension-chabahil', 'Renovation', 'Second-floor Extension, Chabahil (sample)', 'दोस्रो तला थप निर्माण, चाबहिल (नमुना)', 'Chabahil, Kathmandu', '2025', '1,100 sq. ft.', 'completed' ),
		array( 'kitchen-bathroom-renovation-patan', 'Renovation', 'Kitchen and Bathroom Renovation, Patan (sample)', 'किचन र बाथरुम नवीकरण, पाटन (नमुना)', 'Patan, Lalitpur', '2025', '650 sq. ft.', 'completed' ),
		array( 'residential-valuation-bank-loan-kathmandu', 'Valuation', 'Residential Land and Building Valuation, Kathmandu (sample)', 'आवासीय जग्गा तथा भवन मूल्याङ्कन, काठमाडौं (नमुना)', 'Kathmandu', '2025', '5 aana plot with house', 'completed' ),
		array( 'commercial-building-valuation-baneshwor', 'Valuation', 'Commercial Building Valuation, New Baneshwor (sample)', 'व्यावसायिक भवन मूल्याङ्कन, नयाँ बानेश्वर (नमुना)', 'New Baneshwor, Kathmandu', '2025', '12,000 sq. ft.', 'completed' ),
		array( 'land-valuation-partition-lalitpur', 'Valuation', 'Land Valuation for Partition, Lalitpur (sample)', 'बाँडफाँडका लागि जग्गा मूल्याङ्कन, ललितपुर (नमुना)', 'Lalitpur', '2026', '8 aana plot', 'completed' ),
		array( 'apartment-interior-lalitpur', 'Interior', 'Apartment Interior, Lalitpur (sample)', 'अपार्टमेन्ट इन्टेरियर, ललितपुर (नमुना)', 'Lalitpur', '2025', '1,450 sq. ft.', 'completed' ),
		array( 'office-building-design-baneshwor', 'Architecture', 'Office Building Design, New Baneshwor (sample)', 'कार्यालय भवन डिजाइन, नयाँ बानेश्वर (नमुना)', 'New Baneshwor, Kathmandu', '2026', '9,500 sq. ft.', 'ongoing' ),
	);
	foreach ( $projects as $n => $p ) {
		// Stagger dates so the list shows in the order above (newest first); real projects simply use their publish date.
		$when = gmdate( 'Y-m-d H:i:s', time() - ( $n * HOUR_IN_SECONDS ) );
		$id   = kriswa_seed_pair(
			'kriswa_project',
			$p[0],
			array( 'title' => $p[2], 'content' => kriswa_sample_project_body( $p[1], 'en' ), 'extra' => array( 'post_excerpt' => 'Sample ' . strtolower( $p[1] ) . ' project in ' . $p[4] . '.', 'post_date_gmt' => $when, 'post_date' => get_date_from_gmt( $when ) ) ),
			array( 'title' => $p[3], 'content' => kriswa_sample_project_body( $p[1], 'ne' ), 'extra' => array( 'post_excerpt' => $p[4] . ' मा नमुना परियोजना।', 'post_date_gmt' => $when, 'post_date' => get_date_from_gmt( $when ) ) ),
			array( '_kr_location' => $p[4], '_kr_year' => $p[5], '_kr_area' => $p[6], '_kr_status' => $p[7] ),
			$log
		);
		if ( $id && ! has_term( '', 'kriswa_project_type', $id ) ) {
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
		array( 'faq-valuation', 'What does a land and building valuation report include?', 'जग्गा तथा भवन मूल्याङ्कन प्रतिवेदनमा के-के हुन्छ?',
			'A valuation report gives the estimated market value of a plot and any building on it, based on a site inspection, a review of ownership and land documents, local market comparisons and construction cost. It is commonly used for bank loans, buying or selling, partition and insurance. (Sample answer: please edit to match the reports you provide.)',
			'मूल्याङ्कन प्रतिवेदनले साइट निरीक्षण, स्वामित्व तथा जग्गा कागजातको जाँच, स्थानीय बजार तुलना र निर्माण लागतका आधारमा जग्गा र त्यसमाथिको भवनको अनुमानित बजार मूल्य दिन्छ। यो प्रायः बैंक ऋण, खरिद-बिक्री, बाँडफाँड र बीमाका लागि प्रयोग हुन्छ। (नमुना उत्तर: कृपया तपाईंले दिने प्रतिवेदनअनुसार सम्पादन गर्नुहोस्।)' ),
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
