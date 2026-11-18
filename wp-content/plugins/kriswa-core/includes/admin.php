<?php
/**
 * Admin conveniences: top-level "Kriswa Site" menu, dashboard shortcuts, admin-bar link,
 * missing-page notice, and an easy project editor (columns, photo gallery, guidance).
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------------- *
 * Menu: Kriswa Site > Site Details / Setup & Help
 * ------------------------------------------------------------------------- */
add_action(
	'admin_menu',
	function () {
		add_menu_page( 'Kriswa Site Details', 'Kriswa Site', 'manage_options', 'kriswa-settings', 'kriswa_settings_page', 'dashicons-admin-home', 3 );
		add_submenu_page( 'kriswa-settings', 'Site Details', 'Site Details', 'manage_options', 'kriswa-settings', 'kriswa_settings_page' );
		add_submenu_page( 'kriswa-settings', 'Setup & Help', 'Setup & Help', 'manage_options', 'kriswa-setup', 'kriswa_setup_page' );
	}
);

add_filter(
	'plugin_action_links_kriswa-core/kriswa-core.php',
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=kriswa-settings' ) ) . '">Site Details</a>' );
		return $links;
	}
);

/* Front-end admin bar: one click from any page to the numbers and contact details. */
add_action(
	'admin_bar_menu',
	function ( $bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$bar->add_node( array( 'id' => 'kriswa-details', 'title' => 'Edit phone, email &amp; numbers', 'href' => admin_url( 'admin.php?page=kriswa-settings' ) ) );
		$bar->add_node( array( 'id' => 'kriswa-add-project', 'parent' => 'new-content', 'title' => 'Project', 'href' => admin_url( 'post-new.php?post_type=kriswa_project' ) ) );
	},
	90
);

/* ------------------------------------------------------------------------- *
 * Dashboard widget with shortcuts
 * ------------------------------------------------------------------------- */
add_action(
	'wp_dashboard_setup',
	function () {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		wp_add_dashboard_widget(
			'kriswa_shortcuts',
			'Your website',
			function () {
				$new = new WP_Query(
					array(
						'post_type'      => 'kriswa_lead',
						'post_status'    => 'any',
						'meta_key'       => '_lead_status', // phpcs:ignore WordPress.DB.SlowDBQuery
						'meta_value'     => 'new', // phpcs:ignore WordPress.DB.SlowDBQuery
						'posts_per_page' => 1,
						'fields'         => 'ids',
					)
				);
				echo '<p><strong>' . (int) $new->found_posts . '</strong> new quote request(s) waiting. <a href="' . esc_url( admin_url( 'edit.php?post_type=kriswa_lead' ) ) . '">Open Leads</a></p>';
				echo '<p>';
				foreach ( array(
					array( 'Add a project', admin_url( 'post-new.php?post_type=kriswa_project' ), true ),
					array( 'Edit phone, email &amp; numbers', admin_url( 'admin.php?page=kriswa-settings' ), false ),
					array( 'View website', home_url( '/' ), false ),
				) as $b ) {
					echo '<a class="button' . ( $b[2] ? ' button-primary' : '' ) . '" style="margin:0 6px 6px 0" href="' . esc_url( $b[1] ) . '">' . wp_kses_post( $b[0] ) . '</a>';
				}
				echo '</p>';
			}
		);
	}
);

/* ------------------------------------------------------------------------- *
 * Notice when a menu page is missing, with a one-click fix
 * ------------------------------------------------------------------------- */
add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'toplevel_page_kriswa-settings', 'edit-page' ), true ) ) {
			return;
		}
		$missing = array();
		foreach ( kriswa_core_pages() as $slug => $p ) {
			$page = get_page_by_path( $slug );
			if ( ! $page || 'publish' !== $page->post_status ) {
				$missing[] = $p[0];
			}
		}
		if ( $missing ) {
			echo '<div class="notice notice-warning"><p><strong>Some website pages are missing:</strong> ' . esc_html( implode( ', ', $missing ) ) . '. Menu links to them would not work. <a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=kriswa-setup' ) ) . '">Fix it now</a></p></div>';
		}
	}
);

/* ------------------------------------------------------------------------- *
 * Project editor: guidance, columns, gallery
 * ------------------------------------------------------------------------- */
add_action(
	'edit_form_after_title',
	function ( $post ) {
		if ( 'kriswa_project' !== $post->post_type ) {
			return;
		}
		echo '<div class="notice notice-info inline" style="margin:12px 0"><p><strong>Adding a project takes 4 steps:</strong> 1) type the project name above, 2) write a short description below, 3) set a <em>Featured image</em> (main photo, right side) and a <em>Project Type</em> (Residential, Renovation, Valuation, Interior or Architecture), 4) fill in <em>Details</em> and add more <em>Photos</em> lower down. Press <strong>Publish</strong>. It appears on the Projects page automatically.</p></div>';
	}
);

add_filter(
	'manage_kriswa_project_posts_columns',
	function ( $cols ) {
		return array(
			'cb'       => $cols['cb'],
			'kr_thumb' => 'Photo',
			'title'    => $cols['title'],
			'kr_type'  => 'Type',
			'kr_loc'   => 'Location',
			'kr_stat'  => 'Status',
			'date'     => $cols['date'],
		);
	}
);
add_action(
	'manage_kriswa_project_posts_custom_column',
	function ( $col, $id ) {
		switch ( $col ) {
			case 'kr_thumb':
				echo has_post_thumbnail( $id ) ? get_the_post_thumbnail( $id, array( 60, 60 ) ) : '<span style="color:#999">No photo</span>';
				break;
			case 'kr_type':
				$t = get_the_terms( $id, 'kriswa_project_type' );
				echo $t && ! is_wp_error( $t ) ? esc_html( implode( ', ', wp_list_pluck( $t, 'name' ) ) ) : '&mdash;';
				break;
			case 'kr_loc':
				echo esc_html( get_post_meta( $id, '_kr_location', true ) );
				break;
			case 'kr_stat':
				echo 'ongoing' === get_post_meta( $id, '_kr_status', true ) ? 'Ongoing' : 'Completed';
				break;
		}
	},
	10,
	2
);

add_action(
	'add_meta_boxes_kriswa_project',
	function () {
		add_meta_box( 'kriswa_gallery', 'Photos (gallery)', 'kriswa_gallery_box', 'kriswa_project', 'normal', 'high' );
	}
);

function kriswa_gallery_box( $post ) {
	wp_nonce_field( 'kriswa_gallery_' . $post->ID, 'kriswa_gallery_nonce' );
	$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_kr_gallery', true ) ) ) );
	echo '<p class="description">Add as many photos as you like. They show as a gallery on the project page; visitors tap one to enlarge it. The <em>Featured image</em> (right side) is the cover photo.</p>';
	echo '<div class="kriswa-gal" data-kr-gallery><input type="hidden" name="kr_gallery" value="' . esc_attr( implode( ',', $ids ) ) . '"><ul class="kriswa-gal__list">';
	foreach ( $ids as $id ) {
		echo '<li data-id="' . (int) $id . '">' . wp_get_attachment_image( $id, array( 90, 90 ) ) . '<button type="button" class="kriswa-gal__rm" aria-label="Remove photo">&times;</button></li>';
	}
	echo '</ul><p><button type="button" class="button button-primary" data-kr-gal-add>Add photos</button></p></div>';
}

add_action(
	'save_post_kriswa_project',
	function ( $post_id ) {
		if ( ! isset( $_POST['kriswa_gallery_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kriswa_gallery_nonce'] ) ), 'kriswa_gallery_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$raw = isset( $_POST['kr_gallery'] ) ? sanitize_text_field( wp_unslash( $_POST['kr_gallery'] ) ) : '';
		$ids = array();
		foreach ( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) as $id ) {
			if ( wp_attachment_is_image( $id ) ) {
				$ids[] = $id;
			}
		}
		update_post_meta( $post_id, '_kr_gallery', implode( ',', $ids ) );
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$screen = get_current_screen();
		if ( $screen && 'kriswa_project' === $screen->post_type && in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'kriswa-admin', KRISWA_CORE_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), KRISWA_CORE_VERSION, true );
			wp_add_inline_style( 'wp-admin', '.kriswa-gal__list{display:flex;flex-wrap:wrap;gap:8px;margin:0;padding:0;list-style:none}.kriswa-gal__list li{position:relative}.kriswa-gal__list img{display:block;border-radius:6px}.kriswa-gal__rm{position:absolute;top:-6px;right:-6px;width:22px;height:22px;border:0;border-radius:50%;background:#d63638;color:#fff;cursor:pointer;line-height:1}' );
		}
	}
);

/* A plain, familiar editor (title, description, photo, details boxes) is easier for staff than the block editor. */
add_filter(
	'use_block_editor_for_post_type',
	function ( $use, $type ) {
		return in_array( $type, array( 'kriswa_project', 'kriswa_service', 'kriswa_testimonial', 'kriswa_faq', 'kriswa_team' ), true ) ? false : $use;
	},
	10,
	2
);

/* ------------------------------------------------------------------------- *
 * Project <-> Services link ("Related services")
 * ------------------------------------------------------------------------- */
add_action(
	'add_meta_boxes_kriswa_project',
	function () {
		add_meta_box( 'kriswa_related', 'Related services', 'kriswa_related_services_box', 'kriswa_project', 'side', 'default' );
	}
);

function kriswa_related_services_box( $post ) {
	wp_nonce_field( 'kriswa_related_' . $post->ID, 'kriswa_related_nonce' );
	$chosen   = array_map( 'absint', (array) get_post_meta( $post->ID, '_kr_service', false ) );
	$services = get_posts( array( 'post_type' => 'kriswa_service', 'post_status' => 'publish', 'numberposts' => 50, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
	if ( ! $services ) {
		echo '<p>No services yet.</p>';
		return;
	}
	echo '<p class="description">Tick the services this project shows. Visitors see links to them, and the service page lists this project.</p>';
	foreach ( $services as $sv ) {
		echo '<p style="margin:4px 0"><label><input type="checkbox" name="kr_services[]" value="' . (int) $sv->ID . '"' . checked( in_array( $sv->ID, $chosen, true ), true, false ) . '> ' . esc_html( $sv->post_title ) . '</label></p>';
	}
}

add_action(
	'save_post_kriswa_project',
	function ( $post_id ) {
		if ( ! isset( $_POST['kriswa_related_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kriswa_related_nonce'] ) ), 'kriswa_related_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		delete_post_meta( $post_id, '_kr_service' );
		$ids = isset( $_POST['kr_services'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['kr_services'] ) ) : array();
		foreach ( array_unique( $ids ) as $sid ) {
			if ( 'kriswa_service' === get_post_type( $sid ) ) {
				add_post_meta( $post_id, '_kr_service', $sid );
			}
		}
	}
);

/* ------------------------------------------------------------------------- *
 * Team list columns
 * ------------------------------------------------------------------------- */
add_filter(
	'manage_kriswa_team_posts_columns',
	function ( $cols ) {
		return array( 'cb' => $cols['cb'], 'kr_photo' => 'Photo', 'title' => $cols['title'], 'kr_role' => 'Role', 'kr_nec' => 'NEC no.' );
	}
);
add_action(
	'manage_kriswa_team_posts_custom_column',
	function ( $col, $id ) {
		if ( 'kr_photo' === $col ) {
			echo has_post_thumbnail( $id ) ? get_the_post_thumbnail( $id, array( 48, 48 ) ) : '<span style="color:#999">No photo</span>';
		} elseif ( 'kr_role' === $col ) {
			echo esc_html( get_post_meta( $id, '_kr_role', true ) );
		} elseif ( 'kr_nec' === $col ) {
			echo esc_html( get_post_meta( $id, '_kr_nec', true ) );
		}
	},
	10,
	2
);

/* ------------------------------------------------------------------------- *
 * Sample content: flagged, hideable, deletable
 * ------------------------------------------------------------------------- */
function kriswa_sample_types() {
	return array( 'kriswa_project', 'kriswa_testimonial', 'kriswa_team', 'post' );
}

function kriswa_sample_ids() {
	return get_posts(
		array(
			'post_type'      => kriswa_sample_types(),
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'numberposts'    => -1,
			'fields'         => 'ids',
			'meta_key'       => '_kr_sample', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
			'lang'           => '',
			'no_found_rows'  => true,
			'suppress_filters' => false,
		)
	);
}

/* Saving a sample item in the editor means "this is mine now": it stops being a sample. */
add_action(
	'save_post',
	function ( $post_id, $post ) {
		if ( ! is_admin() || ! in_array( $post->post_type, kriswa_sample_types(), true ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		if ( isset( $_POST['post_ID'] ) && (int) $_POST['post_ID'] === (int) $post_id && current_user_can( 'edit_post', $post_id ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			delete_post_meta( $post_id, '_kr_sample' );
		}
	},
	99,
	2
);

/* "Sample" label in the project list. */
add_filter(
	'display_post_states',
	function ( $states, $post ) {
		if ( get_post_meta( $post->ID, '_kr_sample', true ) ) {
			$states['kr_sample'] = 'Sample';
		}
		return $states;
	},
	10,
	2
);

add_action(
	'admin_post_kriswa_delete_samples',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'kriswa_delete_samples' ) ) {
			wp_die( 'Not allowed.', 403 );
		}
		foreach ( kriswa_sample_ids() as $id ) {
			wp_delete_post( $id, true );
		}
		update_option( 'kriswa_samples_done', 1, false ); // Do not bring them back automatically.
		wp_safe_redirect( admin_url( 'admin.php?page=kriswa-setup&samples_deleted=1' ) );
		exit;
	}
);

/* Banner on Site Details and the Dashboard while sample items are showing. */
function kriswa_sample_notice() {
	$n = count( kriswa_sample_ids() );
	if ( ! $n ) {
		return;
	}
	$hidden = '1' === kriswa_opt( 'hide_samples', false );
	echo '<div class="notice notice-' . ( $hidden ? 'info' : 'warning' ) . '"><p><strong>' . (int) $n . ' sample item(s)</strong> (projects, testimonials, team members, blog posts) ' . ( $hidden ? 'are hidden from visitors.' : 'are <strong>visible to visitors</strong>.' ) . ' Edit each one with your real details (saving makes it yours), then ' . ( $hidden ? 'untick' : 'tick' ) . ' <em>Hide sample content</em> under <a href="' . esc_url( admin_url( 'admin.php?page=kriswa-settings#tab-extras' ) ) . '">Site Details &gt; Extras</a> or <a href="' . esc_url( admin_url( 'admin.php?page=kriswa-setup' ) ) . '">delete all samples</a>.</p></div>';
}
add_action(
	'admin_notices',
	function () {
		$screen = get_current_screen();
		if ( $screen && current_user_can( 'manage_options' ) && in_array( $screen->id, array( 'dashboard', 'toplevel_page_kriswa-settings' ), true ) ) {
			kriswa_sample_notice();
			if ( 'dashboard' === $screen->id ) {
				foreach ( kriswa_social_health() as $h ) {
					echo '<div class="notice notice-' . ( 'error' === $h[0] ? 'error' : 'warning' ) . '"><p>' . esc_html( $h[1] ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=kriswa-settings#kriswa-social' ) ) . '">Open the social preview</a></p></div>';
				}
			}
		}
	}
);
