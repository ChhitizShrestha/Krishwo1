<?php
/**
 * Title: Blog index
 * Slug: kriswa/blog-index
 * Inserter: no
 *
 * @package Kriswa
 */
?>
<!-- wp:group {"align":"full","className":"kr-banner","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull kr-banner"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"level":1,"className":"kr-banner__title"} --><h1 class="wp-block-heading kr-banner__title"><?php esc_html_e( 'News and building tips', 'kriswa' ); ?></h1><!-- /wp:heading -->
<!-- wp:paragraph {"className":"kr-banner__sub"} --><p class="kr-banner__sub"><?php esc_html_e( 'Practical advice on building, renovating and designing homes in Nepal.', 'kriswa' ); ?></p><!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<?php echo kriswa_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<!-- wp:query {"queryId":1,"query":{"perPage":9,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":true},"layout":{"type":"default"}} -->
<div class="wp-block-query"><!-- wp:post-template {"className":"kr-post-grid","layout":{"type":"grid","columnCount":3}} -->
<!-- wp:group {"className":"kr-card kr-post","layout":{"type":"default"}} -->
<div class="wp-block-group kr-card kr-post"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3"} /-->
<!-- wp:group {"className":"kr-post__body","layout":{"type":"default"}} -->
<div class="wp-block-group kr-post__body"><!-- wp:post-date {"className":"kr-meta"} /-->
<!-- wp:post-title {"level":3,"isLink":true} /-->
<!-- wp:post-excerpt {"excerptLength":22} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template -->
<!-- wp:query-pagination {"paginationArrow":"arrow","layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->
<!-- wp:query-pagination-numbers /-->
<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->
<!-- wp:query-no-results -->
<!-- wp:paragraph --><p><?php esc_html_e( 'No posts yet. Please check back soon.', 'kriswa' ); ?></p><!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->
<?php echo kriswa_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
