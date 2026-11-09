<?php
/**
 * Plugin Name:       Kriswa Core
 * Description:       Companion plugin for the Kriswa theme: projects, services, testimonials, FAQs, quote-request leads, WhatsApp button, site settings, Nepali (BS) dates and demo-content setup. No paid services required.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Kriswa Consultant and Construction Pvt. Ltd.
 * License:           GPL-2.0-or-later
 * Text Domain:       kriswa-core
 *
 * @package Kriswa_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'KRISWA_CORE_VERSION', '1.0.0' );
define( 'KRISWA_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'KRISWA_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once KRISWA_CORE_DIR . 'includes/helpers.php';
require_once KRISWA_CORE_DIR . 'includes/class-nepali-date.php';
require_once KRISWA_CORE_DIR . 'includes/post-types.php';
require_once KRISWA_CORE_DIR . 'includes/settings.php';
require_once KRISWA_CORE_DIR . 'includes/shortcodes.php';
require_once KRISWA_CORE_DIR . 'includes/leads.php';
require_once KRISWA_CORE_DIR . 'includes/social.php';
require_once KRISWA_CORE_DIR . 'includes/frontend.php';
require_once KRISWA_CORE_DIR . 'includes/demo-content.php';
require_once KRISWA_CORE_DIR . 'includes/admin.php';

register_activation_hook(
	__FILE__,
	function () {
		kriswa_register_post_types();
		// Create every page the menus link to (and sample content) so no link leads to "page not found".
		kriswa_run_setup( true );
	}
);

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
