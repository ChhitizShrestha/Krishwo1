<?php
/**
 * Title: FAQ page content
 * Slug: kriswa/page-faq
 * Categories: kriswa
 *
 * @package Kriswa
 */

echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_head( __( 'FAQ', 'kriswa' ), __( 'Frequently asked questions', 'kriswa' ), __( 'Quick answers about cost, timelines, permits and how we work. Cannot find yours? Just ask.', 'kriswa' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_sc( 'kriswa_faqs' ); // phpcs:ignore WordPress.Security.EscapeOutput
echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput
