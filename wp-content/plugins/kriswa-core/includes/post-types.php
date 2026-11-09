<?php
/**
 * Custom post types, taxonomies and meta boxes.
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'kriswa_register_post_types' );

function kriswa_register_post_types() {
	register_post_type(
		'kriswa_project',
		array(
			'labels'       => array(
				'name'          => __( 'Projects', 'kriswa-core' ),
				'singular_name' => __( 'Project', 'kriswa-core' ),
				'add_new_item'  => __( 'Add New Project', 'kriswa-core' ),
				'edit_item'     => __( 'Edit Project', 'kriswa-core' ),
			),
			'public'       => true,
			'has_archive'  => 'projects',
			'rewrite'      => array( 'slug' => 'projects', 'with_front' => false ),
			'menu_icon'    => 'dashicons-building',
			'menu_position'=> 21,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'taxonomies'   => array( 'kriswa_project_type' ),
		)
	);

	register_taxonomy(
		'kriswa_project_type',
		'kriswa_project',
		array(
			'labels'       => array(
				'name'          => __( 'Project Types', 'kriswa-core' ),
				'singular_name' => __( 'Project Type', 'kriswa-core' ),
			),
			'hierarchical' => true,
			'public'       => true,
			'show_in_rest' => true,
			'rewrite'      => array( 'slug' => 'project-type' ),
		)
	);

	register_taxonomy(
		'kriswa_location',
		'kriswa_project',
		array(
			'labels'       => array(
				'name'          => __( 'Locations', 'kriswa-core' ),
				'singular_name' => __( 'Location', 'kriswa-core' ),
			),
			'hierarchical' => false,
			'public'       => true,
			'show_ui'      => false,
			'show_in_rest' => true,
			'rewrite'      => array( 'slug' => 'project-location' ),
		)
	);

	register_post_type(
		'kriswa_service',
		array(
			'labels'       => array(
				'name'          => __( 'Services', 'kriswa-core' ),
				'singular_name' => __( 'Service', 'kriswa-core' ),
				'add_new_item'  => __( 'Add New Service', 'kriswa-core' ),
				'edit_item'     => __( 'Edit Service', 'kriswa-core' ),
			),
			'public'       => true,
			'has_archive'  => false,
			'rewrite'      => array( 'slug' => 'service', 'with_front' => false ),
			'menu_icon'    => 'dashicons-hammer',
			'menu_position'=> 22,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'revisions' ),
		)
	);

	register_post_type(
		'kriswa_testimonial',
		array(
			'labels'              => array(
				'name'          => __( 'Testimonials', 'kriswa-core' ),
				'singular_name' => __( 'Testimonial', 'kriswa-core' ),
				'add_new_item'  => __( 'Add New Testimonial', 'kriswa-core' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-format-quote',
			'menu_position'       => 23,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		)
	);

	register_post_type(
		'kriswa_faq',
		array(
			'labels'              => array(
				'name'          => __( 'FAQs', 'kriswa-core' ),
				'singular_name' => __( 'FAQ', 'kriswa-core' ),
				'add_new_item'  => __( 'Add New FAQ', 'kriswa-core' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-editor-help',
			'menu_position'       => 24,
			'supports'            => array( 'title', 'editor', 'page-attributes' ),
		)
	);

	register_post_type(
		'kriswa_lead',
		array(
			'labels'              => array(
				'name'          => __( 'Leads', 'kriswa-core' ),
				'singular_name' => __( 'Lead', 'kriswa-core' ),
				'edit_item'     => __( 'View Lead', 'kriswa-core' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => false,
			'capability_type'     => 'post',
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'        => true,
			'menu_icon'           => 'dashicons-email-alt',
			'menu_position'       => 25,
			'supports'            => array( 'title' ),
		)
	);
}

/* ------------------------------------------------------------------------- *
 * Make custom content translatable in Polylang (free) when it is installed.
 * ------------------------------------------------------------------------- */
add_filter(
	'pll_get_post_types',
	function ( $types ) {
		foreach ( array( 'kriswa_project', 'kriswa_service', 'kriswa_testimonial', 'kriswa_faq' ) as $t ) {
			$types[ $t ] = $t;
		}
		return $types;
	}
);
add_filter(
	'pll_get_taxonomies',
	function ( $taxes ) {
		$taxes['kriswa_project_type'] = 'kriswa_project_type';
		$taxes['kriswa_location']     = 'kriswa_location';
		return $taxes;
	}
);

/* ------------------------------------------------------------------------- *
 * Meta boxes (classic, no build step needed).
 * ------------------------------------------------------------------------- */

/**
 * Field definitions per post type: key => [label, type, options|help].
 */
function kriswa_meta_fields( $post_type ) {
	$fields = array(
		'kriswa_project'     => array(
			'_kr_status'   => array( __( 'Status', 'kriswa-core' ), 'select', array( 'completed' => __( 'Completed', 'kriswa-core' ), 'ongoing' => __( 'Ongoing', 'kriswa-core' ) ) ),
			'_kr_location' => array( __( 'Site location (text)', 'kriswa-core' ), 'text', 'e.g. Budhanilkantha, Kathmandu' ),
			'_kr_client'   => array( __( 'Client (optional)', 'kriswa-core' ), 'text', '' ),
			'_kr_year'     => array( __( 'Year completed (AD)', 'kriswa-core' ), 'text', 'e.g. 2025' ),
			'_kr_area'     => array( __( 'Built-up area', 'kriswa-core' ), 'text', 'e.g. 3,200 sq. ft.' ),
			'_kr_before'   => array( __( 'Before photo (media URL)', 'kriswa-core' ), 'url', 'Optional. Use with After photo for a before/after slider on renovation projects.' ),
			'_kr_after'    => array( __( 'After photo (media URL)', 'kriswa-core' ), 'url', '' ),
			'_kr_video'    => array( __( 'Video URL (YouTube)', 'kriswa-core' ), 'url', 'Optional walkthrough video.' ),
		),
		'kriswa_service'     => array(
			'_kr_icon' => array( __( 'Icon', 'kriswa-core' ), 'select', kriswa_icon_choices() ),
		),
		'kriswa_testimonial' => array(
			'_kr_role'   => array( __( 'Role / project', 'kriswa-core' ), 'text', 'e.g. Homeowner, Budhanilkantha' ),
			'_kr_rating' => array( __( 'Rating (1-5)', 'kriswa-core' ), 'select', array( '5' => '5', '4' => '4', '3' => '3' ) ),
		),
	);
	return isset( $fields[ $post_type ] ) ? $fields[ $post_type ] : array();
}

function kriswa_icon_choices() {
	return array(
		'home'      => __( 'House', 'kriswa-core' ),
		'building'  => __( 'Building', 'kriswa-core' ),
		'sofa'      => __( 'Interior', 'kriswa-core' ),
		'ruler'     => __( 'Architecture / design', 'kriswa-core' ),
		'tools'     => __( 'Renovation', 'kriswa-core' ),
		'clipboard' => __( 'Consultancy', 'kriswa-core' ),
		'scale'     => __( 'Valuation', 'kriswa-core' ),
	);
}

add_action(
	'add_meta_boxes',
	function () {
		foreach ( array( 'kriswa_project', 'kriswa_service', 'kriswa_testimonial' ) as $pt ) {
			add_meta_box( 'kriswa_details', __( 'Details', 'kriswa-core' ), 'kriswa_render_meta_box', $pt, 'normal', 'high' );
		}
	}
);

function kriswa_render_meta_box( $post ) {
	wp_nonce_field( 'kriswa_meta_' . $post->ID, 'kriswa_meta_nonce' );
	echo '<table class="form-table" role="presentation">';
	foreach ( kriswa_meta_fields( $post->post_type ) as $key => $def ) {
		list( $label, $type, $extra ) = $def;
		$val = get_post_meta( $post->ID, $key, true );
		echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		if ( 'select' === $type ) {
			echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '">';
			foreach ( $extra as $v => $l ) {
				echo '<option value="' . esc_attr( $v ) . '"' . selected( (string) $val, (string) $v, false ) . '>' . esc_html( $l ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<input class="regular-text" type="' . ( 'url' === $type ? 'url' : 'text' ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" />';
			if ( $extra ) {
				echo '<p class="description">' . esc_html( $extra ) . '</p>';
			}
		}
		echo '</td></tr>';
	}
	echo '</table>';
}

add_action(
	'save_post',
	function ( $post_id ) {
		if ( ! isset( $_POST['kriswa_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kriswa_meta_nonce'] ) ), 'kriswa_meta_' . $post_id ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( kriswa_meta_fields( get_post_type( $post_id ) ) as $key => $def ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw = wp_unslash( $_POST[ $key ] );
			$val = 'url' === $def[1] ? esc_url_raw( $raw ) : sanitize_text_field( $raw );
			if ( 'select' === $def[1] && ! isset( $def[2][ $val ] ) ) {
				continue;
			}
			update_post_meta( $post_id, $key, $val );
		}
	}
);
