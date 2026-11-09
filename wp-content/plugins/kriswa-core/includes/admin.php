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
		return in_array( $type, array( 'kriswa_project', 'kriswa_service', 'kriswa_testimonial', 'kriswa_faq' ), true ) ? false : $use;
	},
	10,
	2
);
