<?php

/**
 * Title: 404 Content
 * Slug: flexa/404-content
 * Categories: flexa
 */
?>
<!-- wp:group {"className":"main-content","layout":{"type":"constrained"}} -->
<div class="wp-block-group main-content">

	<!-- wp:heading {"level":1} -->
	<h1><?php esc_html_e('404 - Page Not Found', 'flexa'); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e("The page you're looking for couldn't be found. Try searching, or head back to the homepage.", 'flexa'); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:search {"label":"<?php esc_attr_e( 'Search', 'flexa' ); ?>","showLabel":true,"buttonText":"<?php esc_attr_e( 'Search', 'flexa' ); ?>"} /-->

	<!-- wp:spacer {"height":"var(--wp--preset--spacing--m)"} -->
	<div style="height:var(--wp--preset--spacing--m)" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:home-link {"label":"<?php esc_attr_e( 'Go to homepage', 'flexa' ); ?>"} /-->

</div>
<!-- /wp:group -->
